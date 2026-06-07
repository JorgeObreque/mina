<?php
/**
 * JWT (JSON Web Token) implementation for Mina SaaS.
 *
 * Pure-PHP HS256 implementation with no external dependencies.
 */
class JWT
{
    private const ALG = 'HS256';
    private const TYP = 'JWT';

    /**
     * Encode a payload as a JWT.
     *
     * @param array|object $payload Data to embed in the token.
     * @param string $secret Secret key.
     * @param int|null $ttl Time-to-live in seconds (added to 'exp').
     *
     * @return string Encoded JWT.
     */
    public function encode($payload, string $secret, ?int $ttl = null): string
    {
        $header = ['typ' => self::TYP, 'alg' => self::ALG];
        $payload = (array) $payload;

        if ($ttl !== null) {
            $payload['iat'] = $payload['iat'] ?? time();
            $payload['exp'] = time() + $ttl;
        }

        $segments = [
            $this->b64UrlEncode(json_encode($header)),
            $this->b64UrlEncode(json_encode($payload)),
        ];

        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $secret, true);
        $segments[] = $this->b64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * Decode and verify a JWT.
     *
     * @param string $token Encoded JWT.
     * @param string|null $secret Secret key. If null, uses config('jwt.secret').
     *
     * @return object Decoded payload.
     *
     * @throws Exception If token is invalid, signature fails, or token is expired.
     */
    public function decode(string $token, ?string $secret = null): object
    {
        $secret ??= config('jwt.secret') ?? '';

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }

        [$h64, $p64, $s64] = $parts;
        $header = json_decode($this->b64UrlDecode($h64), true);
        $payload = json_decode($this->b64UrlDecode($p64), true);
        $signature = $this->b64UrlDecode($s64);

        if (!$header || !$payload) {
            throw new Exception('Invalid token content');
        }

        if (($header['alg'] ?? null) !== self::ALG) {
            throw new Exception('Unsupported algorithm');
        }

        $expected = hash_hmac('sha256', "$h64.$p64", $secret, true);
        if (!hash_equals($expected, $signature)) {
            throw new Exception('Invalid signature');
        }

        if (isset($payload['exp']) && time() > (int) $payload['exp']) {
            throw new Exception('Token expired');
        }

        return (object) $payload;
    }

    /**
     * Extract the payload without verifying the signature.
     *
     * Useful for debugging only. Never use for trust decisions.
     */
    public function decodeUnsafe(string $token): ?object
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        $payload = json_decode($this->b64UrlDecode($parts[1]), true);
        return $payload ? (object) $payload : null;
    }

    private function b64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function b64UrlDecode(string $data): string
    {
        $padded = strtr($data, '-_', '+/');
        $remainder = strlen($padded) % 4;
        if ($remainder !== 0) {
            $padded .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode($padded);
    }
}
