<?php
require_once APPPATH . 'libraries/JWT.php';

class Tenants extends EA_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('tenants_model');
        $this->load->model('users_model');
        $this->load->model('user_settings_model');
        $this->load->model('roles_model');
        $this->load->library('form_validation');
    }

    public function index_get() {
        $this->load->library('JWT');
        $token = $this->input->get_request_header('Authorization');
        $token = str_replace('Bearer ', '', $token);

        try {
            $payload = $this->jwt->decode($token);
            $tenant = $this->tenants_model->find_one(['id' => $payload->tenant_id]);

            if (!$tenant) {
                $this->response_error('Tenant not found', 404);
            }

            $this->response($this->_format_tenant($tenant));
        } catch (Exception $e) {
            $this->response_error($e->getMessage(), 401);
        }
    }

    public function register_post() {
        $this->form_validation->set_rules('name', 'Business Name', 'required|min_length[3]');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
        $this->form_validation->set_rules('password', 'Password', 'required|min_length[8]');
        $this->form_validation->set_rules('plan', 'Plan', 'in_list[basic,pro,enterprise]');

        if (!$this->form_validation->run()) {
            $this->response_error(validation_errors(), 400);
        }

        $existing = $this->tenants_model->find_by_email($this->input->post('email'));
        if ($existing) {
            $this->response_error('Email already registered', 409);
        }

        $tenant_id = $this->tenants_model->create([
            'name' => $this->input->post('name'),
            'email' => $this->input->post('email'),
            'password' => $this->input->post('password'),
            'plan' => $this->input->post('plan') ?: 'basic'
        ]);

        $user_id = $this->_create_admin_user($tenant_id);

        $this->load->library('JWT');
        $token = $this->jwt->generate_token($user_id, $tenant_id, 'admin');

        $this->response([
            'tenant' => $this->tenants_model->find_one(['id' => $tenant_id]),
            'token' => $token
        ], 201);
    }

    public function settings_get() {
        $this->load->library('EA_Tenant_Middleware');
        $tenant_id = $this->ea_tenant_middleware->handle();

        $settings = $this->tenants_model->get_settings($tenant_id);
        $this->response($settings);
    }

    public function settings_put() {
        $this->load->library('EA_Tenant_Middleware');
        $tenant_id = $this->ea_tenant_middleware->handle();

        $settings = json_decode($this->input->raw_input_stream, TRUE);
        $this->tenants_model->save_settings($tenant_id, $settings);

        $this->response(['message' => 'Settings updated']);
    }

    public function plan_get() {
        $this->load->library('EA_Tenant_Middleware');
        $tenant_id = $this->ea_tenant_middleware->handle();

        $tenant = $this->tenants_model->find_one(['id' => $tenant_id]);
        $this->load->model('plan_limits_model');
        $limits = $this->plan_limits_model->get_all_limits($tenant->plan);

        $this->load->model('usage_stats_model');
        $usage = [
            'appointments' => $this->usage_stats_model->get_current_usage($tenant_id, 'appointments'),
            'providers' => $this->usage_stats_model->get_current_usage($tenant_id, 'providers'),
            'customers' => $this->usage_stats_model->get_current_usage($tenant_id, 'customers')
        ];

        $this->response([
            'plan' => $tenant->plan,
            'limits' => $limits,
            'usage' => $usage
        ]);
    }

    private function _create_admin_user($tenant_id) {
        $salt = $this->users_model->generate_salt();
        $password = $this->users_model->hash_password(
            $this->input->post('password'),
            $salt
        );

        $user_data = [
            'tenant_id' => $tenant_id,
            'email' => $this->input->post('email'),
            'role_id' => $this->roles_model->get_admin_role_id()
        ];

        $user_id = $this->users_model->insert($user_data);

        $this->user_settings_model->insert([
            'user_id' => $user_id,
            'tenant_id' => $tenant_id,
            'username' => $this->input->post('email'),
            'password' => $password,
            'salt' => $salt
        ]);

        return $user_id;
    }

    private function _format_tenant($tenant) {
        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'email' => $tenant->email,
            'plan' => $tenant->plan,
            'status' => $tenant->status,
            'created_at' => $tenant->created_at
        ];
    }

    private function response($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function response_error($message, $status = 400) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }
}
