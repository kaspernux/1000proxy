# XUI (3X-UI) Integration Guide

This guide explains how 1000proxy integrates with MHSanaei/3x-ui using the provided Postman collection. It covers authentication, inbounds, client management, traffic resets, and backups. The examples map to our internal services: XuiService and ClientProvisioningService.

## Prerequisites
- A reachable 3x-ui panel (HTTP/HTTPS)
- Panel credentials
- 1000proxy configured with the panel base URL and optional web base path

## Configuration
Set these environment variables in .env (or via config/provisioning.php):

- XUI_BASE_URL=https://panel.example.com
- XUI_PORT=2053              # optional if included in BASE_URL
- XUI_WEB_BASE_PATH=         # e.g. /xui when reverse-proxied under a path
- XUI_USERNAME=admin
- XUI_PASSWORD=change-me
- XUI_TIMEOUT=10
- XUI_RETRIES=2

Our XuiService normalizes URLs like: {BASE_URL}:{PORT}{WEB_BASE_PATH}/panel/api/...
It maintains a session cookie from the Login call and retries idempotent requests with exponential backoff.

## Authentication
POST {BASE}/login with urlencoded username/password.
- On success, capture Set-Cookie header (3x-ui session) for subsequent requests.
- Our service persists the cookie until expiration.

## Inbounds
- List: GET /panel/api/inbounds/list
- Get one: GET /panel/api/inbounds/get/{inboundId}
- Add: POST /panel/api/inbounds/add (JSON body; nested JSON strings for settings/streamSettings/sniffing/allocate)
- Update: POST /panel/api/inbounds/update/{inboundId}
- Delete: POST /panel/api/inbounds/del/{inboundId}

Notes:
- settings, streamSettings, sniffing, allocate must be JSON-stringified in 3x-ui payloads.
- Port collisions return a friendly error; we surface that upstream.

## Clients
- Add: POST /panel/api/inbounds/addClient with body { id, settings }
- Update: POST /panel/api/inbounds/updateClient/{uuid} with body { id, settings }
- Delete: POST /panel/api/inbounds/{inboundId}/delClient/{uuid}
- Traffic by email: GET /panel/api/inbounds/getClientTraffics/{email}
- Traffic by id (uuid): GET /panel/api/inbounds/getClientTrafficsById/{uuid}

## Traffic & Maintenance
- Reset all inbounds: POST /panel/api/inbounds/resetAllTraffics
- Reset all clients in inbound: POST /panel/api/inbounds/resetAllClientTraffics/{inboundId}
- Reset single client: POST /panel/api/inbounds/{inboundId}/resetClientTraffic/{email}
- Client IPs: POST /panel/api/inbounds/clientIps/{email}
- Clear client IPs: POST /panel/api/inbounds/clearClientIps/{email}
- Create backup (tgbot): GET /panel/api/inbounds/createbackup

## Error Handling
- All responses share { success, msg, obj }
- We map success=false to well-typed exceptions; msg is preserved for UI/logs
- Network/timeout errors are retried when safe; otherwise surfaced with context

## Testing
- Tests use HTTP fakes to simulate 3x-ui endpoints based on this contract
- Provide deterministic JSON bodies; ensure nested fields match as strings

## Security
- Use HTTPS for panel whenever possible
- Scope credentials to least privilege
- Rotate panel password regularly

## Troubleshooting
- 200 OK with success=false indicates business error; read msg
- 200 OK with obj=null for lookups means not found
- Check reverse proxy path (WEB_BASE_PATH) if you see 404s on /panel/api/*
