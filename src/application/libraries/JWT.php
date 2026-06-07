<?php
class JWT {

    private $secret;
    private $ttl = 3600;
    private $algorithm = 'HS256';

    public function __construct() {
        $this->secret = getenv('JWT_SECRET') ?: 'mina-default-secret-change-in-production';
        $ttl = getenv('JWT_TTL');
        if ($ttl) {
            $this->ttl = (int)$ttl;
        }
    }

    public function encode($payload) {
        if (is_array($payload)) {
            if (!isset($payload['iat'])) {
                $payload['iat'] = time();
            }
            if (!isset($payload['exp'])) {
                $payload['exp'] = time() + $this->ttl;
            }
        }

        $header = $this->_base64_url_encode(json_encode([
            'alg' => $this->algorithm,
            'typ' => 'JWT'
        ]));

        $payload = $this->_base64_url_encode(json_encode($payload));

        $signature = $this->_base64_url_encode(
            hash_hmac('sha256', $header . '.' . $payload, $this->secret, TRUE)
        );

        return $header . '.' . $payload . '.' . $signature;
    }

    public function decode($token) {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new Exception('Invalid token structure');
        }

        list($header, $payload, $signature) = $parts;

        $expected_signature = $this->_base64_url_encode(
            hash_hmac('sha256', $header . '.' . $payload, $this->secret, TRUE)
        );

        if ($signature !== $expected_signature) {
            throw new Exception('Invalid signature');
        }

        $payload = json_decode($this->_base64_url_decode($payload));

        if ($payload->exp < time()) {
            throw new Exception('Token expired');
        }

        return $payload;
    }

    public function generate_token($user_id, $tenant_id, $role_slug, $extra = []) {
        $payload = array_merge([
            'user_id' => $user_id,
            'tenant_id' => $tenant_id,
            'role_slug' => $role_slug,
            'iat' => time(),
            'exp' => time() + $this->ttl
        ], $extra);

        return $this->encode($payload);
    }

    private function _base64_url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function _base64_url_decode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
