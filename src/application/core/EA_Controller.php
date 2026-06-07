<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Mina EA_Controller.
 *
 * Based on Easy!Appointments 1.6.0 EA_Controller, with multi-tenant
 * initialization added at the start of the constructor.
 *
 * All Mina controllers (Booking, Calendar, Services, etc.) extend this
 * class. The constructor resolves the active tenant_id from
 * JWT/cookie/session and exposes it via $this->tenant_id.
 */
class EA_Controller extends CI_Controller
{
    /**
     * @var int|null Resolved tenant_id for the current request.
     */
    protected ?int $tenant_id = null;

    /**
     * EA_Controller constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->tenant_id = $this->resolve_tenant_id();

        $this->load->library('accounts');

        $this->check_storage_writable();
        $this->ensure_user_exists();
        $this->configure_timezone();
        $this->configure_language();
        $this->load_common_html_vars();
        $this->load_common_script_vars();

        rate_limit($this->input->ip_address());
    }

    /**
     * Resolve the active tenant from (in order):
     *   1. JWT in Authorization header
     *   2. tenant_id cookie
     *   3. session('tenant_id')
     *   4. config('tenant.default_id')  (single-tenant fallback)
     */
    protected function resolve_tenant_id(): int
    {
        $this->load->library('JWT');
        $this->load->library('session');

        $tenant_id = null;

        $auth = $this->input->get_request_header('Authorization', true);
        if ($auth && stripos($auth, 'Bearer ') === 0) {
            $token = trim(substr($auth, 7));
            try {
                $payload = $this->jwt->decode($token);
                $tenant_id = $payload->tenant_id ?? null;
            } catch (Exception $e) {
                // Invalid/expired token - fall through.
            }
        }

        if (!$tenant_id) {
            $tenant_id = $this->input->cookie('tenant_id', true);
        }

        if (!$tenant_id) {
            $tenant_id = $this->session->userdata('tenant_id');
        }

        if (!$tenant_id) {
            $tenant_id = config_item('tenant.default_id') ?: 1;
        }

        $tenant_id = (int) $tenant_id;
        $this->session->set_userdata('tenant_id', $tenant_id);

        return $tenant_id;
    }

    /**
     * Get the active tenant_id.
     */
    public function get_tenant_id(): int
    {
        return (int) $this->tenant_id;
    }

    private function ensure_user_exists()
    {
        $user_id = session('user_id');

        if (!$user_id || !$this->db->table_exists('users')) {
            return;
        }

        if (!$this->accounts->does_account_exist($user_id)) {
            session_destroy();

            abort(403, 'Forbidden');
        }
    }

    private function configure_language()
    {
        $session_language = session('language');
        $query_language = request('language');
        $available_languages = config('available_languages');

        $language = null;

        if ($session_language && in_array($session_language, $available_languages)) {
            $language = $session_language;
        } elseif ($query_language && in_array($query_language, $available_languages)) {
            $language = $query_language;
        }

        if ($language) {
            $language_codes = config('language_codes');

            config([
                'language' => $language,
                'language_code' => array_search($language, $language_codes) ?: 'en',
            ]);
        }

        $this->lang->load('translations');
    }

    private function load_common_html_vars()
    {
        html_vars([
            'base_url' => config('base_url'),
            'index_page' => config('index_page'),
            'available_languages' => config('available_languages'),
            'language' => $this->lang->language,
            'csrf_token' => $this->security->get_csrf_hash(),
        ]);
    }

    private function load_common_script_vars()
    {
        script_vars([
            'base_url' => config('base_url'),
            'index_page' => config('index_page'),
            'available_languages' => config('available_languages'),
            'csrf_token' => $this->security->get_csrf_hash(),
            'language' => config('language'),
            'language_code' => config('language_code'),
        ]);
    }

    private function configure_timezone(): void
    {
        if (!$this->db->table_exists('settings')) {
            return;
        }

        $default_timezone = setting('default_timezone');

        date_default_timezone_set($default_timezone);
    }

    private function check_storage_writable(): void
    {
        $storage_path = APPPATH . '../storage';

        if (!is_dir($storage_path)) {
            show_error(
                'The storage folder does not exist: ' .
                    $storage_path .
                    '. ' .
                    'Please create this directory and ensure it is writable by the web server.',
                500,
                'Storage Configuration Error',
            );
        }

        if (!is_writable($storage_path)) {
            show_error(
                'The storage folder is not writable: ' .
                    $storage_path .
                    '. ' .
                    'Please ensure the web server has write permissions to this directory and its subdirectories (cache, logs, sessions, uploads).',
                500,
                'Storage Configuration Error',
            );
        }
    }
}
