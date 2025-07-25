# 1000proxy Deployment Guide

## Prerequisites

### System Requirements
- **OS**: Ubuntu 20.04 LTS or higher
- **PHP**: 8.1 or higher
- **Memory**: Minimum 4GB RAM (8GB recommended)
- **Storage**: 20GB available space
- **Network**: Stable internet connection

### Required Software
- Nginx 1.18+
- PHP 8.1+ with extensions:
  - php-fpm
  - php-mysql
  - php-redis
  - php-zip
  - php-xml
  - php-mbstring
  - php-curl
  - php-gd
  - php-intl
  - php-bcmath
- MySQL 8.0+ or MariaDB 10.3+
- Redis 6.0+
- Node.js 16+ and npm
- Composer 2.0+
- Supervisor
- Certbot (for SSL certificates)

## Installation Steps

### 1. Server Setup
```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y nginx mysql-server redis-server supervisor certbot python3-certbot-nginx

# Install PHP 8.1 and extensions
sudo apt install -y php8.1-fpm php8.1-mysql php8.1-redis php8.1-zip php8.1-xml php8.1-mbstring php8.1-curl php8.1-gd php8.1-intl php8.1-bcmath php8.1-cli

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_16.x | sudo -E bash -
sudo apt-get install -y nodejs

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Database Setup
```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE proxy_1000 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'proxy_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON proxy_1000.* TO 'proxy_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Application Deployment
```bash
# Clone the repository
cd /var/www
sudo git clone https://github.com/kaspernux/1000proxy.git
sudo chown -R www-data:www-data 1000proxy
cd 1000proxy

# Install PHP dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader

# Install Node.js dependencies
sudo -u www-data npm install

# Build frontend assets
sudo -u www-data npm run build

# Set proper permissions
sudo chown -R www-data:www-data /var/www/1000proxy
sudo chmod -R 755 /var/www/1000proxy
sudo chmod -R 775 /var/www/1000proxy/storage
sudo chmod -R 775 /var/www/1000proxy/bootstrap/cache
```

### 4. Environment Configuration
```bash
# Copy and configure environment file
sudo -u www-data cp .env.example .env
sudo -u www-data nano .env
```

```env
# Application Settings
APP_NAME="1000proxy"
APP_ENV=production
APP_KEY=base64:GENERATE_WITH_php_artisan_key:generate
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=proxy_1000
DB_USERNAME=proxy_user
DB_PASSWORD=secure_password_here

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache Configuration
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"

# NowPayments Configuration
NOWPAYMENTS_API_KEY=your_api_key_here
NOWPAYMENTS_WEBHOOK_SECRET=your_webhook_secret_here
NOWPAYMENTS_SANDBOX=false

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### 5. Application Setup
```bash
# Generate application key
sudo -u www-data php artisan key:generate

# Run database migrations
sudo -u www-data php artisan migrate --force

# Seed database with initial data
sudo -u www-data php artisan db:seed --force

# Cache configuration
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Create symbolic link for storage
sudo -u www-data php artisan storage:link
```

### 6. Nginx Configuration
```bash
sudo nano /etc/nginx/sites-available/1000proxy
```

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/1000proxy/public;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    
    # Hide nginx version
    server_tokens off;
    
    # File upload limit
    client_max_body_size 100M;
    
    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
    
    index index.php;
    charset utf-8;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    
    error_page 404 /index.php;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
    
    # Cache static assets
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
}
```

```bash
# Enable site and restart nginx
sudo ln -s /etc/nginx/sites-available/1000proxy /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 7. SSL Certificate Setup
```bash
# Obtain SSL certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Auto-renewal setup
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

### 8. Queue Worker Setup
```bash
sudo nano /etc/supervisor/conf.d/1000proxy-worker.conf
```

```ini
[program:1000proxy-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/1000proxy/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/1000proxy/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start 1000proxy-worker:*
```

### 9. Cron Jobs Setup
```bash
sudo crontab -e
```

```bash
# Laravel scheduler
* * * * * cd /var/www/1000proxy && php artisan schedule:run >> /dev/null 2>&1

# Database backup (daily at 2 AM)
0 2 * * * mysqldump -u proxy_user -p'secure_password_here' proxy_1000 > /var/backups/proxy_1000_$(date +\%Y\%m\%d).sql

# Log rotation (weekly)
0 0 * * 0 find /var/www/1000proxy/storage/logs -name "*.log" -mtime +7 -delete

# Cache cleanup (daily at 3 AM)
0 3 * * * cd /var/www/1000proxy && php artisan cache:clear
```

## Security Hardening

### 1. Firewall Configuration
```bash
# Enable UFW
sudo ufw enable

# Allow SSH, HTTP, and HTTPS
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'

# Block all other ports
sudo ufw default deny incoming
sudo ufw default allow outgoing
```

### 2. PHP Security
```bash
sudo nano /etc/php/8.1/fpm/php.ini
```

```ini
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
max_execution_time = 30
max_input_time = 60
memory_limit = 256M
post_max_size = 100M
upload_max_filesize = 100M
session.cookie_httponly = On
session.cookie_secure = On
session.use_strict_mode = On
```

### 3. Database Security
```bash
# MySQL configuration
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

```ini
[mysqld]
bind-address = 127.0.0.1
sql_mode = STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION
local-infile = 0
```

### 4. Redis Security
```bash
sudo nano /etc/redis/redis.conf
```

```ini
bind 127.0.0.1
protected-mode yes
requirepass your_redis_password_here
```

## Monitoring and Logging

### 1. Application Monitoring
```bash
# Install monitoring tools
sudo apt install -y htop iotop nethogs

# Set up log monitoring
sudo nano /etc/rsyslog.d/1000proxy.conf
```

```ini
# 1000proxy logs
/var/www/1000proxy/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

### 2. Performance Monitoring
```bash
# PHP-FPM status page
sudo nano /etc/php/8.1/fpm/pool.d/www.conf
```

```ini
pm.status_path = /fpm-status
ping.path = /fpm-ping
```

### 3. Database Monitoring
```sql
-- Create monitoring user
CREATE USER 'monitor'@'localhost' IDENTIFIED BY 'monitor_password';
GRANT PROCESS, REPLICATION CLIENT ON *.* TO 'monitor'@'localhost';
FLUSH PRIVILEGES;
```

## Backup Strategy

### 1. Database Backup
```bash
#!/bin/bash
# /usr/local/bin/backup-database.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/1000proxy"
DB_NAME="proxy_1000"
DB_USER="proxy_user"
DB_PASS="secure_password_here"

mkdir -p $BACKUP_DIR

# Create database backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/database_$DATE.sql

# Compress backup
gzip $BACKUP_DIR/database_$DATE.sql

# Keep only last 30 days
find $BACKUP_DIR -name "database_*.sql.gz" -mtime +30 -delete

echo "Database backup completed: $BACKUP_DIR/database_$DATE.sql.gz"
```

### 2. Application Backup
```bash
#!/bin/bash
# /usr/local/bin/backup-app.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/1000proxy"
APP_DIR="/var/www/1000proxy"

mkdir -p $BACKUP_DIR

# Backup application files (excluding cache and logs)
tar -czf $BACKUP_DIR/app_$DATE.tar.gz \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='node_modules' \
    --exclude='.git' \
    $APP_DIR

# Keep only last 7 days
find $BACKUP_DIR -name "app_*.tar.gz" -mtime +7 -delete

echo "Application backup completed: $BACKUP_DIR/app_$DATE.tar.gz"
```

## Troubleshooting

### Common Issues

#### 1. Permission Errors
```bash
# Fix Laravel permissions
sudo chown -R www-data:www-data /var/www/1000proxy
sudo chmod -R 755 /var/www/1000proxy
sudo chmod -R 775 /var/www/1000proxy/storage
sudo chmod -R 775 /var/www/1000proxy/bootstrap/cache
```

#### 2. 500 Internal Server Error
```bash
# Check error logs
sudo tail -f /var/www/1000proxy/storage/logs/laravel.log
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/php8.1-fpm.log

# Clear cache
cd /var/www/1000proxy
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear
```

#### 3. Database Connection Issues
```bash
# Test database connection
sudo -u www-data php artisan tinker
# In tinker: DB::connection()->getPdo();

# Check database service
sudo systemctl status mysql
sudo systemctl restart mysql
```

#### 4. Queue Worker Issues
```bash
# Check queue worker status
sudo supervisorctl status 1000proxy-worker:*

# Restart queue workers
sudo supervisorctl restart 1000proxy-worker:*

# Check queue jobs
cd /var/www/1000proxy
sudo -u www-data php artisan queue:monitor
```

#### 5. SSL Certificate Issues
```bash
# Check certificate status
sudo certbot certificates

# Renew certificate
sudo certbot renew --dry-run
sudo certbot renew
```

### Performance Optimization

#### 1. OPcache Configuration
```bash
sudo nano /etc/php/8.1/fpm/conf.d/10-opcache.ini
```

```ini
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=512
opcache.interned_strings_buffer=64
opcache.max_accelerated_files=32531
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=0
```

#### 2. MySQL Optimization
```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

```ini
[mysqld]
innodb_buffer_pool_size = 2G
innodb_log_file_size = 512M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
query_cache_type = 1
query_cache_size = 256M
```

#### 3. Redis Optimization
```bash
sudo nano /etc/redis/redis.conf
```

```ini
maxmemory 1gb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

## Updates and Maintenance

### 1. Application Updates
```bash
#!/bin/bash
# /usr/local/bin/update-app.sh

cd /var/www/1000proxy

# Backup before update
/usr/local/bin/backup-app.sh
/usr/local/bin/backup-database.sh

# Put application in maintenance mode
sudo -u www-data php artisan down

# Pull latest changes
sudo -u www-data git pull origin main

# Update dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader

# Update frontend assets
sudo -u www-data npm install
sudo -u www-data npm run build

# Run database migrations
sudo -u www-data php artisan migrate --force

# Clear and cache config
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Restart queue workers
sudo supervisorctl restart 1000proxy-worker:*

# Bring application back online
sudo -u www-data php artisan up

echo "Application updated successfully"
```

### 2. System Updates
```bash
#!/bin/bash
# /usr/local/bin/system-update.sh

# Update system packages
sudo apt update
sudo apt upgrade -y

# Clean up
sudo apt autoremove -y
sudo apt autoclean

# Restart services if needed
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx
sudo systemctl restart mysql
sudo systemctl restart redis

echo "System updated successfully"
```

## Health Checks

### 1. Application Health Check
```bash
#!/bin/bash
# /usr/local/bin/health-check.sh

URL="https://your-domain.com/api/health"
EXPECTED_STATUS=200

# Check HTTP status
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" $URL)

if [ $HTTP_STATUS -eq $EXPECTED_STATUS ]; then
    echo "✓ Application is healthy"
else
    echo "✗ Application health check failed (HTTP $HTTP_STATUS)"
    exit 1
fi

# Check database connection
cd /var/www/1000proxy
DB_CHECK=$(sudo -u www-data php artisan tinker --execute="echo DB::connection()->getPdo() ? 'OK' : 'FAIL';")

if [[ $DB_CHECK == *"OK"* ]]; then
    echo "✓ Database connection is healthy"
else
    echo "✗ Database connection failed"
    exit 1
fi

# Check queue workers
QUEUE_CHECK=$(sudo supervisorctl status 1000proxy-worker:* | grep -c "RUNNING")

if [ $QUEUE_CHECK -gt 0 ]; then
    echo "✓ Queue workers are running"
else
    echo "✗ Queue workers are not running"
    exit 1
fi

echo "All health checks passed"
```

This comprehensive deployment guide covers all aspects of setting up, securing, monitoring, and maintaining the 1000proxy application in a production environment.
