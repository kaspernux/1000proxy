# Installation Guide

This guide provides detailed instructions for installing the 1000proxy application in different environments.

## System Requirements

### Minimum Requirements

- **PHP**: 8.3 or higher
- **Node.js**: 18.0 or higher
- **MySQL**: 8.0 or higher
- **Redis**: 6.0 or higher
- **Memory**: 2GB RAM minimum
- **Storage**: 10GB available space
- **Web Server**: Nginx or Apache

### Recommended Requirements

- **PHP**: 8.3+ with OPcache enabled
- **Node.js**: 20.x LTS
- **MySQL**: 8.0+ with InnoDB storage engine
- **Redis**: 7.0+ for caching and sessions
- **Memory**: 4GB RAM or more
- **Storage**: 50GB SSD storage
- **Web Server**: Nginx with HTTP/2 support

### Required PHP Extensions

```bash
# Core extensions
php8.3-cli
php8.3-fpm
php8.3-mysql
php8.3-redis
php8.3-curl
php8.3-gd
php8.3-mbstring
php8.3-xml
php8.3-zip
php8.3-intl
php8.3-bcmath
php8.3-opcache
php8.3-json
```

## Installation Methods

### Method 1: Automated Installation (Recommended)

Use the provided installation script for Ubuntu/Debian systems:

```bash
# Download and run the installation script
wget https://raw.githubusercontent.com/your-repo/1000proxy/main/scripts/install.sh
chmod +x install.sh
sudo ./install.sh
```

### Method 2: Manual Installation

#### Step 1: Prepare the Environment

**Ubuntu/Debian:**
```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install PHP 8.3 and extensions
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-redis \
    php8.3-curl php8.3-gd php8.3-mbstring php8.3-xml php8.3-zip \
    php8.3-intl php8.3-bcmath php8.3-opcache

# Install MySQL
sudo apt install -y mysql-server

# Install Redis
sudo apt install -y redis-server

# Install Nginx
sudo apt install -y nginx

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

**CentOS/RHEL:**
```bash
# Install EPEL and Remi repositories
sudo dnf install -y epel-release
sudo dnf install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm

# Enable PHP 8.3 module
sudo dnf module enable php:remi-8.3 -y

# Install PHP and extensions
sudo dnf install -y php php-cli php-fpm php-mysqlnd php-redis php-curl \
    php-gd php-mbstring php-xml php-zip php-intl php-bcmath php-opcache

# Install MySQL
sudo dnf install -y mysql-server

# Install Redis
sudo dnf install -y redis

# Install Nginx
sudo dnf install -y nginx

# Install Node.js
curl -fsSL https://rpm.nodesource.com/setup_20.x | sudo bash -
sudo dnf install -y nodejs

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### Step 2: Configure Services

**MySQL Configuration:**
```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -e "CREATE DATABASE proxy1000_production;"
sudo mysql -e "CREATE USER 'proxy1000_user'@'localhost' IDENTIFIED BY 'your_secure_password';"
sudo mysql -e "GRANT ALL PRIVILEGES ON proxy1000_production.* TO 'proxy1000_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

**Redis Configuration:**
```bash
# Configure Redis for production
sudo nano /etc/redis/redis.conf

# Update these settings:
# maxmemory 256mb
# maxmemory-policy allkeys-lru
# save 900 1
# save 300 10
# save 60 10000

# Restart Redis
sudo systemctl restart redis-server
sudo systemctl enable redis-server
```

**PHP Configuration:**
```bash
# Optimize PHP for production
sudo nano /etc/php/8.3/fpm/php.ini

# Update these settings:
# memory_limit = 256M
# upload_max_filesize = 50M
# post_max_size = 50M
# max_execution_time = 300
# opcache.enable = 1
# opcache.memory_consumption = 128
# opcache.max_accelerated_files = 10000
# opcache.validate_timestamps = 0

# Restart PHP-FPM
sudo systemctl restart php8.3-fpm
sudo systemctl enable php8.3-fpm
```

#### Step 3: Download and Install Application

```bash
# Create application directory
sudo mkdir -p /var/www/1000proxy
cd /var/www/1000proxy

# Clone repository (replace with your repository URL)
sudo git clone https://github.com/your-repo/1000proxy.git .

# Set proper ownership
sudo chown -R www-data:www-data /var/www/1000proxy

# Install PHP dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader

# Install Node.js dependencies
sudo -u www-data npm ci --production

# Build assets
sudo -u www-data npm run build
```

#### Step 4: Environment Configuration

```bash
# Copy environment file
sudo -u www-data cp .env.example .env

# Generate application key
sudo -u www-data php artisan key:generate

# Edit environment configuration
sudo -u www-data nano .env
```

**Environment Variables:**
```env
APP_NAME="1000proxy"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_TIMEZONE=UTC

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=proxy1000_production
DB_USERNAME=proxy1000_user
DB_PASSWORD=your_secure_password

BROADCAST_CONNECTION=log
CACHE_STORE=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-server
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Payment Gateway Configuration
STRIPE_KEY=your_stripe_publishable_key
STRIPE_SECRET=your_stripe_secret_key
STRIPE_WEBHOOK_SECRET=your_stripe_webhook_secret

PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_CLIENT_SECRET=your_paypal_client_secret
PAYPAL_MODE=live

# Cryptocurrency Configuration
BITCOIN_NETWORK=mainnet
ETHEREUM_NETWORK=mainnet
MONERO_NETWORK=mainnet

# 3X-UI Configuration
XUI_DEFAULT_HOST=your-xui-panel-host
XUI_DEFAULT_PORT=2053
XUI_DEFAULT_USERNAME=admin
XUI_DEFAULT_PASSWORD=admin

# Security
SESSION_LIFETIME=120
SANCTUM_EXPIRATION=null
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com
```

#### Step 5: Database Setup

```bash
# Run database migrations
sudo -u www-data php artisan migrate --force

# Run database seeders
sudo -u www-data php artisan db:seed --force

# Create admin user
sudo -u www-data php artisan make:filament-user
```

#### Step 6: Optimize Application

```bash
# Cache configuration
sudo -u www-data php artisan config:cache

# Cache routes
sudo -u www-data php artisan route:cache

# Cache views
sudo -u www-data php artisan view:cache

# Cache events
sudo -u www-data php artisan event:cache

# Optimize Composer autoloader
sudo -u www-data composer dump-autoload --optimize
```

#### Step 7: Set File Permissions

```bash
# Set proper permissions
sudo chown -R www-data:www-data /var/www/1000proxy
sudo chmod -R 755 /var/www/1000proxy
sudo chmod -R 775 /var/www/1000proxy/storage
sudo chmod -R 775 /var/www/1000proxy/bootstrap/cache
```

#### Step 8: Configure Web Server

**Nginx Configuration:**
```bash
# Create Nginx site configuration
sudo nano /etc/nginx/sites-available/1000proxy
```

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/1000proxy/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=general:10m rate=10r/m;
    limit_req_zone $binary_remote_addr zone=api:10m rate=60r/m;
    limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;
    limit_conn_zone $binary_remote_addr zone=addr:10m;

    # Laravel routing
    location / {
        limit_req zone=general burst=20 nodelay;
        limit_conn addr 10;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # API rate limiting
    location /api/ {
        limit_req zone=api burst=50 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Admin panel rate limiting
    location /admin/ {
        limit_req zone=login burst=5 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Security
    location ~ /\.(?!well-known) {
        deny all;
    }

    location ~* /(storage|vendor|node_modules|tests)/.*$ {
        deny all;
    }
}
```

```bash
# Enable the site
sudo ln -s /etc/nginx/sites-available/1000proxy /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl enable nginx
```

#### Step 9: SSL Certificate

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Test auto-renewal
sudo certbot renew --dry-run
```

#### Step 10: Configure Queue Workers

```bash
# Install Supervisor
sudo apt install -y supervisor

# Create queue worker configuration
sudo nano /etc/supervisor/conf.d/1000proxy-worker.conf
```

```ini
[program:1000proxy-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/1000proxy/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
directory=/var/www/1000proxy
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/var/www/1000proxy/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Update Supervisor configuration
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start 1000proxy-worker:*
```

#### Step 11: Configure Cron Jobs

```bash
# Edit crontab for www-data user
sudo crontab -u www-data -e

# Add Laravel scheduler
* * * * * cd /var/www/1000proxy && php artisan schedule:run >> /dev/null 2>&1

# Add system maintenance tasks
0 2 * * * cd /var/www/1000proxy && php artisan maintenance:run
0 3 * * * cd /var/www/1000proxy && php artisan backup:run
```

## Development Installation

### Using Docker (Recommended for Development)

```bash
# Clone repository
git clone https://github.com/your-repo/1000proxy.git
cd 1000proxy

# Copy environment file
cp .env.example .env

# Start Docker containers
docker-compose up -d

# Install dependencies
docker-compose exec app composer install
docker-compose exec app npm install

# Generate application key
docker-compose exec app php artisan key:generate

# Run migrations
docker-compose exec app php artisan migrate

# Run seeders
docker-compose exec app php artisan db:seed
```

### Using Laravel Sail

```bash
# Clone repository
git clone https://github.com/your-repo/1000proxy.git
cd 1000proxy

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Start Sail
./vendor/bin/sail up -d

# Run migrations
./vendor/bin/sail artisan migrate

# Run seeders
./vendor/bin/sail artisan db:seed
```

### Local Development Setup

```bash
# Clone repository
git clone https://github.com/your-repo/1000proxy.git
cd 1000proxy

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create database
mysql -u root -p -e "CREATE DATABASE proxy1000_local;"

# Update .env file with local database credentials
# DB_DATABASE=proxy1000_local
# DB_USERNAME=root
# DB_PASSWORD=your_mysql_password

# Run migrations
php artisan migrate

# Run seeders
php artisan db:seed

# Build assets
npm run dev

# Start development server
php artisan serve
```

## Post-Installation Tasks

### 1. Create Admin User

```bash
# Create admin user via Artisan command
php artisan make:filament-user

# Or create via tinker
php artisan tinker
User::create([
    'name' => 'Admin User',
    'email' => 'admin@yourdomain.com',
    'password' => bcrypt('secure_password'),
    'email_verified_at' => now(),
]);
```

### 2. Configure Application Settings

Access the admin panel at `https://yourdomain.com/admin` and configure:

- **System Settings**: Company information, contact details
- **Payment Gateways**: Enable and configure payment methods
- **Email Settings**: SMTP configuration and templates
- **3X-UI Panels**: Add and configure proxy panels
- **User Roles**: Set up user roles and permissions

### 3. Test Installation

```bash
# Run application tests
php artisan test

# Test queue workers
php artisan queue:work --once

# Test scheduled tasks
php artisan schedule:run

# Check system health
php artisan health:check
```

## Troubleshooting

### Common Issues

**1. Permission Errors**
```bash
sudo chown -R www-data:www-data /var/www/1000proxy
sudo chmod -R 775 /var/www/1000proxy/storage
sudo chmod -R 775 /var/www/1000proxy/bootstrap/cache
```

**2. Composer Memory Limit**
```bash
php -d memory_limit=512M /usr/local/bin/composer install
```

**3. Database Connection Errors**
```bash
# Test database connection
php artisan tinker
DB::connection()->getPdo();
```

**4. Queue Worker Issues**
```bash
# Restart queue workers
sudo supervisorctl restart 1000proxy-worker:*

# Check queue status
php artisan horizon:status
```

### Log Files

Check these log files for troubleshooting:

- Application logs: `/var/www/1000proxy/storage/logs/laravel.log`
- Nginx logs: `/var/log/nginx/error.log`
- PHP-FPM logs: `/var/log/php8.3-fpm.log`
- MySQL logs: `/var/log/mysql/error.log`
- System logs: `journalctl -f`

## Security Considerations

### Initial Security Setup

```bash
# Configure firewall
sudo ufw allow ssh
sudo ufw allow http
sudo ufw allow https
sudo ufw enable

# Install fail2ban
sudo apt install -y fail2ban

# Configure fail2ban for nginx
sudo nano /etc/fail2ban/jail.local
```

### Regular Security Tasks

- Keep system packages updated
- Monitor log files for suspicious activity
- Regularly backup database and application files
- Review user accounts and permissions
- Update application dependencies

## Performance Optimization

### PHP Optimization

```ini
# /etc/php/8.3/fpm/php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.fast_shutdown=1
```

### MySQL Optimization

```ini
# /etc/mysql/mysql.conf.d/mysqld.cnf
innodb_buffer_pool_size=1G
innodb_log_file_size=256M
query_cache_type=1
query_cache_size=64M
```

### Redis Optimization

```ini
# /etc/redis/redis.conf
maxmemory 256mb
maxmemory-policy allkeys-lru
tcp-keepalive 60
```

## Monitoring Setup

### Install Monitoring Tools

```bash
# Install system monitoring
sudo apt install -y htop iotop netstat-nat

# Install log monitoring
sudo apt install -y logwatch

# Configure email alerts for system events
sudo apt install -y mailutils
```

### Application Monitoring

Enable Laravel Horizon for queue monitoring:
```bash
# Start Horizon
php artisan horizon

# Monitor at: https://yourdomain.com/horizon
```

---

This installation guide provides comprehensive instructions for setting up the 1000proxy application in both production and development environments. Follow the appropriate section based on your deployment needs.
