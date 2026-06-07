<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Mina EA_Model.
 *
 * Extends the upstream 1.6.0 EA_Model with multi-tenant helpers while
 * preserving the upstream's record-shaping helpers (cast/only/optional/
 * quote_order_by/db_field) that child models depend on.
 *
 * Tenant scoping is opt-in via scope_by_tenant() in read methods.
 * insert/update/delete are not overridden (see commit 68689e5 rationale).
 */
class EA_Model extends CI_Model
{
    /**
     * @var int|null Active tenant_id for this request.
     */
    protected ?int $tenant_id = null;

    /**
     * @var bool Whether the model should enforce tenant scoping.
     */
    protected bool $tenant_scoped = true;

    /**
     * @var string Column name used to scope by tenant.
     */
    protected string $tenant_column = 'tenant_id';

    /**
     * @var array Field cast definitions.
     */
    protected array $casts = [];

    /**
     * @var array API resource mapping.
     */
    protected array $api_resource = [];

    /**
     * EA_Model constructor.
     */
    public function __construct()
    {
        // Note: parent::__construct() intentionally omitted.
        // CI_Model in Easy!Appointments 1.6.0 has no explicit constructor and
        // some PHP 8.x versions raise "Cannot call constructor" when invoking
        // parent::__construct() against a parent with no constructor. The
        // base CI_Model behavior (magic __get for $this->db, etc.) still works
        // because PHP invokes the implicit Object constructor automatically.
        $this->load->library('session');
        $this->tenant_id = (int) ($this->session->userdata('tenant_id') ?: 1);
    }

    /**
     * Inject the active tenant_id into a where array.
     *
     * @param array $conditions Existing where conditions.
     * @return array Conditions with tenant_id added when relevant.
     */
    public function scope_by_tenant(array $conditions = []): array
    {
        if (!$this->tenant_scoped || !$this->tenant_id) {
            return $conditions;
        }
        if (!isset($conditions[$this->tenant_column])) {
            $conditions[$this->tenant_column] = $this->tenant_id;
        }
        return $conditions;
    }

    /**
     * @return int Current tenant_id.
     */
    public function get_tenant_id(): int
    {
        return (int) $this->tenant_id;
    }

    /**
     * Override the tenant_id for this model instance.
     */
    public function set_tenant_id(int $tenant_id): self
    {
        $this->tenant_id = $tenant_id;
        return $this;
    }

    /**
     * Save (insert or update) a record.
     */
    public function add(array $record): int
    {
        return $this->save($record);
    }

    /**
     * @deprecated Since 1.5
     */
    public function get_value(string $field, int $record_id): string
    {
        if (method_exists($this, 'value')) {
            return $this->value($field, $record_id);
        }
        throw new RuntimeException('The "get_value" is not defined in model: ' . __CLASS__);
    }

    /**
     * @deprecated Since 1.5
     */
    public function get_row(int $record_id): array
    {
        if (method_exists($this, 'find')) {
            return $this->find($record_id);
        }
        throw new RuntimeException('The "get_row" is not defined in model: ' . __CLASS__);
    }

    public function get_batch($where = null, ?int $limit = null, ?int $offset = null, ?string $order_by = null): array
    {
        return $this->get($where, $limit, $offset, $order_by);
    }

    public function cast(array &$record)
    {
        foreach ($this->casts as $attribute => $cast) {
            if (!isset($record[$attribute])) {
                continue;
            }

            switch ($cast) {
                case 'integer':
                    $record[$attribute] = (int) $record[$attribute];
                    break;
                case 'float':
                    $record[$attribute] = (float) $record[$attribute];
                    break;
                case 'boolean':
                    $record[$attribute] = (bool) $record[$attribute];
                    break;
                case 'string':
                    $record[$attribute] = (string) $record[$attribute];
                    break;
                default:
                    throw new RuntimeException('Unsupported cast type provided: ' . $cast);
            }
        }
    }

    public function only(array &$record, array $fields)
    {
        if (is_assoc($record)) {
            $record = array_fields($record, $fields);
        } else {
            foreach ($record as &$record_item) {
                $record_item = array_fields($record_item, $fields);
            }
        }
    }

    public function optional(array &$record, array $fields)
    {
        if (is_assoc($record)) {
            foreach ($fields as $field => $default) {
                $record[$field] = $record[$field] ?? null ?: $default;
            }
        } else {
            foreach ($record as &$record_item) {
                foreach ($fields as $field => $default) {
                    $record_item[$field] = $record_item[$field] ?? null ?: $default;
                }
            }
        }
    }

    public function db_field(string $api_field): ?string
    {
        return $this->api_resource[$api_field] ?? null;
    }

    public function quote_order_by(?string $order_by): ?string
    {
        if (!$order_by) {
            return null;
        }

        $parts = explode(',', $order_by);
        $quoted_parts = [];

        foreach ($parts as $part) {
            $tokens = preg_split('/\s+/', trim($part));
            $column = array_shift($tokens);
            $direction = strtoupper($tokens[0] ?? '');

            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $column)) {
                continue;
            }

            if (strpos($column, '.') !== false) {
                $column_parts = explode('.', $column);
                $column = '`' . $column_parts[0] . '`.`' . $column_parts[1] . '`';
            } else {
                $column = '`' . $column . '`';
            }

            if ($direction === 'ASC' || $direction === 'DESC') {
                $quoted_parts[] = $column . ' ' . $direction;
            } else {
                $quoted_parts[] = $column;
            }
        }

        return !empty($quoted_parts) ? implode(', ', $quoted_parts) : null;
    }
}
