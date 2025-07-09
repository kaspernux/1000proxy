# 3X-UI Panel URL Structure Enhancement

## Overview

Updated the server connection structure to properly align with the 3X-UI API format: `http://{{HOST}}:{{PORT}}{{WEBBASEPATH}}/`

## Changes Made

### 1. Database Schema Updates

#### Modified `servers` table migration (`2024_04_21_121802_create_servers_table.php`):

-   Added `host` field: Separate HOST field for 3X-UI API
-   Added `panel_port` field: Separate PORT field for 3X-UI panel (default: 2053)
-   Added `web_base_path` field: WEBBASEPATH for 3X-UI panel (nullable)
-   Made `panel_url` nullable for backward compatibility

#### Created new migration (`2025_01_09_140000_add_structured_panel_fields_to_servers.php`):

-   Adds the new fields to existing tables
-   Maintains backward compatibility with existing `panel_url` field

### 2. Server Model Updates (`app/Models/Server.php`)

#### Added new fillable fields:

-   `host` - The server hostname or IP address
-   `panel_port` - The port number for the 3X-UI panel
-   `web_base_path` - Optional web base path for the panel

#### Enhanced `getApiBaseUrl()` method:

-   **New Structure**: Uses `host` + `panel_port` + `web_base_path`
-   **Backward Compatibility**: Falls back to `panel_url` if new fields are not set
-   **Protocol Handling**: Automatically handles HTTP/HTTPS prefixes
-   **Path Handling**: Properly constructs URLs with optional base paths

#### Added new helper methods:

-   `getFullPanelUrl()` - Get full panel URL for display
-   `getPanelAccessUrl()` - Get panel access URL without API paths

### 3. Filament Admin Panel Updates (`ServerResource.php`)

#### Form Fields:

-   **Host Field**: Input for server hostname or IP with helpful text
-   **Panel Port Field**: Numeric input with default 2053
-   **Web Base Path Field**: Optional field for custom panel paths
-   **Helper Text**: Added descriptive help text for each field

#### Table Display:

-   **Host Column**: Shows the server host
-   **Panel Port Column**: Shows the panel port
-   **Full Panel URL Column**: Displays constructed URL with click-to-open functionality

### 4. API URL Construction Examples

#### New Structure:

```php
// Server with all fields
$server = new Server([
    'host' => 'example.com',
    'panel_port' => 2053,
    'web_base_path' => '/admin'
]);

// Results in: http://example.com:2053/admin
$baseUrl = $server->getApiBaseUrl();

// API endpoints:
// http://example.com:2053/admin/login
// http://example.com:2053/admin/panel/api/inbounds/list
```

#### Backward Compatibility:

```php
// Legacy server with panel_url
$server = new Server([
    'panel_url' => 'http://legacy.example.com:8080/panel'
]);

// Still works: http://legacy.example.com:8080/panel
$baseUrl = $server->getApiBaseUrl();
```

### 5. 3X-UI API Endpoint Mapping

The new structure properly maps to 3X-UI Postman collection variables:

-   `{{HOST}}` → `host` field
-   `{{PORT}}` → `panel_port` field
-   `{{WEBBASEPATH}}` → `web_base_path` field

#### Example Mappings:

```
Login: http://{{HOST}}:{{PORT}}{{WEBBASEPATH}}/login
→ http://example.com:2053/admin/login

Inbounds: http://{{HOST}}:{{PORT}}{{WEBBASEPATH}}/panel/api/inbounds/list
→ http://example.com:2053/admin/panel/api/inbounds/list
```

### 6. Benefits

1. **Proper Structure**: Aligns with official 3X-UI API documentation
2. **Flexibility**: Supports custom ports and web base paths
3. **Backward Compatibility**: Existing servers continue to work
4. **Better UX**: Clearer form fields in admin panel
5. **Maintainability**: Easier to manage server connections
6. **Protocol Support**: Handles both HTTP and HTTPS

### 7. Migration Path

For existing servers:

1. The `panel_url` field remains functional
2. New servers should use the structured fields
3. Existing servers can be updated manually through the admin panel
4. The system automatically uses the new structure when available

### 8. Testing

Created comprehensive tests (`XUIServiceUrlConstructionTest.php`) to verify:

-   URL construction with new fields
-   HTTPS protocol handling
-   Web base path handling
-   Backward compatibility with panel_url
-   API endpoint construction

## Usage in Filament Admin

When adding a new server in the admin panel:

1. **Host**: Enter the server hostname or IP (e.g., `192.168.1.100` or `example.com`)
2. **Panel Port**: Enter the port number (default: 2053)
3. **Web Base Path**: Optional, leave empty for root or enter path like `/admin`

The system will automatically construct the proper URLs for 3X-UI API communication.
