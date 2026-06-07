<?php
class Plan_Limits_Model extends EA_Model {

    protected $tenant_scoped = FALSE;

    public function __construct() {
        parent::__construct();
    }

    public function get_limit($plan, $limit_key) {
        $result = $this->find_one([
            'plan' => $plan,
            'limit_key' => $limit_key
        ]);

        return $result ? (int)$result->limit_value : 0;
    }

    public function get_all_limits($plan) {
        $limits = $this->find_all(['plan' => $plan]);
        $result = [];
        foreach ($limits as $limit) {
            $result[$limit->limit_key] = (int)$limit->limit_value;
        }
        return $result;
    }

    public function check_limit($plan, $limit_key, $current_usage) {
        $limit = $this->get_limit($plan, $limit_key);

        if ($limit == -1) {
            return ['allowed' => TRUE, 'limit' => -1, 'remaining' => -1];
        }

        return [
            'allowed' => $current_usage < $limit,
            'limit' => $limit,
            'remaining' => max(0, $limit - $current_usage)
        ];
    }
}
