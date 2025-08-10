# Deployment Guide

This comprehensive guide covers all aspects of deploying the 1000proxy application to production environments.

## Table of Contents

1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Environment Setup](#environment-setup)
3. [Automated Deployment](#automated-deployment)
4. [Manual Deployment](#manual-deployment)
5. [Database Migration](#database-migration)
6. [SSL/HTTPS Configuration](#sslhttps-configuration)
7. [Performance Optimization](#performance-optimization)
8. [Monitoring Setup](#monitoring-setup)
9. [Backup Configuration](#backup-configuration)
10. [Rollback Procedures](#rollback-procedures)
11. [Troubleshooting](#troubleshooting)

## Pre-Deployment Checklist

### Code Quality

- [ ] All tests pass (`php artisan test`)
- [ ] Code coverage meets requirements (>80%)
- [ ] Static analysis passes (`./vendor/bin/phpstan analyse`)
- [ ] Code style follows PSR-12 (`./vendor/bin/pint`)
- [ ] Security vulnerabilities scanned (`composer audit`)
- [ ] Dependencies up to date and secure

### Configuration

- [ ] Environment variables configured
- [ ] Database credentials set
- [ ] API keys and secrets configured
- [ ] Email configuration tested
- [ ] Payment gateway credentials verified
- [ ] Queue system configured
- [ ] Cache driver configured
- [ ] Session driver configured

### Infrastructure

- [ ] Server requirements met
- [ ] Domain name configured
- [ ] SSL certificate ready
- [ ] Database server prepared
- [ ] Redis/cache server ready
- [ ] File storage configured
- [ ] CDN configured (if applicable)
- [ ] Backup system ready

## Environment Setup

### Server Requirements

#### Minimum Requirements

```bash
# System Requirements
- Ubuntu 20.04 LTS or CentOS 8+
- 2 vCPUs
- 4GB RAM
- 40GB SSD storage
- 100 Mbps network connection

# Software Requirements
- PHP 8.3+
- MySQL 8.0+ or PostgreSQL 13+
- Redis 6.0+
- Nginx 1.18+ or Apache 2.4+
- Node.js 18+
- Composer 2.4+
```

#### Recommended Production Requirements

```bash
# System Requirements
- Ubuntu 22.04 LTS
- 4+ vCPUs
- 8GB+ RAM
- 100GB+ SSD storage
- 1 Gbps network connection

# Software Requirements
- PHP 8.3 with OPcache
- MySQL 8.0 with optimized configuration
- Redis 7.0 with persistence
- Nginx 1.22 with HTTP/2
- Node.js 20 LTS
- Composer 2.6+
```

### Environment Variables

Create production `.env` file:

```bash
# Application
APP_NAME="1000proxy"
APP_ENV=production
APP_KEY=base64:your-generated-app-key
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=proxy_production
DB_USERNAME=proxy_user
DB_PASSWORD=secure_password

# Cache
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=redis_password
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=username
MAIL_PASSWORD=password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"

# Payment Gateways
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
PAYPAL_CLIENT_ID=live_client_id
PAYPAL_CLIENT_SECRET=live_client_secret

# 3X-UI Integration
XUIAPI_BASE_URL=https://your-xui-panel.com
XUIAPI_USERNAME=admin
XUIAPI_PASSWORD=secure_password

# File Storage
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name

# Monitoring
SENTRY_LARAVEL_DSN=https://your-sentry-dsn@sentry.io
LOG_CHANNEL=daily
LOG_LEVEL=error
```

## Automated Deployment

### Using GitHub Actions

Create `.github/workflows/deploy.yml`:

```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]
  workflow_dispatch:

jobs:
  tests:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: testing
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
      
      redis:
        image: redis:7.0
        ports:
          - 6379:6379
        options: --health-cmd="redis-cli ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
    - uses: actions/checkout@v4
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.3'
        extensions: mbstring, dom, fileinfo, mysql, redis
        coverage: xdebug
    
    - name: Copy .env
      run: php -r "file_exists('.env') || copy('.env.testing', '.env');"
    
    - name: Install Dependencies
      run: |
        composer install --no-dev --optimize-autoloader
        npm ci
    
    - name: Generate key
      run: php artisan key:generate
    
    - name: Directory Permissions
      run: chmod -R 755 storage bootstrap/cache
    
    - name: Create Database
      run: |
        mysql --version
        php artisan migrate --force
    
    - name: Execute tests
      run: |
        php artisan test --coverage --min=80
        ./vendor/bin/phpstan analyse
        ./vendor/bin/pint --test

  deploy:
    needs: tests
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    
    steps:
    - uses: actions/checkout@v4
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.3'
    
    - name: Install Dependencies
      run: |
        composer install --no-dev --optimize-autoloader
        npm ci
        npm run build
    
    - name: Create deployment artifact
      env:
        GITHUB_SHA: ${{ github.sha }}
      run: tar -czf "${GITHUB_SHA}".tar.gz --exclude=*.git --exclude=node_modules *
    
    - name: Store artifact for distribution
      uses: actions/upload-artifact@v4
      with:
        name: app-build
        path: ${{ github.sha }}.tar.gz
    
    - name: Deploy to server
      uses: appleboy/ssh-action@v1.0.0
      with:
        host: ${{ secrets.HOST }}
        username: ${{ secrets.USERNAME }}
        key: ${{ secrets.PRIVATE_KEY }}
        port: ${{ secrets.PORT }}
        script: |
          cd /var/www/html
          
          # Download artifact
          curl -H "Authorization: token ${{ secrets.GITHUB_TOKEN }}" \
               -H "Accept: application/vnd.github.v3.raw" \
               -L -o ${{ github.sha }}.tar.gz \
               https://api.github.com/repos/${{ github.repository }}/actions/artifacts/latest/zip
          
          # Backup current deployment
          sudo cp -r current backup-$(date +%Y%m%d_%H%M%S)
          
          # Extract new deployment
          mkdir -p releases/${{ github.sha }}
          tar -xzf ${{ github.sha }}.tar.gz -C releases/${{ github.sha }}
          
          # Update symlink
          sudo ln -nfs releases/${{ github.sha }} current
          
          # Set permissions
          sudo chown -R www-data:www-data current
          sudo chmod -R 755 current/storage current/bootstrap/cache
          
          # Run deployment commands
          cd current
          php artisan config:cache
          php artisan route:cache
          php artisan view:cache
          php artisan migrate --force
          php artisan queue:restart
          
          # Restart services
          sudo systemctl reload nginx
          sudo systemctl restart php8.3-fpm
          
          # Health check
          sleep 5
          curl -f https://yourdomain.com/health || exit 1
```

### Using Docker

#### Dockerfile

```dockerfile
# Multi-stage build
FROM node:20-alpine AS frontend-build

WORKDIR /app
COPY package*.json ./
RUN npm ci --only=production

COPY . .
RUN npm run build

FROM php:8.3-fpm-alpine AS backend-build

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    icu-dev \
    autoconf \
    g++ \
    make

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create app directory
WORKDIR /var/www/html

# Copy composer files
COPY composer*.json ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy application files
COPY . .
COPY --from=frontend-build /app/public/build ./public/build

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Production optimizations
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Copy PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

EXPOSE 9000

CMD ["php-fpm"]
```

#### docker-compose.production.yml

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: proxy-app
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - ./storage:/var/www/html/storage
      - ./bootstrap/cache:/var/www/html/bootstrap/cache
    networks:
      - proxy-network
    depends_on:
      - database
      - redis
    environment:
      - APP_ENV=production
      - DB_HOST=database
      - REDIS_HOST=redis

  nginx:
    image: nginx:alpine
    container_name: proxy-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www/html
      - ./docker/nginx:/etc/nginx/conf.d
      - ./docker/ssl:/etc/ssl/certs
    networks:
      - proxy-network
    depends_on:
      - app

  database:
    image: mysql:8.0
    container_name: proxy-db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_PASSWORD: ${DB_PASSWORD}
      MYSQL_USER: ${DB_USERNAME}
    volumes:
      - db_data:/var/lib/mysql
      - ./docker/mysql/my.cnf:/etc/mysql/my.cnf
    networks:
      - proxy-network

  redis:
    image: redis:7.0-alpine
    container_name: proxy-redis
    restart: unless-stopped
    command: redis-server --appendonly yes --requirepass ${REDIS_PASSWORD}
    volumes:
      - redis_data:/data
    networks:
      - proxy-network

  queue:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: proxy-queue
    restart: unless-stopped
    command: php artisan queue:work --sleep=3 --tries=3
    volumes:
      - ./storage:/var/www/html/storage
    networks:
      - proxy-network
    depends_on:
      - database
      - redis

  scheduler:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: proxy-scheduler
    restart: unless-stopped
    command: |
      sh -c 'while true; do
        php artisan schedule:run
        sleep 60
      done'
    volumes:
      - ./storage:/var/www/html/storage
    networks:
      - proxy-network
    depends_on:
      - database
      - redis

volumes:
  db_data:
  redis_data:

networks:
  proxy-network:
    driver: bridge
```

### Using Deployer

Install Deployer:

```bash
composer require deployer/deployer --dev
```

Create `deploy.php`:

```php
<?php

namespace Deployer;

require 'recipe/laravel.php';

// Configuration
set('application', '1000proxy');
set('repository', 'git@github.com:your-username/1000proxy.git');
set('keep_releases', 5);
set('writable_mode', 'chmod');

// Hosts
host('production')
    ->setHostname('your-server-ip')
    ->setUser('deploy')
    ->setPort(22)
    ->setDeployPath('/var/www/html');

// Tasks
task('npm:install', function () {
    run('cd {{release_path}} && npm ci');
});

task('npm:build', function () {
    run('cd {{release_path}} && npm run build');
});

task('php:optimize', function () {
    run('cd {{release_path}} && php artisan config:cache');
    run('cd {{release_path}} && php artisan route:cache');
    run('cd {{release_path}} && php artisan view:cache');
});

task('queue:restart', function () {
    run('cd {{release_path}} && php artisan queue:restart');
});

task('services:reload', function () {
    run('sudo systemctl reload nginx');
    run('sudo systemctl restart php8.3-fpm');
});

// Build frontend assets
after('deploy:vendors', 'npm:install');
after('npm:install', 'npm:build');

// Optimize Laravel
after('artisan:migrate', 'php:optimize');

// Restart services
after('deploy:symlink', 'queue:restart');
after('deploy:cleanup', 'services:reload');

// Health check
task('deploy:health_check', function () {
    $response = run('curl -f {{app_url}}/health');
    if (strpos($response, '"status":"ok"') === false) {
        throw new Exception('Health check failed');
    }
    info('Health check passed');
});

after('services:reload', 'deploy:health_check');

// [Optional] If deploy fails automatically unlock
after('deploy:failed', 'deploy:unlock');
```

Deploy with:

```bash
vendor/bin/dep deploy production
```

## Manual Deployment

### Step-by-Step Process

#### 1. Prepare Server Environment

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y nginx mysql-server redis-server php8.3 php8.3-fpm php8.3-mysql \
    php8.3-redis php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-bcmath \
    php8.3-intl php8.3-gd php8.3-opcache curl git unzip

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### 2. Clone and Setup Application

```bash
# Create deployment directory
sudo mkdir -p /var/www/html
cd /var/www/html

# Clone repository
sudo git clone https://github.com/your-username/1000proxy.git .

# Set ownership
sudo chown -R www-data:www-data /var/www/html

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Set permissions
sudo chmod -R 755 storage bootstrap/cache
```

#### 3. Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit environment file
nano .env
```

#### 4. Database Setup

```bash
# Create database
sudo mysql -u root -p << EOF
CREATE DATABASE proxy_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'proxy_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON proxy_production.* TO 'proxy_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# Run migrations
php artisan migrate --force

# Seed database (if needed)
php artisan db:seed --force
```

#### 5. Configure Web Server

**Nginx Configuration** (`/etc/nginx/sites-available/1000proxy`):

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/html/public;
    index index.php index.html;

    # SSL Configuration
    ssl_certificate /etc/ssl/certs/yourdomain.com.pem;
    ssl_certificate_key /etc/ssl/private/yourdomain.com.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
    ssl_prefer_server_ciphers off;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
  # CSP disabled (Livewire v3 incompatibility). Keep placeholder for future hardened policy.
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_comp_level 6;
    gzip_types
        application/atom+xml
        application/javascript
        application/json
        application/rss+xml
        application/vnd.ms-fontobject
        application/x-font-ttf
        application/x-web-app-manifest+json
        application/xhtml+xml
        application/xml
        font/opentype
        image/svg+xml
        image/x-icon
        text/css
        text/plain
        text/x-component;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|txt)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

#### 6. Configure PHP-FPM

Edit `/etc/php/8.3/fpm/pool.d/www.conf`:

```ini
[www]
user = www-data
group = www-data
listen = /var/run/php/php8.3-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

; Performance tuning
request_terminate_timeout = 60
rlimit_files = 131072
rlimit_core = unlimited

; Security
security.limit_extensions = .php
```

Edit `/etc/php/8.3/fpm/php.ini`:

```ini
; Performance
memory_limit = 256M
max_execution_time = 60
max_input_time = 60
post_max_size = 64M
upload_max_filesize = 64M

; OPcache
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
opcache.revalidate_freq = 60
opcache.fast_shutdown = 1

; Security
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off

; Session
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
```

#### 7. Configure Queue Workers

Create systemd service file `/etc/systemd/system/laravel-queue.service`:

```ini
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/html/artisan queue:work --sleep=3 --tries=3 --max-time=3600
StandardOutput=journal
StandardError=journal
SyslogIdentifier=laravel-queue

[Install]
WantedBy=multi-user.target
```

Start the service:

```bash
sudo systemctl enable laravel-queue
sudo systemctl start laravel-queue
```

#### 8. Configure Cron Jobs

Add to crontab (`sudo crontab -e`):

```bash
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

#### 9. Final Optimizations

```bash
# Clear and cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Enable services
sudo systemctl enable nginx
sudo systemctl enable php8.3-fpm
sudo systemctl enable mysql
sudo systemctl enable redis-server

# Start services
sudo systemctl start nginx
sudo systemctl start php8.3-fpm
sudo systemctl start mysql
sudo systemctl start redis-server

# Test configuration
sudo nginx -t
sudo systemctl status nginx
sudo systemctl status php8.3-fpm
```

## Database Migration

### Production Migration Strategy

```bash
# Backup database before migration
mysqldump -u root -p proxy_production > backup_$(date +%Y%m%d_%H%M%S).sql

# Run migrations with zero downtime
php artisan migrate --force

# Verify migration status
php artisan migrate:status

# Rollback if needed (use with caution)
php artisan migrate:rollback --step=1
```

### Zero-Downtime Migration

For zero-downtime deployments:

```bash
# 1. Deploy new code without migrating
# 2. Ensure new code is backward compatible
# 3. Run migrations
php artisan migrate --force
# 4. Update symlink to new deployment
# 5. Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## SSL/HTTPS Configuration

### Using Let's Encrypt

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Generate certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Test renewal
sudo certbot renew --dry-run

# Auto-renewal cron job
echo "0 12 * * * /usr/bin/certbot renew --quiet" | sudo crontab -
```

### Using Custom SSL Certificate

```bash
# Copy certificate files
sudo cp yourdomain.com.crt /etc/ssl/certs/
sudo cp yourdomain.com.key /etc/ssl/private/
sudo cp ca_bundle.crt /etc/ssl/certs/

# Set permissions
sudo chmod 644 /etc/ssl/certs/yourdomain.com.crt
sudo chmod 600 /etc/ssl/private/yourdomain.com.key

# Update Nginx configuration
sudo nano /etc/nginx/sites-available/1000proxy

# Test configuration
sudo nginx -t
sudo systemctl reload nginx
```

## Performance Optimization

### Database Optimization

```sql
-- MySQL configuration (/etc/mysql/mysql.conf.d/mysqld.cnf)
[mysqld]
# Performance
innodb_buffer_pool_size = 2G
innodb_log_file_size = 256M
innodb_log_buffer_size = 64M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Query cache
query_cache_type = 1
query_cache_size = 256M
query_cache_limit = 2M

# Connections
max_connections = 300
thread_cache_size = 50

# Slow query log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

### Redis Configuration

```bash
# /etc/redis/redis.conf
maxmemory 1gb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

### Laravel Optimizations

```bash
# Production optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Queue optimization
php artisan queue:work --queue=high,default,low --sleep=3 --tries=3 --max-time=3600

# Database query optimization
php artisan model:cache
```

## Monitoring Setup

### Laravel Telescope (Development)

```bash
# Install only in development
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

### Sentry Integration

```bash
# Install Sentry
composer require sentry/sentry-laravel

# Publish config
php artisan vendor:publish --provider="Sentry\Laravel\ServiceProvider"

# Add to .env
SENTRY_LARAVEL_DSN=https://your-dsn@sentry.io
```

### Health Checks

Create health check endpoint:

```php
// routes/web.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'services' => [
            'database' => DB::connection()->getPdo() ? 'ok' : 'error',
            'redis' => Redis::ping() ? 'ok' : 'error',
            'storage' => Storage::disk()->exists('test') ? 'ok' : 'error',
        ]
    ]);
});
```

### Server Monitoring

```bash
# Install monitoring tools
sudo apt install htop iotop nethogs

# Install custom monitoring script
cat > /usr/local/bin/server-monitor.sh << 'EOF'
#!/bin/bash
while true; do
    echo "=== $(date) ==="
    echo "CPU Usage: $(top -bn1 | grep load | awk '{printf "%.2f%%\t\t\n", $(NF-2)}')"
    echo "Memory Usage: $(free | grep Mem | awk '{printf("%.2f%%"), $3/$2 * 100.0}')"
    echo "Disk Usage: $(df -h / | awk 'NR==2{printf "%s", $5}')"
    echo "Active Connections: $(ss -tuln | wc -l)"
    echo ""
    sleep 300
done
EOF

sudo chmod +x /usr/local/bin/server-monitor.sh
```

## Backup Configuration

### Database Backup

```bash
# Create backup script
cat > /usr/local/bin/db-backup.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/var/backups/mysql"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="proxy_production"

mkdir -p $BACKUP_DIR

# Create backup
mysqldump -u root -p$MYSQL_ROOT_PASSWORD $DB_NAME > $BACKUP_DIR/backup_${DATE}.sql

# Compress backup
gzip $BACKUP_DIR/backup_${DATE}.sql

# Remove backups older than 30 days
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete

echo "Backup completed: backup_${DATE}.sql.gz"
EOF

sudo chmod +x /usr/local/bin/db-backup.sh

# Add to crontab
echo "0 2 * * * /usr/local/bin/db-backup.sh" | sudo crontab -
```

### Application Backup

```bash
# Create application backup script
cat > /usr/local/bin/app-backup.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/var/backups/application"
DATE=$(date +%Y%m%d_%H%M%S)
APP_DIR="/var/www/html"

mkdir -p $BACKUP_DIR

# Create backup
tar -czf $BACKUP_DIR/app_backup_${DATE}.tar.gz \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage/logs' \
    --exclude='storage/cache' \
    $APP_DIR

# Remove backups older than 7 days
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "Application backup completed: app_backup_${DATE}.tar.gz"
EOF

sudo chmod +x /usr/local/bin/app-backup.sh

# Add to crontab
echo "0 1 * * 0 /usr/local/bin/app-backup.sh" | sudo crontab -
```

## Rollback Procedures

### Quick Rollback

```bash
# Using symlinks (if using zero-downtime deployment)
cd /var/www
sudo ln -nfs html-backup html

# Or restore from backup
sudo systemctl stop nginx
sudo systemctl stop php8.3-fpm

# Restore application
sudo rm -rf /var/www/html
sudo tar -xzf /var/backups/application/app_backup_YYYYMMDD_HHMMSS.tar.gz -C /var/www/

# Restore database (if needed)
mysql -u root -p proxy_production < /var/backups/mysql/backup_YYYYMMDD_HHMMSS.sql

# Restart services
sudo systemctl start php8.3-fpm
sudo systemctl start nginx
```

### Database Rollback

```bash
# Rollback migration
php artisan migrate:rollback --step=1

# Or restore from backup
mysql -u root -p proxy_production < backup_file.sql
```

## Troubleshooting

### Common Issues

#### 1. Permission Issues

```bash
# Fix storage permissions
sudo chown -R www-data:www-data /var/www/html/storage
sudo chmod -R 755 /var/www/html/storage

# Fix bootstrap cache permissions
sudo chown -R www-data:www-data /var/www/html/bootstrap/cache
sudo chmod -R 755 /var/www/html/bootstrap/cache
```

#### 2. Database Connection Issues

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check MySQL status
sudo systemctl status mysql
sudo mysql -u root -p

# Check Laravel configuration
php artisan config:show database
```

#### 3. Queue Issues

```bash
# Check queue status
php artisan queue:monitor

# Restart queue workers
sudo systemctl restart laravel-queue

# Clear failed jobs
php artisan queue:flush
```

#### 4. Cache Issues

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Regenerate caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 5. SSL Issues

```bash
# Test SSL certificate
openssl x509 -in /etc/ssl/certs/yourdomain.com.crt -text -noout

# Check certificate expiry
echo | openssl s_client -servername yourdomain.com -connect yourdomain.com:443 2>/dev/null | openssl x509 -noout -dates

# Renew Let's Encrypt certificate
sudo certbot renew --dry-run
```

### Logging and Debugging

```bash
# Laravel logs
tail -f /var/www/html/storage/logs/laravel.log

# Nginx logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log

# PHP-FPM logs
tail -f /var/log/php8.3-fpm.log

# MySQL logs
tail -f /var/log/mysql/error.log
tail -f /var/log/mysql/slow.log

# System logs
journalctl -f -u nginx
journalctl -f -u php8.3-fpm
journalctl -f -u mysql
```

### Performance Troubleshooting

```bash
# Check server resources
htop
iotop
nethogs

# Check MySQL performance
mysql -u root -p -e "SHOW PROCESSLIST;"
mysql -u root -p -e "SHOW STATUS LIKE 'Slow_queries';"

# Check Redis performance
redis-cli info
redis-cli monitor

# Laravel performance debugging
php artisan debugbar:publish  # Development only
```

---

This deployment guide provides comprehensive instructions for deploying the 1000proxy application to production environments. Follow the appropriate section based on your deployment method and infrastructure requirements.
