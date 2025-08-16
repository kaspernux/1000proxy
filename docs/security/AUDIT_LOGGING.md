# Audit Logging

Robust audit logs enable incident response and compliance. This guide defines what to record, where to store it, and how to secure it.

## Goals

- Attribute sensitive actions to identities (who, when, where, what)
- Preserve integrity and retention suitable for your environment
- Enable efficient querying and correlation across systems

## What to log

- Authentication
  - Admin/customer sign-ins and failures (with IP/UA, result)
  - MFA challenges (no secrets)
  - Password resets and 2FA resets

- Authorization / RBAC
  - Role and permission changes (actor, subject, diff)
  - Guard changes (customer vs user)

- Business & Provisioning
  - Order state transitions (created → paid → processing → completed/failed)
  - Provisioning requests to 3X‑UI (endpoint, status, correlation id)
  - Client lifecycle: created/updated/deleted/reset

- Configuration & Secrets
  - Changes to server endpoints, credentials (redacted), web base path
  - Feature flag toggles impacting auth or security

## Where to log

- Application log (default): `storage/logs/laravel.log`
- Security log (channel `security`): `storage/logs/security-*.log`
- XUI integration log (channel `xui`): `storage/logs/xui-*.log`

Forward to ELK/Loki for centralized retention and alerting. Ensure transport is secured (TLS) and access is least-privilege.

## Implementation tips

- Use Laravel events/listeners to capture state changes; include `actor_id`, `actor_type`, `ip`, `ua`, `request_id`
- Wrap 3X‑UI calls with correlation ids; log request URL (without secrets) and response status/success flag
- Redact secrets consistently (`******`) and avoid logging tokens or passwords

### Example fields

```
timestamp, channel, level, event, actor_id, actor_type, ip, ua, subject_type, subject_id, action, details
```

### Example events

- security.login_failed (actor: email or id, ip, ua, reason)
- security.permission_changed (actor_id, subject_id, changes)
- order.status_changed (order_id, from, to, reason)
- xui.request (server_id, endpoint, status, success, latency_ms)

## Retention & integrity

- Production: retain security logs ≥ 90 days (or per policy); archive ≥ 1 year if required
- Development: shorter retention (7–14 days) is acceptable
- Enable immutability (WORM/S3 Object Lock) for critical logs when possible

## Access control

- Restrict access to security logs to a limited admin group
- Do not expose raw logs to customers; surface summaries via dashboards instead

## Queries (examples)

- Top failed admin login sources (last 24h)
- Orders that failed provisioning with XUI errors (last 7d)
- Role changes by actor over period

## References

- Security Best Practices: `docs/security/SECURITY_BEST_PRACTICES.md`
- Monitoring: `docs/security/MONITORING.md`
- XUI Integration: `docs/integrations/XUI_INTEGRATION.md`
