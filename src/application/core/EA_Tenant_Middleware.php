<?php
class EA_Tenant_Middleware {

    private $tenant_id = NULL;
    private $tenant_data = NULL;

    public function handle() {
        $CI =& get_instance();

        $this->tenant_id = $CI->session->userdata('tenant_id');

        if (!$this->tenant_id) {
            $token = $CI->input->get_request_header('Authorization');
            if ($token) {
                $token = str_replace('Bearer ', '', $token);
                try {
                    $CI->load->library('JWT');
                    $payload = $CI->jwt->decode($token);
                    $this->tenant_id = $payload->tenant_id ?? NULL;
                } catch (Exception $e) {
                    log_message('error', 'JWT decode failed: ' . $e->getMessage());
                }
            }
        }

        if (!$this->tenant_id) {
            $this->tenant_id = 1;
        }

        $CI->session->set_userdata('tenant_id', $this->tenant_id);

        return $this->tenant_id;
    }

    public function get_tenant_id() {
        return $this->tenant_id;
    }

    public function get_tenant_data() {
        if (!$this->tenant_data) {
            $CI =& get_instance();
            $CI->load->model('tenants_model');
            $this->tenant_data = $CI->tenants_model->find_one(['id' => $this->tenant_id]);
        }
        return $this->tenant_data;
    }

    public function check_plan_limit($limit_key) {
        $tenant = $this->get_tenant_data();
        $CI =& get_instance();
        $CI->load->model('plan_limits_model');

        $limit = $CI->plan_limits_model->get_limit($tenant->plan, $limit_key);

        if ($limit == -1) {
            return TRUE;
        }

        $CI->load->model('usage_stats_model');
        $current_usage = $CI->usage_stats_model->get_current_usage($this->tenant_id, $limit_key);

        return $current_usage < $limit;
    }

    public function enforce_tenant_scope($model) {
        $model->set_tenant_id($this->tenant_id);
        return $model;
    }
}
