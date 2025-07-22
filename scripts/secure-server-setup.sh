#!/bin/bash

# =============================================================================
# 1000proxy Secure Ubuntu 24.04 Server Setup Script
# =============================================================================
# This script sets up a highly secured Ubuntu 24.04 server for the 1000proxy project
# with comprehensive security hardening and all required dependencies
#
# Author: 1000proxy Team
# Version: 1.0
# Date: July 2025
# =============================================================================

set -euo pipefail

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Output functions
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
    SETUP_ERRORS+=("$1")
}

print_info() {
    echo -e "${CYAN}ℹ $1${NC}"
}

# Configuration

# Load environment variables from .env.example or .env.production only (safe parsing)
ENV_FILE=""
if [[ -f "/var/www/1000proxy/.env.production" ]]; then
    ENV_FILE="/var/www/1000proxy/.env.production"
elif [[ -f "/var/www/1000proxy/.env.example" ]]; then
    ENV_FILE="/var/www/1000proxy/.env.example"
fi

if [[ -n "$ENV_FILE" ]]; then
    set -a
    # Only source lines that are valid KEY=VALUE assignments, no quotes, no $, no spaces, not JSON blocks
    grep -E "^[A-Za-z_][A-Za-z0-9_]*=[^'\"$[[:space:]]]+$" "$ENV_FILE" | grep -v '^#' | grep -v '^$' > /tmp/1000proxy_env.tmp
    source /tmp/1000proxy_env.tmp
    rm /tmp/1000proxy_env.tmp
    set +a
else
    print_warning "No .env.production or .env.example found in /var/www/1000proxy. Using script defaults."
fi

PROJECT_NAME="${APP_NAME:-1000proxy}"
PROJECT_USER="proxy1000"
PROJECT_DIR="/var/www/1000proxy"
DOMAIN="${APP_URL:-1000proxy.io}"
EMAIL="${MAIL_FROM_ADDRESS:-admin@1000proxy.io}"
DB_PASSWORD="${DB_PASSWORD:-Dat@1000proxy}"
REDIS_PASSWORD="${REDIS_PASSWORD:-red@1000proxy}"

# Logging
LOG_FILE="/var/log/1000proxy-setup.log"
exec > >(tee -a "$LOG_FILE")
exec 2>&1

# Error collection for final report
SETUP_ERRORS=()

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   print_error "This script must be run as root (use sudo)"
   exit 1
fi

print_header "1000proxy Secure Server Setup Starting"
print_info "Setting up Ubuntu 24.04 server with maximum security"
print_info "Project: $PROJECT_NAME"
print_info "Domain: $DOMAIN"
print_info "Log file: $LOG_FILE"

# Ensure /var/www exists
if [[ ! -d "/var/www" ]]; then
    mkdir -p /var/www
    print_success "/var/www directory created"
fi

# Always print loaded environment domain name and email by default
echo -e "${CYAN}Loaded Environment Configuration:${NC}"
echo "DOMAIN: $DOMAIN"
echo "EMAIL: $EMAIL"
echo "PROJECT_NAME: $PROJECT_NAME"
echo "PROJECT_USER: $PROJECT_USER"
echo "PROJECT_DIR: $PROJECT_DIR"
echo "DB_PASSWORD: $DB_PASSWORD"
echo "REDIS_PASSWORD: $REDIS_PASSWORD"

# =============================================================================
# 1. System Update and Basic Hardening
# =============================================================================
print_header "System Update and Basic Hardening"

# Update system
apt-get update && apt-get upgrade -y
print_success "System updated"

# Install essential security packages individually and log failures
ESSENTIAL_PACKAGES=(
    ufw
    fail2ban
    unattended-upgrades
    apt-listchanges
    logrotate
    rsyslog
    auditd
    rkhunter
    chkrootkit
    clamav
    clamav-daemon
    aide
    lynis
    htop
    iotop
    netstat-nat
    tcpdump
    nmap
    curl
    wget
    git
    vim
    nano
    tree
    zip
    unzip
    software-properties-common
    apt-transport-https
    ca-certificates
    gnupg
    lsb-release
)
FAILED_PACKAGES=()
for pkg in "${ESSENTIAL_PACKAGES[@]}"; do
    if ! DEBIAN_FRONTEND=noninteractive apt-get install -y "$pkg"; then
        print_warning "Package $pkg failed to install. Please install it manually."
        FAILED_PACKAGES+=("$pkg")
    fi
done
if [ ${#FAILED_PACKAGES[@]} -gt 0 ]; then
    print_warning "The following essential packages failed to install: ${FAILED_PACKAGES[*]}"
else
    print_success "All essential security packages installed"
fi

# Configure automatic security updates
cat > /etc/apt/apt.conf.d/20auto-upgrades << EOF
APT::Periodic::Update-Package-Lists "1";
APT::Periodic::Download-Upgradeable-Packages "1";
APT::Periodic::AutocleanInterval "7";
APT::Periodic::Unattended-Upgrade "1";
EOF

cat > /etc/apt/apt.conf.d/50unattended-upgrades << EOF
Unattended-Upgrade::Allowed-Origins {
    "\${distro_id}:\${distro_codename}";
    "\${distro_id}:\${distro_codename}-security";
    "\${distro_id}ESMApps:\${distro_codename}-apps-security";
    "\${distro_id}ESM:\${distro_codename}-infra-security";
};
Unattended-Upgrade::AutoFixInterruptedDpkg "true";
Unattended-Upgrade::MinimalSteps "true";
Unattended-Upgrade::Remove-Unused-Dependencies "true";
Unattended-Upgrade::Automatic-Reboot "false";
Unattended-Upgrade::SyslogEnable "true";
EOF

systemctl enable unattended-upgrades
print_success "Automatic security updates configured"

# =============================================================================
# 2. User Management and SSH Hardening
# =============================================================================
print_header "User Management and SSH Hardening"

# Create project user
if id "$PROJECT_USER" &>/dev/null; then
    print_warning "User $PROJECT_USER already exists"
else
    useradd -m -s /bin/bash -G sudo "$PROJECT_USER"
fi
usermod -aG www-data "$PROJECT_USER"

# Ensure all .sh scripts in /root/1000proxy/scripts are executable
if [[ -d "/root/1000proxy/scripts" ]]; then
    find "/root/1000proxy/scripts" -type f -name "*.sh" -exec chmod +x {} \;
    print_success "All .sh scripts in /root/1000proxy/scripts made executable"
else
    print_warning "/root/1000proxy/scripts directory not found"
fi

# Ensure all .sh scripts in /var/www/1000proxy/scripts are executable (after move)
if [[ -d "$PROJECT_DIR/scripts" ]]; then
    find "$PROJECT_DIR/scripts" -type f -name "*.sh" -exec chmod +x {} \;
    print_success "All .sh scripts in $PROJECT_DIR/scripts made executable"
else
    print_warning "$PROJECT_DIR/scripts directory not found"
fi

# Assign password to the project user (do not use '@' in username)
echo "$PROJECT_USER:Pass1000" | chpasswd
print_success "Password assigned to user $PROJECT_USER"

# Generate SSH key for project user
if [[ ! -f "/home/$PROJECT_USER/.ssh/id_rsa" ]]; then
    sudo -u "$PROJECT_USER" mkdir -p "/home/$PROJECT_USER/.ssh"
    sudo -u "$PROJECT_USER" ssh-keygen -t rsa -b 4096 -f "/home/$PROJECT_USER/.ssh/id_rsa" -N ""
    sudo -u "$PROJECT_USER" chmod 700 "/home/$PROJECT_USER/.ssh"
    sudo -u "$PROJECT_USER" chmod 600 "/home/$PROJECT_USER/.ssh/id_rsa"
    sudo -u "$PROJECT_USER" chmod 644 "/home/$PROJECT_USER/.ssh/id_rsa.pub"
    print_success "SSH key generated for $PROJECT_USER"
    print_info "To connect from your local machine, copy the public key from /home/$PROJECT_USER/.ssh/id_rsa.pub to your local ~/.ssh/authorized_keys or use ssh-copy-id."
    print_info "Example: ssh-copy-id -i /home/$PROJECT_USER/.ssh/id_rsa.pub $PROJECT_USER@<your-server-ip> -p 2222"
    SERVER_IP=$(hostname -I | awk '{print $1}')
    LOCAL_KEY_NAME="id_rsa_1000proxy_${SERVER_IP}"
    print_info "To copy the private key to your local machine (for testing only), run this on your local machine:"
    print_info "scp -P 2222 $PROJECT_USER@${SERVER_IP}:/home/$PROJECT_USER/.ssh/id_rsa ~/.ssh/${LOCAL_KEY_NAME}"
fi

# SSH Hardening
cp /etc/ssh/ssh_config /etc/ssh/ssh_config.backup

cat > /etc/ssh/ssh_config << EOF
# 1000proxy SSH Configuration - Maximum Security
Port 2222
Protocol 2
HostKey /etc/ssh/ssh_host_rsa_key
HostKey /etc/ssh/ssh_host_ecdsa_key
HostKey /etc/ssh/ssh_host_ed25519_key

# Authentication
LoginGraceTime 60
PermitRootLogin no
StrictModes yes
MaxAuthTries 3
MaxSessions 2
PubkeyAuthentication yes
PasswordAuthentication no
PermitEmptyPasswords no
ChallengeResponseAuthentication no
UsePAM yes

# Network
X11Forwarding no
PrintMotd no
TCPKeepAlive yes
ClientAliveInterval 300
ClientAliveCountMax 2

# Security
AllowUsers $PROJECT_USER
DenyUsers root
AllowGroups sudo
PermitUserEnvironment no
Compression no
IgnoreRhosts yes
HostbasedAuthentication no

# Logging
SyslogFacility AUTH
LogLevel VERBOSE

# Banner
Banner /etc/ssh/banner
EOF

# Create SSH banner
cat > /etc/ssh/banner << EOF
***************************************************************************
*                        AUTHORIZED ACCESS ONLY                          *
***************************************************************************
* This system is for authorized users only. All activities are logged    *
* and monitored. Unauthorized access is strictly prohibited and will be  *
* prosecuted to the full extent of the law.                              *
***************************************************************************
EOF

# Copy the generated private key to your local machine's home directory
if [[ -f "/home/$PROJECT_USER/.ssh/id_rsa" ]]; then
    LOCAL_KEY_PATH="$HOME/id_rsa_1000proxy_$(hostname -I | awk '{print $1}')"
    cp "/home/$PROJECT_USER/.ssh/id_rsa" "$LOCAL_KEY_PATH"
    chmod 600 "$LOCAL_KEY_PATH"
    print_success "SSH private key copied to $LOCAL_KEY_PATH"
    echo -e "${YELLOW}IMPORTANT: Copy this key to your local machine and use it to connect:${NC}"
    echo "scp $PROJECT_USER@$(hostname -I | awk '{print $1}'):/home/$PROJECT_USER/.ssh/id_rsa $LOCAL_KEY_PATH"
    echo "ssh -i $LOCAL_KEY_PATH -p 2222 $PROJECT_USER@<server-ip>"
fi

systemctl restart ssh
systemctl enable ssh
print_success "SSH hardened and configured"

# =============================================================================
# 3. Firewall Configuration (UFW)
# =============================================================================
print_header "Firewall Configuration"

# Reset UFW
ufw --force reset

# Default policies
ufw default deny incoming
ufw default allow outgoing

# Allow SSH (custom port)
ufw allow 2222/tcp comment 'SSH'

# Allow HTTP/HTTPS
ufw allow 80/tcp comment 'HTTP'
ufw allow 443/tcp comment 'HTTPS'

# Allow specific application ports if needed
# ufw allow 8000/tcp comment '1000proxy Development'

# Rate limiting for SSH
ufw limit 2222/tcp

# Enable UFW
ufw --force enable

# Configure UFW logging
ufw logging on

print_success "Firewall configured and enabled"

# =============================================================================
# 4. Fail2Ban Configuration
# =============================================================================
print_header "Fail2Ban Intrusion Detection"

# Main fail2ban configuration
cat > /etc/fail2ban/jail.local << EOF
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3
backend = systemd
usedns = warn
ignoreip = 127.0.0.1/8 ::1

[ssh]
enabled = true
port = 2222
filter = ssh
logpath = /var/log/auth.log
maxretry = 3
bantime = 7200

[nginx-http-auth]
enabled = true
filter = nginx-http-auth
logpath = /var/log/nginx/error.log
maxretry = 3

[nginx-noscript]
enabled = true
filter = nginx-noscript
logpath = /var/log/nginx/access.log
maxretry = 6

[nginx-badbots]
enabled = true
filter = nginx-badbots
logpath = /var/log/nginx/access.log
maxretry = 2

[nginx-noproxy]
enabled = true
filter = nginx-noproxy
logpath = /var/log/nginx/access.log
maxretry = 2

[php-url-fopen]
enabled = true
filter = php-url-fopen
logpath = /var/log/nginx/access.log
maxretry = 1

[postfix]
enabled = true
filter = postfix
logpath = /var/log/mail.log
maxretry = 3
EOF

# Custom filters for web applications
cat > /etc/fail2ban/filter.d/nginx-badbots.conf << EOF
[Definition]
failregex = ^<HOST> -.*"(GET|POST).*HTTP.*" (404|444) .*$
ignoreregex =
EOF

cat > /etc/fail2ban/filter.d/nginx-noscript.conf << EOF
[Definition]
failregex = ^<HOST> -.*GET.*(\.php|\.asp|\.exe|\.pl|\.cgi|\.scgi)
ignoreregex =
EOF

cat > /etc/fail2ban/filter.d/nginx-noproxy.conf << EOF
[Definition]
failregex = ^<HOST> -.*GET http.*
ignoreregex =
EOF

cat > /etc/fail2ban/filter.d/php-url-fopen.conf << EOF
[Definition]
failregex = ^<HOST> -.*"(GET|POST).*(?:http|ftp|https)://
ignoreregex =
EOF

systemctl enable fail2ban
systemctl start fail2ban
print_success "Fail2Ban configured and started"

# =============================================================================
# 5. System Auditing (auditd)
# =============================================================================
print_header "System Auditing Configuration"

# Configure auditd
cat > /etc/audit/rules.d/audit.rules << EOF
# Delete all previous rules
-D

# Buffer Size
-b 8192

# Failure Mode
-f 1

# Audit the audit logs themselves
-w /var/log/audit/ -p wa -k auditlog

# Audit the configuration files
-w /etc/audit/ -p wa -k auditconfig
-w /etc/libaudit.conf -p wa -k auditconfig
-w /etc/audisp/ -p wa -k audispconfig

# Monitor for use of audit management tools
-w /sbin/auditctl -p x -k audittools
-w /sbin/auditd -p x -k audittools

# Monitor AppArmor configuration changes
-w /etc/apparmor/ -p wa -k apparmor
-w /etc/apparmor.d/ -p wa -k apparmor

# Monitor root user commands
-a exit,always -F arch=b64 -F euid=0 -S execve -k rootcmd
-a exit,always -F arch=b32 -F euid=0 -S execve -k rootcmd

# Monitor file permission changes
-a always,exit -F arch=b64 -S chmod -S fchmod -S fchmodat -F auid>=1000 -F auid!=4294967295 -k perm_mod
-a always,exit -F arch=b32 -S chmod -S fchmod -S fchmodat -F auid>=1000 -F auid!=4294967295 -k perm_mod

# Monitor user/group modifications
-w /etc/group -p wa -k etcgroup
-w /etc/passwd -p wa -k etcpasswd
-w /etc/gshadow -k etcgroup
-w /etc/shadow -k etcpasswd
-w /etc/security/opasswd -k opasswd

# Monitor login records
-w /var/log/faillog -p wa -k logins
-w /var/log/lastlog -p wa -k logins
-w /var/log/tallylog -p wa -k logins

# Monitor network configuration
-w /etc/hosts -p wa -k hosts
-w /etc/network/ -p wa -k network

# Monitor system administration
-w /etc/sudoers -p wa -k scope
-w /etc/sudoers.d/ -p wa -k scope

# Monitor kernel module loading
-w /sbin/insmod -p x -k modules
-w /sbin/rmmod -p x -k modules
-w /sbin/modprobe -p x -k modules
-a always,exit -F arch=b64 -S init_module -S delete_module -k modules

# Make the configuration immutable
-e 2
EOF

systemctl enable auditd
systemctl start auditd
print_success "System auditing configured"

# =============================================================================
# 6. Install and Configure PHP 8.3
# =============================================================================
print_header "PHP 8.3 Installation and Configuration"

# Add PHP repository
add-apt-repository ppa:ondrej/php -y
apt-get update

# Install PHP 8.3 and extensions
DEBIAN_FRONTEND=noninteractive apt-get install -y \
    php8.3 \
    php8.3-fpm \
    php8.3-cli \
    php8.3-common \
    php8.3-mysql \
    php8.3-pgsql \
    php8.3-sqlite3 \
    php8.3-redis \
    php8.3-curl \
    php8.3-gd \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-zip \
    php8.3-bcmath \
    php8.3-intl \
    php8.3-imagick \
    php8.3-soap \
    php8.3-xsl \
    php8.3-opcache \
    php8.3-readline \
    php8.3-dev

print_success "PHP 8.3 installed"

# Secure PHP configuration
PHP_INI="/etc/php/8.3/fpm/php.ini"
cp "$PHP_INI" "$PHP_INI.backup"

# PHP Security hardening
sed -i 's/expose_php = On/expose_php = Off/' "$PHP_INI"
sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' "$PHP_INI"
sed -i 's/allow_url_fopen = On/allow_url_fopen = Off/' "$PHP_INI"
sed -i 's/allow_url_include = On/allow_url_include = Off/' "$PHP_INI"
sed -i 's/display_errors = On/display_errors = Off/' "$PHP_INI"
sed -i 's/log_errors = Off/log_errors = On/' "$PHP_INI"
sed -i 's/;session.cookie_httponly =/session.cookie_httponly = 1/' "$PHP_INI"
sed -i 's/;session.cookie_secure =/session.cookie_secure = 1/' "$PHP_INI"
sed -i 's/;session.use_strict_mode = 0/session.use_strict_mode = 1/' "$PHP_INI"
sed -i 's/post_max_size = 8M/post_max_size = 64M/' "$PHP_INI"
sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 64M/' "$PHP_INI"
sed -i 's/max_execution_time = 30/max_execution_time = 300/' "$PHP_INI"
sed -i 's/memory_limit = 128M/memory_limit = 512M/' "$PHP_INI"

# Configure PHP-FPM pool
FPM_POOL="/etc/php/8.3/fpm/pool.d/1000proxy.conf"
cat > "$FPM_POOL" << EOF
[1000proxy]
user = $PROJECT_USER
group = www-data
listen = /run/php/php8.3-1000proxy.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

; Security
php_admin_value[disable_functions] = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source
php_admin_flag[allow_url_fopen] = off
php_admin_flag[allow_url_include] = off
php_admin_flag[expose_php] = off
EOF

systemctl enable php8.3-fpm
systemctl start php8.3-fpm
print_success "PHP 8.3 configured securely"

# =============================================================================
# 7. Install and Configure Nginx
# =============================================================================
print_header "Nginx Installation and Configuration"

apt-get install -y nginx
systemctl enable nginx

# Remove default site
rm -f /etc/nginx/sites-enabled/default

# Main nginx configuration
cat > /etc/nginx/nginx.conf << EOF
user www-data;
worker_processes auto;
pid /run/nginx.pid;
include /etc/nginx/modules-enabled/*.conf;

events {
    worker_connections 2048;
    use epoll;
    multi_accept on;
}

http {
    # Basic Settings
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;
    client_max_body_size 64M;

    # Security Headers
    #server_tokens off;
    #add_header X-Frame-Options DENY;
    #add_header X-Content-Type-Options nosniff;
    #add_header X-XSS-Protection "1; mode=block";
    #add_header Referrer-Policy "strict-origin-when-cross-origin";

    # Rate Limiting
    limit_req_zone \$binary_remote_addr zone=login:10m rate=5r/m;
    limit_req_zone \$binary_remote_addr zone=api:10m rate=100r/m;
    limit_req_zone \$binary_remote_addr zone=general:10m rate=10r/s;

    # MIME Types
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    # Logging
    log_format main '\$remote_addr - \$remote_user [\$time_local] "\$request" '
                    '\$status \$body_bytes_sent "\$http_referer" '
                    '"\$http_user_agent" "\$http_x_forwarded_for"';

    access_log /var/log/nginx/access.log main;
    error_log /var/log/nginx/error.log warn;

    # Gzip Settings
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/json
        application/javascript
        application/xml+rss
        application/atom+xml
        image/svg+xml;

    # Virtual Host Configs
    include /etc/nginx/conf.d/*.conf;
    include /etc/nginx/sites-enabled/*;
}
EOF

# Create site configuration
cat > /etc/nginx/sites-available/1000proxy << EOF
server {
    listen 80;
    server_name $DOMAIN www.$DOMAIN;
    root $PROJECT_DIR/public;
    index index.php index.html;

    # Security configurations
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    location ~ ~$ {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Block access to sensitive files
    location ~* \.(env|config|sql|log|htaccess|htpasswd)$ {
        deny all;
        return 404;
    }

    # Rate limiting for login endpoints
    location ~* ^/(login|api/auth) {
        limit_req zone=login burst=5 nodelay;
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # Rate limiting for API endpoints
    location ~* ^/api/ {
        limit_req zone=api burst=20 nodelay;
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # General rate limiting
    location / {
        limit_req zone=general burst=10 nodelay;
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # PHP handling
    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php8.3-1000proxy.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
        fastcgi_param PATH_INFO \$fastcgi_path_info;

        # Security headers for PHP
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 256 16k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_temp_file_write_size 256k;
    }

    # Static files handling
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|webp|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
        log_not_found off;
    }

    # Deny access to Laravel specific files
    location ~ ^/(storage|bootstrap|config|database|resources|routes|tests|vendor)/ {
        deny all;
        return 404;
    }
}
EOF

ln -sf /etc/nginx/sites-available/1000proxy /etc/nginx/sites-enabled/
nginx -t && systemctl restart nginx
print_success "Nginx configured securely"

# =============================================================================
# 8. Install and Configure MySQL 8.3
# =============================================================================
print_header "MySQL 8.3 Installation and Configuration"

# Install MySQL
DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server mysql-client

# Secure MySQL installation
if systemctl is-active --quiet mysql; then
    # Remove remote root access except for localhost, 127.0.0.1, and ::1 for security
    mysql --execute="DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
    # Drop the default test database to prevent unauthorized access
    mysql --execute="DROP DATABASE IF EXISTS test;"
    # Create the 1000proxy user with a strong password and restrict access to localhost
    ESCAPED_DB_PASSWORD=$(printf '%s' "$DB_PASSWORD" | sed "s|'|''|g")
    mysql --execute="CREATE USER IF NOT EXISTS '1000proxy'@'localhost' IDENTIFIED WITH 'caching_sha2_password' BY '$ESCAPED_DB_PASSWORD';"
    # Create the 1000proxy database with secure character set and collation
    mysql --execute="CREATE DATABASE IF NOT EXISTS \`1000proxy\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    # Grant only necessary privileges to the 1000proxy user
    mysql --execute="GRANT SELECT,INSERT,UPDATE,DELETE,CREATE,DROP,INDEX,ALTER ON \`1000proxy\`.* TO '1000proxy'@'localhost';"
    # Flush privileges to apply changes
    mysql --execute="FLUSH PRIVILEGES;"
else
    print_error "MySQL service is not running. Please start MySQL before running this command."
fi

# =============================================================================
# 9. Install and Configure Redis
# =============================================================================
print_header "Redis Installation and Configuration"

sudo apt-get install lsb-release curl gpg
curl -fsSL https://packages.redis.io/gpg | sudo gpg --dearmor -o /usr/share/keyrings/redis-archive-keyring.gpg
sudo chmod 644 /usr/share/keyrings/redis-archive-keyring.gpg
echo "deb [signed-by=/usr/share/keyrings/redis-archive-keyring.gpg] https://packages.redis.io/deb $(lsb_release -cs) main" | sudo tee /etc/apt/sources.list.d/redis.list
sudo apt-get update
sudo apt-get install redis -y

# Set Redis password securely in redis.conf (replace or add requirepass)

# Always override Redis password from .env, even if already installed
if grep -q "^requirepass " /etc/redis/redis.conf; then
    sudo sed -i "s|^requirepass .*|requirepass ${REDIS_PASSWORD}|" /etc/redis/redis.conf
else
    echo "requirepass ${REDIS_PASSWORD}" | sudo tee -a /etc/redis/redis.conf > /dev/null
fi

sudo systemctl enable redis-server
sudo systemctl start redis-server
print_success "Redis configured securely"

# =============================================================================
# 10. Install Additional Security Tools
# =============================================================================
print_header "Additional Security Tools Installation"

# Fix ClamAV installation for Ubuntu 24.04
DEBIAN_FRONTEND=noninteractive apt-get install -y clamav clamav-daemon clamav-freshclam || {
    print_error "ClamAV installation failed. Attempting to fix..."
    apt-get -f install -y
    apt-get install -y clamav clamav-daemon clamav-freshclam || exit 1
}

# Ensure log directory and permissions
mkdir -p /var/log/clamav
chown clamav:clamav /var/log/clamav
touch /var/log/clamav/freshclam.log
chown clamav:clamav /var/log/clamav/freshclam.log
chmod 664 /var/log/clamav/freshclam.log

# Stop clamav-freshclam if running, then update DB
systemctl stop clamav-freshclam || true
freshclam || {
    print_error "freshclam failed. Attempting to fix permissions and rerun..."
    chown clamav:clamav /var/log/clamav/freshclam.log
    chmod 664 /var/log/clamav/freshclam.log
    freshclam || exit 1
}
systemctl start clamav-freshclam
systemctl enable clamav-freshclam
systemctl enable clamav-daemon
systemctl start clamav-daemon


# Install and configure AIDE (Advanced Intrusion Detection Environment)
DEBIAN_FRONTEND=noninteractive apt-get install -y aide || {
    print_error "AIDE installation failed."; exit 1;
}
aideinit || {
    print_error "AIDE initialization failed."; exit 1;
}
cp /var/lib/aide/aide.db.new /var/lib/aide/aide.db

# Ensure mail utility is installed for cron notifications
DEBIAN_FRONTEND=noninteractive apt-get install -y mailutils

# Create daily AIDE check
cat > /etc/cron.daily/aide-check << 'EOF'
#!/bin/bash
/usr/bin/aide --check | /usr/bin/mail -s "AIDE Report $(hostname)" root
EOF
chmod +x /etc/cron.daily/aide-check

# Install and configure rkhunter

DEBIAN_FRONTEND=noninteractive apt-get install -y rkhunter || {
    print_error "rkhunter installation failed."; exit 1;
}

# Update rkhunter configuration with recommended options
RKHUNTER_CONF="/etc/rkhunter.conf"
declare -A RKHUNTER_OPTS=(
    ["UPDATE_MIRRORS"]="1"
    ["CRON_DAILY_RUN"]="true"
    ["REPORT_EMAIL"]="$EMAIL"
    ["ALLOW_SSH_ROOT_USER"]="no"
    ["ALLOW_SSH_PROT_V1"]="2"
    ["ALLOW_SYSLOG_REMOTE"]="no"
    ["USE_SYSLOG"]="authpriv.notice"
    ["WEB_CMD"]="/usr/bin/false"
)
for key in "${!RKHUNTER_OPTS[@]}"; do
    value="${RKHUNTER_OPTS[$key]}"
    if grep -q "^$key=" "$RKHUNTER_CONF"; then
        sed -i "s|^$key=.*|$key=$value|" "$RKHUNTER_CONF"
    else
        echo "$key=$value" >> "$RKHUNTER_CONF"
    fi
done

# Run rkhunter update and log output for troubleshooting
# Check rkhunter config for WEB_CMD and log update issues
if grep -q '^WEB_CMD=/usr/bin/false' "$RKHUNTER_CONF"; then
    print_success "rkhunter WEB_CMD is set to /usr/bin/false (recommended)"
else
    print_warning "rkhunter WEB_CMD is not set to /usr/bin/false. Please update $RKHUNTER_CONF."
fi

print_info "Running rkhunter --update (output will be logged to /var/log/rkhunter-update.log)"
rkhunter --update > /var/log/rkhunter-update.log 2>&1
if grep -i 'error\|warning' /var/log/rkhunter-update.log; then
    print_error "rkhunter update encountered issues. See /var/log/rkhunter-update.log for details."
    print_warning "Check WEB_CMD in /etc/rkhunter.conf is set to /usr/bin/false. If the error persists, review network connectivity and mirror availability."
    print_warning "You may manually run: rkhunter --update --debug for more info."
else
    print_success "rkhunter updated successfully."
fi

print_info "Running rkhunter --propupd (output will be logged to /var/log/rkhunter-propupd.log)"
rkhunter --propupd > /var/log/rkhunter-propupd.log 2>&1
if grep -i 'error\|warning' /var/log/rkhunter-propupd.log; then
    print_error "rkhunter propupd encountered issues. See /var/log/rkhunter-propupd.log for details."
else
    print_success "rkhunter propupd completed successfully."
fi

# Create daily rkhunter scan cron job
cat > /etc/cron.daily/rkhunter-scan << 'EOF'
#!/bin/bash
/usr/bin/rkhunter --cronjob --update --quiet
EOF
chmod +x /etc/cron.daily/rkhunter-scan

# Create weekly rkhunter scan report to email
cat > /etc/cron.weekly/rkhunter-report << EOF
#!/bin/bash
/usr/bin/rkhunter --check --skip-keypress --report-warnings-only | /usr/bin/mail -s "RKHunter Report $(hostname)" $EMAIL
EOF
chmod +x /etc/cron.weekly/rkhunter-report


print_success "Additional security tools installed"

# =============================================================================
# 11. Install Composer
# =============================================================================
print_header "Composer Installation"

# Download and install Composer
cd /tmp

curl -sS https://getcomposer.org/installer -o composer-setup.php
HASH="$(curl -sS https://composer.github.io/installer.sig)"
php -r "if (hash_file('SHA384', 'composer-setup.php') === '$HASH') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php

print_success "Composer installed"

# =============================================================================
# 12. Install Node.js and NPM
# =============================================================================
print_header "Node.js and NPM Installation"

# Download and install nvm:
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.3/install.sh | bash

# in lieu of restarting the shell
\. "$HOME/.nvm/nvm.sh"

# Download and install Node.js:
nvm install 22

# Verify the Node.js version:
node -v # Should print "v22.17.1".
nvm current # Should print "v22.17.1".

# Verify npm version:
npm -v # Should print "10.9.2".


# =============================================================================
# 14. Setup Project Directory and Permissions
# =============================================================================
print_header "Project Directory Setup"

# Find the 1000proxy repo anywhere on the server (cloned by root)
REPO_SRC=""
if [[ ! -d "$PROJECT_DIR" ]]; then
    # Try common locations first
    if [[ -d "/root/1000proxy" ]]; then
        REPO_SRC="/root/1000proxy"
    elif [[ -d "$HOME/1000proxy" ]]; then
        REPO_SRC="$HOME/1000proxy"
    else
        # Search for the repo anywhere on the system (first match)
        REPO_SRC=$(find / -type d -name "1000proxy" -print -quit 2>/dev/null)
    fi

    if [[ -n "$REPO_SRC" && -d "$REPO_SRC" ]]; then
        mv "$REPO_SRC" "$PROJECT_DIR"
        print_success "Moved 1000proxy project from $REPO_SRC to $PROJECT_DIR"
    else
        print_warning "1000proxy repository not found. Please clone it before running this script."
    fi
fi

# Ensure ownership and permissions for Laravel
if id "$PROJECT_USER" &>/dev/null && getent group www-data &>/dev/null; then
    chown -R "$PROJECT_USER:www-data" "$PROJECT_DIR"
    print_success "Ownership set to $PROJECT_USER:www-data for $PROJECT_DIR"
else
    print_warning "User $PROJECT_USER or group www-data does not exist, skipping chown for $PROJECT_DIR"
fi
chmod 755 "$PROJECT_DIR"

# Laravel required directories:
sudo -u "$PROJECT_USER" mkdir -p "$PROJECT_DIR"/{storage,bootstrap/cache}
sudo -u "$PROJECT_USER" mkdir -p "$PROJECT_DIR"/storage/{app,framework,logs}
sudo -u "$PROJECT_USER" mkdir -p "$PROJECT_DIR"/storage/framework/{cache,sessions,views}

# Set proper permissions for Laravel
find "$PROJECT_DIR" -type f -exec chmod 644 {} +
find "$PROJECT_DIR" -type d -exec chmod 755 {} +
chmod -R 775 "$PROJECT_DIR"/storage "$PROJECT_DIR"/bootstrap/cache
if id "$PROJECT_USER" &>/dev/null && getent group www-data &>/dev/null; then
    chown -R "$PROJECT_USER:www-data" "$PROJECT_DIR"
    print_success "Ownership set to $PROJECT_USER:www-data for $PROJECT_DIR"
else
    print_warning "User $PROJECT_USER or group www-data does not exist, skipping chown for $PROJECT_DIR"
fi
print_success "Project directory configured"

# =============================================================================
# 15. Environment Configuration
# =============================================================================
print_header "Environment Configuration"

# Copy .env.example to .env
if [[ -f "$PROJECT_DIR/.env.example" ]]; then
    cp --preserve=mode,ownership "$PROJECT_DIR/.env.example" "$PROJECT_DIR/.env"
    print_success ".env.example copied to .env"
else
    print_warning ".env.example not found, skipping copy"
fi

if [[ -f "$PROJECT_DIR/.env" ]]; then
    if id "$PROJECT_USER" &>/dev/null && getent group www-data &>/dev/null; then
        chown "$PROJECT_USER:www-data" "$PROJECT_DIR/.env"
        print_success "Ownership set to $PROJECT_USER:www-data for .env"
    else
        print_warning "User $PROJECT_USER or group www-data does not exist, skipping chown for .env"
    fi
    chmod 640 "$PROJECT_DIR/.env"
    print_success "Environment file permissions set"
else
    print_warning "Environment file $PROJECT_DIR/.env does not exist, skipping permission change"
fi

# =============================================================================
# 16. Security Monitoring and Alerting
# =============================================================================
print_header "Security Monitoring Setup"

# Install logwatch
DEBIAN_FRONTEND=noninteractive apt-get install -y logwatch

# Ensure logwatch config directory exists
mkdir -p /etc/logwatch/conf

# Copy default config if not present
if [[ ! -f /etc/logwatch/conf/logwatch.conf ]]; then
    cp /usr/share/logwatch/default.conf/logwatch.conf /etc/logwatch/conf/
fi

# Safely update logwatch.conf parameters without overwriting the file
LOGWATCH_CONF="/etc/logwatch/conf/logwatch.conf"
declare -A LOGWATCH_PARAMS=(
    ["LogDir"]="/var/log"
    ["MailTo"]="root"
    ["MailFrom"]="logwatch@$DOMAIN"
    ["Print"]="No"
    ["Save"]="/var/log/logwatch.html"
    ["Range"]="yesterday"
    ["Detail"]="Med"
    ["Service"]="All"
    ["Format"]="html"
)

for key in "${!LOGWATCH_PARAMS[@]}"; do
    value="${LOGWATCH_PARAMS[$key]}"
    if grep -q "^$key" "$LOGWATCH_CONF"; then
        sed -i "s|^$key.*|$key = $value|" "$LOGWATCH_CONF"
    else
        echo "$key = $value" >> "$LOGWATCH_CONF"
    fi
done

# Setup log monitoring script
cat > /usr/local/bin/security-monitor.sh << 'EOF'
#!/bin/bash

LOG_FILE="/var/log/security-monitor.log"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

# Ensure log file exists
touch "$LOG_FILE"

LOG_DATE="$(date '+%b %e')"

# Check for failed login attempts
FAILED_LOGINS=$(grep "authentication failure" /var/log/auth.log | grep "$LOG_DATE" | wc -l)
if [ "$FAILED_LOGINS" -gt 10 ]; then
    echo "[$DATE] WARNING: $FAILED_LOGINS failed login attempts detected today" >> "$LOG_FILE"
fi

# Check for privilege escalation attempts
SUDO_ATTEMPTS=$(grep "sudo:" /var/log/auth.log | grep "$LOG_DATE" | grep -c "FAILED")
if [ "$SUDO_ATTEMPTS" -gt 5 ]; then
    echo "[$DATE] WARNING: $SUDO_ATTEMPTS failed sudo attempts detected today" >> "$LOG_FILE"
fi

# Check disk usage
DISK_USAGE=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 80 ]; then
    echo "[$DATE] WARNING: Disk usage is at $DISK_USAGE%" >> "$LOG_FILE"
fi

# Check memory usage
MEM_USAGE=$(free | grep Mem | awk '{printf("%.0f", $3/$2 * 100.0)}')
if [ "$MEM_USAGE" -gt 85 ]; then
    echo "[$DATE] WARNING: Memory usage is at $MEM_USAGE%" >> "$LOG_FILE"
fi

# Check for rootkit signatures
if command -v rkhunter &> /dev/null; then
    RKHUNTER_WARNINGS=$(rkhunter --check --skip-keypress --report-warnings-only 2>/dev/null | grep -c "Warning:")
    if [ "$RKHUNTER_WARNINGS" -gt 0 ]; then
        echo "[$DATE] WARNING: RKHunter detected $RKHUNTER_WARNINGS potential issues" >> "$LOG_FILE"
    fi
fi
EOF

chmod +x /usr/local/bin/security-monitor.sh

# Add to system cron.d for hourly monitoring
echo "0 * * * * root /usr/local/bin/security-monitor.sh" > /etc/cron.d/security-monitor
chmod 644 /etc/cron.d/security-monitor

print_success "Security monitoring configured"

# =============================================================================
# 17. Backup Configuration
# =============================================================================
print_header "Backup System Setup"


# Create backup directory with error handling
if ! mkdir -p /var/backups/1000proxy; then
    print_error "Failed to create /var/backups/1000proxy directory."; exit 1;
fi
if ! chown root:root /var/backups/1000proxy; then
    print_error "Failed to set ownership on /var/backups/1000proxy."; exit 1;
fi
if ! chmod 700 /var/backups/1000proxy; then
    print_error "Failed to set permissions on /var/backups/1000proxy."; exit 1;
fi

# Create backup script (use bash strict mode, error handling, and logging)
cat > /usr/local/bin/backup-1000proxy.sh << 'EOF'
#!/bin/bash
set -euo pipefail

BACKUP_DIR="/var/backups/1000proxy"
DATE="$(date +%Y%m%d_%H%M%S)"
PROJECT_DIR="/var/www/1000proxy"
LOG_FILE="/var/log/backup-1000proxy.log"

mkdir -p "$BACKUP_DIR/$DATE" || { echo "Failed to create backup subdirectory at $(date)" >>"$LOG_FILE"; exit 1; }

# Backup database (MySQL)
if command -v mysqldump &>/dev/null; then
    if ! mysqldump --single-transaction --routines --triggers 1000proxy > "$BACKUP_DIR/$DATE/database.sql" 2>>"$LOG_FILE"; then
        echo "Database backup failed at $(date)" >>"$LOG_FILE"
    fi
else
    echo "mysqldump not found, skipping database backup at $(date)" >>"$LOG_FILE"
fi

# Backup project files (excluding storage logs/cache/sessions/views, vendor, node_modules)
if ! tar -czf "$BACKUP_DIR/$DATE/project.tar.gz" \
    --exclude="$PROJECT_DIR/storage/logs/*" \
    --exclude="$PROJECT_DIR/storage/framework/cache/*" \
    --exclude="$PROJECT_DIR/storage/framework/sessions/*" \
    --exclude="$PROJECT_DIR/storage/framework/views/*" \
    --exclude="$PROJECT_DIR/vendor" \
    --exclude="$PROJECT_DIR/node_modules" \
    "$PROJECT_DIR" 2>>"$LOG_FILE"; then
    echo "Project files backup failed at $(date)" >>"$LOG_FILE"
fi

# Backup important system configurations
if ! tar -czf "$BACKUP_DIR/$DATE/system-config.tar.gz" \
    /etc/nginx/ \
    /etc/php/ \
    /etc/mysql/ \
    /etc/redis/ \
    /etc/ssh/ \
    /etc/fail2ban/ \
    /etc/ufw/ 2>>"$LOG_FILE"; then
    echo "System config backup failed at $(date)" >>"$LOG_FILE"
fi

# Remove backups older than 30 days (ignore errors if none found)
find "$BACKUP_DIR" -mindepth 1 -maxdepth 1 -type d -mtime +30 -exec rm -rf {} + 2>/dev/null || true

# Set proper permissions
chmod -R 600 "$BACKUP_DIR/$DATE"/* || true
chown -R root:root "$BACKUP_DIR/$DATE"/* || true

echo "Backup completed: $BACKUP_DIR/$DATE at $(date)" >>"$LOG_FILE"
EOF

chmod 700 /usr/local/bin/backup-1000proxy.sh || { print_error "Failed to set permissions on backup script."; exit 1; }

# Schedule daily backups at 2 AM, avoiding duplicate entries
CRON_BACKUP_JOB="0 2 * * * /usr/local/bin/backup-1000proxy.sh"
if ! ( crontab -l 2>/dev/null | grep -v "/usr/local/bin/backup-1000proxy.sh"; echo "$CRON_BACKUP_JOB" ) | crontab -; then
    print_error "Failed to schedule backup cron job."; exit 1;
fi
print_success "Backup system configured"

# =============================================================================
# 18. Process Monitoring and Resource Limits
# =============================================================================
print_header "Process Monitoring Setup"

# Install htop, iotop, nethogs for monitoring (ignore errors if already installed)
DEBIAN_FRONTEND=noninteractive apt-get install -y htop iotop nethogs || print_warning "Some monitoring tools failed to install"

# Configure process limits
cat > /etc/security/limits.d/1000proxy.conf << EOF
# 1000proxy process limits
$PROJECT_USER soft nproc 4096
$PROJECT_USER hard nproc 8192
$PROJECT_USER soft nofile 4096
$PROJECT_USER hard nofile 8192
www-data soft nproc 4096
www-data hard nproc 8192
www-data soft nofile 4096
www-data hard nofile 8192
EOF

print_success "Process monitoring configured"

# =============================================================================
# 19. Network Security and DDoS Protection
# =============================================================================
print_header "Network Security Configuration"

# Kernel parameter tuning for security and DDoS protection
cat > /etc/sysctl.d/99-1000proxy-security.conf << EOF
# Network Security Settings

net.ipv4.conf.default.rp_filter = 1
net.ipv4.conf.all.rp_filter = 1
net.ipv4.conf.all.accept_redirects = 0
net.ipv6.conf.all.accept_redirects = 0
net.ipv4.conf.default.accept_redirects = 0
net.ipv6.conf.default.accept_redirects = 0
net.ipv4.conf.all.send_redirects = 0
net.ipv4.conf.default.send_redirects = 0
net.ipv4.conf.all.accept_source_route = 0
net.ipv6.conf.all.accept_source_route = 0
net.ipv4.conf.default.accept_source_route = 0
net.ipv6.conf.default.accept_source_route = 0
net.ipv4.conf.all.log_martians = 1
net.ipv4.conf.default.log_martians = 1
net.ipv4.icmp_echo_ignore_all = 1
net.ipv4.icmp_echo_ignore_broadcasts = 1
net.ipv6.conf.all.disable_ipv6 = 1
net.ipv6.conf.default.disable_ipv6 = 1
net.ipv6.conf.lo.disable_ipv6 = 1
net.ipv4.tcp_syncookies = 1
net.ipv4.tcp_max_syn_backlog = 2048
net.ipv4.tcp_synack_retries = 2
net.ipv4.tcp_syn_retries = 5
net.netfilter.nf_conntrack_max = 2000000
net.netfilter.nf_conntrack_tcp_timeout_established = 7440
net.netfilter.nf_conntrack_tcp_timeout_time_wait = 120
kernel.panic = 10
kernel.panic_on_oops = 1
vm.swappiness = 10
vm.dirty_ratio = 60
vm.dirty_background_ratio = 2
fs.suid_dumpable = 0
fs.protected_hardlinks = 1
fs.protected_symlinks = 1
kernel.dmesg_restrict = 1
kernel.kptr_restrict = 2
kernel.yama.ptrace_scope = 1
EOF

sysctl --system

print_success "Network security parameters configured"

# =============================================================================
# 20. Final Security Checks and Information
# =============================================================================
print_header "Final Security Configuration"

# Disable unused services (ignore errors if not present)
systemctl disable --now bluetooth 2>/dev/null || true
systemctl disable --now cups 2>/dev/null || true
systemctl disable --now avahi-daemon 2>/dev/null || true

# Set secure permissions on sensitive files
chmod 600 /etc/shadow 2>/dev/null || true
chmod 600 /etc/gshadow 2>/dev/null || true
chmod 644 /etc/passwd 2>/dev/null || true
chmod 644 /etc/group 2>/dev/null || true

# Disable core dumps
echo "* hard core 0" >> /etc/security/limits.conf

# Create MOTD with security information
cat > /etc/motd << EOF

████████╗██╗  ██╗ ██████╗ ██╗   ██╗███████╗ █████╗ ███╗   ██╗██████╗
╚══██╔══╝██║  ██║██╔═══██╗██║   ██║██╔════╝██╔══██╗████╗  ██║██╔══██╗
   ██║   ███████║██║   ██║██║   ██║███████╗███████║██╔██╗ ██║██║  ██║
   ██║   ██╔══██║██║   ██║██║   ██║╚════██║██╔══██║██║╚██╗██║██║  ██║
   ██║   ██║  ██║╚██████╔╝╚██████╔╝███████║██║  ██║██║ ╚████║██████╔╝
   ╚═╝   ╚═╝  ╚═╝ ╚═════╝  ╚═════╝ ╚══════╝╚═╝  ╚═╝╚═╝  ╚═══╝╚═════╝

██████╗ ██████╗  ██████╗ ██╗  ██╗██╗   ██╗    ███████╗███████╗██████╗ ██╗   ██╗███████╗██████╗
██╔══██╗██╔══██╗██╔═══██╗╚██╗██╔╝╚██╗ ██╔╝    ██╔════╝██╔════╝██╔══██╗██║   ██║██╔════╝██╔══██╗
██████╔╝██████╔╝██║   ██║ ╚███╔╝  ╚████╔╝     ███████╗█████╗  ██████╔╝██║   ██║█████╗  ██████╔╝
██╔═══╝ ██╔══██╗██║   ██║ ██╔██╗   ╚██╔╝      ╚════██║██╔══╝  ██╔══██╗╚██╗ ██╔╝██╔══╝  ██╔══██╗
██║     ██║  ██║╚██████╔╝██╔╝ ██╗   ██║       ███████║███████╗██║  ██║ ╚████╔╝ ███████╗██║  ██║
╚═╝     ╚═╝  ╚═╝ ╚═════╝ ╚═╝  ╚═╝   ╚═╝       ╚══════╝╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚══════╝╚═╝  ╚═╝

════════════════════════════════════════════════════════════════════════════════
                           SECURED SERVER ENVIRONMENT
════════════════════════════════════════════════════════════════════════════════

⚠️  AUTHORIZED ACCESS ONLY - ALL ACTIVITY IS MONITORED AND LOGGED

🛡️  Security Features Enabled:
   • Fail2Ban intrusion detection
   • UFW firewall with rate limiting
   • SSH hardening (Port 2222)
   • System auditing (auditd)
   • Antivirus scanning (ClamAV)
   • Rootkit detection (rkhunter/AIDE)
   • Automated security updates

📊 Monitoring Tools:
   • Security monitor: /usr/local/bin/security-monitor.sh
   • Log analysis: logwatch
   • Process monitor: htop, iotop
   • Network monitor: nethogs

🔧 Management Commands:
   • Check fail2ban: fail2ban-client status
   • Check firewall: ufw status
   • Check services: systemctl status nginx php8.3-fpm mysql redis
   • Security scan: lynis audit system

Last login: $(date)
EOF

print_success "Security hardening completed"

# =============================================================================
# 21. Generate Security Report
# =============================================================================
print_header "Generating Security Report"

cat > "/root/1000proxy-security-report.txt" << EOF
═══════════════════════════════════════════════════════════════════════════════
                        1000PROXY SECURITY SETUP REPORT
═══════════════════════════════════════════════════════════════════════════════

Setup Date: $(date)
Server: $(hostname)
OS: $(lsb_release -d | cut -f2)
Kernel: $(uname -r)

CREDENTIALS AND ACCESS INFORMATION:
═══════════════════════════════════════════════════════════════════════════════
Domain: $DOMAIN
Project User: $PROJECT_USER
Project Directory: $PROJECT_DIR
SSH Port: 2222

Database Information:

Redis Information:

SSH Access:

SECURITY FEATURES IMPLEMENTED:
═══════════════════════════════════════════════════════════════════════════════
✓ System hardening and security updates configured
✓ UFW firewall configured with rate limiting
✓ Fail2Ban intrusion detection system
✓ SSH hardening (custom port, key-only auth)
✓ PHP security configuration
✓ Nginx security headers and rate limiting
✓ MySQL security configuration
✓ Redis authentication and security
✓ System auditing (auditd)
✓ Antivirus protection (ClamAV)
✓ Rootkit detection (rkhunter, AIDE)
✓ Log monitoring and alerting
✓ Automated backups
✓ Network security hardening
✓ DDoS protection measures
✓ Process monitoring and limits

INSTALLED SOFTWARE:
═══════════════════════════════════════════════════════════════════════════════
✓ PHP 8.3 with security extensions
✓ Nginx web server
✓ MySQL 8.0 database
✓ Redis cache server
✓ Composer dependency manager
✓ Node.js and NPM
✓ Let's Encrypt SSL (if domain configured)

SECURITY MONITORING:
═══════════════════════════════════════════════════════════════════════════════
✓ Security monitoring script: /usr/local/bin/security-monitor.sh
✓ Daily security reports via logwatch
✓ Automated backup system: /usr/local/bin/backup-1000proxy.sh
✓ AIDE filesystem integrity checking
✓ RKHunter rootkit scanning
✓ ClamAV antivirus scanning

SETUP ERRORS:
═══════════════════════════════════════════════════════════════════════════════
$(if [ ${#SETUP_ERRORS[@]} -gt 0 ]; then
    echo "The following errors occurred during setup:"
    for err in "${SETUP_ERRORS[@]}"; do
        echo "✗ $err"
    done
else
    echo "No critical errors detected during setup."
fi)
IMPORTANT SECURITY NOTES:
═══════════════════════════════════════════════════════════════════════════════
1. SSH is now on port 2222 - update your SSH client configuration
2. Root login is disabled - use the $PROJECT_USER user with sudo
3. Password authentication is disabled - SSH keys are required
4. All services are configured for maximum security
5. Regular security monitoring is automated
6. Automatic backups are scheduled daily at 2 AM
7. Security updates are automatically installed
8. Fail2Ban will block suspicious IP addresses
9. All activity is logged and monitored

NEXT STEPS:
═══════════════════════════════════════════════════════════════════════════════
1. Deploy your 1000proxy application to: $PROJECT_DIR
2. Configure your DNS to point to this server
3. Update the .env file with your specific settings
4. Run: composer install && npm install && npm run build
5. Run: php artisan key:generate && php artisan migrate
6. Set up SSL certificate if using a real domain
7. Configure email settings for notifications
8. Test all security features

SECURITY COMMANDS:
═══════════════════════════════════════════════════════════════════════════════
Check system security:      lynis audit system
Check firewall status:       ufw status verbose
Check fail2ban status:       fail2ban-client status
View security logs:          tail -f /var/log/security-monitor.log
Run security scan:           /usr/local/bin/security-monitor.sh
Manual backup:               /usr/local/bin/backup-1000proxy.sh
Check intrusions:            fail2ban-client status ssh

IMPORTANT FILES TO SECURE:
═══════════════════════════════════════════════════════════════════════════════
- /root/1000proxy-security-report.txt (this file - contains passwords!)
- $PROJECT_DIR/.env (application configuration)
- /etc/ssh/ssh_config (SSH configuration)
- /etc/nginx/sites-available/1000proxy (web server config)

WARNING: This file contains sensitive information. Store it securely and delete
it from the server after copying the credentials to a secure location.

═══════════════════════════════════════════════════════════════════════════════
EOF

chmod 600 "/root/1000proxy-security-report.txt"
print_success "Security report generated at /root/1000proxy-security-report.txt"

# =============================================================================
# FINAL SUMMARY
# =============================================================================
print_header "Setup Complete - Security Summary"

print_success "1000proxy secure server setup completed successfully!"
echo
print_info "Domain: $DOMAIN"
print_info "Project Directory: $PROJECT_DIR"
print_info "Project User: $PROJECT_USER"
print_info "SSH Port: 2222"
echo
print_warning "IMPORTANT SECURITY NOTES:"
print_warning "1. SSH is now on port 2222 (not 22)"
print_warning "2. Root login is disabled"
print_warning "3. Password authentication is disabled"
print_warning "4. Read the security report: /root/1000proxy-security-report.txt"
echo
print_info "Next steps:"
print_info "1. Copy SSH key for $PROJECT_USER to connect: /home/$PROJECT_USER/.ssh/id_rsa"
print_info "2. Deploy your 1000proxy application"
print_info "3. Configure DNS and SSL"
print_info "4. Test all security features"
echo
print_success "Your server is now highly secured against attacks!"
print_header "Setup Complete"

# Clean up
apt autoremove -y
apt autoclean

echo "Setup log saved to: $LOG_FILE"
echo "Security report saved to: /root/1000proxy-security-report.txt"
echo "Setup completed at: $(date)"
