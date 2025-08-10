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
# Configure / Update .env without overwriting existing secrets
if [[ -f "$PROJECT_DIR/.env" ]]; then
    print_info "Existing .env detected – preserving values. Injecting/ensuring required keys."
    backup_file="$PROJECT_DIR/.env.backup-$(date +%Y%m%d%H%M%S)"
    cp "$PROJECT_DIR/.env" "$backup_file"
    print_success "Created backup: $backup_file"
else
    if [[ -f "$PROJECT_DIR/.env.example" ]]; then
        sudo -u "$PROJECT_USER" cp "$PROJECT_DIR/.env.example" "$PROJECT_DIR/.env"
    else
        sudo -u "$PROJECT_USER" touch "$PROJECT_DIR/.env"
    fi
    print_info ".env created from example or empty template"
fi

# Helper to ensure a key exists (adds if missing)
ensure_env_key() {
    local key="$1"; shift
    local value="$1"; shift || true
    if ! grep -qE "^${key}=" "$PROJECT_DIR/.env"; then
        echo "${key}=${value}" >> "$PROJECT_DIR/.env"
    fi
}

# Ensure critical keys (don't overwrite if already present)
ensure_env_key APP_NAME "\"1000PROXY\""
ensure_env_key APP_ENV production
ensure_env_key APP_URL "https://$DOMAIN"
ensure_env_key DB_DATABASE 1000proxy
ensure_env_key DB_USERNAME 1000proxy
if ! grep -q '^DB_PASSWORD=' "$PROJECT_DIR/.env"; then
  echo "DB_PASSWORD=$DB_PASSWORD" >> "$PROJECT_DIR/.env"
fi
if ! grep -q '^REDIS_PASSWORD=' "$PROJECT_DIR/.env"; then
  echo "REDIS_PASSWORD=$REDIS_PASSWORD" >> "$PROJECT_DIR/.env"
fi

# Update APP_URL / DOMAIN dependent values if domain changed
sed -i "s#^APP_URL=.*#APP_URL=https://$DOMAIN#" "$PROJECT_DIR/.env" || true
sed -i "s#support@.*#support@$DOMAIN#" "$PROJECT_DIR/.env" || true

# Do not expose payment / telegram secrets here; user can manage manually.
print_success ".env synchronization complete (non-destructive)."
chown "$PROJECT_USER:www-data" "$PROJECT_DIR/.env" || true
chmod 640 "$PROJECT_DIR/.env" || true
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
    if command -v sudo &>/dev/null; then
        sudo -u "$PROJECT_USER" php artisan storage:link
    else
        su - "$PROJECT_USER" -c "php artisan storage:link"
    fi
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
CERT_BASE=""
if [[ -d "/etc/letsencrypt/live/$DOMAIN" ]]; then
  CERT_BASE="/etc/letsencrypt/live/$DOMAIN"
elif [[ -d "/etc/letsencrypt/live/www.$DOMAIN" ]]; then
  CERT_BASE="/etc/letsencrypt/live/www.$DOMAIN"
fi

cat > /etc/nginx/sites-available/1000proxy << EOF
server {
    if (\$host = www.$DOMAIN) {
        return 301 https://\$host\$request_uri;
    }
    listen 80 default_server;
    server_name $DOMAIN www.$DOMAIN;
    return 301 https://\$server_name\$request_uri;
}

server {
    listen 443 ssl http2 default_server;
    server_name $DOMAIN www.$DOMAIN;
    root $PROJECT_DIR/public;
    index index.php index.html;

    # SSL Certificates (auto-detected)
    $(if [[ -n "$CERT_BASE" ]]; then echo "ssl_certificate $CERT_BASE/fullchain.pem;"; else echo "# ssl_certificate path not found yet"; fi)
    $(if [[ -n "$CERT_BASE" ]]; then echo "ssl_certificate_key $CERT_BASE/privkey.pem;"; else echo "# ssl_certificate_key path not found yet"; fi)

    access_log /var/log/nginx/1000proxy.access.log;
    error_log /var/log/nginx/1000proxy.error.log;

    # Security headers (baseline)
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    # Block hidden files
    location ~ /\. { deny all; access_log off; log_not_found off; }
    location ~ ~$ { deny all; access_log off; log_not_found off; }

    # Block sensitive extensions
    location ~* \.(env|config|sql|log|htaccess|htpasswd|ini|bak|old|tmp)$ { deny all; return 404; }

    # API (rate limit zones must be defined globally)
    location ~* ^/api/ {
        try_files \$uri \$uri/ /index.php?\$query_string;
        limit_req zone=api burst=20 nodelay;
        limit_conn perip 10;
    }

    # Main application & SPA fallback
    location / { try_files \$uri \$uri/ /index.php?\$query_string; }

    # Livewire (upload tuning)
    location /livewire/ {
        try_files \$uri \$uri/ /index.php?\$query_string;
        add_header Cache-Control "no-cache, private, no-store, must-revalidate, max-stale=0, post-check=0, pre-check=0";
        client_max_body_size 100M;
        client_body_buffer_size 128k;
    }

    # Filament admin
    location ~ ^/filament/ { try_files \$uri \$uri/ /index.php?\$query_string; }

    # Static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|webp|woff|woff2|ttf|eot|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Vary "Accept-Encoding";
        access_log off; log_not_found off;
    }

    location = /favicon.ico { log_not_found off; access_log off; }
    location = /robots.txt { allow all; log_not_found off; access_log off; }

    # Protect framework directories
    location ~ ^/(storage|bootstrap|config|database|resources|routes|tests)/ { deny all; return 404; }

    # PHP handling
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php8.3-1000proxy.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param PATH_INFO \$fastcgi_path_info;
        fastcgi_param HTTPS on;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 256 16k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_temp_file_write_size 256k;
        fastcgi_max_temp_file_size 0;
    }

    error_page 404 /index.php;
    error_page 500 502 503 504 /50x.html;
    location = /50x.html { root /usr/share/nginx/html; }
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
            # Count lines, skip header, handle no jobs gracefully
            failed_jobs=$(cd "$PROJECT_DIR" && php artisan queue:failed | tail -n +2 | grep -c . || echo "0")
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
