# 3X-UI API Mapping Analysis

## Overview

This document analyzes the 3X-UI Postman collection to extract the complete remote server structure, parameters, and logic for implementing a comprehensive proxy seller platform.

## 3X-UI API Endpoints Summary

### Authentication

-   **POST** `/login` - Authenticate and get session cookie

### Inbound Management

-   **GET** `/panel/api/inbounds/list` - List all inbounds with client stats
-   **GET** `/panel/api/inbounds/get/{inboundId}` - Get specific inbound details
-   **POST** `/panel/api/inbounds/add` - Create new inbound
-   **POST** `/panel/api/inbounds/update/{inboundId}` - Update existing inbound
-   **POST** `/panel/api/inbounds/del/{inboundId}` - Delete inbound

### Client Management

-   **GET** `/panel/api/inbounds/getClientTraffics/{email}` - Get client by email
-   **GET** `/panel/api/inbounds/getClientTrafficsById/{uuid}` - Get client by UUID
-   **POST** `/panel/api/inbounds/addClient` - Add client to inbound
-   **POST** `/panel/api/inbounds/updateClient/{uuid}` - Update client
-   **POST** `/panel/api/inbounds/{inboundId}/delClient/{uuid}` - Delete client
-   **POST** `/panel/api/inbounds/clientIps/{email}` - Get client IP records
-   **POST** `/panel/api/inbounds/clearClientIps/{email}` - Clear client IPs

### Traffic Management

-   **POST** `/panel/api/inbounds/resetAllTraffics` - Reset all traffic stats
-   **POST** `/panel/api/inbounds/resetAllClientTraffics/{inboundId}` - Reset inbound client traffic
-   **POST** `/panel/api/inbounds/{inboundId}/resetClientTraffic/{email}` - Reset specific client traffic

### Monitoring & Utilities

-   **POST** `/panel/api/inbounds/onlines` - Get online clients
-   **POST** `/panel/api/inbounds/delDepletedClients/{inboundId}` - Remove depleted clients
-   **GET** `/panel/api/inbounds/createbackup` - Create backup via Telegram bot

## Remote Inbound Structure (from API responses)

### Core Inbound Fields

```json
{
  "id": 3,
  "up": 0,                    // Upload traffic bytes
  "down": 0,                  // Download traffic bytes
  "total": 0,                 // Total traffic bytes
  "remark": "",               // Description/comment
  "enable": true,             // Active status
  "expiry_time": 0,            // Expiration timestamp (0 = never)
  "clientStats": [...],       // Array of client statistics
  "listen": "",               // Listening IP (empty = all interfaces)
  "port": 37155,              // Listening port
  "protocol": "vless",        // Protocol (vless, vmess, trojan, etc.)
  "settings": "{...}",        // JSON string of protocol settings
  "streamSettings": "{...}",  // JSON string of stream/transport settings
  "tag": "inbound-37155",     // Unique tag identifier
  "sniffing": "{...}",        // JSON string of sniffing config
  "allocate": "{...}"         // JSON string of allocation strategy
}
```

### Settings Structure (JSON stringified)

```json
{
    "clients": [
        {
            "id": "819920c0-22c8-4c83-8713-9c3da4980396", // UUID
            "flow": "", // Flow control
            "email": "hyvcs325", // Client identifier
            "limit_ip": 0, // IP connection limit
            "totalGB": 0, // Traffic limit in bytes
            "expiry_time": 0, // Expiration timestamp
            "enable": true, // Active status
            "tg_id": "", // Telegram ID
            "subId": "jmrwimzhicxm7hrm", // Subscription ID
            "reset": 0 // Reset counter/timestamp
        }
    ],
    "decryption": "none",
    "fallbacks": []
}
```

### StreamSettings Structure (JSON stringified)

```json
{
  "network": "tcp",
  "security": "reality",
  "externalProxy": [],
  "realitySettings": {
    "show": false,
    "xver": 0,
    "dest": "yahoo.com:443",
    "serverNames": ["yahoo.com", "www.yahoo.com"],
    "privateKey": "QJS9AerMmDU-DrTe_SAL7vX6_2wg19OxCuthZLLs40g",
    "minClient": "",
    "maxClient": "",
    "maxTimediff": 0,
    "shortIds": ["97de", "5f7b4df7d0605151", ...],
    "settings": {
      "publicKey": "UNXIILQ_LpbZdXGbhNCMele1gaPVIfCJ9N0AoLYdRUE",
      "fingerprint": "random",
      "serverName": "",
      "spiderX": "/"
    }
  },
  "tcpSettings": {
    "acceptProxyProtocol": false,
    "header": {
      "type": "none"
    }
  }
}
```

### Sniffing Structure (JSON stringified)

```json
{
    "enabled": false,
    "destOverride": ["http", "tls", "quic", "fakedns"],
    "metadataOnly": false,
    "routeOnly": false
}
```

### Allocate Structure (JSON stringified)

```json
{
    "strategy": "always",
    "refresh": 5,
    "concurrency": 3
}
```

### Client Statistics Structure

```json
{
    "id": 3,
    "inboundId": 3,
    "enable": true,
    "email": "hyvcs325",
    "up": 0,
    "down": 0,
    "expiry_time": 0,
    "total": 0,
    "reset": 0
}
```

## API Response Format

All responses follow this format:

```json
{
  "success": true/false,
  "msg": "Message text",
  "obj": data_object_or_null
}
```

## Required Model Updates

### 1. ServerInbound Model Enhancements

-   Add `tag` field for unique identification
-   Add `listen` field for binding IP
-   Ensure `settings`, `streamSettings`, `sniffing`, `allocate` are properly structured
-   Add methods for JSON encoding/decoding of complex fields
-   Add validation for required protocol-specific settings

### 2. ServerClient Model Enhancements

-   Ensure `id` field stores UUID properly
-   Add `flow` field for flow control
-   Add `limit_ip` field for IP connection limits
-   Add `tg_id` field for Telegram integration
-   Add `subId` field for subscription management
-   Add `reset` field for traffic reset tracking
-   Improve traffic calculation methods

### 3. New Models/Services Required

-   **XUIInboundService** - Direct API wrapper for inbound operations
-   **XUIClientService** - Direct API wrapper for client operations
-   **XUITrafficService** - Direct API wrapper for traffic operations
-   **XUIMonitoringService** - Direct API wrapper for monitoring operations

### 4. Enhanced XUIService Methods

-   `login()` - Authentication with session management
-   `listInbounds()` - Get all inbounds with client stats
-   `getInbound($id)` - Get specific inbound details
-   `createInbound($data)` - Create new inbound
-   `updateInbound($id, $data)` - Update existing inbound
-   `deleteInbound($id)` - Delete inbound
-   `addClient($inboundId, $clientData)` - Add client to inbound
-   `updateClient($uuid, $clientData)` - Update client by UUID
-   `deleteClient($inboundId, $uuid)` - Delete client
-   `getClientByEmail($email)` - Get client by email
-   `getClientByUuid($uuid)` - Get client by UUID
-   `resetClientTraffic($inboundId, $email)` - Reset specific client traffic
-   `resetAllTraffics()` - Reset all traffic stats
-   `getOnlineClients()` - Get currently online clients
-   `getClientIps($email)` - Get client IP records
-   `clearClientIps($email)` - Clear client IP records

### 5. Database Schema Updates

-   Add missing fields to server_inbounds table
-   Add missing fields to server_clients table
-   Create indexes for performance optimization
-   Add foreign key constraints where needed

### 6. Enhanced Error Handling

-   Implement robust retry mechanisms
-   Add detailed logging for API interactions
-   Create fallback strategies for failed operations
-   Implement circuit breaker pattern for API calls

### 7. Configuration Management

-   Store 3X-UI panel credentials securely
-   Manage session tokens and automatic refresh
-   Configure API timeouts and retry policies
-   Set up monitoring and alerting thresholds

## Implementation Priority

1. **High Priority**

    - Update ServerInbound and ServerClient models with missing fields
    - Enhance XUIService with complete API wrapper methods
    - Implement proper JSON handling for complex settings
    - Add robust error handling and logging

2. **Medium Priority**

    - Create specialized service classes for different API areas
    - Implement monitoring and health check features
    - Add comprehensive testing for all API interactions
    - Optimize database queries and indexing

3. **Low Priority**
    - Add advanced traffic analytics
    - Implement automated client lifecycle management
    - Create dashboard for real-time monitoring
    - Add integration with external monitoring tools

## Notes

-   All JSON fields in 3X-UI are stored as stringified JSON, not native JSON
-   UUIDs are used for client identification, not sequential IDs
-   Traffic values are in bytes, not MB/GB
-   Timestamps use Unix timestamp format
-   Session management is cookie-based with "session" cookie name
-   API responses are consistent with success/msg/obj structure
