# Security Monitoring

This guide describes what to monitor in 1000proxy and how to wire app-, infra-, and panel-level signals into actionable alerts. It complements the hardening guidance in `docs/security/SECURITY_BEST_PRACTICES.md`.

## Objectives

- Detect incidents early (auth abuse, provisioning failures, DDoS, resource exhaustion)
- Preserve forensic data (audit records, relevant logs, request correlation)
- Keep customer-impacting paths green (checkout, provisioning, renewal, backups)

## What to monitor

- Application
  - HTTP 5xx/4xx rates, latency percentiles (p50/p95/p99)
  - Queue health: pending jobs, runtime, failed jobs (Horizon)
  - Cache/DB/Redis errors, timeouts
  - Rate limiting: throttled requests and spikes
  - Business KPIs: orders created/paid/hour, provisioning duration

- Security
  - Auth anomalies: failed admin logins, password reset storms, CSRF validation errors
  - Privilege changes: role/permission mutations, guard changes
  - WAF/Fail2Ban events (top source IPs, blocked attempts)
  - Sensitive route access (e.g., /admin, API keys endpoints)

- Infrastructure
  - CPU, RAM, disk (with filesystem inode usage) and network IO
  - Nginx/PHP-FPM availability, upstream errors, TLS cert expiry
  - Database replication/lag (if applicable), slow queries
  - Backup jobs completion, integrity checks, storage capacity

- 3X‑UI Integration (XUI)
  - Login success rate and latency
  - API availability for list/addClient/updateClient/createbackup
  - Provisioning success/failure counts per server

## Built-in tools

- Laravel Horizon for queues (recommended enabled in production)
- Log channels: `laravel.log` (app), `xui.log` (panel API), optional `security.log`
- Health probes (custom `/health` endpoint recommended)

### Horizon essentials

- Workers online and balanced per queue
- Failed jobs: alert on any spike (> 3/min over 5 min)
- Pending jobs age: alert if > 60s sustained

### Log channels

Ensure these channels exist in `config/logging.php` (create if missing):

- xui (daily): records 3X‑UI panel interactions; path `storage/logs/xui-*.log`
- security (daily): records auth, permission changes, audit events; path `storage/logs/security-*.log`

Rotate daily with default retention or forward to ELK/Loki for centralization.

## Probes & runbooks

### App health

- Endpoint: `GET /health` should return 200 OK with JSON body
- If not present, add a controller to check DB, cache, queue connection

### Queue health quick checks

- `php artisan horizon:status` should be `running`
- `php artisan horizon:supervisors` shows active supervisors

### XUI panel health (per server)

- Login: expect 200 and cookie set on `POST {base}/login`
- List inbounds: `GET {base}/panel/api/inbounds/list` success=true
- Backup: `GET {base}/panel/api/inbounds/createbackup` success=true

Correlate requests using timestamps in `storage/logs/xui-*.log`.

### Backup verification

- Verify backups exist, are recent, and restorable
- If Telegram bot delivery is enabled, ensure at least weekly success messages

## Alerting thresholds (starter)

- 3X‑UI login failures > 3 in 10 minutes per server → warn
- Provisioning failure rate > 5% over 15 minutes → critical
- Failed jobs > 10 in 5 minutes → critical
- Disk usage > 90% or inodes > 90% → critical
- TLS certificate expires in < 14 days → warn

## Wiring alerts (email/Telegram)

- Use Laravel notifications for app signals; integrate Telegram via the existing bot for ops alerts
- Ship logs to your SIEM (ELK, Loki) and configure rules on:
  - `channel = security` and `action in [login_failed, permission_changed, user_locked]`
  - `channel = xui` and `level >= warning`

## Dashboards checklist

- App: requests, p95 latency, 5xx rate, user sign-ins, orders/hour
- Queue: processed/min, failed/min, runtime p95
- XUI: login success rate, addClient success rate, backup status by day
- Infra: CPU/RAM/disk, Nginx 5xx, DB connections/slow queries, Redis ops/sec

## Maintenance

- Review alert thresholds quarterly
- Test restore from backups quarterly (tabletop + live)
- Rotate tokens/passwords; validate monitors after rotation

## References

- Security Best Practices: `docs/security/SECURITY_BEST_PRACTICES.md`
- Audit Logging: `docs/security/AUDIT_LOGGING.md`
- XUI Integration: `docs/integrations/XUI_INTEGRATION.md`
