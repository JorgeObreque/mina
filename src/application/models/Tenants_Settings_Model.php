<?php
class Tenants_Settings_Model extends EA_Model {

    protected $tenant_scoped = FALSE;

    public function __construct() {
        parent::__construct();
    }

    public function get_setting($tenant_id, $key) {
        $result = $this->find_one([
            'tenant_id' => $tenant_id,
            'setting_key' => $key
        ]);

        return $result ? $result->setting_value : NULL;
    }

    public function get_all_settings($tenant_id) {
        $settings = $this->find_all(['tenant_id' => $tenant_id]);
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->setting_key] = $setting->setting_value;
        }
        return $result;
    }

    public function save_setting($tenant_id, $key, $value) {
        $existing = $this->find_one([
            'tenant_id' => $tenant_id,
            'setting_key' => $key
        ]);

        if ($existing) {
            return $this->update(
                ['id' => $existing->id],
                ['setting_value' => $value]
            );
        } else {
            return $this->insert([
                'tenant_id' => $tenant_id,
                'setting_key' => $key,
                'setting_value' => $value
            ]);
        }
    }

    public function save_settings($tenant_id, $settings) {
        foreach ($settings as $key => $value) {
            $this->save_setting($tenant_id, $key, $value);
        }
        return TRUE;
    }

    public function set_default_settings($tenant_id) {
        $defaults = [
            'business_name' => '',
            'business_email' => '',
            'business_phone' => '',
            'business_address' => '',
            'timezone' => 'UTC',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'currency' => 'EUR',
            'currency_symbol' => '€',
            'logo_url' => '',
            'primary_color' => '#3f51b5',
            'secondary_color' => '#f5f5f5'
        ];

        return $this->save_settings($tenant_id, $defaults);
    }
}
