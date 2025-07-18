<div align="center">
  <img src="/images/1000proxy.png" width="200" alt="1000Proxy Security">
  
  # 🛡️ Secure Server Setup Guide
  
  <p><em>Enterprise-grade security deployment for 1000proxy platform</em></p>
  
  <p>
    <a href="README.md">🏠 Back to Main</a> •
    <a href="docs/README.md">📚 Documentation</a> •
    <a href="#-quick-setup">🚀 Quick Setup</a> •
    <a href="#-security-features">🛡️ Security</a>
  </p>
  
  <img src="https://img.shields.io/badge/Security-Enterprise_Grade-red?style=for-the-badge" alt="Enterprise Security" />
  <img src="https://img.shields.io/badge/OS-Ubuntu_24.04-orange?style=for-the-badge" alt="Ubuntu Support" />
  <img src="https://img.shields.io/badge/Hardening-Multi_Layer-blue?style=for-the-badge" alt="Multi-Layer Security" />
  
</div>

---

## 📖 Overview

This guide provides **comprehensive, enterprise-grade Ubuntu 24.04 server setup** for the 1000proxy platform with **multi-layer security measures** designed to protect against sophisticated attacks and unauthorized access.

<div align="center">

### 🎯 **Security Implementation**

| Security Layer | Protection Level | Implementation |
|:---------------|:---------------:|:---------------|
| 🔥 **System Hardening** | ⭐⭐⭐⭐⭐ | Kernel tuning, service lockdown |
| 🚪 **SSH Security** | ⭐⭐⭐⭐⭐ | Custom port, key-only auth |
| 🛡️ **Firewall** | ⭐⭐⭐⭐⭐ | UFW + rate limiting |
| 🚨 **Intrusion Detection** | ⭐⭐⭐⭐⭐ | Fail2Ban + OSSEC IDS |
| 🔒 **Web Application** | ⭐⭐⭐⭐⭐ | ModSecurity + OWASP |

</div>

## 📋 Prerequisites

<table>
<tr>
<td width="50%">

### 🖥️ **System Requirements**
- ✅ Fresh Ubuntu 24.04 LTS Server
- ✅ Minimum 2GB RAM (4GB+ recommended)
- ✅ 20GB+ storage space
- ✅ Root or sudo access
- ✅ Internet connectivity

</td>
<td width="50%">

### 📝 **Preparation Checklist**
- 📧 Email address for notifications
- 🌐 Domain name (optional)
- 🔑 SSH keys (will be generated if needed)
- 📱 Phone for 2FA setup (optional)
- 🗒️ Notepad for credentials

</td>
</tr>
</table>

## 🛡️ Security Features

### Multi-Layer Security Protection
- **System Hardening**: Kernel parameter tuning, disabled unused services
- **SSH Hardening**: Custom port (2222), key-only authentication, fail2ban protection
- **Firewall**: UFW with rate limiting and connection controls
- **Intrusion Detection**: Fail2Ban, OSSEC IDS, real-time monitoring
- **Web Application Firewall**: ModSecurity with OWASP Core Rule Set
- **DDoS Protection**: Advanced iptables rules and rate limiting
- **Malware Protection**: ClamAV antivirus, rkhunter rootkit detection
- **File Integrity**: AIDE monitoring for unauthorized changes
- **Audit Logging**: Comprehensive system activity logging
- **Automated Security**: Unattended security updates

### Application Security
- **PHP Security**: Disabled dangerous functions, secure configuration
- **Database Security**: MySQL hardening, encrypted connections
- **Cache Security**: Redis authentication and secure configuration
- **Session Security**: Secure cookie settings, encrypted sessions
- **Input Validation**: CSRF protection, XSS prevention, SQL injection protection
- **File Upload Security**: Type validation, size limits, secure storage

## 🚀 Quick Setup

### Main Setup Launcher

```bash
# Clone the repository
git clone https://github.com/kaspernux/1000proxy.git
cd 1000proxy

# Make main launcher executable
chmod +x setup.sh

# Run the main setup launcher with interactive menu
sudo ./setup.sh
```

### Direct Quick Setup

```bash
# Run complete setup with interactive configuration
sudo ./scripts/quick-setup.sh
```

## 📋 Manual Setup Process

### Step 1: Core Security Setup

```bash
# Set executable permissions
chmod +x scripts/*.sh

# Run basic security hardening
sudo ./scripts/secure-server-setup.sh
```

**What this does:**
- Configures SSH security (port 2222, key-only auth)
- Sets up UFW firewall with minimal required ports
- Installs and configures Fail2Ban
- Hardens system security settings
- Creates non-root user 'proxy1000'
- Sets up automatic security updates

### Step 2: Advanced Security Setup

```bash
# Run advanced security measures
sudo ./scripts/advanced-security-setup.sh
```

**What this does:**
- Configures advanced DDoS protection
- Sets up OSSEC Intrusion Detection System
- Implements real-time security monitoring
- Configures database security hardening
- Sets up application-level security scanning

### Step 3: Deploy 1000proxy Application

```bash
# Set repository URL (if different)
export REPO_URL="https://github.com/kaspernux/1000proxy.git"

# Deploy the application
sudo ./scripts/deploy-1000proxy.sh
```

**What this does:**
- Clones/updates the 1000proxy repository
- Installs Composer and NPM dependencies
- Configures environment and database
- Sets up queue workers and scheduler
- Configures web server and SSL
- Interactive payment gateway configuration
- Application security monitoring
- Automated backup system

### Step 4: Quick Setup Alternative

```bash
# Run everything with one command
sudo ./scripts/quick-setup.sh
```

**What this does:**
- Updates system and installs security packages
- Hardens SSH (moves to port 2222, disables root login)
- Configures UFW firewall with rate limiting
- Sets up Fail2Ban intrusion detection
- Installs and configures: PHP 8.3, Nginx, MySQL 8.0, Redis
- Configures SSL with Let's Encrypt (if domain provided)
- Sets up automated backups and monitoring
- Creates comprehensive audit logging

### Step 3: Advanced Security Layer

```bash
# Run advanced security configuration
sudo ./advanced-security-setup.sh
```

**What this does:**
- Installs ModSecurity Web Application Firewall
- Configures advanced DDoS protection
- Sets up OSSEC Intrusion Detection System
- Implements real-time security monitoring
- Configures database security hardening
- Sets up application-level security scanning

### Step 4: Deploy 1000proxy Application

```bash
# Set repository URL (if different)
export REPO_URL="https://github.com/kaspernux/1000proxy.git"

# Deploy the application
sudo ./deploy-1000proxy.sh
```

**What this does:**
- Clones/updates the 1000proxy repository
- Installs Composer and NPM dependencies
- Configures environment and database
- Sets up queue workers and scheduler
- Optimizes for production performance
- Configures application monitoring and backups

## � Payment Gateway Configuration

The deployment script supports interactive configuration for multiple payment gateways:

### Stripe Configuration
```bash
STRIPE_KEY=pk_live_your_stripe_key
STRIPE_SECRET=sk_live_your_stripe_secret
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
```

### PayPal Configuration
```bash
PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_CLIENT_SECRET=your_paypal_client_secret
PAYPAL_WEBHOOK_ID=your_webhook_id
PAYPAL_MODE=sandbox  # or 'live' for production
```

### NowPayments Configuration
```bash
NOWPAYMENTS_API_KEY=your_nowpayments_api_key
NOWPAYMENTS_WEBHOOK_SECRET=your_webhook_secret
```

### Telegram Bot Configuration
```bash
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_WEBHOOK_URL=https://your-domain.com/telegram/webhook
```

**Note:** The deployment script will prompt you to configure each gateway. You can skip any gateway by pressing Enter if not needed.

---

## 🔧 Post-Installation Management

### Environment Variables

Set these before running scripts to customize the installation:

```bash
export DOMAIN="your-domain.com"           # Your domain name
export EMAIL="admin@your-domain.com"      # Admin email
export DB_PASSWORD="custom_db_password"   # Custom database password
export REDIS_PASSWORD="custom_redis_pass" # Custom Redis password
export REPO_URL="your_repo_url"           # Custom repository URL
```

### Security Customization

#### SSH Configuration
- **Default Port**: 2222 (customizable in script)
- **Authentication**: Key-only (password disabled)
- **Root Login**: Disabled
- **Connection Limits**: Max 2 sessions per user

#### Firewall Rules
- **SSH**: Port 2222 with rate limiting
- **HTTP**: Port 80 (redirects to HTTPS)
- **HTTPS**: Port 443 with rate limiting
- **Custom**: Add your application ports in the script

#### Rate Limiting
- **Login endpoints**: 5 requests per minute
- **API endpoints**: 100 requests per minute
- **General requests**: 10 requests per second
- **Admin panel**: 20 requests per minute

## 📊 Monitoring and Maintenance

### Security Monitoring Commands

```bash
# Check firewall status
sudo ufw status verbose

# Check fail2ban status
sudo fail2ban-client status
sudo fail2ban-client status ssh

# View security logs
sudo tail -f /var/log/security-monitor.log
sudo tail -f /var/log/realtime-security.log

# Run security scans
sudo lynis audit system
sudo /usr/local/bin/security-monitor.sh

# Check system health
sudo /usr/local/bin/1000proxy-health-check.sh
```

### Application Management

```bash
# Check application status
sudo systemctl status nginx
sudo systemctl status php8.3-fpm
sudo systemctl status mysql
sudo systemctl status redis
sudo systemctl status 1000proxy-queue

# View application logs
sudo tail -f /var/www/1000proxy/storage/logs/laravel.log
sudo tail -f /var/log/nginx/1000proxy.access.log
sudo tail -f /var/log/nginx/1000proxy.error.log

# Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.3-fpm
sudo systemctl restart 1000proxy-queue

# Run Laravel commands
cd /var/www/1000proxy
sudo -u proxy1000 php artisan <command>
```

### Backup and Recovery

```bash
# Manual backup
sudo /usr/local/bin/backup-1000proxy-app.sh

# View backup files
ls -la /var/backups/1000proxy/

# Restore from backup (example)
sudo mysql 1000proxy < /var/backups/1000proxy/YYYYMMDD_HHMMSS/database.sql
```

## 🔐 Security Best Practices

### After Installation

1. **Change Default Passwords**: Update all default passwords in environment files
2. **SSH Key Setup**: Copy SSH keys for the proxy1000 user to connect remotely
3. **Admin Account**: Create a strong admin account for the application
4. **Email Configuration**: Set up SMTP for security notifications
5. **Backup Testing**: Test backup and restore procedures
6. **Security Scanning**: Run initial security scans and address findings

### Regular Maintenance

1. **Monitor Logs**: Regularly check security and application logs
2. **Update System**: Security updates are automated, but check manually monthly
3. **Review Access**: Regularly review user accounts and access logs  
4. **Backup Verification**: Test backups monthly
5. **Security Scans**: Run weekly security scans with Lynis
6. **Certificate Renewal**: SSL certificates auto-renew, but monitor status

### Additional Security Measures

1. **VPN Access**: Consider VPN-only access for admin panel
2. **IP Whitelisting**: Restrict admin access to specific IP addresses
3. **Two-Factor Authentication**: Implement 2FA for admin accounts
4. **Database Encryption**: Enable MySQL encryption at rest
5. **Log Shipping**: Send logs to external SIEM system
6. **Network Segmentation**: Use separate networks for different services

## 🚨 Incident Response

### If Under Attack

1. **Check Fail2Ban**: `sudo fail2ban-client status`
2. **Block IPs**: `sudo fail2ban-client set ssh banip <IP>`
3. **Check Connections**: `sudo netstat -tuln | grep :443`
4. **Review Logs**: Check `/var/log/realtime-security.log`
5. **Update Rules**: Add more restrictive firewall rules if needed

### Emergency Commands

```bash
# Block all HTTP/HTTPS traffic temporarily
sudo ufw deny 80
sudo ufw deny 443

# Stop application services
sudo systemctl stop nginx
sudo systemctl stop 1000proxy-queue

# Enable maintenance mode
cd /var/www/1000proxy && sudo -u proxy1000 php artisan down

# Check for rootkits
sudo rkhunter --check
sudo chkrootkit

# Full system scan
sudo clamscan -r /var/www/1000proxy
```

## 📞 Support and Troubleshooting

### Common Issues

1. **SSH Connection Refused**: Check if SSH is running on port 2222
2. **Website Not Loading**: Check Nginx status and logs
3. **Database Connection Error**: Verify MySQL service and credentials
4. **Queue Not Processing**: Check queue worker service status
5. **SSL Certificate Issues**: Verify domain DNS and certificate status

### Log Locations

- **System Logs**: `/var/log/syslog`, `/var/log/auth.log`
- **Security Logs**: `/var/log/security-monitor.log`, `/var/log/realtime-security.log`
- **Web Server**: `/var/log/nginx/1000proxy.*.log`
- **Application**: `/var/www/1000proxy/storage/logs/laravel.log`
- **Database**: `/var/log/mysql/error.log`
- **PHP**: `/var/log/php8.3-fpm.log`

### Getting Help

1. **Check Documentation**: Review setup reports in `/root/`
2. **Check Logs**: Always check relevant log files first
3. **System Status**: Use `systemctl status <service>` for service issues
4. **Security Status**: Use security monitoring commands
5. **Community Support**: Refer to Laravel and security community resources

## 📄 Generated Reports

After setup completion, check these reports:

- `/root/1000proxy-security-report.txt` - Complete security configuration
- `/root/advanced-security-report.txt` - Advanced security features  
- `/root/1000proxy-deployment-report.txt` - Application deployment details

These files contain passwords and sensitive information - secure them properly!

---

**⚠️ IMPORTANT SECURITY REMINDERS**

1. **SSH Port**: SSH is now on port 2222, not 22
2. **Root Access**: Root login is disabled, use sudo with proxy1000 user
3. **Passwords**: All default passwords must be changed
4. **Backups**: Verify backups are working and test restore procedures
5. **Updates**: Keep system updated and monitor security advisories
6. **Monitoring**: Regularly check security logs and alerts

Your 1000proxy server is now protected with enterprise-level security measures designed to withstand sophisticated attacks. Regular monitoring and maintenance will ensure continued security.
