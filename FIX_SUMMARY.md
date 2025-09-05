# Fix for Orders 8114, 8115, 8116 Configuration Display Issue

## Problem Summary
The purchased configuration clients were not being displayed on the product details page for orders 8114, 8115, and 8116. This was happening because the OrderItem model's relationship to ServerClient was not properly configured.

## Root Cause
The `OrderItem::serverClients()` relationship was missing the correct foreign key specification for the `order_server_clients` pivot table. The table uses `order_item_id` and `server_client_id` as foreign keys, but the relationship was using Laravel's default naming convention.

## Fix Applied

### 1. Core Relationship Fix
**File:** `app/Models/OrderItem.php`
```php
// Before (incorrect):
public function serverClients(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
{
    return $this->belongsToMany(ServerClient::class, 'order_server_clients')
                ->withPivot(['provision_status', 'provision_error', 'provision_attempts'])
                ->withTimestamps();
}

// After (correct):
public function serverClients(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
{
    return $this->belongsToMany(ServerClient::class, 'order_server_clients', 'order_item_id', 'server_client_id')
                ->withPivot(['provision_status', 'provision_error', 'provision_attempts'])
                ->withTimestamps();
}
```

### 2. Added Reverse Relationship
**File:** `app/Models/ServerClient.php`
```php
public function orderServerClients(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(OrderServerClient::class, 'server_client_id');
}
```

### 3. Data Migration for Existing Orders
**File:** `database/migrations/2025_09_05_110000_backfill_order_server_clients_pivot_table.php`

This migration creates missing `OrderServerClient` pivot records for existing `ServerClient` records that have an `order_id` but no corresponding pivot table entry.

### 4. Debugging Tools
**File:** `app/Console/Commands/CheckOrderClientStatus.php`

Console command to check the status of specific orders:
```bash
php artisan order:check-clients 8114 8115 8116
```

## Deployment Instructions

### Step 1: Apply the Code Changes
The code changes are already applied in this PR.

### Step 2: Run the Migration
```bash
php artisan migrate
```

This will:
- Backfill missing `OrderServerClient` records for existing orders
- Specifically fix orders 8114, 8115, 8116 if they have ServerClient records but missing pivot entries
- Report the status of the target orders

### Step 3: Verify the Fix
```bash
php artisan order:check-clients 8114 8115 8116
```

This command will show:
- How many clients are associated with each order
- Whether configuration links are available
- The status of the relationship connections

### Step 4: Test in Browser
Navigate to the order details page for orders 8114, 8115, 8116 and verify that:
- Configuration sections are now visible
- Client links, subscription links, and JSON links are displayed
- QR codes and copy buttons are working

## How It Works

### Before the Fix
1. Order details view calls `$item->server_client`
2. `getServerClientAttribute()` calls `$this->serverClients()->first()`
3. `serverClients()` relationship fails to find records due to incorrect foreign keys
4. Returns `null`, so no configuration is displayed

### After the Fix
1. Order details view calls `$item->server_client`
2. `getServerClientAttribute()` calls `$this->serverClients()->first()`
3. `serverClients()` relationship correctly queries with `order_item_id` and `server_client_id`
4. Finds the associated `ServerClient` record via the `order_server_clients` pivot table
5. Returns the client with all configuration links (client_link, remote_sub_link, remote_json_link)
6. Order details view displays the configuration sections

## Testing

Comprehensive tests have been added in:
`tests/Unit/Models/OrderItemServerClientRelationshipTest.php`

Run tests with:
```bash
php artisan test tests/Unit/Models/OrderItemServerClientRelationshipTest.php
```

## Rollback Plan

If issues arise, the migration can be rolled back:
```bash
php artisan migrate:rollback
```

This will remove the backfilled `OrderServerClient` records (identified by a `backfilled: true` flag in their `provision_config`).

The code changes can be reverted by removing the foreign key specifications from the relationship definition.

## Impact

- **Positive**: Orders 8114, 8115, 8116 and any other affected orders will now show configuration clients
- **Zero Risk**: The fix only adds missing relationships and data, doesn't modify existing working functionality
- **Performance**: No negative impact, may slightly improve performance by using proper database relationships
- **Backward Compatibility**: Maintained through dual approaches (direct order_id queries and pivot table relationships)