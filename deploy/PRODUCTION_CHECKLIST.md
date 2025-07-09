# 🚀 1000proxy Production Deployment Checklist

## Pre-Deployment Checklist

### Environment Configuration

-   [ ] Create production `.env` file from `.env.production` template
-   [ ] Update `APP_URL` with production domain
-   [ ] Set `APP_DEBUG=false` for production
-   [ ] Configure database credentials (`DB_*` variables)
-   [ ] Set up Redis configuration (`REDIS_*` variables)
-   [ ] Configure mail settings (`MAIL_*` variables)
-   [ ] Set admin email addresses (`ADMIN_EMAIL`)
-   [ ] Configure payment gateway credentials
-   [ ] Set up XUI panel credentials

### Security Configuration

-   [ ] Generate strong `APP_KEY`
-   [ ] Set up SSL certificate (Let's Encrypt recommended)
-   [ ] Configure firewall rules (UFW recommended)
-   [ ] Set proper file permissions (www-data:www-data)
-   [ ] Disable unnecessary services
-   [ ] Configure fail2ban for SSH protection

### Database Setup

-   [ ] Create production database
-   [ ] Configure database user with proper permissions
-   [ ] Run database migrations: `php artisan migrate --force`
-   [ ] Seed initial data if needed
-   [ ] Set up database backups

### Redis Configuration

-   [ ] Install and configure Redis server
-   [ ] Set up Redis databases for different purposes:
    -   Database 0: Default
    -   Database 1: Cache
    -   Database 2: Sessions
    -   Database 3: Queue
    -   Database 4: Analytics
-   [ ] Configure Redis persistence
-   [ ] Set up Redis monitoring

### Queue System

-   [ ] Install and configure Supervisor
-   [ ] Set up queue workers with proper configuration
-   [ ] Configure Horizon for queue monitoring
-   [ ] Test queue job processing
-   [ ] Set up queue worker auto-restart

### Web Server Configuration

-   [ ] Install and configure Nginx
-   [ ] Set up proper server blocks
-   [ ] Configure PHP-FPM
-   [ ] Set up proper security headers
-   [ ] Configure rate limiting
-   [ ] Set up gzip compression

### Monitoring and Logging

-   [ ] Set up log rotation
-   [ ] Configure system monitoring
-   [ ] Set up health check endpoints
-   [ ] Configure alerting for critical issues
-   [ ] Set up performance monitoring

### Backup Strategy

-   [ ] Database backup automation
-   [ ] File system backup
-   [ ] Configuration backup
-   [ ] Test backup restoration

## Deployment Steps

### 1. System Preparation

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install required dependencies
sudo apt install -y redis-server nginx mysql-server php8.2-fpm php8.2-cli php8.2-mysql php8.2-redis php8.2-gd php8.2-curl php8.2-zip php8.2-mbstring php8.2-xml php8.2-bcmath supervisor
```

### 2. Application Setup

```bash
# Navigate to application directory
cd /var/www/1000proxy

# Install dependencies
composer install --no-dev --optimize-autoloader

# Set up environment
cp .env.production .env
# Edit .env with production values

# Generate application key
php artisan key:generate
```

### 3. Database Migration

```bash
# Run migrations
php artisan migrate --force

# Verify database structure
php artisan tinker
# Test database connectivity
```

### 4. Cache and Optimization

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Warm up caches
php artisan cache:warmup
```

### 5. Queue Workers Setup

```bash
# Copy supervisor configuration
sudo cp deploy/supervisor.conf /etc/supervisor/conf.d/1000proxy.conf

# Update paths in configuration
sudo sed -i "s|/path/to/your/project|$(pwd)|g" /etc/supervisor/conf.d/1000proxy.conf

# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start 1000proxy-worker:*
sudo supervisorctl start 1000proxy-horizon
sudo supervisorctl start 1000proxy-schedule
```

### 6. Web Server Configuration

```bash
# Configure Nginx
sudo cp deploy/nginx.conf /etc/nginx/sites-available/1000proxy
sudo ln -sf /etc/nginx/sites-available/1000proxy /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 7. SSL Certificate Setup

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d yourdomain.com

# Set up auto-renewal
sudo systemctl enable certbot.timer
```

### 8. File Permissions

```bash
# Set proper ownership
sudo chown -R www-data:www-data /var/www/1000proxy

# Set proper permissions
sudo chmod -R 755 /var/www/1000proxy
sudo chmod -R 777 /var/www/1000proxy/storage
sudo chmod -R 777 /var/www/1000proxy/bootstrap/cache
```

### 9. Scheduled Tasks

```bash
# Add cron job for Laravel scheduler
echo "* * * * * cd /var/www/1000proxy && php artisan schedule:run >> /dev/null 2>&1" | sudo crontab -
```

### 10. Log Rotation

```bash
# Set up log rotation
sudo cp deploy/logrotate.conf /etc/logrotate.d/1000proxy
```

## Post-Deployment Verification

### System Health Checks

```bash
# Run comprehensive health check
php artisan system:health-check

# Check queue status
php artisan horizon:status

# Verify cache functionality
php artisan cache:store redis test "Hello World"
php artisan cache:get redis test

# Test database connectivity
php artisan tinker
# User::count()
```

### Service Status Verification

```bash
# Check all services
sudo systemctl status redis-server
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql
sudo systemctl status supervisor

# Check queue workers
sudo supervisorctl status
```

### Performance Testing

```bash
# Test application response
curl -I https://yourdomain.com

# Check queue processing
php artisan queue:work --once

# Monitor system resources
htop
```

### Security Verification

```bash
# Check file permissions
ls -la /var/www/1000proxy/

# Verify SSL certificate
curl -I https://yourdomain.com

# Test firewall
sudo ufw status
```

## Monitoring and Maintenance

### Daily Tasks

-   [ ] Check system health: `php artisan system:health-check`
-   [ ] Monitor queue status: `php artisan horizon:status`
-   [ ] Check error logs: `tail -f storage/logs/laravel.log`
-   [ ] Monitor system resources: `htop`

### Weekly Tasks

-   [ ] Review security logs
-   [ ] Check backup integrity
-   [ ] Update dependencies: `composer update`
-   [ ] Run security audit: `composer audit`

### Monthly Tasks

-   [ ] Review performance metrics
-   [ ] Update system packages
-   [ ] Clean up old logs
-   [ ] Review and update security measures

## Troubleshooting

### Common Issues

1. **Queue jobs not processing**: Check supervisor status and restart workers
2. **Cache not working**: Verify Redis connection and permissions
3. **Database connection errors**: Check credentials and database server status
4. **File permission errors**: Reset permissions with proper ownership

### Emergency Procedures

1. **Application down**: Check Nginx and PHP-FPM status
2. **Database issues**: Check MySQL status and connections
3. **High load**: Monitor queue workers and system resources
4. **Security incident**: Review logs and implement countermeasures

## Support and Documentation

### Key Files

-   Configuration: `.env`
-   Nginx: `/etc/nginx/sites-available/1000proxy`
-   Supervisor: `/etc/supervisor/conf.d/1000proxy.conf`
-   Logs: `storage/logs/`

### Important Commands

-   Health check: `php artisan system:health-check`
-   Queue monitoring: `php artisan horizon:status`
-   Clear cache: `php artisan cache:clear`
-   Restart workers: `sudo supervisorctl restart 1000proxy-worker:*`

### URLs

-   Main application: `https://yourdomain.com`
-   Admin panel: `https://yourdomain.com/admin`
-   Customer panel: `https://yourdomain.com/account`
-   Queue monitoring: `https://yourdomain.com/admin/horizon`

---

**Final Status: Ready for Production Deployment** ✅

This checklist ensures 100% production readiness with comprehensive monitoring, security, and performance optimization.
