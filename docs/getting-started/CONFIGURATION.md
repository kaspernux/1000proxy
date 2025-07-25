# Configuration Guide

Complete configuration guide for 1000proxy application.

## Environment Configuration

### Core Application Settings

```env
# Application Identity
APP_NAME="1000proxy"
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Application Features
APP_TIMEZONE=UTC
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
```

### Database Configuration

#### MySQL (Recommended)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=proxy1000
DB_USERNAME=proxy_user
DB_PASSWORD=secure_password

# MySQL specific options
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

#### PostgreSQL (Alternative)

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=proxy1000
DB_USERNAME=proxy_user
DB_PASSWORD=secure_password
DB_SCHEMA=public
```

### Cache & Session Configuration

#### Redis (Recommended)

```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0

# Session configuration
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=null
```

#### File-based (Development)

```env
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

### Mail Configuration

#### SMTP

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

#### SendGrid

```env
MAIL_MAILER=sendgrid
SENDGRID_API_KEY=your-sendgrid-api-key
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### File Storage Configuration

#### Local Storage

```env
FILESYSTEM_DISK=local
```

#### S3 Compatible Storage

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_USE_PATH_STYLE_ENDPOINT=false
```

### Queue Configuration

#### Redis Queue

```env
QUEUE_CONNECTION=redis
REDIS_QUEUE=default
```

#### Database Queue

```env
QUEUE_CONNECTION=database
```

#### SQS (AWS)

```env
QUEUE_CONNECTION=sqs
SQS_KEY=your-key
SQS_SECRET=your-secret
SQS_PREFIX=https://sqs.region.amazonaws.com/account-id
SQS_QUEUE=default
SQS_REGION=us-east-1
```

## Payment Gateway Configuration

### Stripe

```env
STRIPE_KEY=pk_test_your_stripe_key
STRIPE_SECRET=sk_test_your_stripe_secret
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
```

### PayPal

```env
PAYPAL_MODE=sandbox
PAYPAL_SANDBOX_CLIENT_ID=your-sandbox-client-id
PAYPAL_SANDBOX_CLIENT_SECRET=your-sandbox-client-secret
PAYPAL_LIVE_CLIENT_ID=your-live-client-id
PAYPAL_LIVE_CLIENT_SECRET=your-live-client-secret
```

### Cryptocurrency

```env
# Bitcoin
BTC_WALLET_ADDRESS=your-btc-wallet-address
BTC_API_KEY=your-btc-api-key

# Ethereum
ETH_WALLET_ADDRESS=your-eth-wallet-address
ETH_API_KEY=your-eth-api-key

# Monero
XMR_WALLET_ADDRESS=your-xmr-wallet-address
XMR_API_KEY=your-xmr-api-key
```

## 3X-UI Integration

### Panel Configuration

```env
# 3X-UI Panel Settings
XUI_PANEL_URL=http://your-panel-ip:2053
XUI_USERNAME=admin
XUI_PASSWORD=admin
XUI_API_PATH=/panel/api

# SSL Configuration
XUI_SSL_VERIFY=true
XUI_SSL_CERT_PATH=/path/to/cert.pem

# Connection Settings
XUI_TIMEOUT=30
XUI_RETRY_ATTEMPTS=3
XUI_RETRY_DELAY=5
```

### Server Management

```env
# Default Server Settings
XUI_DEFAULT_PROTOCOL=vless
XUI_DEFAULT_NETWORK=tcp
XUI_DEFAULT_SECURITY=reality

# Traffic Limits
XUI_DEFAULT_TRAFFIC_LIMIT=107374182400  # 100GB in bytes
XUI_DEFAULT_CLIENT_LIMIT=1
XUI_DEFAULT_EXPIRY_DAYS=30

# Reality Settings
XUI_REALITY_DEST=yahoo.com:443
XUI_REALITY_SERVER_NAMES=yahoo.com,www.yahoo.com
```

## Security Configuration

### Authentication

```env
# Two-Factor Authentication
GOOGLE2FA_ENABLE=true
GOOGLE2FA_COMPANY=1000proxy

# Password Requirements
PASSWORD_MIN_LENGTH=8
PASSWORD_REQUIRE_UPPERCASE=true
PASSWORD_REQUIRE_LOWERCASE=true
PASSWORD_REQUIRE_NUMBERS=true
PASSWORD_REQUIRE_SYMBOLS=true
```

### Rate Limiting

```env
# API Rate Limiting
API_RATE_LIMIT=60
API_RATE_LIMIT_WINDOW=1

# Login Rate Limiting
LOGIN_RATE_LIMIT=5
LOGIN_RATE_LIMIT_WINDOW=15
```

### Security Headers

```env
# HTTPS Enforcement
FORCE_HTTPS=true

# Content Security Policy
CSP_ENABLED=true
CSP_REPORT_ONLY=false

# HSTS
HSTS_ENABLED=true
HSTS_MAX_AGE=31536000
```

## Performance Configuration

### Optimization Settings

```env
# OPcache
OPCACHE_ENABLE=true
OPCACHE_MEMORY_CONSUMPTION=256
OPCACHE_MAX_ACCELERATED_FILES=20000

# View Caching
VIEW_CACHE_ENABLED=true

# Route Caching
ROUTE_CACHE_ENABLED=true

# Config Caching
CONFIG_CACHE_ENABLED=true
```

### Queue Workers

```env
# Queue Worker Settings
QUEUE_WORKER_TIMEOUT=300
QUEUE_WORKER_MEMORY=512
QUEUE_WORKER_SLEEP=3
QUEUE_WORKER_TRIES=3
```

## Logging Configuration

### Log Channels

```env
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# Additional Log Channels
LOG_SLACK_WEBHOOK_URL=your-slack-webhook
LOG_PAPERTRAIL_URL=your-papertrail-url
LOG_PAPERTRAIL_PORT=your-papertrail-port
```

### Error Tracking

```env
# Sentry Integration
SENTRY_LARAVEL_DSN=your-sentry-dsn
SENTRY_TRACES_SAMPLE_RATE=0.1
```

## Monitoring Configuration

### Application Monitoring

```env
# New Relic
NEW_RELIC_LICENSE_KEY=your-license-key
NEW_RELIC_APP_NAME=1000proxy

# Datadog
DATADOG_API_KEY=your-datadog-api-key
DATADOG_APP_KEY=your-datadog-app-key
```

### Health Checks

```env
# Health Check Endpoints
HEALTH_CHECK_ENABLED=true
HEALTH_CHECK_SECRET=your-health-check-secret
```

## Social Authentication

### Google OAuth

```env
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URL=${APP_URL}/auth/google/callback
```

### GitHub OAuth

```env
GITHUB_CLIENT_ID=your-github-client-id
GITHUB_CLIENT_SECRET=your-github-client-secret
GITHUB_REDIRECT_URL=${APP_URL}/auth/github/callback
```

## Telegram Bot Integration

```env
# Telegram Bot
TELEGRAM_BOT_TOKEN=your-bot-token
TELEGRAM_CHAT_ID=your-chat-id

# Notifications
TELEGRAM_NOTIFICATIONS_ENABLED=true
TELEGRAM_ADMIN_NOTIFICATIONS=true
TELEGRAM_ORDER_NOTIFICATIONS=true
```

## Development-Specific Configuration

### Debug Tools

```env
# Laravel Debugbar
DEBUGBAR_ENABLED=true

# Telescope
TELESCOPE_ENABLED=true
TELESCOPE_DOMAIN=admin.yourdomain.com

# Ray
RAY_ENABLED=true
RAY_HOST=localhost
RAY_PORT=23517
```

### Testing

```env
# Test Database
DB_CONNECTION_TEST=sqlite
DB_DATABASE_TEST=:memory:

# Test Mail
MAIL_MAILER_TEST=array
```

## Configuration Files

### Key Configuration Files

- `config/app.php` - Application settings
- `config/database.php` - Database connections
- `config/cache.php` - Cache configuration
- `config/queue.php` - Queue settings
- `config/mail.php` - Email configuration
- `config/filesystems.php` - File storage
- `config/services.php` - Third-party services

### Custom Configuration

Create custom configuration files in `config/` directory:

```php
<?php
// config/proxy.php

return [
    'default_protocol' => env('XUI_DEFAULT_PROTOCOL', 'vless'),
    'default_traffic_limit' => env('XUI_DEFAULT_TRAFFIC_LIMIT', 107374182400),
    'reality_settings' => [
        'dest' => env('XUI_REALITY_DEST', 'yahoo.com:443'),
        'server_names' => explode(',', env('XUI_REALITY_SERVER_NAMES', 'yahoo.com,www.yahoo.com')),
    ],
];
```

## Configuration Commands

### Caching Configuration

```bash
# Cache configuration
php artisan config:cache

# Clear configuration cache
php artisan config:clear

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache
```

### Publishing Configuration

```bash
# Publish package configurations
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

## Environment Validation

Create a configuration validation script:

```php
<?php
// config/validation.php

return [
    'required_env_vars' => [
        'APP_KEY',
        'DB_CONNECTION',
        'DB_DATABASE',
        'DB_USERNAME',
        'DB_PASSWORD',
    ],
    'required_directories' => [
        'storage/app',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/logs',
        'bootstrap/cache',
    ],
    'required_permissions' => [
        'storage' => 0755,
        'bootstrap/cache' => 0755,
    ],
];
```

## Troubleshooting

### Common Configuration Issues

1. **Application Key Missing**

   ```bash
   php artisan key:generate
   ```

2. **Database Connection Failed**

   ```bash
   php artisan config:cache
   php artisan migrate:status
   ```

3. **Permission Denied Errors**

   ```bash
   chmod -R 755 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

4. **Redis Connection Failed**

   ```bash
   redis-cli ping
   systemctl status redis-server
   ```

### Configuration Testing

```bash
# Test database connection
php artisan migrate:status

# Test cache
php artisan cache:clear
php artisan cache:table

# Test queue
php artisan queue:work --once

# Test mail
php artisan mail:test
```

---

**Next Steps**: Continue with [Development Setup](DEVELOPMENT_SETUP.md) for development environment configuration.
