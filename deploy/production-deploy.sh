#!/bin/bash

# 1000proxy Production Deployment Script
# This script prepares the application for production deployment

set -e

echo "🚀 Starting 1000proxy Production Deployment..."

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Please run as root or with sudo"
    exit 1
fi

# Set application directory
APP_DIR="/var/www/1000proxy"
cd "$APP_DIR"

echo "📁 Working in: $APP_DIR"

# 1. Update system packages
echo "🔄 Updating system packages..."
apt update && apt upgrade -y

# 2. Install required dependencies
echo "📦 Installing required dependencies..."
apt install -y redis-server nginx mysql-server php8.2-fpm php8.2-cli php8.2-mysql php8.2-redis php8.2-gd php8.2-curl php8.2-zip php8.2-mbstring php8.2-xml php8.2-bcmath supervisor

# 3. Configure Redis
echo "🔧 Configuring Redis..."
systemctl enable redis-server
systemctl start redis-server

# Configure Redis databases
redis-cli CONFIG SET databases 16
redis-cli CONFIG SET maxmemory 256mb
redis-cli CONFIG SET maxmemory-policy allkeys-lru

# 4. Set proper file permissions
echo "🔐 Setting file permissions..."
chown -R www-data:www-data "$APP_DIR"
chmod -R 755 "$APP_DIR"
chmod -R 777 "$APP_DIR/storage"
chmod -R 777 "$APP_DIR/bootstrap/cache"

# 5. Install/Update Composer dependencies
echo "📋 Installing Composer dependencies..."
su -s /bin/bash -c "composer install --no-dev --optimize-autoloader" www-data

# 6. Copy production environment file
echo "⚙️ Setting up production environment..."
if [ ! -f .env ]; then
    cp .env.production .env
    echo "✅ Production environment file created"
    echo "⚠️  Please update .env file with your production values"
else
    echo "✅ Environment file already exists"
fi

# 7. Generate application key if not exists
if ! grep -q "APP_KEY=" .env || [ -z "$(grep APP_KEY= .env | cut -d'=' -f2)" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate
fi

# 8. Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# 9. Clear and cache configuration
echo "🧹 Clearing and caching configuration..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

echo "📊 Caching configuration for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 10. Run optimization commands
echo "⚡ Running optimization commands..."
php artisan optimize
php artisan storage:link

# 11. Warm up caches
echo "🔥 Warming up caches..."
php artisan cache:warmup

# 12. Set up queue workers with Supervisor
echo "👥 Setting up queue workers..."
cp deploy/supervisor.conf /etc/supervisor/conf.d/1000proxy.conf

# Update paths in supervisor config
sed -i "s|/path/to/your/project|$APP_DIR|g" /etc/supervisor/conf.d/1000proxy.conf

# Reload supervisor
supervisorctl reread
supervisorctl update
supervisorctl start 1000proxy-worker:*
supervisorctl start 1000proxy-horizon
supervisorctl start 1000proxy-schedule

# 13. Set up cron jobs for scheduled tasks
echo "⏰ Setting up cron jobs..."
(crontab -l 2>/dev/null; echo "* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# 14. Configure Nginx (basic configuration)
echo "🌐 Configuring Nginx..."
cat > /etc/nginx/sites-available/1000proxy << EOF
server {
    listen 80;
    server_name YOUR_DOMAIN;
    root $APP_DIR/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Enable the site
ln -sf /etc/nginx/sites-available/1000proxy /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx

# 15. Set up log rotation
echo "📝 Setting up log rotation..."
cat > /etc/logrotate.d/1000proxy << EOF
$APP_DIR/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    sharedscripts
    postrotate
        php $APP_DIR/artisan log:clear --days=30
    endscript
}
EOF

# 16. Run final health check
echo "🏥 Running system health check..."
php artisan system:health-check

# 17. Final status check
echo "📊 Final status check..."
echo "✅ Redis: $(systemctl is-active redis-server)"
echo "✅ Nginx: $(systemctl is-active nginx)"
echo "✅ PHP-FPM: $(systemctl is-active php8.2-fpm)"
echo "✅ MySQL: $(systemctl is-active mysql)"
echo "✅ Supervisor: $(systemctl is-active supervisor)"

echo ""
echo "🎉 1000proxy Production Deployment Completed Successfully!"
echo ""
echo "📋 Next Steps:"
echo "1. Update .env file with your production configuration"
echo "2. Update Nginx server_name with your domain"
echo "3. Set up SSL certificate (Let's Encrypt recommended)"
echo "4. Configure firewall rules"
echo "5. Set up monitoring and backups"
echo ""
echo "🔧 Important Commands:"
echo "- Check application health: php artisan system:health-check"
echo "- Monitor queues: php artisan horizon:status"
echo "- View logs: tail -f storage/logs/laravel.log"
echo "- Restart workers: supervisorctl restart 1000proxy-worker:*"
echo ""
echo "🌐 Your application should now be accessible at: http://YOUR_DOMAIN"
echo "🔧 Admin panel: http://YOUR_DOMAIN/admin"
echo "👥 Customer panel: http://YOUR_DOMAIN/account"
echo "📊 Queue monitoring: http://YOUR_DOMAIN/admin/horizon"
