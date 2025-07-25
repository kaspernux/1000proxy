# 🌍 Environment Configuration Guide

<div align="center">
  <img src="/images/1000proxy.png" width="200" alt="1000Proxy Logo">
  
  ## Environment Variables & Configuration
  
  *Complete guide to configuring your 1000proxy environment*
</div>

---

## 📋 Overview

This guide covers all environment variables and configuration options for 1000proxy. Proper configuration is crucial for security, performance, and functionality.

## 🔧 Quick Setup

### 1. Copy Environment Template
```bash
cp .env.example .env
```

### 2. Generate Application Key
```bash
php artisan key:generate
```

### 3. Configure Basic Settings
Edit `.env` with your specific values:

```env
# Application
APP_NAME="1000proxy"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=1000proxy
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## 🗂️ Configuration Categories

### 🚀 Application Settings

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `APP_NAME` | Application name | 1000proxy | ✅ |
| `APP_ENV` | Environment (local/staging/production) | production | ✅ |
| `APP_DEBUG` | Debug mode (true/false) | false | ✅ |
| `APP_URL` | Application URL | http://localhost | ✅ |
| `APP_KEY` | Encryption key | - | ✅ |
| `APP_TIMEZONE` | Application timezone | UTC | ❌ |
| `APP_LOCALE` | Default language | en | ❌ |

### 💾 Database Configuration

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `DB_CONNECTION` | Database type | mysql | ✅ |
| `DB_HOST` | Database host | 127.0.0.1 | ✅ |
| `DB_PORT` | Database port | 3306 | ✅ |
| `DB_DATABASE` | Database name | 1000proxy | ✅ |
| `DB_USERNAME` | Database username | - | ✅ |
| `DB_PASSWORD` | Database password | - | ✅ |

### 📧 Mail Configuration

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `MAIL_MAILER` | Mail driver | smtp | ✅ |
| `MAIL_HOST` | SMTP host | - | ✅ |
| `MAIL_PORT` | SMTP port | 587 | ✅ |
| `MAIL_USERNAME` | SMTP username | - | ✅ |
| `MAIL_PASSWORD` | SMTP password | - | ✅ |
| `MAIL_ENCRYPTION` | Encryption type | tls | ❌ |
| `MAIL_FROM_ADDRESS` | From email | - | ✅ |
| `MAIL_FROM_NAME` | From name | ${APP_NAME} | ❌ |

### 🔐 Security Settings

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `SANCTUM_STATEFUL_DOMAINS` | API domains | localhost | ❌ |
| `SESSION_DRIVER` | Session storage | file | ❌ |
| `QUEUE_CONNECTION` | Queue driver | sync | ❌ |
| `CACHE_DRIVER` | Cache driver | file | ❌ |

### 💳 Payment Gateways

#### NowPayments (Crypto)
| Variable | Description | Required |
|----------|-------------|----------|
| `NOWPAYMENTS_API_KEY` | API key | ✅ |
| `NOWPAYMENTS_IPN_SECRET` | IPN secret | ✅ |
| `NOWPAYMENTS_SANDBOX` | Sandbox mode | ❌ |

#### Bitcoin Core
| Variable | Description | Required |
|----------|-------------|----------|
| `BITCOIN_RPC_USER` | RPC username | ❌ |
| `BITCOIN_RPC_PASSWORD` | RPC password | ❌ |
| `BITCOIN_RPC_HOST` | RPC host | ❌ |
| `BITCOIN_RPC_PORT` | RPC port | ❌ |

#### Monero
| Variable | Description | Required |
|----------|-------------|----------|
| `MONERO_WALLET_RPC_HOST` | Wallet RPC host | ❌ |
| `MONERO_WALLET_RPC_PORT` | Wallet RPC port | ❌ |
| `MONERO_DAEMON_HOST` | Daemon host | ❌ |

### 🤖 Telegram Bot

| Variable | Description | Required |
|----------|-------------|----------|
| `TELEGRAM_BOT_TOKEN` | Bot token | ❌ |
| `TELEGRAM_WEBHOOK_URL` | Webhook URL | ❌ |
| `TELEGRAM_ADMIN_CHAT_ID` | Admin chat ID | ❌ |

### 📊 Redis & Caching

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `REDIS_HOST` | Redis host | 127.0.0.1 | ❌ |
| `REDIS_PASSWORD` | Redis password | null | ❌ |
| `REDIS_PORT` | Redis port | 6379 | ❌ |

### 🐳 3X-UI Integration

| Variable | Description | Required |
|----------|-------------|----------|
| `XRAY_PANELS_DEFAULT_URL` | Default panel URL | ✅ |
| `XRAY_PANELS_DEFAULT_USERNAME` | Default username | ✅ |
| `XRAY_PANELS_DEFAULT_PASSWORD` | Default password | ✅ |

## 🔒 Security Best Practices

### 🛡️ Production Security
- Set `APP_DEBUG=false` in production
- Use strong, unique passwords
- Enable HTTPS with SSL certificates
- Configure proper firewall rules
- Use Redis for sessions and cache
- Enable rate limiting
- Regular security updates

### 🔑 Key Management
- Generate unique `APP_KEY`
- Rotate secrets regularly
- Use environment-specific configs
- Secure `.env` file permissions

```bash
chmod 600 .env
chown www-data:www-data .env
```

## 🚀 Performance Optimization

### 📈 Recommended Settings

**Production Environment:**
```env
APP_ENV=production
APP_DEBUG=false
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

**High Traffic:**
```env
DB_CONNECTION=mysql
REDIS_HOST=127.0.0.1
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## 🔧 Configuration Commands

### Cache Configuration
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Clear Configuration
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Optimize for Production
```bash
php artisan optimize
```

## 🌐 Multi-Environment Setup

### Development
```env
APP_ENV=local
APP_DEBUG=true
MAIL_MAILER=log
QUEUE_CONNECTION=sync
```

### Staging
```env
APP_ENV=staging
APP_DEBUG=false
MAIL_MAILER=smtp
QUEUE_CONNECTION=database
```

### Production
```env
APP_ENV=production
APP_DEBUG=false
MAIL_MAILER=smtp
QUEUE_CONNECTION=redis
```

## 🔍 Troubleshooting

### Common Issues

**Configuration Cached:**
```bash
php artisan config:clear
```

**Permission Denied:**
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

**Database Connection:**
- Check credentials
- Verify host/port
- Test MySQL connection

**Mail Issues:**
- Verify SMTP settings
- Check firewall ports
- Test with mail client

## 📚 Related Documentation

- [🔧 Installation Guide](../getting-started/INSTALLATION.md)
- [💾 Database Setup](DATABASE.md)
- [📧 Email Configuration](EMAIL.md)
- [🔐 Payment Gateways](PAYMENT_GATEWAYS.md)
- [🛡️ Security Guide](../security/SECURITY_BEST_PRACTICES.md)

---

<div align="center">
  <p>
    <a href="../README.md">📚 Back to Documentation</a> •
    <a href="../getting-started/QUICK_START.md">🚀 Quick Start</a> •
    <a href="DATABASE.md">💾 Database Setup</a>
  </p>
  
  **Need Help?** Check our [FAQ](../FAQ.md) or open an issue.
</div>
