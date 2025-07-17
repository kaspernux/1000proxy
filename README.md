<div align="center">
  <img src="/images/1000proxy.png" width="400" alt="1000Proxy Logo">
</div>

<h1 align="center">1000proxy - Professional Proxy Management Platform</h1>

<p align="center">
  <b>Version 2.1.0</b><br>
  <i>A modern, professional proxy management platform with stunning UI, comprehensive 3X-UI integration, multi-panel administration, and fully automated client provisioning system.</i>
</p>

<p align="center">
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-12.x-red.svg" alt="Laravel"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.3+-blue.svg" alt="PHP"></a>
  <a href="https://livewire.laravel.com"><img src="https://img.shields.io/badge/Livewire-3.x-purple.svg" alt="Livewire"></a>
  <a href="https://tailwindcss.com"><img src="https://img.shields.io/badge/Tailwind-CSS-cyan.svg" alt="Tailwind CSS"></a>
  <a href="https://heroicons.com"><img src="https://img.shields.io/badge/Heroicons-SVG-indigo.svg" alt="Heroicons"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/License-MIT-green.svg" alt="License"></a>
  <a href="tests/"><img src="https://img.shields.io/badge/Tests-Comprehensive-brightgreen.svg" alt="Tests"></a>
  <a href="docs/security/SECURITY_BEST_PRACTICES.md"><img src="https://img.shields.io/badge/Security-Hardened-orange.svg" alt="Security"></a>
  <a href="docs/README.md"><img src="https://img.shields.io/badge/Documentation-Complete-blue.svg" alt="Documentation"></a>
</p>

---

## 🚀 Quick Start

```bash
# Clone the repository
git clone https://github.com/kaspernux/1000proxy.git
cd 1000proxy

# Install dependencies
composer install && npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate:fresh --seed

# Start development server
php artisan serve
```

**Default Admin Access**: http://localhost:8000/admin (admin@example.com / password)

## 📚 Documentation

Comprehensive documentation is available in the `/docs` directory:

- **[📖 Complete Documentation](docs/README.md)** - Full documentation index
- **[🚀 Quick Start Guide](docs/getting-started/QUICK_START.md)** - Get running in 10 minutes
- **[⚙️ Installation Guide](docs/getting-started/INSTALLATION.md)** - Detailed setup instructions
- **[🔧 Configuration Guide](docs/getting-started/CONFIGURATION.md)** - Environment configuration
- **[👨‍💻 Development Setup](docs/getting-started/DEVELOPMENT_SETUP.md)** - Development environment
- **[🔌 API Documentation](docs/api/API_DOCUMENTATION.md)** - Complete API reference
- **[👥 User Guides](docs/user-guides/USER_GUIDES.md)** - Admin and customer guides
- **[🚀 Deployment Guide](docs/deployment/DEPLOYMENT_GUIDE.md)** - Production deployment
- **[🛡️ Security Guide](docs/security/SECURITY_BEST_PRACTICES.md)** - Security best practices

## 🛠️ Development Scripts

Three essential PowerShell scripts for comprehensive project management:

### 🔍 Debug & Diagnostics
```powershell
./debug-project.ps1              # Complete system diagnostics
./debug-project.ps1 -Verbose     # Detailed debug output
./debug-project.ps1 -OutputFile "report.txt"  # Save report to file
```

### 🧪 Testing
```powershell
./test-project.ps1               # Run all tests
./test-project.ps1 -Coverage     # Run tests with coverage
./test-project.ps1 -API          # Test API endpoints only
./test-project.ps1 -Filter "AuthTest"  # Filter specific tests
```

### ✅ Feature Verification
```powershell
./check-features.ps1             # Verify all features
./check-features.ps1 -Verbose    # Detailed feature check
./check-features.ps1 -Authentication -AdminPanels  # Check specific features
```

# 🔧 Full Project Presentation

**1000Proxy** is a fully-featured web system built with Laravel 12 for automating **proxy client sales** via remote XUI panels. It is specifically designed for high-volume proxy services using various protocols and provides full backend management for crypto wallets, customer orders, and server client generation.

## 🎯 Main Features

### 🎨 Modern User Interface
- **Stunning Modern Design**: Professional gradient-based UI with competitive proxy service aesthetics
- **Heroicons Integration**: Complete replacement of emojis with professional SVG icons (20+ custom icons)
- **Livewire 3.x Components**: Reactive, dynamic user interface with real-time updates
- **Tailwind CSS**: Utility-first CSS framework for rapid, responsive design
- **Mobile-First Responsive**: Optimized for all devices and screen sizes
- **Advanced Filtering**: Dynamic product filtering with real-time search and category management
- **Professional UX**: Clean, intuitive user experience designed for proxy service customers

### Core System Features

-   **Proxy Client Sales System** based on external **XUI Panels**
-   **Comprehensive Security**: Input validation, SQL injection prevention, XSS protection, CSRF protection
-   **Advanced Caching**: Multi-level caching with Redis for optimal performance
-   **Queue Management**: Laravel Horizon for background job processing
-   **Audit Logging**: Complete audit trail for all sensitive operations
-   **Rate Limiting**: API and authentication rate limiting
-   **Webhook Security**: Signature verification for payment webhooks

### Protocol Support

-   **Supports all major XUI protocols**:
    -   `VLESS` with XTLS support
    -   `VMESS` with various encryption options
    -   `TROJAN` for enhanced security
    -   `SHADOWSOCKS` with multiple encryption methods
    -   `SOCKS5` proxy support
    -   `HTTP` proxy support
    -   `REALITY (VLESS/VMESS+Reality)` for advanced obfuscation
    -   `gRPC` support for advanced tunneling

### Payment & Wallet System

-   **Crypto Wallet System**:
    -   Customer wallets in USD (converted instantly from BTC, XMR, or SOL deposits)
    -   Full transaction history and top-up tracking
    -   Automated conversion rates and fee calculations
-   **Payments Integration**:
    -   Stripe payments for fiat top-ups
    -   NowPayments.io integration for crypto top-ups
    -   Comprehensive webhook handling with signature verification
    -   Failed payment retry mechanisms

### Automation & Management

-   **Order Management**:
    -   Automated order creation and processing
    -   Client creation on XUI panel after payment success
    -   Order status tracking and notifications
-   **XUI API Automation**:
    -   Dynamic server inbound and client management
    -   Automatic link generation (`vless://`, `vmess://`, `trojan://`, `ss://`, etc.)
    -   QR Code generation for client configuration links
    -   Real-time traffic monitoring and statistics
-   **Queue System with Laravel Horizon**:
    -   Background job processing for client creation and wallet management
    -   Real-time monitoring with Horizon dashboard
    -   Job retry mechanisms and failure handling

### Technology Stack

-   **Modern Web Technology Stack**:
    -   Laravel 12 backend with comprehensive security features
    -   Livewire 3.x for reactive, dynamic user interfaces
    -   Tailwind CSS 3.x with custom gradient design system
    -   Heroicons SVG icon library (professional iconography)
    -   Vite.js for optimized asset compilation and Hot Module Replacement
    -   Alpine.js for lightweight JavaScript interactions
    -   Redis queue management and caching
    -   Supervisor daemon for job workers
    -   Comprehensive test coverage (100% API coverage)
    -   Performance optimizations and monitoring

### Telegram Bot Integration

-   **Full Telegram Bot Support**:
    -   Secure account linking with web platform
    -   Complete proxy management through Telegram
    -   Real-time wallet balance checking
    -   Server browsing and ordering
    -   Order history and status tracking
    -   Direct support access
    -   Instant notifications for orders and payments
    -   Mobile-first experience

## 🏆 Highlights

- **Modern Professional UI**: Stunning gradient-based design with competitive proxy service aesthetics
- **Complete Heroicons Integration**: Professional SVG iconography replacing all emojis (20+ custom icons)
- **Secure wallet management and checkout process**: Advanced payment processing with crypto support
- **Fully automated client provisioning**: Automatic subscription link generation and XUI panel integration
- **Reactive User Experience**: Livewire 3.x components with real-time updates and dynamic filtering
- **Mobile-First Responsive Design**: Optimized for all devices with Tailwind CSS utility framework
- **Scalable architecture**: Supporting multiple XUI panels and thousands of clients with Redis caching

---

---

# 📅 Full Server Deployment Process

## 🌎 1. Ubuntu Server Setup

```bash
# Connect to your server
ssh your_user@your_server_ip

# Update and install essential packages
sudo apt update && sudo apt upgrade -y
sudo apt install nginx mysql-server php php-cli php-fpm php-mysql php-curl php-mbstring php-xml php-bcmath php-redis unzip git curl supervisor redis-server -y

# Install Composer (PHP package manager)
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js and npm
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install Certbot for SSL
sudo apt install certbot python3-certbot-nginx -y
```

## 📂 2. Clone the Project

```bash
cd /var/www/
sudo git clone https://github.com/your_github_username/1000proxy.git
cd 1000proxy
sudo chown -R www-data:www-data .
```

## 🔠 3. Laravel Application Setup

```bash
# Copy environment variables
cp .env.example .env

# Edit the .env file with your credentials
nano .env

# Generate the application key
php artisan key:generate

# Install PHP and Node.js dependencies
composer install --optimize-autoloader --no-dev
npm install
npm run build

# Run database migrations
php artisan migrate

# Set correct permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

#Allow storage link
cd /PATH/YOUR/PROJECT/
php artisan storage:link
```

## 🚧 4. Configure Nginx

```bash
sudo nano /etc/nginx/sites-available/1000proxy
```

Paste the following configuration:

```nginx
server {
    server_name YOUR_DOMAIN www.YOUR_DOMAIN;

    root /var/www/1000proxy/public;
    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }

    listen 443 ssl; # managed by Certbot
    ssl_certificate /etc/letsencrypt/live/YOUR_DOMAIN/fullchain.pem; # managed by Certbot
    ssl_certificate_key /etc/letsencrypt/live/YOUR_DOMAIN/privkey.pem; # managed by Certbot
    include /etc/letsencrypt/options-ssl-nginx.conf; # managed by Certbot
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem; # managed by Certbot
}

server {
    if ($host = YOUR_DOMAIN) {
        return 301 https://$host$request_uri;
    } # managed by Certbot


    listen 80;
    server_name YOUR_DOMAIN www.YOUR_DOMAIN;
    return 404; # managed by Certbot
}

```

```bash
# Enable the site and reload Nginx
sudo ln -s /etc/nginx/sites-available/1000proxy /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## ⚡ 5. Enable SSL Certificate (Let's Encrypt)

```bash
sudo certbot --nginx -d your_domain.com
sudo certbot renew --dry-run
```

## 🛠️ 6. Configure Laravel Horizon

Create Supervisor config for Horizon:

```bash
sudo nano /etc/supervisor/conf.d/horizon.conf
```

Paste:

```conf
[program:horizon]
process_name=%(program_name)s
command=php /PATH/YOUR/PROJECT/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/PATH/YOUR/PROJECTstorage/logs/horizon.log
stopwaitsecs=3600
```

Then:

```bash
# Reload Supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start horizon
```

Enable Redis to auto-start:

```bash
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

---

# 📊 Environment Variables

Edit your `.env` file accordingly:

```dotenv
APP_NAME=1000Proxy
APP_ENV=production
APP_URL=https://your_domain.com
APP_KEY=base64:YOUR_APP_KEY
APP_DEBUG=false

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Mail
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mail_username
MAIL_PASSWORD=your_mail_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=hello@your_domain.com
MAIL_FROM_NAME="1000Proxy"

# XUI Panel
XUI_PANEL_URL=https://your_xui_panel_url
XUI_USERNAME=your_xui_username
XUI_PASSWORD=your_xui_password

# Stripe and NowPayments
STRIPE_SECRET=your_stripe_secret
STRIPE_WEBHOOK_SECRET=your_webhook_secret
NOWPAYMENTS_API_KEY=your_nowpayments_api_key
NOWPAYMENTS_IPN_SECRET=your_nowpayments_ipn_secret

# Telegram Bot
TELEGRAM_BOT_TOKEN=your_telegram_bot_token
TELEGRAM_WEBHOOK_URL=https://your_domain.com/telegram/webhook
TELEGRAM_SECRET_TOKEN=your_secret_token_for_webhook_security
```

---

# 💡 Useful Commands

```bash
# Clear cache and optimize
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate

# Install composer and npm dependencies
composer install && npm install && npm run build

# Telegram Bot Setup
php artisan telegram:set-webhook
php artisan telegram:test-bot
php artisan telegram:webhook-info

# Restart services
sudo systemctl reload nginx
sudo systemctl restart php8.1-fpm
sudo supervisorctl restart horizon
```

---

<p align="center">🌍 Made with <span style="color:red;">&hearts;</span> for the 1000Proxy Project by Osimorph
</p>
