# Maintenance Guide

This guide provides comprehensive instructions for maintaining the 1000proxy platform, including routine maintenance tasks, monitoring, troubleshooting, and system optimization.

## Table of Contents

1. [Routine Maintenance](#routine-maintenance)
2. [System Monitoring](#system-monitoring)
3. [Performance Monitoring](#performance-monitoring)
4. [Database Maintenance](#database-maintenance)
5. [Security Maintenance](#security-maintenance)
6. [Backup and Recovery](#backup-and-recovery)
7. [Log Management](#log-management)
8. [Update Procedures](#update-procedures)
9. [Troubleshooting Guide](#troubleshooting-guide)
10. [Emergency Procedures](#emergency-procedures)

## Routine Maintenance

### Daily Tasks

#### System Health Checks

```bash
#!/bin/bash
# daily-health-check.sh

echo "=== Daily Health Check - $(date) ==="

# Check system resources
echo "1. System Resources:"
echo "   CPU Usage: $(top -bn1 | grep load | awk '{printf "%.2f%%", $(NF-2)}')"
echo "   Memory Usage: $(free | grep Mem | awk '{printf("%.2f%%"), $3/$2 * 100.0}')"
echo "   Disk Usage: $(df -h / | awk 'NR==2{printf "%s", $5}')"

# Check services
echo "2. Service Status:"
systemctl is-active nginx && echo "   ✓ Nginx: Running" || echo "   ✗ Nginx: Stopped"
systemctl is-active php8.3-fpm && echo "   ✓ PHP-FPM: Running" || echo "   ✗ PHP-FPM: Stopped"
systemctl is-active mysql && echo "   ✓ MySQL: Running" || echo "   ✗ MySQL: Stopped"
systemctl is-active redis-server && echo "   ✓ Redis: Running" || echo "   ✗ Redis: Stopped"

# Check Laravel application
echo "3. Application Status:"
cd /var/www/html
php artisan health:check --quiet && echo "   ✓ Application: Healthy" || echo "   ✗ Application: Issues detected"

# Check database connections
mysql -u proxy_user -p${DB_PASSWORD} -e "SELECT 1" > /dev/null 2>&1 && echo "   ✓ Database: Connected" || echo "   ✗ Database: Connection failed"

# Check Redis connection
redis-cli ping > /dev/null 2>&1 && echo "   ✓ Redis: Connected" || echo "   ✗ Redis: Connection failed"

# Check SSL certificate expiry
CERT_EXPIRY=$(echo | openssl s_client -servername yourdomain.com -connect yourdomain.com:443 2>/dev/null | openssl x509 -noout -enddate | cut -d= -f2)
CERT_EXPIRY_EPOCH=$(date -d "$CERT_EXPIRY" +%s)
CURRENT_EPOCH=$(date +%s)
DAYS_UNTIL_EXPIRY=$(( ($CERT_EXPIRY_EPOCH - $CURRENT_EPOCH) / 86400 ))

echo "4. SSL Certificate:"
if [ $DAYS_UNTIL_EXPIRY -gt 30 ]; then
    echo "   ✓ SSL Certificate: Valid for $DAYS_UNTIL_EXPIRY days"
elif [ $DAYS_UNTIL_EXPIRY -gt 7 ]; then
    echo "   ⚠ SSL Certificate: Expires in $DAYS_UNTIL_EXPIRY days (renew soon)"
else
    echo "   ✗ SSL Certificate: Expires in $DAYS_UNTIL_EXPIRY days (URGENT)"
fi

# Check queue workers
QUEUE_WORKERS=$(ps aux | grep "queue:work" | grep -v grep | wc -l)
echo "5. Queue Workers: $QUEUE_WORKERS active"

echo "=== Health Check Complete ==="
```

#### Log Review

```bash
#!/bin/bash
# daily-log-review.sh

echo "=== Daily Log Review - $(date) ==="

# Check Laravel logs for errors
echo "1. Laravel Application Errors (last 24 hours):"
find /var/www/html/storage/logs -name "*.log" -mtime -1 -exec grep -l "ERROR\|CRITICAL\|EMERGENCY" {} \; | while read logfile; do
    echo "   Found errors in: $logfile"
    grep "ERROR\|CRITICAL\|EMERGENCY" "$logfile" | tail -5
done

# Check Nginx error logs
echo "2. Nginx Errors (last 24 hours):"
grep "$(date --date='1 day ago' '+%Y/%m/%d')" /var/log/nginx/error.log | wc -l | xargs echo "   Error count:"

# Check PHP-FPM errors
echo "3. PHP-FPM Errors (last 24 hours):"
journalctl -u php8.3-fpm --since "1 day ago" --no-pager | grep -i error | wc -l | xargs echo "   Error count:"

# Check MySQL slow queries
echo "4. MySQL Slow Queries (last 24 hours):"
if [ -f /var/log/mysql/slow.log ]; then
    grep "$(date --date='1 day ago' '+%y%m%d')" /var/log/mysql/slow.log | wc -l | xargs echo "   Slow query count:"
fi

echo "=== Log Review Complete ==="
```

### Weekly Tasks

#### Performance Analysis

```bash
#!/bin/bash
# weekly-performance-analysis.sh

echo "=== Weekly Performance Analysis - $(date) ==="

# Database performance
echo "1. Database Performance:"
mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "
    SELECT 
        'Query Cache Hit Rate' as Metric,
        ROUND(Qcache_hits / (Qcache_hits + Qcache_inserts) * 100, 2) as Value
    FROM information_schema.GLOBAL_STATUS
    WHERE VARIABLE_NAME IN ('Qcache_hits', 'Qcache_inserts');
    
    SELECT 
        'Buffer Pool Hit Rate' as Metric,
        ROUND((1 - (Innodb_buffer_pool_reads / Innodb_buffer_pool_read_requests)) * 100, 2) as Value
    FROM information_schema.GLOBAL_STATUS
    WHERE VARIABLE_NAME IN ('Innodb_buffer_pool_reads', 'Innodb_buffer_pool_read_requests');
"

# Redis performance
echo "2. Redis Performance:"
redis-cli info stats | grep -E "(total_commands_processed|total_connections_received|keyspace_hits|keyspace_misses)"

# Application metrics
echo "3. Application Metrics:"
cd /var/www/html
php artisan metrics:weekly-report

echo "=== Performance Analysis Complete ==="
```

#### Security Audit

```bash
#!/bin/bash
# weekly-security-audit.sh

echo "=== Weekly Security Audit - $(date) ==="

# Check for failed login attempts
echo "1. Failed Login Attempts (last 7 days):"
grep "Failed password" /var/log/auth.log | grep "$(date --date='7 days ago' '+%b %d')" | wc -l | xargs echo "   Failed attempts:"

# Check file permissions
echo "2. Critical File Permissions:"
find /var/www/html -name ".env" -exec ls -la {} \;
find /var/www/html -name "storage" -type d -exec ls -ld {} \;

# Check for suspicious processes
echo "3. Process Analysis:"
ps aux | grep -E "(nc|netcat|telnet)" | grep -v grep && echo "   ⚠ Suspicious network tools found" || echo "   ✓ No suspicious network tools"

# Check open ports
echo "4. Open Ports:"
netstat -tlnp | grep LISTEN

echo "=== Security Audit Complete ==="
```

### Monthly Tasks

#### Capacity Planning

```sql
-- monthly-capacity-analysis.sql
-- Run this in MySQL to analyze growth trends

-- User growth
SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as new_users,
    SUM(COUNT(*)) OVER (ORDER BY DATE_FORMAT(created_at, '%Y-%m')) as total_users
FROM users 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY month;

-- Service usage growth
SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    type,
    COUNT(*) as service_count,
    AVG(DATEDIFF(expires_at, created_at)) as avg_duration_days
FROM proxy_services 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(created_at, '%Y-%m'), type
ORDER BY month, type;

-- Revenue trends
SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as order_count,
    SUM(total_amount) as total_revenue,
    AVG(total_amount) as avg_order_value
FROM orders 
WHERE status = 'completed' 
AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY month;

-- Storage usage by table
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Size (MB)",
    table_rows as "Row Count"
FROM information_schema.tables 
WHERE table_schema = 'proxy_production'
ORDER BY (data_length + index_length) DESC;
```

#### System Updates

```bash
#!/bin/bash
# monthly-system-updates.sh

echo "=== Monthly System Updates - $(date) ==="

# Backup before updates
echo "1. Creating backup before updates..."
/usr/local/bin/full-backup.sh

# Update system packages
echo "2. Updating system packages..."
apt update && apt list --upgradable

# Update PHP packages (review before applying)
echo "3. Checking PHP updates..."
composer show --outdated --direct

# Update Node.js packages
echo "4. Checking Node.js updates..."
npm outdated

# Laravel framework updates
echo "5. Checking Laravel updates..."
cd /var/www/html
composer show laravel/framework

echo "=== Update Check Complete - Review before applying ==="
```

## System Monitoring

### Automated Monitoring Setup

#### System Resource Monitoring

```bash
#!/bin/bash
# system-monitor.sh

ALERT_EMAIL="admin@1000proxy.com"
HOSTNAME=$(hostname)

# CPU usage threshold (80%)
CPU_USAGE=$(top -bn1 | grep load | awk '{print $(NF-2)}' | sed 's/,//')
if (( $(echo "$CPU_USAGE > 0.8" | bc -l) )); then
    echo "High CPU usage detected: $CPU_USAGE" | mail -s "[$HOSTNAME] High CPU Alert" $ALERT_EMAIL
fi

# Memory usage threshold (90%)
MEMORY_USAGE=$(free | grep Mem | awk '{printf("%.2f"), $3/$2 * 100.0}')
if (( $(echo "$MEMORY_USAGE > 90" | bc -l) )); then
    echo "High memory usage detected: ${MEMORY_USAGE}%" | mail -s "[$HOSTNAME] High Memory Alert" $ALERT_EMAIL
fi

# Disk usage threshold (85%)
DISK_USAGE=$(df -h / | awk 'NR==2{print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 85 ]; then
    echo "High disk usage detected: ${DISK_USAGE}%" | mail -s "[$HOSTNAME] High Disk Usage Alert" $ALERT_EMAIL
fi

# Service availability
SERVICES=("nginx" "php8.3-fpm" "mysql" "redis-server")
for service in "${SERVICES[@]}"; do
    if ! systemctl is-active --quiet $service; then
        echo "Service $service is not running" | mail -s "[$HOSTNAME] Service Down: $service" $ALERT_EMAIL
    fi
done
```

#### Application Monitoring

```php
<?php
// app/Console/Commands/MonitorApplication.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Mail;
use App\Models\ProxyService;
use App\Models\User;

class MonitorApplication extends Command
{
    protected $signature = 'monitor:application';
    protected $description = 'Monitor application health and performance';

    public function handle(): int
    {
        $this->info('Starting application monitoring...');

        $alerts = [];

        // Check database connection
        try {
            DB::connection()->getPdo();
            $this->info('✓ Database connection OK');
        } catch (\Exception $e) {
            $alerts[] = 'Database connection failed: ' . $e->getMessage();
            $this->error('✗ Database connection failed');
        }

        // Check Redis connection
        try {
            Redis::ping();
            $this->info('✓ Redis connection OK');
        } catch (\Exception $e) {
            $alerts[] = 'Redis connection failed: ' . $e->getMessage();
            $this->error('✗ Redis connection failed');
        }

        // Check queue workers
        $queueSize = Redis::llen('queues:default');
        if ($queueSize > 100) {
            $alerts[] = "Queue backlog detected: {$queueSize} jobs pending";
            $this->warn("⚠ Queue backlog: {$queueSize} jobs");
        } else {
            $this->info("✓ Queue size OK: {$queueSize} jobs");
        }

        // Check expired services that should be suspended
        $expiredActive = ProxyService::where('status', 'active')
            ->where('expires_at', '<', now())
            ->count();

        if ($expiredActive > 0) {
            $alerts[] = "{$expiredActive} expired services still active";
            $this->warn("⚠ {$expiredActive} expired services need suspension");
        }

        // Check recent error rate
        $recentErrors = DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subMinutes(30))
            ->count();

        if ($recentErrors > 10) {
            $alerts[] = "{$recentErrors} job failures in last 30 minutes";
            $this->error("✗ High error rate: {$recentErrors} failures");
        }

        // Send alerts if any
        if (!empty($alerts)) {
            $this->sendAlerts($alerts);
        }

        $this->info('Application monitoring complete');
        return 0;
    }

    private function sendAlerts(array $alerts): void
    {
        $subject = '[' . config('app.name') . '] Application Alerts';
        $body = "Application monitoring detected the following issues:\n\n" 
              . implode("\n", array_map(fn($alert) => "- {$alert}", $alerts));

        Mail::raw($body, function ($message) use ($subject) {
            $message->to(config('monitoring.alert_email'))
                   ->subject($subject);
        });
    }
}
```

### Monitoring Dashboard

#### Grafana Configuration

```yaml
# docker-compose.monitoring.yml
version: '3.8'

services:
  prometheus:
    image: prom/prometheus:latest
    container_name: prometheus
    ports:
      - "9090:9090"
    volumes:
      - ./monitoring/prometheus.yml:/etc/prometheus/prometheus.yml
      - prometheus_data:/prometheus
    command:
      - '--config.file=/etc/prometheus/prometheus.yml'
      - '--storage.tsdb.path=/prometheus'
      - '--web.console.libraries=/etc/prometheus/console_libraries'
      - '--web.console.templates=/etc/prometheus/consoles'

  grafana:
    image: grafana/grafana:latest
    container_name: grafana
    ports:
      - "3000:3000"
    volumes:
      - grafana_data:/var/lib/grafana
      - ./monitoring/grafana/dashboards:/etc/grafana/provisioning/dashboards
      - ./monitoring/grafana/datasources:/etc/grafana/provisioning/datasources
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=admin

  node_exporter:
    image: prom/node-exporter:latest
    container_name: node_exporter
    ports:
      - "9100:9100"
    volumes:
      - /proc:/host/proc:ro
      - /sys:/host/sys:ro
      - /:/rootfs:ro
    command:
      - '--path.procfs=/host/proc'
      - '--path.sysfs=/host/sys'
      - '--collector.filesystem.ignored-mount-points=^/(sys|proc|dev|host|etc)($$|/)'

volumes:
  prometheus_data:
  grafana_data:
```

## Performance Monitoring

### Database Performance

#### Query Analysis

```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;
SET GLOBAL slow_query_log_file = '/var/log/mysql/slow.log';

-- Analyze slow queries
SELECT 
    query_time,
    lock_time,
    rows_sent,
    rows_examined,
    sql_text
FROM mysql.slow_log 
ORDER BY query_time DESC 
LIMIT 10;

-- Index usage analysis
SELECT 
    table_schema,
    table_name,
    index_name,
    cardinality,
    non_unique
FROM information_schema.statistics 
WHERE table_schema = 'proxy_production'
ORDER BY table_name, seq_in_index;

-- Connection analysis
SHOW PROCESSLIST;

-- Buffer pool status
SHOW ENGINE INNODB STATUS\G
```

#### Performance Optimization Queries

```sql
-- Identify tables without primary keys
SELECT 
    table_schema,
    table_name
FROM information_schema.tables 
WHERE table_schema = 'proxy_production'
AND table_name NOT IN (
    SELECT table_name 
    FROM information_schema.key_column_usage 
    WHERE constraint_name = 'PRIMARY'
    AND table_schema = 'proxy_production'
);

-- Find unused indexes
SELECT 
    s.table_schema,
    s.table_name,
    s.index_name,
    s.cardinality
FROM information_schema.statistics s
LEFT JOIN information_schema.index_statistics i 
    ON s.table_schema = i.table_schema 
    AND s.table_name = i.table_name 
    AND s.index_name = i.index_name
WHERE s.table_schema = 'proxy_production'
AND i.index_name IS NULL
AND s.index_name != 'PRIMARY';

-- Table size analysis
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Size (MB)",
    table_rows,
    ROUND((data_length / 1024 / 1024), 2) AS "Data (MB)",
    ROUND((index_length / 1024 / 1024), 2) AS "Index (MB)"
FROM information_schema.tables 
WHERE table_schema = 'proxy_production'
ORDER BY (data_length + index_length) DESC;
```

### Application Performance

#### Laravel Performance Monitoring

```php
<?php
// app/Console/Commands/PerformanceReport.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\ProxyService;
use App\Models\User;
use App\Models\Order;

class PerformanceReport extends Command
{
    protected $signature = 'performance:report {--period=daily}';
    protected $description = 'Generate performance report';

    public function handle(): int
    {
        $period = $this->option('period');
        
        $this->info("Generating {$period} performance report...");

        // Database performance
        $this->checkDatabasePerformance();
        
        // Cache performance
        $this->checkCachePerformance();
        
        // Queue performance
        $this->checkQueuePerformance();
        
        // Business metrics
        $this->checkBusinessMetrics($period);

        return 0;
    }

    private function checkDatabasePerformance(): void
    {
        $this->info("\n=== Database Performance ===");

        // Query count
        $queries = DB::getQueryLog();
        $this->line("Queries executed: " . count($queries));

        // Slow queries (if logging enabled)
        $slowQueries = DB::select("
            SELECT COUNT(*) as count 
            FROM mysql.slow_log 
            WHERE start_time >= DATE_SUB(NOW(), INTERVAL 1 DAY)
        ");
        
        if (!empty($slowQueries)) {
            $this->line("Slow queries (24h): " . $slowQueries[0]->count);
        }

        // Connection pool
        $connections = DB::select("SHOW STATUS LIKE 'Threads_connected'");
        $this->line("Active connections: " . $connections[0]->Value);
    }

    private function checkCachePerformance(): void
    {
        $this->info("\n=== Cache Performance ===");

        try {
            $info = Redis::info('stats');
            $hits = (int)$info['keyspace_hits'];
            $misses = (int)$info['keyspace_misses'];
            $total = $hits + $misses;
            
            if ($total > 0) {
                $hitRate = round(($hits / $total) * 100, 2);
                $this->line("Cache hit rate: {$hitRate}%");
            }
            
            $this->line("Total cache commands: " . $info['total_commands_processed']);
        } catch (\Exception $e) {
            $this->error("Cache info unavailable: " . $e->getMessage());
        }
    }

    private function checkQueuePerformance(): void
    {
        $this->info("\n=== Queue Performance ===");

        $queueSize = Redis::llen('queues:default');
        $this->line("Default queue size: {$queueSize}");

        $failedJobs = DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subDay())
            ->count();
        $this->line("Failed jobs (24h): {$failedJobs}");
    }

    private function checkBusinessMetrics(string $period): void
    {
        $this->info("\n=== Business Metrics ===");

        $startDate = match($period) {
            'daily' => now()->subDay(),
            'weekly' => now()->subWeek(),
            'monthly' => now()->subMonth(),
            default => now()->subDay(),
        };

        // New users
        $newUsers = User::where('created_at', '>=', $startDate)->count();
        $this->line("New users: {$newUsers}");

        // New services
        $newServices = ProxyService::where('created_at', '>=', $startDate)->count();
        $this->line("New services: {$newServices}");

        // Revenue
        $revenue = Order::where('payment_status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->sum('total_amount');
        $this->line("Revenue: $" . number_format($revenue, 2));

        // Active services
        $activeServices = ProxyService::where('status', 'active')
            ->where('expires_at', '>', now())
            ->count();
        $this->line("Active services: {$activeServices}");
    }
}
```

## Database Maintenance

### Regular Maintenance Tasks

#### Database Optimization

```sql
-- Daily optimization script
-- Run during low-traffic hours

-- Optimize tables
OPTIMIZE TABLE users;
OPTIMIZE TABLE proxy_services;
OPTIMIZE TABLE orders;
OPTIMIZE TABLE payment_transactions;
OPTIMIZE TABLE usage_logs;

-- Update table statistics
ANALYZE TABLE users;
ANALYZE TABLE proxy_services;
ANALYZE TABLE orders;
ANALYZE TABLE payment_transactions;

-- Check and repair tables if needed
CHECK TABLE users;
CHECK TABLE proxy_services;
CHECK TABLE orders;

-- Clean up old data
-- Remove old usage logs (older than 90 days)
DELETE FROM usage_logs 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
LIMIT 1000;

-- Remove old failed jobs (older than 30 days)
DELETE FROM failed_jobs 
WHERE failed_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
LIMIT 1000;

-- Remove expired password reset tokens
DELETE FROM password_reset_tokens 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY);
```

#### Index Maintenance

```sql
-- Analyze index usage and recommendations

-- Find duplicate indexes
SELECT 
    t1.table_name,
    t1.index_name as index1,
    t2.index_name as index2,
    t1.column_name
FROM information_schema.statistics t1
JOIN information_schema.statistics t2 
    ON t1.table_name = t2.table_name 
    AND t1.column_name = t2.column_name
    AND t1.index_name != t2.index_name
WHERE t1.table_schema = 'proxy_production'
ORDER BY t1.table_name, t1.column_name;

-- Find indexes with low cardinality
SELECT 
    table_name,
    index_name,
    column_name,
    cardinality,
    (cardinality / (SELECT table_rows FROM information_schema.tables WHERE table_name = s.table_name AND table_schema = s.table_schema)) * 100 as selectivity
FROM information_schema.statistics s
WHERE table_schema = 'proxy_production'
AND cardinality IS NOT NULL
HAVING selectivity < 10
ORDER BY selectivity;
```

### Database Backup Strategy

#### Automated Backup Script

```bash
#!/bin/bash
# database-backup.sh

DB_NAME="proxy_production"
DB_USER="backup_user"
DB_PASS="${DB_BACKUP_PASSWORD}"
BACKUP_DIR="/var/backups/mysql"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Create backup directory
mkdir -p ${BACKUP_DIR}

# Full backup
mysqldump \
    --user=${DB_USER} \
    --password=${DB_PASS} \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --lock-tables=false \
    --add-drop-table \
    --add-drop-database \
    --databases ${DB_NAME} | gzip > ${BACKUP_DIR}/full_backup_${DATE}.sql.gz

# Check backup integrity
if [ $? -eq 0 ]; then
    echo "Backup completed successfully: full_backup_${DATE}.sql.gz"
    
    # Test backup by checking if it can be read
    zcat ${BACKUP_DIR}/full_backup_${DATE}.sql.gz | head -20 > /dev/null
    if [ $? -eq 0 ]; then
        echo "Backup integrity check passed"
    else
        echo "Backup integrity check failed"
        exit 1
    fi
else
    echo "Backup failed"
    exit 1
fi

# Incremental backup (binary logs)
mysql --user=${DB_USER} --password=${DB_PASS} -e "FLUSH LOGS;"
rsync -av /var/lib/mysql/mysql-bin.* ${BACKUP_DIR}/binlogs/

# Clean up old backups
find ${BACKUP_DIR} -name "full_backup_*.sql.gz" -mtime +${RETENTION_DAYS} -delete
find ${BACKUP_DIR}/binlogs -name "mysql-bin.*" -mtime +7 -delete

# Upload to cloud storage (optional)
if command -v aws &> /dev/null; then
    aws s3 cp ${BACKUP_DIR}/full_backup_${DATE}.sql.gz s3://your-backup-bucket/mysql/
fi

echo "Database backup process completed"
```

## Security Maintenance

### Security Monitoring

#### Failed Login Monitoring

```bash
#!/bin/bash
# security-monitor.sh

ALERT_EMAIL="security@1000proxy.com"
LOG_FILE="/var/www/html/storage/logs/laravel.log"

# Monitor failed login attempts
FAILED_LOGINS=$(grep "authentication.failed" ${LOG_FILE} | grep "$(date '+%Y-%m-%d')" | wc -l)

if [ $FAILED_LOGINS -gt 50 ]; then
    echo "High number of failed login attempts detected: ${FAILED_LOGINS}" | \
    mail -s "Security Alert: Failed Login Attempts" ${ALERT_EMAIL}
fi

# Monitor suspicious IP addresses
grep "authentication.failed" ${LOG_FILE} | grep "$(date '+%Y-%m-%d')" | \
awk '{print $4}' | sort | uniq -c | sort -nr | head -10 > /tmp/suspicious_ips.txt

if [ -s /tmp/suspicious_ips.txt ]; then
    while read count ip; do
        if [ $count -gt 10 ]; then
            echo "Suspicious IP detected: $ip with $count failed attempts"
            # Optionally block IP with fail2ban or iptables
            # fail2ban-client set ssh banip $ip
        fi
    done < /tmp/suspicious_ips.txt
fi

# Check for SQL injection attempts
SQL_INJECTION_PATTERNS=("union select" "or 1=1" "drop table" "'; --" "script>" "alert(")

for pattern in "${SQL_INJECTION_PATTERNS[@]}"; do
    MATCHES=$(grep -i "$pattern" /var/log/nginx/access.log | grep "$(date '+%d/%b/%Y')" | wc -l)
    if [ $MATCHES -gt 0 ]; then
        echo "Potential SQL injection attempts detected: $MATCHES matches for pattern '$pattern'" | \
        mail -s "Security Alert: SQL Injection Attempts" ${ALERT_EMAIL}
    fi
done
```

#### File Integrity Monitoring

```bash
#!/bin/bash
# file-integrity-check.sh

BASELINE_DIR="/var/backups/integrity"
WEB_ROOT="/var/www/html"
ALERT_EMAIL="security@1000proxy.com"

# Create baseline if it doesn't exist
if [ ! -f ${BASELINE_DIR}/file_hashes.txt ]; then
    mkdir -p ${BASELINE_DIR}
    find ${WEB_ROOT} -type f -name "*.php" -exec sha256sum {} \; > ${BASELINE_DIR}/file_hashes.txt
    echo "Baseline created"
    exit 0
fi

# Generate current hashes
find ${WEB_ROOT} -type f -name "*.php" -exec sha256sum {} \; > /tmp/current_hashes.txt

# Compare with baseline
if ! diff -q ${BASELINE_DIR}/file_hashes.txt /tmp/current_hashes.txt > /dev/null; then
    echo "File integrity changes detected:" > /tmp/integrity_report.txt
    diff ${BASELINE_DIR}/file_hashes.txt /tmp/current_hashes.txt >> /tmp/integrity_report.txt
    
    mail -s "Security Alert: File Integrity Changes" ${ALERT_EMAIL} < /tmp/integrity_report.txt
    
    # Update baseline after manual verification
    # cp /tmp/current_hashes.txt ${BASELINE_DIR}/file_hashes.txt
fi
```

### SSL Certificate Management

```bash
#!/bin/bash
# ssl-certificate-monitor.sh

DOMAINS=("1000proxy.com" "www.1000proxy.com" "api.1000proxy.com")
ALERT_EMAIL="admin@1000proxy.com"
WARNING_DAYS=30

for domain in "${DOMAINS[@]}"; do
    echo "Checking SSL certificate for $domain"
    
    # Get certificate expiry date
    CERT_EXPIRY=$(echo | openssl s_client -servername $domain -connect $domain:443 2>/dev/null | \
                  openssl x509 -noout -enddate | cut -d= -f2)
    
    if [ -z "$CERT_EXPIRY" ]; then
        echo "Could not retrieve certificate for $domain" | \
        mail -s "SSL Alert: Certificate Check Failed for $domain" $ALERT_EMAIL
        continue
    fi
    
    # Calculate days until expiry
    CERT_EXPIRY_EPOCH=$(date -d "$CERT_EXPIRY" +%s)
    CURRENT_EPOCH=$(date +%s)
    DAYS_UNTIL_EXPIRY=$(( ($CERT_EXPIRY_EPOCH - $CURRENT_EPOCH) / 86400 ))
    
    echo "Certificate for $domain expires in $DAYS_UNTIL_EXPIRY days"
    
    if [ $DAYS_UNTIL_EXPIRY -le 7 ]; then
        echo "URGENT: SSL certificate for $domain expires in $DAYS_UNTIL_EXPIRY days" | \
        mail -s "URGENT SSL Alert: Certificate Expiring for $domain" $ALERT_EMAIL
        
        # Auto-renew with Certbot if available
        if command -v certbot &> /dev/null; then
            certbot renew --quiet --no-self-upgrade
        fi
    elif [ $DAYS_UNTIL_EXPIRY -le $WARNING_DAYS ]; then
        echo "Warning: SSL certificate for $domain expires in $DAYS_UNTIL_EXPIRY days" | \
        mail -s "SSL Warning: Certificate Expiring for $domain" $ALERT_EMAIL
    fi
done
```

## Backup and Recovery

### Comprehensive Backup Strategy

#### Full System Backup

```bash
#!/bin/bash
# full-system-backup.sh

BACKUP_ROOT="/var/backups"
DATE=$(date +%Y%m%d_%H%M%S)
APP_ROOT="/var/www/html"
RETENTION_DAYS=7

echo "Starting full system backup - $DATE"

# Create backup directories
mkdir -p ${BACKUP_ROOT}/system/${DATE}

# 1. Application files backup
echo "Backing up application files..."
tar -czf ${BACKUP_ROOT}/system/${DATE}/application.tar.gz \
    --exclude='storage/logs/*' \
    --exclude='storage/cache/*' \
    --exclude='node_modules' \
    --exclude='vendor' \
    -C ${APP_ROOT} .

# 2. Database backup
echo "Backing up database..."
/usr/local/bin/database-backup.sh

# 3. Configuration files backup
echo "Backing up configuration files..."
tar -czf ${BACKUP_ROOT}/system/${DATE}/configs.tar.gz \
    /etc/nginx \
    /etc/php/8.3 \
    /etc/mysql \
    /etc/redis \
    /etc/ssl/certs \
    /etc/systemd/system/laravel-queue.service

# 4. System state backup
echo "Backing up system state..."
cat > ${BACKUP_ROOT}/system/${DATE}/system_info.txt << EOF
Backup Date: $(date)
Hostname: $(hostname)
Kernel: $(uname -a)
PHP Version: $(php -v | head -1)
MySQL Version: $(mysql --version)
Nginx Version: $(nginx -v 2>&1)
Disk Usage: $(df -h)
Memory: $(free -h)
Running Services: $(systemctl list-units --type=service --state=running)
EOF

# 5. Create backup manifest
echo "Creating backup manifest..."
cat > ${BACKUP_ROOT}/system/${DATE}/manifest.txt << EOF
Backup Contents:
- application.tar.gz: Application files and code
- configs.tar.gz: System configuration files
- system_info.txt: System state information
- ../mysql/full_backup_${DATE}.sql.gz: Database backup

Backup Size:
$(du -sh ${BACKUP_ROOT}/system/${DATE})

Checksums:
$(cd ${BACKUP_ROOT}/system/${DATE} && sha256sum *)
EOF

# 6. Upload to cloud storage (if configured)
if [ -n "$AWS_S3_BUCKET" ]; then
    echo "Uploading to S3..."
    aws s3 sync ${BACKUP_ROOT}/system/${DATE}/ s3://${AWS_S3_BUCKET}/backups/system/${DATE}/
fi

# 7. Clean up old backups
echo "Cleaning up old backups..."
find ${BACKUP_ROOT}/system -type d -mtime +${RETENTION_DAYS} -exec rm -rf {} +

echo "Full system backup completed: ${DATE}"
```

### Recovery Procedures

#### Database Recovery

```bash
#!/bin/bash
# database-recovery.sh

BACKUP_FILE="$1"
DB_NAME="proxy_production"
DB_USER="root"

if [ -z "$BACKUP_FILE" ]; then
    echo "Usage: $0 <backup_file.sql.gz>"
    echo "Available backups:"
    ls -la /var/backups/mysql/full_backup_*.sql.gz
    exit 1
fi

echo "Starting database recovery from: $BACKUP_FILE"

# Stop application services
echo "Stopping application services..."
systemctl stop nginx
systemctl stop php8.3-fpm
systemctl stop laravel-queue

# Create recovery database
echo "Creating recovery database..."
mysql -u ${DB_USER} -p -e "DROP DATABASE IF EXISTS ${DB_NAME}_recovery;"
mysql -u ${DB_USER} -p -e "CREATE DATABASE ${DB_NAME}_recovery;"

# Restore from backup
echo "Restoring database from backup..."
zcat "$BACKUP_FILE" | mysql -u ${DB_USER} -p ${DB_NAME}_recovery

if [ $? -eq 0 ]; then
    echo "Database restore successful"
    
    # Switch databases
    read -p "Switch to recovered database? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        mysql -u ${DB_USER} -p -e "DROP DATABASE IF EXISTS ${DB_NAME}_old;"
        mysql -u ${DB_USER} -p -e "RENAME TABLE ${DB_NAME}.* TO ${DB_NAME}_old.*;"
        mysql -u ${DB_USER} -p -e "RENAME TABLE ${DB_NAME}_recovery.* TO ${DB_NAME}.*;"
        echo "Database switched to recovered version"
    fi
else
    echo "Database restore failed"
    exit 1
fi

# Start services
echo "Starting application services..."
systemctl start php8.3-fpm
systemctl start nginx
systemctl start laravel-queue

echo "Database recovery completed"
```

#### Application Recovery

```bash
#!/bin/bash
# application-recovery.sh

BACKUP_DATE="$1"
BACKUP_ROOT="/var/backups/system"
APP_ROOT="/var/www/html"

if [ -z "$BACKUP_DATE" ]; then
    echo "Usage: $0 <backup_date>"
    echo "Available backups:"
    ls -la ${BACKUP_ROOT}/
    exit 1
fi

BACKUP_DIR="${BACKUP_ROOT}/${BACKUP_DATE}"

if [ ! -d "$BACKUP_DIR" ]; then
    echo "Backup directory not found: $BACKUP_DIR"
    exit 1
fi

echo "Starting application recovery from: $BACKUP_DATE"

# Stop services
systemctl stop nginx
systemctl stop php8.3-fpm
systemctl stop laravel-queue

# Backup current application
if [ -d "$APP_ROOT" ]; then
    mv "$APP_ROOT" "${APP_ROOT}_backup_$(date +%Y%m%d_%H%M%S)"
fi

# Restore application files
echo "Restoring application files..."
mkdir -p "$APP_ROOT"
tar -xzf "${BACKUP_DIR}/application.tar.gz" -C "$APP_ROOT"

# Set permissions
chown -R www-data:www-data "$APP_ROOT"
chmod -R 755 "${APP_ROOT}/storage"
chmod -R 755 "${APP_ROOT}/bootstrap/cache"

# Restore configurations
echo "Restoring configuration files..."
tar -xzf "${BACKUP_DIR}/configs.tar.gz" -C /

# Start services
systemctl start php8.3-fpm
systemctl start nginx
systemctl start laravel-queue

echo "Application recovery completed"
echo "Don't forget to:"
echo "1. Update .env file if needed"
echo "2. Run: php artisan config:cache"
echo "3. Run: php artisan route:cache"
echo "4. Test application functionality"
```

## Log Management

### Log Rotation Configuration

#### Nginx Log Rotation

```bash
# /etc/logrotate.d/nginx
/var/log/nginx/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data adm
    postrotate
        if [ -f /var/run/nginx.pid ]; then
            kill -USR1 `cat /var/run/nginx.pid`
        fi
    endscript
}
```

#### Laravel Log Rotation

```bash
# /etc/logrotate.d/laravel
/var/www/html/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        # Clear Laravel cache to reset log file handles
        cd /var/www/html && php artisan cache:clear --quiet
    endscript
}
```

### Log Analysis Scripts

#### Error Analysis

```bash
#!/bin/bash
# log-analysis.sh

LOG_DIR="/var/www/html/storage/logs"
REPORT_FILE="/tmp/log_analysis_$(date +%Y%m%d).txt"

echo "=== Laravel Log Analysis - $(date) ===" > $REPORT_FILE

# Error summary
echo "Error Summary:" >> $REPORT_FILE
echo "-------------" >> $REPORT_FILE

grep -h "ERROR\|CRITICAL\|EMERGENCY" ${LOG_DIR}/*.log | \
awk '{print $3}' | sort | uniq -c | sort -nr >> $REPORT_FILE

# Top errors
echo -e "\nTop Error Messages:" >> $REPORT_FILE
echo "-------------------" >> $REPORT_FILE

grep -h "ERROR\|CRITICAL\|EMERGENCY" ${LOG_DIR}/*.log | \
sed 's/.*] //' | sort | uniq -c | sort -nr | head -10 >> $REPORT_FILE

# Recent critical errors
echo -e "\nRecent Critical Errors:" >> $REPORT_FILE
echo "----------------------" >> $REPORT_FILE

grep "CRITICAL\|EMERGENCY" ${LOG_DIR}/*.log | tail -20 >> $REPORT_FILE

# Performance issues
echo -e "\nSlow Queries:" >> $REPORT_FILE
echo "-------------" >> $REPORT_FILE

grep "slow query" ${LOG_DIR}/*.log | tail -10 >> $REPORT_FILE

# Send report
mail -s "Daily Log Analysis Report" admin@1000proxy.com < $REPORT_FILE
```

## Update Procedures

### Laravel Framework Updates

#### Safe Update Process

```bash
#!/bin/bash
# laravel-update.sh

APP_ROOT="/var/www/html"
BACKUP_DIR="/var/backups/pre-update-$(date +%Y%m%d_%H%M%S)"

echo "Starting Laravel update process..."

# 1. Create backup
echo "Creating backup..."
mkdir -p $BACKUP_DIR
cp -r $APP_ROOT $BACKUP_DIR/application
/usr/local/bin/database-backup.sh

# 2. Enable maintenance mode
cd $APP_ROOT
php artisan down --message="System update in progress" --retry=60

# 3. Update composer dependencies
echo "Updating Composer dependencies..."
composer update --no-dev --optimize-autoloader

if [ $? -ne 0 ]; then
    echo "Composer update failed, rolling back..."
    php artisan up
    exit 1
fi

# 4. Run migrations
echo "Running database migrations..."
php artisan migrate --force

if [ $? -ne 0 ]; then
    echo "Migration failed, rolling back..."
    # Restore database backup if needed
    php artisan up
    exit 1
fi

# 5. Clear and rebuild caches
echo "Rebuilding caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Update frontend assets
echo "Building frontend assets..."
npm ci --production
npm run build

# 7. Restart queue workers
echo "Restarting queue workers..."
systemctl restart laravel-queue

# 8. Run tests
echo "Running tests..."
php artisan test --env=testing

if [ $? -ne 0 ]; then
    echo "Tests failed, consider rolling back"
    read -p "Continue anyway? (y/N): " -n 1 -r
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        php artisan up
        exit 1
    fi
fi

# 9. Disable maintenance mode
php artisan up

echo "Laravel update completed successfully"
echo "Backup stored at: $BACKUP_DIR"
```

### System Updates

#### Security Updates

```bash
#!/bin/bash
# security-updates.sh

echo "=== Security Updates - $(date) ==="

# Update package lists
apt update

# List available security updates
apt list --upgradable | grep -i security

# Install security updates only
unattended-upgrade -d

# Check if reboot is required
if [ -f /var/run/reboot-required ]; then
    echo "Reboot required after security updates"
    echo "Reboot recommended for security updates" | \
    mail -s "Security Updates: Reboot Required" admin@1000proxy.com
fi

# Update PHP packages
composer update --no-dev --optimize-autoloader --working-dir=/var/www/html

# Restart services
systemctl restart nginx
systemctl restart php8.3-fpm
systemctl restart mysql
systemctl restart redis-server

echo "Security updates completed"
```

## Troubleshooting Guide

### Common Issues and Solutions

#### High CPU Usage

```bash
# Identify CPU-intensive processes
top -o %CPU
htop

# Check for runaway PHP processes
ps aux | grep php | grep -v grep

# Check MySQL processes
mysql -u root -p -e "SHOW PROCESSLIST;"

# Solution steps:
# 1. Identify the problematic process
# 2. Check if it's a legitimate operation
# 3. If needed, kill the process: kill -9 <PID>
# 4. Investigate root cause in logs
# 5. Optimize queries or code if needed
```

#### High Memory Usage

```bash
# Check memory usage
free -h
cat /proc/meminfo

# Identify memory-hungry processes
ps aux --sort=-%mem | head -20

# Check for memory leaks in PHP
grep "Fatal error" /var/www/html/storage/logs/*.log | grep "memory"

# Solution steps:
# 1. Increase PHP memory limit if needed
# 2. Optimize database queries
# 3. Clear unnecessary caches
# 4. Restart PHP-FPM: systemctl restart php8.3-fpm
```

#### Database Connection Issues

```bash
# Check MySQL status
systemctl status mysql
mysql -u root -p -e "SHOW STATUS LIKE 'Threads_connected';"

# Check connection limits
mysql -u root -p -e "SHOW VARIABLES LIKE 'max_connections';"

# Test Laravel database connection
cd /var/www/html
php artisan tinker
>>> DB::connection()->getPdo();

# Solution steps:
# 1. Restart MySQL: systemctl restart mysql
# 2. Check .env database configuration
# 3. Increase max_connections if needed
# 4. Check for connection pool exhaustion
```

#### Queue Worker Issues

```bash
# Check queue worker status
systemctl status laravel-queue

# Check queue size
redis-cli llen queues:default

# Check failed jobs
cd /var/www/html
php artisan queue:failed

# Solution steps:
# 1. Restart queue workers: systemctl restart laravel-queue
# 2. Clear failed jobs: php artisan queue:flush
# 3. Retry failed jobs: php artisan queue:retry all
# 4. Check worker memory limits
```

### Emergency Response Procedures

#### Service Outage Response

```bash
#!/bin/bash
# emergency-response.sh

echo "=== EMERGENCY RESPONSE ACTIVATED ==="

# 1. Assess the situation
echo "1. Checking system status..."
systemctl status nginx php8.3-fpm mysql redis-server

# 2. Check disk space
echo "2. Checking disk space..."
df -h

# 3. Check memory usage
echo "3. Checking memory..."
free -h

# 4. Check for obvious errors
echo "4. Checking error logs..."
tail -50 /var/log/nginx/error.log
tail -50 /var/www/html/storage/logs/laravel.log

# 5. Quick fixes
echo "5. Attempting quick fixes..."

# Restart services
systemctl restart nginx
systemctl restart php8.3-fpm

# Clear caches
cd /var/www/html
php artisan cache:clear
php artisan config:clear

# 6. Test application
echo "6. Testing application..."
curl -I http://localhost/health

# 7. Notify team
echo "Emergency response completed" | \
mail -s "EMERGENCY: System Response Executed" admin@1000proxy.com

echo "=== EMERGENCY RESPONSE COMPLETED ==="
```

This comprehensive maintenance guide provides all the necessary procedures and scripts for maintaining the 1000proxy platform effectively. Regular execution of these maintenance tasks will ensure optimal performance, security, and reliability.
