# Mina SaaS - Session Status (June 7, 2026)

## Summary
Multi-tenant appointment scheduler for barbershops/salons. Based on Easy!Appointments **1.6.0** (full upstream, not partial fork). Phase 1 (multi-tenant foundation) in progress.

## Stack
- PHP 8.2 / CodeIgniter 3
- Vanilla JS + jQuery, Bootstrap 5
- MySQL 8.0
- Redis (sessions/cache)
- nginx
- Docker + docker-compose (dev & prod)

## Upstream Strategy
We base Mina on the **complete** Easy!Appointments 1.6.0 release (not a partial copy).
Local reference: `/home/jorge/docker/easyappointments` checked out on tag `1.6.0` (commit `4bc7ecd5`).
1.6.0 includes 400 commits of security hardening vs 1.5.1 — relevant fixes:
  - `enforce provider ownership on appointment save` (anti-IDOR)
  - `enforce provider ownership in google oauth callback`
  - `harden caldav url validation against ssrf`
  - `sanitize disabled booking message HTML`
  - `prevent PII disclosure in appointment reschedule flow`
  - `reuse existing customer when saving calendar appointments`
  - PHP 8.5 for development

## Dev / Build
- Working dir: /home/jorge/docker/agenda_saas
- Dev env: docker-compose.dev.yml (port 8080)
- Test URLs:
  - http://localhost:8080/booking (public)
  - http://localhost:8080/installation (first-time installer)
  - http://localhost:8080/index.php/backend/login (backoffice)

## Multi-tenant Architecture

### Tenant Resolution
`EA_Controller::__construct()` (src/application/core/EA_Controller.php) resolves `tenant_id` from:
1. JWT in `Authorization: Bearer <token>` header
2. `tenant_id` cookie
3. `session('tenant_id')`
4. `config('tenant.default_id')` fallback (1)

Resolution order is JWT-first so API clients with valid tokens get correct tenant even with stale cookies.

### Tenant Scoping
- `EA_Model` exposes `scope_by_tenant(array $where): array` helper.
- Models must **opt in** to tenant filtering by calling `scope_by_tenant()` in their read methods.
- `insert/update/delete` are **not** overridden (lesson learned from v1.5.1 conflict saga) — child models keep their strict `insert(array): int` / `update(array): int` / `delete(int): void` signatures and the multi-tenant filter is applied at controller level via `where = scope_by_tenant($where)`.

### Config
- `src/application/config/jwt.php` — JWT secret/TTL/algorithm + tenant defaults.
- `src/config.php` — `Config` class with DB + base URL. Values mirror `.env`.

## Migrations
- `migrations/001_tenant.sql` — creates `ea_tenants` table and adds `tenant_id` column to all 13 EA tables.

## Pending Phases (in order)
1. **Phase 2 — SaaS Core**: apply 001_tenant.sql, run installer, restore Tenants/Plan_Limits/Usage_Stats/Api_Keys models, build tenant registration endpoint, onboarding wizard, plan limits enforcement, white-label basics.
2. **Phase 3 — Premium Features**: Webhooks, Google Calendar per-tenant, API keys for outbound integrations.
3. **Phase 4 — DevOps**: SSL, backups, monitoring, multi-tenant observability.

## Container Info
- mina-app (PHP-FPM + nginx sidecar) — port 9000 internal
- mina-nginx-proxy (nginx) — port 8080 (dev) / 80+443 (prod)
- mina-mysql (MySQL 8.0) — port 3306
- mina-redis (Redis) — port 6379
- mina-cron — restart loop (cron binary issue, low priority)

## DB Connection
- Host: mysql
- User: mina_user
- Pass: mina_dev_password
- DB: agenda_saas
- Prefix: ea_

## Opcache Note
opcache is enabled with `validate_timestamps=0`. After code changes:
```bash
docker exec mina-app kill -USR2 10
```

## Branches
- `main` — current
- `backup/partial-pre-reset` — pre-reset snapshot (kept for reference of Mina multi-tenant work-in-progress; do not delete yet)

## Useful Commands
```bash
# Test booking page
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8080/booking

# Test installation
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8080/installation

# Reload PHP-FPM (clear opcache)
docker exec mina-app kill -USR2 10

# Check logs
docker exec mina-app tail -20 /var/www/html/storage/logs/log-$(date +%Y-%m-%d).php
```

## File Locations
- Upstream reference: /home/jorge/docker/easyappointments/ (tag 1.6.0)
- App source: /home/jorge/docker/agenda_saas/src/
- Custom core: /home/jorge/docker/agenda_saas/src/application/core/EA_Controller.php, EA_Model.php
- JWT library: /home/jorge/docker/agenda_saas/src/application/libraries/JWT.php
- Models: /home/jorge/docker/agenda_saas/src/application/models/
- Views: /home/jorge/docker/agenda_saas/src/application/views/
- Controllers: /home/jorge/docker/agenda_saas/src/application/controllers/
- Helpers: /home/jorge/docker/agenda_saas/src/application/helpers/
- Migrations: /home/jorge/docker/agenda_saas/migrations/
