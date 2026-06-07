<?php
class Tenants_Model extends EA_Model {

    protected $tenant_scoped = FALSE;

    public function __construct() {
        parent::__construct();
    }

    public function create($data) {
        $slug = $this->_generate_slug($data['name']);

        $tenant_data = [
            'name' => $data['name'],
            'slug' => $slug,
            'email' => $data['email'],
            'plan' => $data['plan'] ?? 'basic',
            'status' => 'trial',
            'settings' => json_encode($data['settings'] ?? [])
        ];

        $tenant_id = $this->insert($tenant_data);

        $this->load->model('Tenants_Settings_Model');
        $this->tenants_settings_model->set_default_settings($tenant_id);

        return $tenant_id;
    }

    public function find_by_slug($slug) {
        return $this->find_one(['slug' => $slug]);
    }

    public function find_by_email($email) {
        return $this->find_one(['email' => $email]);
    }

    public function update_plan($tenant_id, $plan) {
        return $this->update(['id' => $tenant_id], ['plan' => $plan]);
    }

    public function update_status($tenant_id, $status) {
        return $this->update(['id' => $tenant_id], ['status' => $status]);
    }

    public function get_settings($tenant_id) {
        $this->load->model('Tenants_Settings_Model');
        return $this->tenants_settings_model->get_all_settings($tenant_id);
    }

    public function save_settings($tenant_id, $settings) {
        $this->load->model('Tenants_Settings_Model');
        return $this->tenants_settings_model->save_settings($tenant_id, $settings);
    }

    private function _generate_slug($name) {
        $slug = url_title(convert_accented_characters($name), '-', TRUE);
        $slug = strtolower($slug);

        $original_slug = $slug;
        $counter = 1;

        while ($this->find_by_slug($slug)) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
