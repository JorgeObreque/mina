<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Mina EA_Model.
 *
 * Extends the upstream 1.6.0 EA_Model with multi-tenant helpers.
 *
 * The upstream EA_Model deliberately does NOT override insert/update/delete
 * to keep signatures compatible with the CodeIgniter DB_query_builder.
 * Child models define their own `insert(array): int`, `update(array): int`
 * and `delete(int): void` methods as custom methods (not overrides).
 *
 * This Mina subclass adds:
 *   - $tenant_id (resolved from EA_Controller or session)
 *   - $tenant_scoped (models can opt out via $tenant_scoped = false)
 *   - $tenant_column (defaults to 'tenant_id')
 *   - scope_by_tenant(): helper to inject tenant filter into a where array
 *
 * Tenant_id is NOT injected automatically into queries. Models must
 * explicitly call scope_by_tenant() in their read methods to enforce
 * isolation. This keeps the change additive and easy to audit.
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
     * EA_Model constructor.
     */
    public function __construct()
    {
        parent::__construct();
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
}
