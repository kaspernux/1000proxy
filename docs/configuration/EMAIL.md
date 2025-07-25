# 📧 Email Configuration Guide

<div align="center">
  <img src="/images/1000proxy.png" width="200" alt="1000Proxy Logo">
  
  ## Email System Setup & Configuration
  
  *Complete guide to configuring email services for 1000proxy*
</div>

---

## 📋 Overview

1000proxy includes a comprehensive email system for notifications, user registration, password resets, and automated reports. This guide covers setup for various email providers.

## 🚀 Quick Setup

### Basic SMTP Configuration

Edit your `.env` file:

```env
# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yoursite.com
MAIL_FROM_NAME="1000proxy"
```

### Test Email Configuration

```bash
# Test email sending
php artisan mail:test

# Send test notification
php artisan notify:test admin@yoursite.com
```

## 📮 Supported Email Providers

### 1. Gmail/Google Workspace

**Configuration:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

**Setup Steps:**
1. Enable 2-factor authentication
2. Generate app-specific password
3. Use app password in configuration

### 2. SendGrid

**Configuration:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
```

**API Configuration:**
```env
# Alternative API method
MAIL_MAILER=sendgrid
SENDGRID_API_KEY=your-api-key
```

### 3. Mailgun

**Configuration:**
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=your-domain.com
MAILGUN_SECRET=your-mailgun-key
MAILGUN_ENDPOINT=api.mailgun.net
```

### 4. Amazon SES

**Configuration:**
```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_SES_REGION=us-east-1
```

### 5. Custom SMTP Server

**Configuration:**
```env
MAIL_MAILER=smtp
MAIL_HOST=mail.yourserver.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yoursite.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

## 🔧 Advanced Configuration

### Multiple Email Accounts

**Config file (`config/mail.php`):**
```php
'mailers' => [
    'smtp' => [
        'transport' => 'smtp',
        'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
        'port' => env('MAIL_PORT', 587),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
    ],
    
    'notifications' => [
        'transport' => 'smtp',
        'host' => env('NOTIFICATION_MAIL_HOST'),
        'port' => env('NOTIFICATION_MAIL_PORT', 587),
        'username' => env('NOTIFICATION_MAIL_USERNAME'),
        'password' => env('NOTIFICATION_MAIL_PASSWORD'),
    ],
],
```

### Email Templates

**Custom Templates Location:**
```
resources/views/emails/
├── auth/
│   ├── password-reset.blade.php
│   └── verification.blade.php
├── notifications/
│   ├── server-down.blade.php
│   ├── payment-received.blade.php
│   └── proxy-expiring.blade.php
└── reports/
    ├── daily-stats.blade.php
    └── monthly-summary.blade.php
```

## 📨 Email Types & Configuration

### 1. Authentication Emails

```env
# Email verification
MAIL_VERIFICATION_ENABLED=true
MAIL_VERIFICATION_EXPIRES=24

# Password reset
MAIL_PASSWORD_RESET_EXPIRES=60
```

### 2. Notification Emails

```env
# System notifications
MAIL_ADMIN_NOTIFICATIONS=true
MAIL_ADMIN_EMAIL=admin@yoursite.com

# User notifications
MAIL_USER_NOTIFICATIONS=true
MAIL_NOTIFICATION_FREQUENCY=daily
```

### 3. Marketing Emails

```env
# Newsletter
MAIL_NEWSLETTER_ENABLED=true
MAIL_NEWSLETTER_FROM=newsletter@yoursite.com

# Promotional emails
MAIL_MARKETING_ENABLED=true
MAIL_UNSUBSCRIBE_URL=https://yoursite.com/unsubscribe
```

## 🔒 Security Configuration

### Email Security Best Practices

**1. DKIM Configuration:**
```env
# DKIM settings
MAIL_DKIM_ENABLED=true
MAIL_DKIM_DOMAIN=yoursite.com
MAIL_DKIM_SELECTOR=default
MAIL_DKIM_PRIVATE_KEY=path/to/private.key
```

**2. SPF Record:**
```
v=spf1 include:_spf.google.com include:sendgrid.net ~all
```

**3. DMARC Policy:**
```
v=DMARC1; p=quarantine; rua=mailto:dmarc@yoursite.com
```

### Rate Limiting

```env
# Email rate limiting
MAIL_RATE_LIMIT_PER_MINUTE=60
MAIL_RATE_LIMIT_PER_HOUR=1000
MAIL_RATE_LIMIT_PER_DAY=10000
```

## 📊 Email Queue Configuration

### Queue Setup

```env
# Use Redis for email queue
QUEUE_CONNECTION=redis
MAIL_QUEUE_CONNECTION=redis

# Queue settings
MAIL_QUEUE_NAME=emails
MAIL_QUEUE_DELAY=0
MAIL_QUEUE_RETRY_AFTER=90
```

### Queue Workers

```bash
# Start queue worker
php artisan queue:work --queue=emails

# Supervisor configuration
[program:1000proxy-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/1000proxy/artisan queue:work --queue=emails
directory=/var/www/1000proxy
user=www-data
autostart=true
autorestart=true
numprocs=3
```

## 🔍 Email Monitoring

### Email Tracking

```env
# Email tracking
MAIL_TRACKING_ENABLED=true
MAIL_TRACK_OPENS=true
MAIL_TRACK_CLICKS=true
```

### Bounce Handling

```env
# Bounce handling
MAIL_BOUNCE_HANDLING=true
MAIL_BOUNCE_WEBHOOK=https://yoursite.com/webhooks/bounce
```

### Email Logs

```bash
# View email logs
tail -f storage/logs/laravel.log | grep "Mail"

# Email specific log
tail -f storage/logs/mail.log
```

## 🧪 Testing & Debugging

### Local Development

**1. MailHog (Recommended):**
```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
```

**2. Log Driver:**
```env
MAIL_MAILER=log
```

**3. Array Driver (Testing):**
```env
MAIL_MAILER=array
```

### Email Testing Commands

```bash
# Test basic email functionality
php artisan tinker
>>> Mail::raw('Test message', function($msg) { $msg->to('test@example.com')->subject('Test'); });

# Test specific notification
php artisan notify:send UserRegistered 1

# Test email template
php artisan mail:preview welcome-email
```

## 🚀 Performance Optimization

### Email Performance Tips

**1. Queue All Emails:**
```php
// Queue emails instead of sending immediately
Mail::to($user)->queue(new WelcomeEmail());
```

**2. Bulk Email Optimization:**
```php
// Send bulk emails efficiently
Mail::to($users)->bcc($recipients)->send(new NewsletterEmail());
```

**3. Email Template Caching:**
```bash
# Cache email templates
php artisan view:cache
```

## 📧 Email Templates Customization

### Creating Custom Templates

**1. Publish Default Templates:**
```bash
php artisan vendor:publish --tag=mail
```

**2. Custom Email Template:**
```blade
{{-- resources/views/emails/custom-notification.blade.php --}}
@component('mail::message')
# Hello {{ $user->name }}!

Your proxy service is expiring soon.

@component('mail::button', ['url' => $renewUrl])
Renew Service
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
```

### Email Styling

**Custom CSS:**
```css
/* resources/sass/email.scss */
.email-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2rem;
    text-align: center;
}

.email-button {
    background: #4f46e5;
    color: white;
    padding: 12px 24px;
    border-radius: 6px;
    text-decoration: none;
}
```

## 🔧 Troubleshooting

### Common Issues

**1. SMTP Authentication Failed:**
```bash
# Check credentials
php artisan config:clear
php artisan cache:clear

# Test connection
telnet smtp.gmail.com 587
```

**2. Emails Not Sending:**
```bash
# Check queue status
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

**3. Emails Going to Spam:**
- Set up SPF, DKIM, and DMARC
- Use reputable SMTP provider
- Avoid spam trigger words
- Include unsubscribe link

**4. Rate Limiting Issues:**
```bash
# Check rate limits
php artisan horizon:status

# Increase limits in provider
# Implement exponential backoff
```

### Email Delivery Issues

**1. Debug Email Sending:**
```php
// Add to .env
MAIL_LOG_CHANNEL=single
LOG_LEVEL=debug

// Check logs
tail -f storage/logs/laravel.log
```

**2. Test Different Providers:**
```bash
# Switch to different provider temporarily
MAIL_MAILER=mailgun  # Test with Mailgun
MAIL_MAILER=sendgrid # Test with SendGrid
```

## 📚 Related Documentation

- [🌍 Environment Configuration](ENVIRONMENT.md)
- [💾 Database Setup](DATABASE.md)
- [🔐 Payment Gateways](PAYMENT_GATEWAYS.md)
- [🤖 Telegram Bot Setup](../TELEGRAM_BOT_SETUP.md)
- [🛡️ Security Guide](../security/SECURITY_BEST_PRACTICES.md)

---

<div align="center">
  <p>
    <a href="../README.md">📚 Back to Documentation</a> •
    <a href="../getting-started/CONFIGURATION.md">⚙️ Configuration</a> •
    <a href="DATABASE.md">💾 Database</a>
  </p>
  
  **Need Help?** Check our [FAQ](../FAQ.md) or open an issue.
</div>
