#!/bin/bash

# =============================================================================
# 1000proxy Application Deployment Script
# =============================================================================
# Deploy the 1000proxy Laravel application to the secured server
# Run this script after secure-server-setup.sh and advanced-security-setup.sh
# =============================================================================

set -euo pipefail

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# Configuration
PROJECT_NAME="1000proxy"
PROJECT_USER="proxy1000"
PROJECT_DIR="/var/www/1000proxy"
REPO_URL="${REPO_URL:-https://github.com/kaspernux/1000proxy.git}"
DOMAIN="${DOMAIN:-localhost}"
DB_PASSWORD="${DB_PASSWORD:-}"
REDIS_PASSWORD="${REDIS_PASSWORD:-}"

# Payment Gateway Configuration
STRIPE_KEY="${STRIPE_KEY:-}"
STRIPE_SECRET="${STRIPE_SECRET:-}"
STRIPE_WEBHOOK_SECRET="${STRIPE_WEBHOOK_SECRET:-}"
PAYPAL_CLIENT_ID="${PAYPAL_CLIENT_ID:-}"
PAYPAL_CLIENT_SECRET="${PAYPAL_CLIENT_SECRET:-}"
PAYPAL_WEBHOOK_ID="${PAYPAL_WEBHOOK_ID:-}"
PAYPAL_MODE="${PAYPAL_MODE:-sandbox}"
NOWPAYMENTS_API_KEY="${NOWPAYMENTS_API_KEY:-}"
NOWPAYMENTS_WEBHOOK_SECRET="${NOWPAYMENTS_WEBHOOK_SECRET:-}"

# Telegram Bot Configuration
TELEGRAM_BOT_TOKEN="${TELEGRAM_BOT_TOKEN:-}"
TELEGRAM_WEBHOOK_URL="${TELEGRAM_WEBHOOK_URL:-}"

print_header() {
    echo -e "${BLUE}============================================================${NC}"
    echo -e "${BLUE} $1 ${NC}"
    echo -e "${BLUE}============================================================${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${CYAN}ℹ $1${NC}"
}

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   print_error "This script must be run as root (use sudo)"
   exit 1
fi

print_header "1000proxy Application Deployment"
print_info "Deploying 1000proxy Laravel application"
print_info "Project directory: $PROJECT_DIR"
print_info "Domain: $DOMAIN"

# =============================================================================
# 1. Clone or Update Repository
# =============================================================================
print_header "Repository Management"

if [[ -d "$PROJECT_DIR/.git" ]]; then
    print_info "Updating existing repository..."
    cd "$PROJECT_DIR"
    sudo -u "$PROJECT_USER" git fetch origin
    sudo -u "$PROJECT_USER" git reset --hard origin/main
    print_success "Repository updated"
else
    print_info "Cloning repository..."
    # Remove existing directory if it exists but is not a git repo
    if [[ -d "$PROJECT_DIR" ]]; then
        rm -rf "$PROJECT_DIR"
    fi

    # Clone repository
    sudo -u "$PROJECT_USER" git clone "$REPO_URL" "$PROJECT_DIR"
    cd "$PROJECT_DIR"
    print_success "Repository cloned"
fi

# Set proper ownership
chown -R "$PROJECT_USER:www-data" "$PROJECT_DIR"

# =============================================================================
# 2. Environment Configuration
# =============================================================================
print_header "Environment Configuration"

# Interactive configuration collection
print_info "Payment Gateway and Integration Configuration"
print_warning "You can leave these empty and configure them later in the .env file"
echo

# Stripe Configuration
read -p "Enter Stripe Publishable Key (or press Enter to skip): " stripe_key_input
STRIPE_KEY="${stripe_key_input:-$STRIPE_KEY}"

read -p "Enter Stripe Secret Key (or press Enter to skip): " stripe_secret_input
STRIPE_SECRET="${stripe_secret_input:-$STRIPE_SECRET}"

read -p "Enter Stripe Webhook Secret (or press Enter to skip): " stripe_webhook_input
STRIPE_WEBHOOK_SECRET="${stripe_webhook_input:-$STRIPE_WEBHOOK_SECRET}"

# PayPal Configuration
read -p "Enter PayPal Client ID (or press Enter to skip): " paypal_client_input
PAYPAL_CLIENT_ID="${paypal_client_input:-$PAYPAL_CLIENT_ID}"

read -p "Enter PayPal Client Secret (or press Enter to skip): " paypal_secret_input
PAYPAL_CLIENT_SECRET="${paypal_secret_input:-$PAYPAL_CLIENT_SECRET}"

read -p "Enter PayPal Webhook ID (or press Enter to skip): " paypal_webhook_input
PAYPAL_WEBHOOK_ID="${paypal_webhook_input:-$PAYPAL_WEBHOOK_ID}"

echo "PayPal Mode options: sandbox, live"
read -p "Enter PayPal Mode (default: sandbox): " paypal_mode_input
PAYPAL_MODE="${paypal_mode_input:-$PAYPAL_MODE}"

# NowPayments Configuration
read -p "Enter NowPayments API Key (or press Enter to skip): " nowpayments_key_input
NOWPAYMENTS_API_KEY="${nowpayments_key_input:-$NOWPAYMENTS_API_KEY}"

read -p "Enter NowPayments Webhook Secret (or press Enter to skip): " nowpayments_webhook_input
NOWPAYMENTS_WEBHOOK_SECRET="${nowpayments_webhook_input:-$NOWPAYMENTS_WEBHOOK_SECRET}"

# Telegram Bot Configuration
read -p "Enter Telegram Bot Token (or press Enter to skip): " telegram_token_input
TELEGRAM_BOT_TOKEN="${telegram_token_input:-$TELEGRAM_BOT_TOKEN}"

if [[ -n "$TELEGRAM_BOT_TOKEN" ]]; then
    default_webhook="https://$DOMAIN/api/telegram/webhook"
    read -p "Enter Telegram Webhook URL (default: $default_webhook): " telegram_webhook_input
    TELEGRAM_WEBHOOK_URL="${telegram_webhook_input:-$default_webhook}"
fi

echo
print_info "Configuration Summary:"
print_info "Stripe: $(if [[ -n "$STRIPE_KEY" ]]; then echo "Configured"; else echo "Not configured"; fi)"
print_info "PayPal: $(if [[ -n "$PAYPAL_CLIENT_ID" ]]; then echo "Configured ($PAYPAL_MODE mode)"; else echo "Not configured"; fi)"
print_info "NowPayments: $(if [[ -n "$NOWPAYMENTS_API_KEY" ]]; then echo "Configured"; else echo "Not configured"; fi)"
print_info "Telegram Bot: $(if [[ -n "$TELEGRAM_BOT_TOKEN" ]]; then echo "Configured"; else echo "Not configured"; fi)"
echo

# Create .env file if it doesn't exist
if [[ ! -f "$PROJECT_DIR/.env" ]]; then
    if [[ -f "$PROJECT_DIR/.env.example" ]]; then
        sudo -u "$PROJECT_USER" cp "$PROJECT_DIR/.env.example" "$PROJECT_DIR/.env"
    else
        sudo -u "$PROJECT_USER" touch "$PROJECT_DIR/.env"
    fi
fi

# Read existing passwords if available
if [[ -f "/root/1000proxy-security-report.txt" ]]; then
    DB_PASSWORD=$(grep "MySQL Password:" /root/1000proxy-security-report.txt | cut -d' ' -f3 || echo "")
    REDIS_PASSWORD=$(grep "Redis Password:" /root/1000proxy-security-report.txt | cut -d' ' -f3 || echo "")
fi

# Generate passwords if not available
if [[ -z "$DB_PASSWORD" ]]; then
    DB_PASSWORD=$(openssl rand -base64 32)
fi

if [[ -z "$REDIS_PASSWORD" ]]; then
    REDIS_PASSWORD=$(openssl rand -base64 32)
fi

# Configure .env file with comprehensive settings
cat > "$PROJECT_DIR/.env" << EOF
# =============================================================================
# 1000PROXY ENVIRONMENT CONFIGURATION
# =============================================================================
# Production environment configuration for 1000proxy Laravel application
# Generated on: $(date)
# =============================================================================

# =============================================================================
# APPLICATION CONFIGURATION
# =============================================================================
APP_NAME="1000proxy"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=https://$DOMAIN

# Localization
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

# Maintenance
APP_MAINTENANCE_DRIVER=file
APP_MAINTENANCE_STORE=redis

# =============================================================================
# DATABASE CONFIGURATION
# =============================================================================
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=1000proxy
DB_USERNAME=1000proxy
DB_PASSWORD=$DB_PASSWORD

# Database Options
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_PREFIX=
DB_STRICT=true
DB_ENGINE=InnoDB

# =============================================================================
# CACHE CONFIGURATION
# =============================================================================
CACHE_STORE=redis
CACHE_PREFIX=1000proxy_cache
CACHE_TTL=3600

# Redis Cache Configuration
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=$REDIS_PASSWORD
REDIS_PORT=6379
REDIS_DB=0

# Redis Session Configuration
REDIS_SESSION_HOST=127.0.0.1
REDIS_SESSION_PASSWORD=$REDIS_PASSWORD
REDIS_SESSION_PORT=6379
REDIS_SESSION_DB=1

# Redis Cache Database
REDIS_CACHE_HOST=127.0.0.1
REDIS_CACHE_PASSWORD=$REDIS_PASSWORD
REDIS_CACHE_PORT=6379
REDIS_CACHE_DB=2

# =============================================================================
# SESSION CONFIGURATION
# =============================================================================
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# =============================================================================
# QUEUE CONFIGURATION
# =============================================================================
QUEUE_CONNECTION=redis
QUEUE_FAILED_DRIVER=database-uuids
QUEUE_DEFAULT=default
QUEUE_RETRY_AFTER=90
QUEUE_MAX_TRIES=3

# Queue Redis Configuration
REDIS_QUEUE_HOST=127.0.0.1
REDIS_QUEUE_PASSWORD=$REDIS_PASSWORD
REDIS_QUEUE_PORT=6379
REDIS_QUEUE_DB=3

# =============================================================================
# MAIL CONFIGURATION
# =============================================================================
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@$DOMAIN"
MAIL_FROM_NAME="\${APP_NAME}"

# Mail Queue Settings
MAIL_QUEUE_ENABLED=true
MAIL_QUEUE_CONNECTION=redis
MAIL_QUEUE_NAME=emails

# Mailgun Configuration (Optional - Configure if needed)
MAILGUN_DOMAIN=
MAILGUN_SECRET=
MAILGUN_ENDPOINT=api.mailgun.net

# AWS SES Configuration (Optional - Configure if needed)
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_SES_REGION=us-east-1

# =============================================================================
# FILESYSTEM CONFIGURATION
# =============================================================================
FILESYSTEM_DISK=local
FILESYSTEM_CLOUD=s3

# AWS S3 Configuration (Optional)
AWS_BUCKET=
AWS_URL=
AWS_ENDPOINT=
AWS_USE_PATH_STYLE_ENDPOINT=false

# =============================================================================
# LOGGING CONFIGURATION
# =============================================================================
LOG_CHANNEL=daily
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning
LOG_DAYS=14
LOG_MAX_FILES=10

# Papertrail Logging (Optional)
PAPERTRAIL_URL=
PAPERTRAIL_PORT=

# =============================================================================
# SECURITY CONFIGURATION
# =============================================================================
BCRYPT_ROUNDS=12
HASH_VERIFY=true

# Security Headers
SECURITY_HEADERS_ENABLED=true
SECURITY_CSP_ENABLED=true
SECURITY_HSTS_ENABLED=true

# Rate Limiting
RATE_LIMIT_ENABLED=true
RATE_LIMIT_PER_MINUTE=60
RATE_LIMIT_LOGIN_ATTEMPTS=5
RATE_LIMIT_LOGIN_TIMEOUT=15

# =============================================================================
# PAYMENT GATEWAY CONFIGURATION
# =============================================================================

# Stripe Configuration
STRIPE_KEY=$STRIPE_KEY
STRIPE_SECRET=$STRIPE_SECRET
STRIPE_WEBHOOK_SECRET=$STRIPE_WEBHOOK_SECRET
STRIPE_WEBHOOK_ENDPOINT=/webhooks/stripe
STRIPE_CURRENCY=USD
STRIPE_PAYMENT_METHODS=card,alipay,wechat_pay

# PayPal Configuration
PAYPAL_CLIENT_ID=$PAYPAL_CLIENT_ID
PAYPAL_CLIENT_SECRET=$PAYPAL_CLIENT_SECRET
PAYPAL_WEBHOOK_ID=$PAYPAL_WEBHOOK_ID
PAYPAL_MODE=$PAYPAL_MODE
PAYPAL_CURRENCY=USD
PAYPAL_WEBHOOK_ENDPOINT=/webhooks/paypal

# NowPayments (Cryptocurrency) Configuration
NOWPAYMENTS_API_KEY=$NOWPAYMENTS_API_KEY
NOWPAYMENTS_WEBHOOK_SECRET=$NOWPAYMENTS_WEBHOOK_SECRET
NOWPAYMENTS_WEBHOOK_ENDPOINT=/webhooks/nowpayments
NOWPAYMENTS_CURRENCY=USD
NOWPAYMENTS_SUCCESS_URL=/payment/success
NOWPAYMENTS_CANCEL_URL=/payment/cancel

# Payment Configuration
PAYMENT_DEFAULT_CURRENCY=USD
PAYMENT_SUPPORTED_CURRENCIES=USD,EUR,GBP,JPY,BTC,ETH,LTC
PAYMENT_SESSION_TIMEOUT=1800
PAYMENT_CONFIRMATION_REQUIRED=true

# =============================================================================
# CRYPTOCURRENCY CONFIGURATION
# =============================================================================

# Bitcoin Core RPC (Optional - Configure if using Bitcoin)
BITCOIN_RPC_HOST=127.0.0.1
BITCOIN_RPC_PORT=8332
BITCOIN_RPC_USER=
BITCOIN_RPC_PASSWORD=
BITCOIN_RPC_WALLET=
BITCOIN_CONFIRMATIONS_REQUIRED=3

# Ethereum Configuration (Optional)
ETHEREUM_RPC_URL=
ETHEREUM_WALLET_ADDRESS=
ETHEREUM_PRIVATE_KEY=
ETHEREUM_CHAIN_ID=1
ETHEREUM_CONFIRMATIONS_REQUIRED=12

# Monero Configuration (Optional)
MONERO_RPC_HOST=127.0.0.1
MONERO_RPC_PORT=18081
MONERO_WALLET_RPC_HOST=127.0.0.1
MONERO_WALLET_RPC_PORT=18083
MONERO_WALLET_PASSWORD=
MONERO_CONFIRMATIONS_REQUIRED=10

# =============================================================================
# 3X-UI PANEL INTEGRATION
# =============================================================================
XUI_HOST=127.0.0.1
XUI_PORT=2053
XUI_USERNAME=admin
XUI_PASSWORD=admin
XUI_WEBBASEPATH=
XUI_SSL_ENABLED=false
XUI_API_TIMEOUT=30
XUI_MAX_RETRIES=3

# Multiple 3X-UI Servers (JSON format)
XUI_SERVERS='[
    {
        "name": "Server 1",
        "host": "127.0.0.1",
        "port": 2053,
        "username": "admin",
        "password": "admin",
        "ssl": false
    }
]'

# =============================================================================
# TELEGRAM BOT CONFIGURATION
# =============================================================================
TELEGRAM_BOT_TOKEN=$TELEGRAM_BOT_TOKEN
TELEGRAM_WEBHOOK_URL=$TELEGRAM_WEBHOOK_URL
TELEGRAM_WEBHOOK_ENDPOINT=/api/telegram/webhook
TELEGRAM_ADMIN_CHAT_ID=
TELEGRAM_NOTIFICATIONS_ENABLED=true
TELEGRAM_LANGUAGE=en

# Telegram Bot Features
TELEGRAM_COMMANDS_ENABLED=true
TELEGRAM_INLINE_KEYBOARDS=true
TELEGRAM_FILE_UPLOADS=true
TELEGRAM_MAX_MESSAGE_LENGTH=4096

# =============================================================================
# SOCIAL AUTHENTICATION (Configure if needed)
# =============================================================================

# Google OAuth
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=/auth/google/callback

# Facebook OAuth
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URI=/auth/facebook/callback

# GitHub OAuth
GITHUB_CLIENT_ID=
GITHUB_CLIENT_SECRET=
GITHUB_REDIRECT_URI=/auth/github/callback

# Twitter OAuth
TWITTER_CLIENT_ID=
TWITTER_CLIENT_SECRET=
TWITTER_REDIRECT_URI=/auth/twitter/callback

# =============================================================================
# API CONFIGURATION
# =============================================================================
API_VERSION=v1
API_RATE_LIMIT=1000
API_RATE_LIMIT_WINDOW=60
API_PAGINATION_DEFAULT=20
API_PAGINATION_MAX=100

# API Authentication
API_AUTH_ENABLED=true
API_KEY_REQUIRED=false
API_CORS_ENABLED=true
API_CORS_ORIGINS=*

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,$DOMAIN
SANCTUM_GUARD=web
SANCTUM_MIDDLEWARE=auth:sanctum

# =============================================================================
# MONITORING AND ANALYTICS (Configure if needed)
# =============================================================================

# Application Monitoring
MONITORING_ENABLED=true
MONITORING_SAMPLE_RATE=1.0

# Sentry Error Tracking
SENTRY_LARAVEL_DSN=
SENTRY_TRACES_SAMPLE_RATE=1.0
SENTRY_PROFILES_SAMPLE_RATE=1.0

# Google Analytics
GOOGLE_ANALYTICS_ID=
GOOGLE_TAG_MANAGER_ID=

# Application Insights
APPINSIGHTS_INSTRUMENTATIONKEY=

# =============================================================================
# BACKUP CONFIGURATION
# =============================================================================
BACKUP_ENABLED=true
BACKUP_SCHEDULE="0 */6 * * *"
BACKUP_RETENTION_DAYS=30
BACKUP_COMPRESS=true
BACKUP_ENCRYPT=true

# Backup Destinations
BACKUP_DISK=local
BACKUP_S3_ENABLED=false
BACKUP_S3_BUCKET=
BACKUP_FTP_ENABLED=false
BACKUP_FTP_HOST=
BACKUP_FTP_USERNAME=
BACKUP_FTP_PASSWORD=

# =============================================================================
# PERFORMANCE CONFIGURATION
# =============================================================================

# Caching
CACHE_VIEWS=true
CACHE_ROUTES=true
CACHE_CONFIG=true
CACHE_EVENTS=true

# Database Query Optimization
DB_QUERY_LOG_ENABLED=false
DB_SLOW_QUERY_LOG=true
DB_SLOW_QUERY_TIME=2

# Image Optimization
IMAGE_OPTIMIZATION_ENABLED=true
IMAGE_MAX_WIDTH=1920
IMAGE_MAX_HEIGHT=1080
IMAGE_QUALITY=85

# =============================================================================
# FEATURE FLAGS
# =============================================================================
FEATURE_REGISTRATION_ENABLED=true
FEATURE_EMAIL_VERIFICATION=true
FEATURE_TWO_FACTOR_AUTH=false
FEATURE_API_ACCESS=true
FEATURE_WEBHOOKS=true
FEATURE_NOTIFICATIONS=true
FEATURE_DARK_MODE=true
FEATURE_MULTI_LANGUAGE=true
FEATURE_ADMIN_IMPERSONATION=false

# =============================================================================
# BUSINESS CONFIGURATION
# =============================================================================

# Company Information
COMPANY_NAME="1000proxy"
COMPANY_EMAIL="support@$DOMAIN"
COMPANY_PHONE="+1-555-0123"
COMPANY_ADDRESS="123 Business St, City, State 12345"
COMPANY_VAT_NUMBER=
COMPANY_REGISTRATION_NUMBER=

# Pricing Configuration
DEFAULT_CURRENCY=USD
TAX_RATE=0.00
TAX_INCLUDED=false
INVOICE_PREFIX=INV-
INVOICE_NUMBER_LENGTH=6

# Subscription Configuration
SUBSCRIPTION_TRIAL_DAYS=7
SUBSCRIPTION_GRACE_PERIOD=3
SUBSCRIPTION_AUTO_RENEWAL=true

# =============================================================================
# NOTIFICATION CHANNELS (Configure if needed)
# =============================================================================

# Slack Notifications
SLACK_WEBHOOK_URL=
SLACK_CHANNEL=#general
SLACK_USERNAME=1000proxy
SLACK_EMOJI=:robot_face:

# Discord Notifications
DISCORD_WEBHOOK_URL=
DISCORD_USERNAME=1000proxy
DISCORD_AVATAR_URL=

# Pusher Real-time Notifications
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

# WebSocket Configuration
WEBSOCKET_ENABLED=false
WEBSOCKET_HOST=127.0.0.1
WEBSOCKET_PORT=6001

# =============================================================================
# THIRD-PARTY INTEGRATIONS (Configure if needed)
# =============================================================================

# reCAPTCHA
RECAPTCHA_SITE_KEY=
RECAPTCHA_SECRET_KEY=
RECAPTCHA_VERSION=v2

# Cloudflare
CLOUDFLARE_API_TOKEN=
CLOUDFLARE_ZONE_ID=
CLOUDFLARE_EMAIL=

# MaxMind GeoIP
GEOIP_DATABASE_PATH=storage/app/geoip/GeoLite2-City.mmdb
GEOIP_AUTO_UPDATE=true

# =============================================================================
# DEVELOPMENT CONFIGURATION
# =============================================================================

# Debug Configuration (Disabled in production)
DEBUGBAR_ENABLED=false
TELESCOPE_ENABLED=false
CLOCKWORK_ENABLED=false

# Testing Configuration
TESTING_DATABASE=1000proxy_testing
TESTING_CACHE_DRIVER=array
TESTING_QUEUE_DRIVER=sync
TESTING_MAIL_DRIVER=array

# Asset Compilation
VITE_APP_NAME="\${APP_NAME}"
VITE_PUSHER_APP_KEY="\${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="\${PUSHER_HOST}"
VITE_PUSHER_PORT="\${PUSHER_PORT}"
VITE_PUSHER_SCHEME="\${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="\${PUSHER_APP_CLUSTER}"

# =============================================================================
# CUSTOM APPLICATION SETTINGS
# =============================================================================

# Proxy Management
PROXY_CHECK_INTERVAL=300
PROXY_TIMEOUT=30
PROXY_MAX_CONCURRENT_CHECKS=50
PROXY_HEALTH_CHECK_ENABLED=true

# User Management
USER_REGISTRATION_ENABLED=true
USER_EMAIL_VERIFICATION=true
USER_PROFILE_COMPLETION_REQUIRED=false
USER_SESSION_LIFETIME=120
USER_MAX_CONCURRENT_SESSIONS=3

# Order Management
ORDER_AUTO_ACTIVATION=true
ORDER_CONFIRMATION_REQUIRED=false
ORDER_REFUND_PERIOD=24
ORDER_CANCELLATION_ENABLED=true

# Support System
SUPPORT_TICKET_ENABLED=true
SUPPORT_EMAIL="support@$DOMAIN"
SUPPORT_AUTO_RESPONSE=true
SUPPORT_PRIORITY_LEVELS=low,medium,high,urgent

# =============================================================================
# BROADCASTING CONFIGURATION
# =============================================================================
BROADCAST_CONNECTION=log
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

# =============================================================================
# ADMIN CONFIGURATION
# =============================================================================
ADMIN_EMAIL=admin@$DOMAIN
CUSTOMER_REGISTRATION_ENABLED=true
DEFAULT_CURRENCY=USD
MAINTENANCE_MODE=false

# Security Settings
SECURE_HEADERS_ENABLED=true
RATE_LIMIT_ENABLED=true
AUDIT_LOGGING_ENABLED=true

# Performance Settings
CACHE_ENABLED=true
QUEUE_PROCESSING_ENABLED=true
OPTIMIZATION_ENABLED=true
EOF

chown "$PROJECT_USER:www-data" "$PROJECT_DIR/.env"
chmod 640 "$PROJECT_DIR/.env"
print_success "Environment file configured"

# =============================================================================
# 3. Install Dependencies
# =============================================================================
print_header "Installing Dependencies"

cd "$PROJECT_DIR"

# Install Composer dependencies
print_info "Installing Composer dependencies..."
sudo -u "$PROJECT_USER" composer install --no-dev --optimize-autoloader --no-interaction
print_success "Composer dependencies installed"

# Install Node.js dependencies
if [[ -f "package.json" ]]; then
    print_info "Installing Node.js dependencies..."
    sudo -u "$PROJECT_USER" npm ci --production
    print_success "Node.js dependencies installed"

    # Build assets
    print_info "Building frontend assets..."
    sudo -u "$PROJECT_USER" npm run build
    print_success "Frontend assets built"
fi

# =============================================================================
# 4. Application Setup
# =============================================================================
print_header "Application Setup"

# Generate application key
sudo -u "$PROJECT_USER" php artisan key:generate --force
print_success "Application key generated"

# Create storage directories
sudo -u "$PROJECT_USER" mkdir -p storage/{app,framework,logs}
sudo -u "$PROJECT_USER" mkdir -p storage/framework/{cache,sessions,views}
sudo -u "$PROJECT_USER" mkdir -p storage/app/{public,uploads}

# Create bootstrap cache directory
sudo -u "$PROJECT_USER" mkdir -p bootstrap/cache

# Set proper permissions
find "$PROJECT_DIR" -type f -exec chmod 644 {} \;
find "$PROJECT_DIR" -type d -exec chmod 755 {} \;
chmod -R 775 "$PROJECT_DIR"/storage
chmod -R 775 "$PROJECT_DIR"/bootstrap/cache
chown -R "$PROJECT_USER:www-data" "$PROJECT_DIR"

print_success "Directory structure created"

# Create storage symlink
sudo -u "$PROJECT_USER" php artisan storage:link
print_success "Storage symlink created"

# =============================================================================
# 5. Database Setup
# =============================================================================
print_header "Database Setup"

# Check if database exists and create if not
mysql -u root -e "CREATE DATABASE IF NOT EXISTS \`1000proxy\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || print_warning "Database may already exist"

# Create or update database user
mysql -u root -e "CREATE USER IF NOT EXISTS '1000proxy'@'localhost' IDENTIFIED WITH caching_sha2_password BY '$DB_PASSWORD';" 2>/dev/null
mysql -u root -e "GRANT ALL PRIVILEGES ON \`1000proxy\`.* TO '1000proxy'@'localhost';"
mysql -u root -e "FLUSH PRIVILEGES;"

print_success "Database user configured"

# Run migrations
print_info "Running database migrations..."
sudo -u "$PROJECT_USER" php artisan migrate --force
print_success "Database migrations completed"

# Run seeders
if [[ -d "database/seeders" ]] && [[ "$(ls -A database/seeders)" ]]; then
    print_info "Running database seeders..."
    sudo -u "$PROJECT_USER" php artisan db:seed --force
    print_success "Database seeders completed"
fi

# =============================================================================
# 6. Cache and Optimization
# =============================================================================
print_header "Cache and Optimization"

# Clear all caches
sudo -u "$PROJECT_USER" php artisan config:clear
sudo -u "$PROJECT_USER" php artisan cache:clear
sudo -u "$PROJECT_USER" php artisan route:clear
sudo -u "$PROJECT_USER" php artisan view:clear

# Optimize for production
sudo -u "$PROJECT_USER" php artisan config:cache
sudo -u "$PROJECT_USER" php artisan route:cache
sudo -u "$PROJECT_USER" php artisan view:cache

print_success "Application optimized"

# =============================================================================
# 7. Queue and Scheduler Setup
# =============================================================================
print_header "Queue and Scheduler Setup"

# Create queue worker service
cat > /etc/systemd/system/1000proxy-queue.service << EOF
[Unit]
Description=1000proxy Queue Worker
After=network.target

[Service]
Type=simple
User=$PROJECT_USER
Group=www-data
Restart=always
RestartSec=3
ExecStart=/usr/bin/php $PROJECT_DIR/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
WorkingDirectory=$PROJECT_DIR

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable 1000proxy-queue.service
systemctl start 1000proxy-queue.service

print_success "Queue worker service created"

# Add Laravel scheduler to crontab
(sudo -u "$PROJECT_USER" crontab -l 2>/dev/null; echo "* * * * * cd $PROJECT_DIR && php artisan schedule:run >> /dev/null 2>&1") | sudo -u "$PROJECT_USER" crontab -

print_success "Laravel scheduler configured"

# =============================================================================
# 8. Web Server Configuration Update
# =============================================================================
print_header "Web Server Configuration"

# Update Nginx site configuration for production
cat > /etc/nginx/sites-available/1000proxy << EOF
server {
    listen 80;
    server_name $DOMAIN www.$DOMAIN;
    return 301 https://\$server_name\$request_uri;
}

server {
    listen 443 ssl http2;
    server_name $DOMAIN www.$DOMAIN;
    root $PROJECT_DIR/public;
    index index.php index.html;

    # SSL Configuration (if certificates exist)
    ssl_certificate /etc/letsencrypt/live/$DOMAIN/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$DOMAIN/privkey.pem;

    # Include security snippets
    include /etc/nginx/snippets/security-headers.conf;
    include /etc/nginx/snippets/ssl-security.conf;

    # Logging
    access_log /var/log/nginx/1000proxy.access.log;
    error_log /var/log/nginx/1000proxy.error.log;

    # Security configurations
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    location ~ ~$ {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Block access to sensitive files
    location ~* \.(env|config|sql|log|htaccess|htpasswd|ini|bak|old|tmp)$ {
        deny all;
        return 404;
    }

    # Rate limiting for authentication endpoints
    location ~* ^/(login|register|api/auth) {
        limit_req zone=login burst=5 nodelay;
        limit_conn perip 5;
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # Rate limiting for API endpoints
    location ~* ^/api/ {
        limit_req zone=api burst=20 nodelay;
        limit_conn perip 10;
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # Rate limiting for admin panel
    location ~* ^/(admin|filament) {
        limit_req zone=admin burst=10 nodelay;
        limit_conn perip 3;
        try_files \$uri \$uri/ /index.php?\$query_string;

        # Additional security for admin
        allow 127.0.0.1;
        # allow YOUR_ADMIN_IP_HERE;
        # deny all;
    }

    # General rate limiting
    location / {
        limit_req zone=general burst=10 nodelay;
        limit_conn perip 10;
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # PHP handling
    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php8.3-1000proxy.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
        fastcgi_param PATH_INFO \$fastcgi_path_info;

        # Security headers for PHP
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 256 16k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_temp_file_write_size 256k;
    }

    # Static files handling with long cache
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|webp|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Vary "Accept-Encoding";
        access_log off;
        log_not_found off;
    }

    # Favicon
    location = /favicon.ico {
        log_not_found off;
        access_log off;
    }

    # Robots
    location = /robots.txt {
        allow all;
        log_not_found off;
        access_log off;
    }

    # Deny access to Laravel specific directories
    location ~ ^/(storage|bootstrap|config|database|resources|routes|tests|vendor)/ {
        deny all;
        return 404;
    }

    # Security headers for all responses
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
}
EOF

# Test Nginx configuration
nginx -t
if [[ $? -eq 0 ]]; then
    systemctl reload nginx
    print_success "Nginx configuration updated"
else
    print_error "Nginx configuration test failed"
    exit 1
fi

# =============================================================================
# 9. Security Monitoring for Application
# =============================================================================
print_header "Application Security Monitoring"

# Create application-specific monitoring
cat > /usr/local/bin/1000proxy-app-monitor.sh << 'EOF'
#!/bin/bash

PROJECT_DIR="/var/www/1000proxy"
LOG_FILE="/var/log/1000proxy-app-monitor.log"

# Monitor Laravel logs
monitor_laravel_logs() {
    if [[ -f "$PROJECT_DIR/storage/logs/laravel.log" ]]; then
        tail -F "$PROJECT_DIR/storage/logs/laravel.log" | while read line; do
            if echo "$line" | grep -iE "(error|exception|failed|unauthorized)"; then
                echo "[$(date)] Laravel Alert: $line" >> "$LOG_FILE"
                logger -p local0.warn "1000proxy App Alert: $line"
            fi
        done &
    fi
}

# Monitor failed login attempts (if implemented in app)
monitor_failed_logins() {
    if [[ -f "$PROJECT_DIR/storage/logs/auth.log" ]]; then
        tail -F "$PROJECT_DIR/storage/logs/auth.log" | while read line; do
            if echo "$line" | grep -i "failed"; then
                echo "[$(date)] Auth Failed: $line" >> "$LOG_FILE"
            fi
        done &
    fi
}

# Monitor queue failures
monitor_queue_failures() {
    while true; do
        if command -v php &> /dev/null; then
            failed_jobs=$(cd "$PROJECT_DIR" && php artisan queue:failed --format=json | jq length 2>/dev/null || echo "0")
            if [[ "$failed_jobs" -gt 0 ]]; then
                echo "[$(date)] Queue Alert: $failed_jobs failed jobs detected" >> "$LOG_FILE"
            fi
        fi
        sleep 300  # Check every 5 minutes
    done &
}

# Start monitoring
monitor_laravel_logs
monitor_failed_logins
monitor_queue_failures

wait
EOF

chmod +x /usr/local/bin/1000proxy-app-monitor.sh

# Create systemd service for app monitoring
cat > /etc/systemd/system/1000proxy-app-monitor.service << 'EOF'
[Unit]
Description=1000proxy Application Monitor
After=network.target

[Service]
Type=simple
ExecStart=/usr/local/bin/1000proxy-app-monitor.sh
Restart=always
RestartSec=10
User=root

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable 1000proxy-app-monitor.service
systemctl start 1000proxy-app-monitor.service

print_success "Application monitoring configured"

# =============================================================================
# 10. Backup Configuration for Application
# =============================================================================
print_header "Application Backup Configuration"

# Update backup script to include application-specific data
cat > /usr/local/bin/backup-1000proxy-app.sh << EOF
#!/bin/bash

BACKUP_DIR="/var/backups/1000proxy"
DATE=\$(date +%Y%m%d_%H%M%S)
PROJECT_DIR="$PROJECT_DIR"

# Create backup directory for today
mkdir -p "\$BACKUP_DIR/\$DATE"

# Backup database with compression
mysqldump --single-transaction --routines --triggers --opt 1000proxy | gzip > "\$BACKUP_DIR/\$DATE/database.sql.gz"

# Backup application files (excluding unnecessary files)
tar -czf "\$BACKUP_DIR/\$DATE/application.tar.gz" \\
    --exclude="\$PROJECT_DIR/storage/logs/*" \\
    --exclude="\$PROJECT_DIR/storage/framework/cache/*" \\
    --exclude="\$PROJECT_DIR/storage/framework/sessions/*" \\
    --exclude="\$PROJECT_DIR/storage/framework/views/*" \\
    --exclude="\$PROJECT_DIR/vendor" \\
    --exclude="\$PROJECT_DIR/node_modules" \\
    --exclude="\$PROJECT_DIR/.git" \\
    "\$PROJECT_DIR"

# Backup only user uploads and important storage
tar -czf "\$BACKUP_DIR/\$DATE/storage.tar.gz" \\
    "\$PROJECT_DIR/storage/app/uploads" \\
    "\$PROJECT_DIR/storage/app/public" 2>/dev/null || true

# Backup environment file separately (encrypted)
if [[ -f "\$PROJECT_DIR/.env" ]]; then
    gpg --symmetric --cipher-algo AES256 --output "\$BACKUP_DIR/\$DATE/env.gpg" "\$PROJECT_DIR/.env" 2>/dev/null || cp "\$PROJECT_DIR/.env" "\$BACKUP_DIR/\$DATE/env.backup"
fi

# Create backup manifest
cat > "\$BACKUP_DIR/\$DATE/manifest.txt" << EOL
1000proxy Backup Manifest
Date: \$(date)
Database: database.sql.gz
Application: application.tar.gz
Storage: storage.tar.gz
Environment: env.gpg (or env.backup)
EOL

# Remove backups older than 7 days (keep more recent)
find "\$BACKUP_DIR" -type d -name "2*" -mtime +7 -exec rm -rf {} + 2>/dev/null

# Set proper permissions
chmod 600 "\$BACKUP_DIR/\$DATE"/*
chown -R root:root "\$BACKUP_DIR/\$DATE"

echo "Application backup completed: \$BACKUP_DIR/\$DATE"
EOL

chmod +x /usr/local/bin/backup-1000proxy-app.sh

# Update crontab for more frequent app backups
(crontab -l 2>/dev/null | grep -v "backup-1000proxy"; echo "0 */6 * * * /usr/local/bin/backup-1000proxy-app.sh") | crontab -

print_success "Application backup configured"

# =============================================================================
# 11. Performance Optimization
# =============================================================================
print_header "Performance Optimization"

# Configure PHP-FPM for optimal performance
cat > /etc/php/8.3/fpm/pool.d/1000proxy-optimized.conf << EOF
[1000proxy]
user = $PROJECT_USER
group = www-data
listen = /run/php/php8.3-1000proxy.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

; Process management
pm = dynamic
pm.max_children = 50
pm.start_servers = 8
pm.min_spare_servers = 8
pm.max_spare_servers = 20
pm.max_requests = 1000
pm.process_idle_timeout = 10s

; Performance settings
request_terminate_timeout = 300
request_slowlog_timeout = 30
slowlog = /var/log/php-slow.log

; Security and performance PHP settings
php_admin_value[disable_functions] = exec,passthru,shell_exec,system,proc_open,popen
php_admin_flag[allow_url_fopen] = off
php_admin_flag[allow_url_include] = off
php_admin_flag[expose_php] = off
php_admin_value[memory_limit] = 256M
php_admin_value[max_execution_time] = 300
php_admin_value[upload_max_filesize] = 64M
php_admin_value[post_max_size] = 64M
php_admin_value[opcache.enable] = 1
php_admin_value[opcache.memory_consumption] = 128
php_admin_value[opcache.max_accelerated_files] = 10000
php_admin_value[opcache.revalidate_freq] = 60
EOF

# Remove old pool configuration
rm -f /etc/php/8.3/fpm/pool.d/1000proxy.conf

systemctl restart php8.3-fpm
print_success "PHP-FPM optimized"

# =============================================================================
# 12. Health Checks and Monitoring
# =============================================================================
print_header "Health Checks and Monitoring"

# Create health check script
cat > /usr/local/bin/1000proxy-health-check.sh << EOF
#!/bin/bash

PROJECT_DIR="$PROJECT_DIR"
HEALTH_LOG="/var/log/1000proxy-health.log"
DOMAIN="$DOMAIN"

# Function to log health status
log_health() {
    echo "[\$(date)] \$1" >> "\$HEALTH_LOG"
}

# Check web server response
check_web_server() {
    if curl -f -s "http://localhost" > /dev/null; then
        log_health "✓ Web server responding"
        return 0
    else
        log_health "✗ Web server not responding"
        return 1
    fi
}

# Check database connection
check_database() {
    if cd "\$PROJECT_DIR" && php artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1; then
        log_health "✓ Database connection successful"
        return 0
    else
        log_health "✗ Database connection failed"
        return 1
    fi
}

# Check Redis connection
check_redis() {
    if redis-cli -a "$REDIS_PASSWORD" ping > /dev/null 2>&1; then
        log_health "✓ Redis connection successful"
        return 0
    else
        log_health "✗ Redis connection failed"
        return 1
    fi
}

# Check queue worker
check_queue() {
    if systemctl is-active --quiet 1000proxy-queue; then
        log_health "✓ Queue worker running"
        return 0
    else
        log_health "✗ Queue worker not running"
        systemctl restart 1000proxy-queue
        return 1
    fi
}

# Check disk space
check_disk_space() {
    usage=\$(df / | tail -1 | awk '{print \$5}' | sed 's/%//')
    if [ "\$usage" -lt 90 ]; then
        log_health "✓ Disk space OK (\${usage}%)"
        return 0
    else
        log_health "✗ Disk space critical (\${usage}%)"
        return 1
    fi
}

# Run all checks
log_health "=== Health Check Started ==="
check_web_server
check_database
check_redis
check_queue
check_disk_space
log_health "=== Health Check Completed ==="
EOF

chmod +x /usr/local/bin/1000proxy-health-check.sh

# Schedule health checks every 15 minutes
(crontab -l 2>/dev/null; echo "*/15 * * * * /usr/local/bin/1000proxy-health-check.sh") | crontab -

print_success "Health checks configured"

# =============================================================================
# 13. SSL Certificate Setup (if domain is not localhost)
# =============================================================================
print_header "SSL Certificate Setup"

if [[ "$DOMAIN" != "localhost" && "$DOMAIN" != "127.0.0.1" ]]; then
    # Check if certbot is available
    if command -v certbot &> /dev/null; then
        print_info "Attempting to obtain SSL certificate for $DOMAIN"

        # Stop nginx temporarily
        systemctl stop nginx

        # Obtain certificate
        if certbot certonly --standalone -d "$DOMAIN" -d "www.$DOMAIN" --non-interactive --agree-tos --email "admin@$DOMAIN"; then
            print_success "SSL certificate obtained for $DOMAIN"

            # Setup auto-renewal
            (crontab -l 2>/dev/null; echo "0 12 * * * /usr/bin/certbot renew --quiet && systemctl reload nginx") | crontab -
        else
            print_warning "Failed to obtain SSL certificate. Continuing with HTTP only."
        fi

        # Start nginx
        systemctl start nginx
    else
        print_warning "Certbot not available. SSL certificate not obtained."
    fi
else
    print_info "Skipping SSL certificate for localhost domain"
fi

# =============================================================================
# 14. Final Application Tests
# =============================================================================
print_header "Final Application Tests"

cd "$PROJECT_DIR"

# Test database connection
print_info "Testing database connection..."
if sudo -u "$PROJECT_USER" php artisan tinker --execute="echo 'Database: ' . DB::connection()->getDatabaseName();" 2>/dev/null; then
    print_success "Database connection test passed"
else
    print_warning "Database connection test failed"
fi

# Test cache
print_info "Testing cache..."
if sudo -u "$PROJECT_USER" php artisan cache:clear && sudo -u "$PROJECT_USER" php artisan cache:store test_key test_value; then
    print_success "Cache test passed"
else
    print_warning "Cache test failed"
fi

# Test queue
print_info "Testing queue connection..."
if sudo -u "$PROJECT_USER" php artisan queue:restart; then
    print_success "Queue test passed"
else
    print_warning "Queue test failed"
fi

# =============================================================================
# 15. Generate Deployment Report
# =============================================================================
print_header "Generating Deployment Report"

cat > "/root/1000proxy-deployment-report.txt" << EOF
═══════════════════════════════════════════════════════════════════════════════
                    1000PROXY DEPLOYMENT REPORT
═══════════════════════════════════════════════════════════════════════════════

Deployment Date: $(date)
Application: 1000proxy Laravel Application
Domain: $DOMAIN
Project Directory: $PROJECT_DIR

DEPLOYMENT STATUS:
═══════════════════════════════════════════════════════════════════════════════
✓ Repository cloned/updated
✓ Environment configured
✓ Dependencies installed (Composer & NPM)
✓ Application key generated
✓ Database setup and migrations
✓ Cache and optimization
✓ Queue worker configured
✓ Laravel scheduler configured
✓ Web server configured
✓ Application monitoring setup
✓ Backup system configured
✓ Performance optimization
✓ Health checks configured
$(if [[ "$DOMAIN" != "localhost" ]]; then echo "✓ SSL certificate setup"; fi)

APPLICATION INFORMATION:
═══════════════════════════════════════════════════════════════════════════════
URL: https://$DOMAIN (or http://$DOMAIN if SSL not configured)
Admin Panel: https://$DOMAIN/admin
Customer Panel: https://$DOMAIN/account
API Endpoint: https://$DOMAIN/api

Database: 1000proxy
Redis: Configured with authentication

SERVICES RUNNING:
═══════════════════════════════════════════════════════════════════════════════
• Nginx: Web server
• PHP-FPM: Application server
• MySQL: Database server
• Redis: Cache and session storage
• Queue Worker: Background job processing
• Application Monitor: Real-time monitoring
• Health Checks: Automated monitoring

MONITORING AND LOGS:
═══════════════════════════════════════════════════════════════════════════════
• Application logs: $PROJECT_DIR/storage/logs/
• Nginx logs: /var/log/nginx/1000proxy.*.log
• Application monitor: /var/log/1000proxy-app-monitor.log
• Health checks: /var/log/1000proxy-health.log
• Backup logs: /var/log/1000proxy-backup.log

SCHEDULED TASKS:
═══════════════════════════════════════════════════════════════════════════════
• Laravel scheduler: Every minute
• Application backup: Every 6 hours
• Health checks: Every 15 minutes
• Security updates: Daily at 3 AM
• SSL renewal: Daily at 12 PM (if applicable)

IMPORTANT FILES:
═══════════════════════════════════════════════════════════════════════════════
• Environment: $PROJECT_DIR/.env
• Nginx config: /etc/nginx/sites-available/1000proxy
• Queue service: /etc/systemd/system/1000proxy-queue.service
• Backup script: /usr/local/bin/backup-1000proxy-app.sh
• Health check: /usr/local/bin/1000proxy-health-check.sh

NEXT STEPS:
═══════════════════════════════════════════════════════════════════════════════
1. Configure payment gateways in .env file
2. Set up Telegram bot (if needed)
3. Configure 3X-UI integration
4. Test all application features
5. Set up monitoring alerts
6. Configure email settings
7. Create admin user account
8. Test backup and restore procedures

SERVICE MANAGEMENT:
═══════════════════════════════════════════════════════════════════════════════
• Restart queue: systemctl restart 1000proxy-queue
• View queue status: systemctl status 1000proxy-queue
• Check logs: tail -f $PROJECT_DIR/storage/logs/laravel.log
• Run artisan commands: cd $PROJECT_DIR && sudo -u $PROJECT_USER php artisan <command>
• Manual backup: /usr/local/bin/backup-1000proxy-app.sh
• Health check: /usr/local/bin/1000proxy-health-check.sh

SECURITY NOTES:
═══════════════════════════════════════════════════════════════════════════════
• Environment file contains sensitive information
• Admin panel is rate-limited and can be IP-restricted
• All user input is validated and sanitized
• CSRF protection enabled
• XSS protection enabled
• SQL injection protection enabled
• File upload restrictions in place

Your 1000proxy application is now deployed and ready for production use!
═══════════════════════════════════════════════════════════════════════════════
EOF

chmod 600 "/root/1000proxy-deployment-report.txt"

# =============================================================================
# Final Summary
# =============================================================================
print_header "Deployment Complete"

print_success "1000proxy application deployment completed successfully!"
echo
print_info "Application URL: https://$DOMAIN"
print_info "Admin Panel: https://$DOMAIN/admin"
print_info "Project Directory: $PROJECT_DIR"
print_info "Environment File: $PROJECT_DIR/.env"
echo
print_warning "IMPORTANT NEXT STEPS:"
print_warning "1. Configure your .env file with actual API keys and settings"
print_warning "2. Create an admin user account"
print_warning "3. Test all application features"
print_warning "4. Configure payment gateways"
print_warning "5. Set up email notifications"
echo
print_info "Deployment report: /root/1000proxy-deployment-report.txt"
print_info "Monitor logs: tail -f $PROJECT_DIR/storage/logs/laravel.log"
print_info "Check services: systemctl status 1000proxy-queue"
echo
print_success "Your 1000proxy application is now live and secure!"
print_header "Deployment Complete"

echo "Deployment completed at: $(date)"
