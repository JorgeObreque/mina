<?php
class Usage_Stats_Model extends EA_Model {

    protected $tenant_scoped = FALSE;

    public function __construct() {
        parent::__construct();
    }

    public function increment($tenant_id, $stat_type, $amount = 1) {
        $period_start = date('Y-m-01');
        $period_end = date('Y-m-t');

        $existing = $this->find_one([
            'tenant_id' => $tenant_id,
            'stat_type' => $stat_type,
            'period_start' => $period_start
        ]);

        if ($existing) {
            return $this->update(
                ['id' => $existing->id],
                ['count' => $existing->count + $amount]
            );
        } else {
            return $this->insert([
                'tenant_id' => $tenant_id,
                'stat_type' => $stat_type,
                'count' => $amount,
                'period_start' => $period_start,
                'period_end' => $period_end
            ]);
        }
    }

    public function get_current_usage($tenant_id, $stat_type) {
        $period_start = date('Y-m-01');

        $result = $this->find_one([
            'tenant_id' => $tenant_id,
            'stat_type' => $stat_type,
            'period_start' => $period_start
        ]);

        return $result ? (int)$result->count : 0;
    }

    public function get_usage_for_period($tenant_id, $stat_type, $period_start, $period_end) {
        $result = $this->find_one([
            'tenant_id' => $tenant_id,
            'stat_type' => $stat_type,
            'period_start' => $period_start,
            'period_end' => $period_end
        ]);

        return $result ? (int)$result->count : 0;
    }
}
