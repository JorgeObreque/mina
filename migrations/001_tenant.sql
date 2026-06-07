-- Mina SaaS Multi-tenant Migration
-- Phase 1: Multi-tenancy Core

-- Tenants table (master tenant registry)
CREATE TABLE IF NOT EXISTS ea_tenants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL,
    plan ENUM('basic', 'pro', 'enterprise') DEFAULT 'basic',
    status ENUM('active', 'suspended', 'trial', 'cancelled') DEFAULT 'trial',
    settings JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add tenant_id to existing tables
ALTER TABLE ea_users ADD COLUMN tenant_id INT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE ea_user_settings ADD COLUMN tenant_id INT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE ea_appointments ADD COLUMN tenant_id INT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE ea_services ADD COLUMN tenant_id INT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE ea_service_categories ADD COLUMN tenant_id INT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE ea_customers ADD COLUMN tenant_id INT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE ea_settings ADD COLUMN tenant_id INT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE ea_blocked_periods ADD COLUMN tenant_id INT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE ea_webhooks ADD COLUMN tenant_id INT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE ea_working_plan_exceptions ADD COLUMN tenant_id INT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE ea_consents ADD COLUMN tenant_id INT UNSIGNED NOT NULL DEFAULT 1;

-- Services-Providers junction table (needs tenant_id too)
ALTER TABLE ea_services_providers ADD COLUMN tenant_id INT UNSIGNED NOT NULL DEFAULT 1;

-- Roles table add tenant_id
ALTER TABLE ea_roles ADD COLUMN tenant_id INT UNSIGNED NOT NULL DEFAULT 1;

-- Create indexes for tenant_id columns
ALTER TABLE ea_users ADD INDEX idx_tenant (tenant_id);
ALTER TABLE ea_user_settings ADD INDEX idx_tenant (tenant_id);
ALTER TABLE ea_appointments ADD INDEX idx_tenant (tenant_id);
ALTER TABLE ea_services ADD INDEX idx_tenant (tenant_id);
ALTER TABLE ea_service_categories ADD INDEX idx_tenant (tenant_id);
ALTER TABLE ea_customers ADD INDEX idx_tenant (tenant_id);
ALTER TABLE ea_settings ADD INDEX idx_tenant (tenant_id);
ALTER TABLE ea_blocked_periods ADD INDEX idx_tenant (tenant_id);
ALTER TABLE ea_webhooks ADD INDEX idx_tenant (tenant_id);
ALTER TABLE ea_working_plan_exceptions ADD INDEX idx_tenant (tenant_id);
ALTER TABLE ea_consents ADD INDEX idx_tenant (tenant_id);
ALTER TABLE ea_services_providers ADD INDEX idx_tenant (tenant_id);
ALTER TABLE ea_roles ADD INDEX idx_tenant (tenant_id);

-- Tenant settings table (isolated per-tenant configuration)
CREATE TABLE IF NOT EXISTS ea_tenant_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    setting_key VARCHAR(255) NOT NULL,
    setting_value TEXT,
    UNIQUE KEY unique_tenant_setting (tenant_id, setting_key),
    INDEX idx_tenant (tenant_id),
    FOREIGN KEY (tenant_id) REFERENCES ea_tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API keys per tenant
CREATE TABLE IF NOT EXISTS ea_api_keys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    api_key VARCHAR(64) NOT NULL UNIQUE,
    api_secret VARCHAR(255) NOT NULL,
    name VARCHAR(255),
    permissions JSON,
    last_used_at DATETIME,
    expires_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_api_key (api_key),
    FOREIGN KEY (tenant_id) REFERENCES ea_tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usage tracking for quotas
CREATE TABLE IF NOT EXISTS ea_usage_stats (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    stat_type ENUM('appointments', 'providers', 'customers', 'api_calls') NOT NULL,
    count INT UNSIGNED DEFAULT 0,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_tenant_stat_period (tenant_id, stat_type, period_start),
    INDEX idx_tenant (tenant_id),
    FOREIGN KEY (tenant_id) REFERENCES ea_tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default tenant (for system)
INSERT INTO ea_tenants (id, name, slug, email, plan, status) VALUES
(1, 'System', 'system', 'system@mina.local', 'enterprise', 'active');

-- Insert plan limits (reference table)
CREATE TABLE IF NOT EXISTS ea_plan_limits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan ENUM('basic', 'pro', 'enterprise') NOT NULL,
    limit_key VARCHAR(100) NOT NULL,
    limit_value INT NOT NULL,
    UNIQUE KEY unique_plan_limit (plan, limit_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ea_plan_limits (plan, limit_key, limit_value) VALUES
('basic', 'providers', 1),
('basic', 'appointments_per_month', 50),
('basic', 'customers', 100),
('basic', 'webhooks', 0),
('basic', 'api_calls_per_month', 100),
('pro', 'providers', 3),
('pro', 'appointments_per_month', 500),
('pro', 'customers', 1000),
('pro', 'webhooks', 5),
('pro', 'api_calls_per_month', 5000),
('enterprise', 'providers', -1),
('enterprise', 'appointments_per_month', -1),
('enterprise', 'customers', -1),
('enterprise', 'webhooks', -1),
('enterprise', 'api_calls_per_month', -1);
