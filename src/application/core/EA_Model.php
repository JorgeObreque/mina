<?php
require_once APPPATH . 'libraries/JWT.php';

class EA_Model extends CI_Model {

    protected $tenant_id = NULL;
    protected $tenant_scoped = TRUE;
    protected $tenant_column = 'tenant_id';

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->_set_tenant_from_context();
    }

    protected function _set_tenant_from_context() {
        $CI =& get_instance();

        if ($CI->input->get_request_header('Authorization')) {
            $token = str_replace('Bearer ', '', $CI->input->get_request_header('Authorization'));
            try {
                $jwt = new JWT();
                $payload = $jwt->decode($token);
                $this->tenant_id = $payload->tenant_id ?? NULL;
            } catch (Exception $e) {
                $this->tenant_id = $CI->session->userdata('tenant_id');
            }
        } else {
            $this->tenant_id = $CI->session->userdata('tenant_id');
        }

        if (!$this->tenant_id) {
            $this->tenant_id = 1;
        }
    }

    public function set_tenant_id($tenant_id) {
        $this->tenant_id = $tenant_id;
        return $this;
    }

    public function get_tenant_id() {
        return $this->tenant_id;
    }

    public function get_where($conditions, $limit = NULL, $offset = NULL) {
        if ($this->tenant_scoped && $this->tenant_id && !isset($conditions[$this->tenant_column])) {
            $conditions[$this->tenant_column] = $this->tenant_id;
        }
        return parent::get_where($conditions, $limit, $offset);
    }

    public function insert($data, $return_scalar = FALSE) {
        if ($this->tenant_scoped && $this->tenant_id && !isset($data[$this->tenant_column])) {
            $data[$this->tenant_column] = $this->tenant_id;
        }
        return parent::insert($data, $return_scalar);
    }

    public function update($conditions, $data) {
        if ($this->tenant_scoped && $this->tenant_id && !isset($conditions[$this->tenant_column])) {
            $conditions[$this->tenant_column] = $this->tenant_id;
        }
        return parent::update($conditions, $data);
    }

    public function delete($conditions) {
        if ($this->tenant_scoped && $this->tenant_id && !isset($conditions[$this->tenant_column])) {
            $conditions[$this->tenant_column] = $this->tenant_id;
        }
        return parent::delete($conditions);
    }

    public function find_all($conditions = [], $order_by = NULL, $limit = NULL, $offset = NULL) {
        if ($this->tenant_scoped && $this->tenant_id && !isset($conditions[$this->tenant_column])) {
            $conditions[$this->tenant_column] = $this->tenant_id;
        }
        return parent::find_all($conditions, $order_by, $limit, $offset);
    }

    public function find_one($conditions) {
        if ($this->tenant_scoped && $this->tenant_id && !isset($conditions[$this->tenant_column])) {
            $conditions[$this->tenant_column] = $this->tenant_id;
        }
        return parent::find_one($conditions);
    }

    public function query($sql, $binds = [], $return_object = TRUE) {
        if ($this->tenant_scoped && $this->tenant_id) {
            $sql = str_replace('WHERE', 'WHERE ' . $this->tenant_column . ' = ' . $this->tenant_id . ' AND', $sql);
        }
        return parent::query($sql, $binds, $return_object);
    }

    public function count_all($conditions = []) {
        if ($this->tenant_scoped && $this->tenant_id && !isset($conditions[$this->tenant_column])) {
            $conditions[$this->tenant_column] = $this->tenant_id;
        }
        return parent::count_all($conditions);
    }

    public function disable_tenant_scope() {
        $this->tenant_scoped = FALSE;
        return $this;
    }

    public function enable_tenant_scope() {
        $this->tenant_scoped = TRUE;
        return $this;
    }
}
