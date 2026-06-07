<?php
class EA_Controller extends CI_Controller {

    protected $tenant_id = NULL;
    protected $current_user = NULL;

    public function __construct() {
        parent::__construct();

        $this->load->library('EA_Tenant_Middleware');
        $this->tenant_id = $this->ea_tenant_middleware->handle();

        $this->_load_current_user();
    }

    protected function _load_current_user() {
        $user_id = $this->session->userdata('user_id');

        if ($user_id) {
            $this->load->model('users_model');
            $this->current_user = $this->users_model->find_one(['id' => $user_id]);
        }
    }

    protected function require_auth() {
        if (!$this->current_user && !$this->input->get_request_header('Authorization')) {
            $this->response_error('Authentication required', 401);
        }
    }

    protected function require_role($role_slug) {
        $this->require_auth();

        if ($this->current_user->role_slug !== $role_slug) {
            $this->response_error('Insufficient permissions', 403);
        }
    }

    protected function response($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function response_error($message, $status = 400) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }

    protected function response_list($items, $total = NULL, $page = 1, $length = 20) {
        $this->response([
            'data' => $items,
            'pagination' => [
                'page' => (int)$page,
                'length' => (int)$length,
                'total' => $total ?? count($items)
            ]
        ]);
    }
}
