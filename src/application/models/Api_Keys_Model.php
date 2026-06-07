<?php
class Api_Keys_Model extends EA_Model {

    protected $tenant_scoped = FALSE;

    public function __construct() {
        parent::__construct();
    }

    public function generate_key($tenant_id, $name, $permissions = []) {
        $api_key = bin2hex(random_bytes(32));
        $api_secret = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);

        return $this->insert([
            'tenant_id' => $tenant_id,
            'api_key' => $api_key,
            'api_secret' => $api_secret,
            'name' => $name,
            'permissions' => json_encode($permissions),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year'))
        ]);
    }

    public function validate_key($api_key) {
        $key_data = $this->find_one(['api_key' => $api_key]);

        if (!$key_data) {
            return FALSE;
        }

        if ($key_data->expires_at && strtotime($key_data->expires_at) < time()) {
            return FALSE;
        }

        $this->update(['id' => $key_data->id], ['last_used_at' => date('Y-m-d H:i:s')]);

        return $key_data;
    }

    public function get_permissions($api_key_id) {
        $key_data = $this->find_one(['id' => $api_key_id]);
        return $key_data ? json_decode($key_data->permissions, TRUE) : [];
    }

    public function revoke($api_key_id) {
        return $this->delete(['id' => $api_key_id]);
    }
}
