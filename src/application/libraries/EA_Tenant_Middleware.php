<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tenant middleware for Mina SaaS.
 *
 * Resolves the active tenant_id from (in order):
 *   1. JWT in Authorization header
 *   2. tenant_id cookie
 *   3. session('tenant_id')
 *   4. Default to 1 (single-tenant fallback during development)
 *
 * The resolved tenant_id is stored in session and exposed to the
 * controller via the session helper.
 */
class EA_Tenant_Middleware
{
    public function handle(): void
    {
        $CI = &get_instance();
        $CI->load->library('session');
        $CI->load->library('JWT');

        $tenant_id = null;

        $auth = $CI->input->get_request_header('Authorization', true);
        if ($auth && stripos($auth, 'Bearer ') === 0) {
            $token = trim(substr($auth, 7));
            try {
                $payload = $CI->jwt->decode($token);
                $tenant_id = $payload->tenant_id ?? null;
            } catch (Exception $e) {
                // Invalid/expired token - fall through to other sources
            }
        }

        if (!$tenant_id) {
            $tenant_id = $CI->input->cookie('tenant_id', true);
        }

        if (!$tenant_id) {
            $tenant_id = $CI->session->userdata('tenant_id');
        }

        if (!$tenant_id) {
            $tenant_id = config_item('tenant.default_id') ?: 1;
        }

        $CI->session->set_userdata('tenant_id', (int) $tenant_id);
    }
}
