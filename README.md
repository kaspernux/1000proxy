<div align="center">
  <img src="/images/1000proxy.png" width="400" alt="1000Proxy Logo">
</div>

<h1 align="center">1000Proxy - XUI-Based Proxy Client Sales Platform</h1>

<p align="center">
  <b>Version 2.0.0</b><br>
  <i>A professional Laravel 10 application for managing proxy client sales using XUI panels, multi-protocol support, crypto payments, wallet management, and Laravel Horizon queue system.</i>
</p>

---

# 🔧 Full Project Presentation

**1000Proxy** is a fully-featured web system built with Laravel 10 for automating **proxy client sales** via remote XUI panels. It is specifically designed for high-volume proxy services using various protocols and provides full backend management for crypto wallets, customer orders, and server client generation.

## 🎯 Main Features

- **Proxy Client Sales System** based on external **XUI Panels**.
- **Supports all major XUI protocols**:
  - `VLESS`
  - `VMESS`
  - `TROJAN`
  - `SHADOWSOCKS`
  - `SOCKS5`
  - `HTTP`
  - `REALITY (VLESS/VMESS+Reality)`
  - `gRPC` support for advanced tunneling
- **Crypto Wallet System**:
  - Customer wallets in USD (converted instantly from BTC, XMR, or SOL deposits)
  - Full transaction history and top-up tracking
- **Payments Integration**:
  - Stripe payments for fiat top-ups
  - NowPayments.io integration for crypto top-ups
- **Order Management**:
  - Automated order creation and processing
  - Client creation on XUI panel after payment success
- **XUI API Automation**:
  - Dynamic server inbound and client management
  - Automatic link generation (`vless://`, `vmess://`, `trojan://`, `ss://`, etc.)
  - QR Code generation for client configuration links
- **Queue System with Laravel Horizon**:
  - Background job processing for client creation and wallet management
  - Real-time monitoring with Horizon dashboard
- **Modern Web Technology Stack**:
  - Laravel 10 backend
  - Vite.js + TailwindCSS frontend
  - Redis queue management
  - Supervisor daemon for job workers

## 🏆 Highlights

- Secure wallet management and checkout process
- Fully automated client provisioning and subscription link generation
- Elegant, responsive, and fast UI/UX
- Scalable design supporting multiple XUI panels and thousands of clients

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

    root /var/www/teranova/public;
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

# Restart services
sudo systemctl reload nginx
sudo systemctl restart php8.1-fpm
sudo supervisorctl restart horizon
```

---

<p align="center">🌍 Made with <span style="color:red;">&hearts;</span> for the 1000Proxy Project by Osimorph
</p>

