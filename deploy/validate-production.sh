#!/bin/bash

# 1000proxy Production Validation Script
# Validates that all systems are ready for production deployment

set -e

echo "🔍 1000proxy Production Validation Starting..."

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Counters
PASSED=0
FAILED=0
WARNINGS=0

# Helper functions
check_pass() {
    echo -e "✅ ${GREEN}$1${NC}"
    ((PASSED++))
}

check_fail() {
    echo -e "❌ ${RED}$1${NC}"
    ((FAILED++))
}

check_warn() {
    echo -e "⚠️  ${YELLOW}$1${NC}"
    ((WARNINGS++))
}

# Get application directory
APP_DIR="/var/www/1000proxy"
if [ ! -d "$APP_DIR" ]; then
    APP_DIR=$(pwd)
fi

cd "$APP_DIR"

echo "📁 Validating application at: $APP_DIR"
echo ""

# 1. Environment Configuration Validation
echo "🔧 Validating Environment Configuration..."

if [ -f .env ]; then
    check_pass "Environment file exists"
    
    # Check critical environment variables
    if grep -q "APP_DEBUG=false" .env; then
        check_pass "APP_DEBUG is set to false"
    else
        check_fail "APP_DEBUG should be false for production"
    fi
    
    if grep -q "APP_ENV=production" .env; then
        check_pass "APP_ENV is set to production"
    else
        check_warn "APP_ENV should be set to production"
    fi
    
    if grep -q "APP_KEY=base64:" .env && [ -n "$(grep APP_KEY= .env | cut -d'=' -f2)" ]; then
        check_pass "APP_KEY is configured"
    else
        check_fail "APP_KEY is not configured"
    fi
else
    check_fail "Environment file (.env) not found"
fi

echo ""

# 2. Database Validation
echo "🗄️ Validating Database Configuration..."

if php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connection successful';" 2>/dev/null; then
    check_pass "Database connection successful"
else
    check_fail "Database connection failed"
fi

# Check if migrations are up to date
if php artisan migrate:status | grep -q "Ran?"; then
    check_pass "Database migrations are up to date"
else
    check_fail "Database migrations need to be run"
fi

echo ""

# 3. Redis Validation
echo "🔴 Validating Redis Configuration..."

if redis-cli ping 2>/dev/null | grep -q "PONG"; then
    check_pass "Redis server is running"
else
    check_fail "Redis server is not accessible"
fi

# Test Redis databases
for db in 0 1 2 3 4; do
    if redis-cli -n $db ping 2>/dev/null | grep -q "PONG"; then
        check_pass "Redis database $db is accessible"
    else
        check_warn "Redis database $db is not accessible"
    fi
done

echo ""

# 4. Cache Validation
echo "🗂️ Validating Cache Configuration..."

if php artisan cache:store redis test "validation" 2>/dev/null && [ "$(php artisan cache:get redis test 2>/dev/null)" = "validation" ]; then
    check_pass "Cache functionality is working"
    php artisan cache:forget redis test 2>/dev/null
else
    check_fail "Cache functionality is not working"
fi

echo ""

# 5. Queue System Validation
echo "👥 Validating Queue System..."

if command -v supervisorctl > /dev/null; then
    check_pass "Supervisor is installed"
    
    if supervisorctl status | grep -q "1000proxy"; then
        check_pass "1000proxy queue workers are configured"
    else
        check_fail "1000proxy queue workers are not configured"
    fi
else
    check_fail "Supervisor is not installed"
fi

if php artisan horizon:status 2>/dev/null | grep -q "running"; then
    check_pass "Horizon is running"
else
    check_warn "Horizon is not running"
fi

echo ""

# 6. Web Server Validation
echo "🌐 Validating Web Server Configuration..."

if command -v nginx > /dev/null; then
    check_pass "Nginx is installed"
    
    if systemctl is-active nginx > /dev/null; then
        check_pass "Nginx is running"
    else
        check_fail "Nginx is not running"
    fi
    
    if nginx -t > /dev/null 2>&1; then
        check_pass "Nginx configuration is valid"
    else
        check_fail "Nginx configuration has errors"
    fi
else
    check_fail "Nginx is not installed"
fi

if command -v php-fpm8.2 > /dev/null; then
    check_pass "PHP-FPM is installed"
    
    if systemctl is-active php8.2-fpm > /dev/null; then
        check_pass "PHP-FPM is running"
    else
        check_fail "PHP-FPM is not running"
    fi
else
    check_fail "PHP-FPM is not installed"
fi

echo ""

# 7. File Permissions Validation
echo "🔐 Validating File Permissions..."

if [ -w storage/ ]; then
    check_pass "Storage directory is writable"
else
    check_fail "Storage directory is not writable"
fi

if [ -w bootstrap/cache/ ]; then
    check_pass "Bootstrap cache directory is writable"
else
    check_fail "Bootstrap cache directory is not writable"
fi

if [ "$(stat -c %U storage/)" = "www-data" ]; then
    check_pass "Storage directory has correct ownership"
else
    check_warn "Storage directory ownership should be www-data"
fi

echo ""

# 8. Security Validation
echo "🔒 Validating Security Configuration..."

if [ -f /etc/nginx/sites-available/1000proxy ]; then
    if grep -q "ssl_certificate" /etc/nginx/sites-available/1000proxy; then
        check_pass "SSL certificate is configured"
    else
        check_warn "SSL certificate is not configured"
    fi
else
    check_warn "Nginx site configuration not found"
fi

if command -v ufw > /dev/null; then
    if ufw status | grep -q "Status: active"; then
        check_pass "Firewall is active"
    else
        check_warn "Firewall is not active"
    fi
else
    check_warn "UFW firewall is not installed"
fi

echo ""

# 9. Application Health Check
echo "🏥 Running Application Health Check..."

if php artisan system:health-check > /dev/null 2>&1; then
    check_pass "Application health check passed"
else
    check_fail "Application health check failed"
fi

echo ""

# 10. Performance Validation
echo "⚡ Validating Performance Configuration..."

if php artisan config:show app.debug | grep -q "false"; then
    check_pass "Debug mode is disabled"
else
    check_fail "Debug mode should be disabled for production"
fi

if [ -f bootstrap/cache/config.php ]; then
    check_pass "Configuration is cached"
else
    check_warn "Configuration should be cached for production"
fi

if [ -f bootstrap/cache/routes.php ]; then
    check_pass "Routes are cached"
else
    check_warn "Routes should be cached for production"
fi

if [ -f bootstrap/cache/views.php ]; then
    check_pass "Views are cached"
else
    check_warn "Views should be cached for production"
fi

echo ""

# 11. Monitoring Validation
echo "📊 Validating Monitoring Configuration..."

if [ -f /etc/logrotate.d/1000proxy ]; then
    check_pass "Log rotation is configured"
else
    check_warn "Log rotation is not configured"
fi

if crontab -l | grep -q "schedule:run"; then
    check_pass "Scheduled tasks are configured"
else
    check_warn "Scheduled tasks are not configured"
fi

echo ""

# 12. Backup Validation
echo "💾 Validating Backup Configuration..."

if command -v mysqldump > /dev/null; then
    check_pass "Database backup tool is available"
else
    check_warn "Database backup tool is not available"
fi

echo ""

# Summary
echo "📋 Validation Summary:"
echo "===================="
echo -e "✅ ${GREEN}Passed: $PASSED${NC}"
echo -e "❌ ${RED}Failed: $FAILED${NC}"
echo -e "⚠️  ${YELLOW}Warnings: $WARNINGS${NC}"
echo ""

# Final status
if [ $FAILED -eq 0 ]; then
    echo -e "🎉 ${GREEN}Production Validation PASSED!${NC}"
    echo -e "🚀 ${GREEN}System is ready for production deployment.${NC}"
    
    if [ $WARNINGS -gt 0 ]; then
        echo -e "⚠️  ${YELLOW}Please review warnings for optimal production setup.${NC}"
    fi
    
    exit 0
else
    echo -e "❌ ${RED}Production Validation FAILED!${NC}"
    echo -e "🔧 ${RED}Please fix the failed checks before deploying to production.${NC}"
    exit 1
fi
