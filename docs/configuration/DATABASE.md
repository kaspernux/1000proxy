# 💾 Database Setup Guide

<div align="center">
  <img src="/images/1000proxy.png" width="200" alt="1000Proxy Logo">
  
  ## Database Configuration & Setup
  
  *Complete guide to setting up and configuring databases for 1000proxy*
</div>

---

## 📋 Overview

1000proxy supports multiple database systems with MySQL being the primary recommended database. This guide covers installation, configuration, and optimization.

## 🎯 Supported Databases

- **MySQL 8.0+** (Recommended)
- **MariaDB 10.6+**
- **PostgreSQL 13+**
- **SQLite** (Development only)

## 🚀 Quick Setup

### MySQL Installation

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install mysql-server mysql-client
sudo systemctl start mysql
sudo systemctl enable mysql
```

**CentOS/RHEL:**
```bash
sudo yum install mysql-server mysql
sudo systemctl start mysqld
sudo systemctl enable mysqld
```

**Docker:**
```bash
docker run -d \
  --name 1000proxy-mysql \
  -e MYSQL_ROOT_PASSWORD=yourpassword \
  -e MYSQL_DATABASE=1000proxy \
  -e MYSQL_USER=proxy_user \
  -e MYSQL_PASSWORD=userpassword \
  -p 3306:3306 \
  mysql:8.0
```

## 🔧 Database Configuration

### 1. Create Database and User

```sql
-- Connect as root
mysql -u root -p

-- Create database
CREATE DATABASE 1000proxy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER 'proxy_user'@'localhost' IDENTIFIED BY 'your_secure_password';

-- Grant privileges
GRANT ALL PRIVILEGES ON 1000proxy.* TO 'proxy_user'@'localhost';
FLUSH PRIVILEGES;

-- Exit
EXIT;
```

### 2. Configure Environment

Edit your `.env` file:

```env
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=1000proxy
DB_USERNAME=proxy_user
DB_PASSWORD=your_secure_password

# Optional: Specific MySQL settings
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_STRICT_MODE=true
DB_ENGINE=InnoDB
```

### 3. Run Migrations

```bash
# Run database migrations
php artisan migrate

# Seed with initial data
php artisan db:seed

# Create admin user
php artisan make:admin-user
```

## 🔒 Security Configuration

### MySQL Security

**1. Secure Installation:**
```bash
sudo mysql_secure_installation
```

**2. Create Dedicated User:**
```sql
-- Remove anonymous users
DELETE FROM mysql.user WHERE User='';

-- Remove test database
DROP DATABASE IF EXISTS test;

-- Reload privilege tables
FLUSH PRIVILEGES;
```

**3. Configure Connection Limits:**
```sql
-- Set connection limits
SET GLOBAL max_connections = 200;
SET GLOBAL max_user_connections = 50;
```

### Firewall Configuration

```bash
# Allow MySQL port (internal only)
sudo ufw allow from 127.0.0.1 to any port 3306
sudo ufw allow from 10.0.0.0/8 to any port 3306

# Block external access
sudo ufw deny 3306
```

## 🚀 Performance Optimization

### MySQL Configuration (`/etc/mysql/mysql.conf.d/mysqld.cnf`)

```ini
[mysqld]
# Basic Settings
user                    = mysql
bind-address           = 127.0.0.1
port                   = 3306
datadir                = /var/lib/mysql

# Performance Settings
max_connections        = 200
max_user_connections   = 50
thread_cache_size      = 16
table_open_cache       = 4000
table_definition_cache = 1000

# Memory Settings
innodb_buffer_pool_size        = 1G
innodb_buffer_pool_instances   = 1
innodb_log_file_size          = 256M
innodb_log_buffer_size        = 16M
innodb_flush_log_at_trx_commit = 2

# Query Cache (MySQL 5.7 and below)
query_cache_type       = 1
query_cache_size       = 128M
query_cache_limit      = 2M

# Binary Logging
log-bin               = mysql-bin
expire_logs_days      = 3
max_binlog_size       = 100M

# Slow Query Log
slow_query_log        = 1
slow_query_log_file   = /var/log/mysql/slow.log
long_query_time       = 1
```

### Application-Level Optimization

**1. Database Connections:**
```env
# Connection pooling
DB_CONNECTION_POOL_SIZE=10
DB_CONNECTION_TIMEOUT=30
DB_RECONNECT_ATTEMPTS=3
```

**2. Query Optimization:**
```bash
# Enable query caching
php artisan config:cache

# Optimize database
php artisan optimize:database
```

## 🔄 Backup & Maintenance

### Automated Backups

**1. Create Backup Script:**
```bash
#!/bin/bash
# /usr/local/bin/backup-1000proxy.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/1000proxy"
DB_NAME="1000proxy"
DB_USER="proxy_user"
DB_PASS="your_password"

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_backup_$DATE.sql.gz

# Application files backup
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz /var/www/1000proxy --exclude=/var/www/1000proxy/storage/logs

# Keep only last 7 days
find $BACKUP_DIR -name "*.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
```

**2. Schedule with Cron:**
```bash
# Edit crontab
crontab -e

# Add daily backup at 2 AM
0 2 * * * /usr/local/bin/backup-1000proxy.sh >> /var/log/backup.log 2>&1
```

### Database Maintenance

**1. Optimize Tables:**
```sql
-- Optimize all tables
OPTIMIZE TABLE users, proxy_servers, transactions, notifications;

-- Check table status
SHOW TABLE STATUS LIKE '%';
```

**2. Analyze Query Performance:**
```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;

-- Show processlist
SHOW FULL PROCESSLIST;
```

## 🐳 Docker Setup

### Docker Compose

```yaml
# docker-compose.yml
version: '3.8'

services:
  mysql:
    image: mysql:8.0
    container_name: 1000proxy-mysql
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/conf.d:/etc/mysql/conf.d
    ports:
      - "3306:3306"
    networks:
      - 1000proxy

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    container_name: 1000proxy-phpmyadmin
    restart: unless-stopped
    environment:
      PMA_HOST: mysql
      PMA_PORT: 3306
      PMA_USER: ${DB_USERNAME}
      PMA_PASSWORD: ${DB_PASSWORD}
    ports:
      - "8080:80"
    depends_on:
      - mysql
    networks:
      - 1000proxy

volumes:
  mysql_data:

networks:
  1000proxy:
    driver: bridge
```

### Environment Variables

```env
# Docker MySQL Configuration
DB_ROOT_PASSWORD=super_secure_root_password
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=1000proxy
DB_USERNAME=proxy_user
DB_PASSWORD=secure_user_password
```

## 🔍 Troubleshooting

### Common Issues

**1. Connection Refused:**
```bash
# Check MySQL status
sudo systemctl status mysql

# Check if MySQL is listening
sudo netstat -tlnp | grep :3306

# Check MySQL logs
sudo tail -f /var/log/mysql/error.log
```

**2. Access Denied:**
```sql
-- Reset user password
ALTER USER 'proxy_user'@'localhost' IDENTIFIED BY 'new_password';
FLUSH PRIVILEGES;
```

**3. Database Not Found:**
```sql
-- List all databases
SHOW DATABASES;

-- Create if missing
CREATE DATABASE 1000proxy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**4. Migration Errors:**
```bash
# Reset migrations
php artisan migrate:reset

# Fresh migration
php artisan migrate:fresh

# With seeding
php artisan migrate:fresh --seed
```

### Performance Issues

**1. Slow Queries:**
```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';

-- Check current queries
SHOW FULL PROCESSLIST;

-- Kill long-running query
KILL QUERY process_id;
```

**2. High Memory Usage:**
```bash
# Check MySQL memory usage
sudo mysql -e "SHOW VARIABLES LIKE 'innodb_buffer_pool_size';"

# Monitor MySQL process
top -p $(pgrep mysqld)
```

## 📊 Monitoring

### Database Metrics

**1. Query Performance:**
```sql
-- Query execution statistics
SELECT * FROM performance_schema.events_statements_summary_by_digest 
ORDER BY avg_timer_wait DESC LIMIT 10;
```

**2. Connection Monitoring:**
```sql
-- Current connections
SHOW STATUS LIKE 'Threads_connected';

-- Max connections used
SHOW STATUS LIKE 'Max_used_connections';
```

### Automated Monitoring

```bash
#!/bin/bash
# /usr/local/bin/monitor-mysql.sh

# Check MySQL status
if ! systemctl is-active --quiet mysql; then
    echo "MySQL is down!" | mail -s "MySQL Alert" admin@yoursite.com
fi

# Check connections
CONNECTIONS=$(mysql -N -e "SHOW STATUS LIKE 'Threads_connected';" | awk '{print $2}')
if [ $CONNECTIONS -gt 150 ]; then
    echo "High connection count: $CONNECTIONS" | mail -s "MySQL Alert" admin@yoursite.com
fi
```

## 📚 Related Documentation

- [🌍 Environment Configuration](ENVIRONMENT.md)
- [📧 Email Configuration](EMAIL.md)
- [🔐 Payment Gateways](PAYMENT_GATEWAYS.md)
- [🛡️ Security Guide](../security/SECURITY_BEST_PRACTICES.md)
- [🐳 Docker Guide](../docker/DOCKER_GUIDE.md)

---

<div align="center">
  <p>
    <a href="../README.md">📚 Back to Documentation</a> •
    <a href="../getting-started/INSTALLATION.md">🔧 Installation</a> •
    <a href="ENVIRONMENT.md">🌍 Environment</a>
  </p>
  
  **Need Help?** Check our [FAQ](../FAQ.md) or open an issue.
</div>
