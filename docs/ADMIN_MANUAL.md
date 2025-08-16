# 🔧 Admin Manual - Internal Procedures

## Table of Contents

-   [System Overview](#system-overview)
-   [Admin Panel Access](#admin-panel-access)
-   [User Management](#user-management)
-   [Server Management](#server-management)
-   [Order Management](#order-management)
-   [Payment Management](#payment-management)
-   [Monitoring & Maintenance](#monitoring--maintenance)
-   [Security Procedures](#security-procedures)
-   [Troubleshooting](#troubleshooting)

---

## System Overview

### Architecture Components

```
Frontend (Livewire) → Laravel Backend → XUI Panels → Proxy Servers
                    ↓
                Database (MySQL) → Redis (Cache/Queue) → Horizon (Queue Management)
```

### Key Technologies

-   **Laravel 10**: Backend framework
-   **Livewire**: Frontend components
-   **Filament**: Admin panel
-   **Horizon**: Queue management
-   **Redis**: Caching and queues
-   **MySQL**: Primary database

### File Structure

```
/app
  /Http/Controllers     # API and web controllers
  /Models              # Database models
  /Jobs                # Background jobs
  /Services            # Business logic
  /Middleware          # Security and validation
/resources/views       # Blade templates
/database/migrations   # Database schema
/tests                # Automated tests
```

---

## Admin Panel Access

### Login Requirements

-   **Admin Role**: User must have `role = 'admin'`
-   **Active Status**: `is_active = true`
-   **Admin Email**: Listed in admin_emails configuration

### Accessing Admin Panel

1. Navigate to `/admin`
2. Login with admin credentials
3. Access granted via `User::canAccessPanel()` method

### Admin Panel Features

-   **User Management**: View, edit, activate/deactivate users
-   **Server Management**: Add, edit, monitor servers
-   **Order Management**: View orders, process manually
-   **Payment Management**: Track payments, handle refunds
-   **System Monitoring**: Logs, performance metrics

### Security Measures

```php
// Admin access control in User model
public function canAccessPanel(): bool
{
    return $this->hasRole('admin') ||
           in_array($this->email, config('app.admin_emails', []));
}
```

---

## User Management

### User Roles

-   **Customer**: Default role, can purchase services
-   **Admin**: Full system access
-   **Support**: Limited access for customer support

### User Operations

**Creating Admin Users:**

```bash
# Via artisan command
php artisan make:admin admin@example.com

# Via database
INSERT INTO users (name, email, password, role, is_active)
VALUES ('Admin User', 'admin@example.com', bcrypt('password'), 'admin', 1);
```

**User Status Management:**

-   **Active**: `is_active = 1` - Can access system
-   **Inactive**: `is_active = 0` - Login disabled
-   **Suspended**: Special handling for policy violations

**Password Reset:**

```bash
# Reset user password
php artisan password:reset user@example.com
```

### User Monitoring

-   **Last Login**: Track via `last_login_at`
-   **Activity Logs**: Monitor user actions
-   **Payment History**: View transaction records

---

## Server Management

### Server Types

-   **XUI Panel**: Primary server type for proxy management
-   **Standalone**: Direct server without panel interface

### Adding New Servers

**Step 1: Server Configuration**

```sql
INSERT INTO servers (name, host, port, username, password, protocol, status)
VALUES ('Server 1', 'server1.example.com', 443, 'admin', 'password', 'vless', 'active');
```

**Step 2: XUI Panel Setup**

1. Install XUI panel on server
2. Configure SSL certificate
3. Set up authentication
4. Test API connectivity

**Step 3: Integration Testing**

```bash
# Test XUI API connection
curl -X POST "https://server1.example.com/login" \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'
```

### Server Monitoring

-   **Health Checks**: Automated ping tests
-   **Load Monitoring**: CPU, memory, bandwidth usage
-   **Client Limits**: Monitor active connections
-   **API Status**: Track XUI panel availability

### Server Maintenance

```bash
# Check server status
php artisan server:status

# Update server configuration
php artisan server:update {server_id}

# Restart server services
php artisan server:restart {server_id}
```

---

## Order Management

### Order Lifecycle

1. **Created**: Order placed by customer
2. **Paid**: Payment confirmed
3. **Processing**: Queue job started
4. **Completed**: Client configuration delivered
5. **Failed**: Error occurred, requires manual intervention

### Manual Order Processing

**Step 1: Identify Failed Orders**

```sql
SELECT * FROM orders WHERE status = 'failed' OR status = 'processing';
```

**Step 2: Process Order Manually**

```bash
# Requeue failed order
php artisan queue:retry {job_id}

# Process specific order
php artisan order:process {order_id}
```

**Step 3: Update Order Status**

```php
// In admin panel or via artisan command
$order = Order::find($orderId);
$order->status = 'completed';
$order->save();
```

### Order Troubleshooting

-   **Payment Issues**: Verify payment status
-   **Server Errors**: Check XUI panel connectivity
-   **Configuration Problems**: Validate client settings

---

## Payment Management

### Payment Methods

-   **Stripe**: Credit card processing
-   **NowPayments**: Cryptocurrency payments
-   **Wallet**: Internal balance system

### Payment Verification

**Stripe Payments:**

```bash
# Check Stripe webhook logs
tail -f storage/logs/payments.log | grep stripe

# Verify payment in Stripe dashboard
# Match payment_intent_id with order
```

**Cryptocurrency Payments:**

```bash
# Check NowPayments status
curl -X GET "https://api.nowpayments.io/v1/payment/{payment_id}" \
  -H "x-api-key: YOUR_API_KEY"
```

### Refund Processing

**Stripe Refunds:**

```php
// Process refund via Stripe API
$refund = \Stripe\Refund::create([
    'payment_intent' => $paymentIntentId,
    'amount' => $refundAmount,
    'reason' => 'requested_by_customer'
]);
```

**Wallet Refunds (Customer Wallet):**

```php
// Add refund to customer wallet
$customer->wallet()->create([
    'amount' => $refundAmount,
    'type' => 'refund',
    'description' => 'Order refund: ' . $orderId
]);
```

### Payment Monitoring

-   **Failed Payments**: Monitor decline rates
-   **Chargeback Alerts**: Handle payment disputes
-   **Fraud Detection**: Unusual payment patterns

---

## Monitoring & Maintenance

### System Monitoring

**Queue Monitoring:**

```bash
# Check queue status
php artisan horizon:status

# Monitor failed jobs
php artisan horizon:failed

# Clear failed jobs
php artisan horizon:clear
```

**Performance Monitoring:**

```bash
# Check application performance
php artisan monitor:performance

# Database query analysis
php artisan db:monitor

# Cache hit rates
php artisan cache:stats
```

### Log Management

**Log Files:**

-   `storage/logs/laravel.log`: General application logs
-   `storage/logs/audit.log`: User activity logs
-   `storage/logs/security.log`: Security events
-   `storage/logs/payments.log`: Payment transactions
-   `storage/logs/xui.log`: XUI panel interactions

**Log Rotation:**

```bash
# Rotate logs daily
0 0 * * * /usr/bin/php /path/to/artisan log:rotate
```

### Maintenance Tasks

**Daily Tasks:**

```bash
# Clear expired sessions
php artisan session:gc

# Optimize caches
php artisan optimize:clear
php artisan optimize

# Backup database
php artisan backup:run
```

**Weekly Tasks:**

```bash
# Update dependencies
composer update

# Run tests
php artisan test

# Check security vulnerabilities
composer audit
```

---

## Security Procedures

### Security Monitoring

**Failed Login Attempts:**

```sql
SELECT * FROM audit_logs
WHERE event_type = 'login_failed'
AND created_at > NOW() - INTERVAL 1 HOUR;
```

**Suspicious Activity:**

-   Multiple failed login attempts
-   Unusual payment patterns
-   API rate limit violations
-   Database access anomalies

### Security Incident Response

**Step 1: Immediate Response**

1. **Identify threat**: Analyze logs and alerts
2. **Isolate affected systems**: Disable compromised accounts
3. **Preserve evidence**: Backup relevant logs
4. **Notify stakeholders**: Alert team members

**Step 2: Investigation**

1. **Analyze attack vector**: How was security breached?
2. **Assess damage**: What data was compromised?
3. **Identify root cause**: Security gaps or vulnerabilities
4. **Document findings**: Create incident report

**Step 3: Recovery**

1. **Patch vulnerabilities**: Apply security updates
2. **Restore systems**: From clean backups if needed
3. **Reset credentials**: Force password changes
4. **Monitor closely**: Watch for continued threats

### Security Best Practices

-   **Regular Updates**: Keep all software current
-   **Strong Passwords**: Enforce password policies
-   **Two-Factor Auth**: Enable for admin accounts
-   **Access Control**: Principle of least privilege
-   **Audit Trails**: Log all significant actions

---

## Troubleshooting

### Common Issues

**Queue Jobs Failing:**

```bash
# Check failed jobs
php artisan queue:failed

# Get job details
php artisan queue:failed:show {job_id}

# Retry failed job
php artisan queue:retry {job_id}
```

**XUI Panel Connectivity:**

```bash
# Test XUI API
curl -X POST "https://panel.example.com/login" \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'

# Check SSL certificate
openssl s_client -connect panel.example.com:443
```

**Database Issues:**

```bash
# Check database connection
php artisan db:show

# Optimize database
php artisan db:optimize

# Check for locks
SHOW PROCESSLIST;
```

### Performance Issues

**Slow Queries:**

```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;

-- Check slow queries
SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 10;
```

**Memory Issues:**

```bash
# Check memory usage
free -h
top -p $(pgrep php)

# Monitor Laravel memory
php artisan monitor:memory
```

### Emergency Procedures

**System Outage:**

1. **Check infrastructure**: Servers, database, network
2. **Review logs**: Identify error patterns
3. **Rollback changes**: If recent deployment caused issues
4. **Notify users**: Status page or email notifications

**Database Corruption:**

1. **Stop application**: Prevent further damage
2. **Restore from backup**: Latest clean backup
3. **Verify integrity**: Check data consistency
4. **Resume operations**: Gradually restore services

---

## Backup & Recovery

### Backup Strategy

**Daily Backups:**

```bash
# Database backup
php artisan backup:run --only-db

# Full application backup
php artisan backup:run
```

**Backup Verification:**

```bash
# Test backup integrity
php artisan backup:restore --test

# Verify backup files
ls -la storage/app/backups/
```

### Recovery Procedures

**Database Recovery:**

```bash
# Stop application
php artisan down

# Restore database
mysql -u root -p 1000proxy < backup.sql

# Restart application
php artisan up
```

**File Recovery:**

```bash
# Restore application files
tar -xzf app-backup.tar.gz -C /

# Fix permissions
chown -R www-data:www-data /var/www/html
```

---

## Contact Information

### Emergency Contacts

-   **System Administrator**: admin@1000proxy.io
-   **Technical Lead**: tech@1000proxy.io
-   **Security Team**: security@1000proxy.io

### External Services

-   **Hosting Provider**: Contact details and support
-   **Payment Processors**: Stripe, NowPayments support
-   **SSL Certificate**: Authority contact information

### Documentation

-   **API Documentation**: `/docs/API.md`
-   **Architecture Guide**: `/docs/ARCHITECTURE.md`
-   **Deployment Guide**: `/docs/DEPLOYMENT.md`

---

_Last updated: July 8, 2025_

This manual should be kept current with system changes and shared only with authorized personnel.
