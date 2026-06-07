-- Mina SaaS Multi-tenant Migration (idempotent)
-- Phase 1: Multi-tenancy Core
-- Safe to apply multiple times: every ALTER / CREATE is gated by an
-- information_schema check inside a stored procedure.

DELIMITER $$

-- Drop existing helper procs so this script is re-runnable
DROP PROCEDURE IF EXISTS add_column_if_missing$$
DROP PROCEDURE IF EXISTS add_index_if_missing$$
DROP PROCEDURE IF EXISTS create_tenants_table$$

DELIMITER ;

DELIMITER $$

CREATE PROCEDURE add_column_if_missing(
    IN p_table VARCHAR(128),
    IN p_column VARCHAR(128),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND COLUMN_NAME = p_column
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE ', p_table, ' ADD COLUMN ', p_column, ' ', p_definition);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

CREATE PROCEDURE add_index_if_missing(
    IN p_table VARCHAR(128),
    IN p_index VARCHAR(128),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND INDEX_NAME = p_index
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE ', p_table, ' ADD ', p_definition);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

CREATE PROCEDURE create_tenants_table()
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'ea_tenants'
    ) THEN
        CREATE TABLE ea_tenants (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL,
            plan ENUM('basic', 'pro', 'enterprise') DEFAULT 'basic',
            status ENUM('active', 'suspended', 'trial', 'cancelled') DEFAULT 'trial',
            settings JSON,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_tenants_slug (slug),
            INDEX idx_tenants_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    END IF;
END$$

DELIMITER ;

-- ---------------------------------------------------------------------------
-- 1. Master tenants table
-- ---------------------------------------------------------------------------
CALL create_tenants_table();

-- ---------------------------------------------------------------------------
-- 2. Add tenant_id column to every EA business table
-- ---------------------------------------------------------------------------
CALL add_column_if_missing('ea_users',                    'tenant_id', 'INT UNSIGNED NOT NULL DEFAULT 1');
CALL add_column_if_missing('ea_user_settings',           'tenant_id', 'INT UNSIGNED NOT NULL DEFAULT 1');
CALL add_column_if_missing('ea_appointments',             'tenant_id', 'INT UNSIGNED NOT NULL DEFAULT 1');
CALL add_column_if_missing('ea_services',                 'tenant_id', 'INT UNSIGNED NOT NULL DEFAULT 1');
CALL add_column_if_missing('ea_service_categories',       'tenant_id', 'INT UNSIGNED NOT NULL DEFAULT 1');
CALL add_column_if_missing('ea_settings',                 'tenant_id', 'INT UNSIGNED NOT NULL DEFAULT 1');
CALL add_column_if_missing('ea_blocked_periods',          'tenant_id', 'INT UNSIGNED NOT NULL DEFAULT 1');
CALL add_column_if_missing('ea_webhooks',                 'tenant_id', 'INT UNSIGNED NOT NULL DEFAULT 1');
CALL add_column_if_missing('ea_working_plan_exceptions',  'tenant_id', 'INT UNSIGNED NOT NULL DEFAULT 1');
CALL add_column_if_missing('ea_consents',                 'tenant_id', 'INT UNSIGNED NOT NULL DEFAULT 1');
CALL add_column_if_missing('ea_services_providers',       'tenant_id', 'INT UNSIGNED NOT NULL DEFAULT 1');
CALL add_column_if_missing('ea_secretaries_providers',    'tenant_id', 'INT UNSIGNED NOT NULL DEFAULT 1');
CALL add_column_if_missing('ea_roles',                    'tenant_id', 'INT UNSIGNED NOT NULL DEFAULT 1');

-- ---------------------------------------------------------------------------
-- 3. Add tenant_id indexes for query performance
-- ---------------------------------------------------------------------------
CALL add_index_if_missing('ea_users',                   'idx_users_tenant',                   'INDEX idx_users_tenant (tenant_id)');
CALL add_index_if_missing('ea_user_settings',           'idx_user_settings_tenant',           'INDEX idx_user_settings_tenant (tenant_id)');
CALL add_index_if_missing('ea_appointments',            'idx_appointments_tenant',            'INDEX idx_appointments_tenant (tenant_id)');
CALL add_index_if_missing('ea_services',                'idx_services_tenant',                'INDEX idx_services_tenant (tenant_id)');
CALL add_index_if_missing('ea_service_categories',      'idx_service_categories_tenant',      'INDEX idx_service_categories_tenant (tenant_id)');
CALL add_index_if_missing('ea_settings',                'idx_settings_tenant',                'INDEX idx_settings_tenant (tenant_id)');
CALL add_index_if_missing('ea_blocked_periods',         'idx_blocked_periods_tenant',         'INDEX idx_blocked_periods_tenant (tenant_id)');
CALL add_index_if_missing('ea_webhooks',                'idx_webhooks_tenant',                'INDEX idx_webhooks_tenant (tenant_id)');
CALL add_index_if_missing('ea_working_plan_exceptions', 'idx_working_plan_exceptions_tenant', 'INDEX idx_working_plan_exceptions_tenant (tenant_id)');
CALL add_index_if_missing('ea_consents',                'idx_consents_tenant',                'INDEX idx_consents_tenant (tenant_id)');
CALL add_index_if_missing('ea_services_providers',      'idx_services_providers_tenant',      'INDEX idx_services_providers_tenant (tenant_id)');
CALL add_index_if_missing('ea_secretaries_providers',   'idx_secretaries_providers_tenant',   'INDEX idx_secretaries_providers_tenant (tenant_id)');
CALL add_index_if_missing('ea_roles',                   'idx_roles_tenant',                   'INDEX idx_roles_tenant (tenant_id)');

-- ---------------------------------------------------------------------------
-- 4. Cleanup helper procedures
-- ---------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS add_column_if_missing;
DROP PROCEDURE IF EXISTS add_index_if_missing;
DROP PROCEDURE IF EXISTS create_tenants_table;
