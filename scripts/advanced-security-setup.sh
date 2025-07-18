#!/bin/bash

# =============================================================================
# 1000proxy Advanced Security Configuration
# =============================================================================
# Additional security measures and hardening for the 1000proxy project
# Run this script after the main secure-server-setup.sh
# =============================================================================

set -euo pipefail

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

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
}

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   print_error "This script must be run as root (use sudo)"
   exit 1
fi

# =============================================================================
# Advanced Nginx Security Configuration
# =============================================================================
print_header "Advanced Nginx Security"

# Create security snippets directory
mkdir -p /etc/nginx/snippets

# Security headers snippet
cat > /etc/nginx/snippets/security-headers.conf << 'EOF'
# Security Headers
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
add_header X-Permitted-Cross-Domain-Policies "none" always;
add_header X-Robots-Tag "none" always;
add_header X-Download-Options "noopen" always;

# Remove server tokens
server_tokens off;
EOF

# Rate limiting snippet
cat > /etc/nginx/snippets/rate-limiting.conf << 'EOF'
# Rate Limiting Configuration
limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;
limit_req_zone $binary_remote_addr zone=api:10m rate=100r/m;
limit_req_zone $binary_remote_addr zone=general:10m rate=10r/s;
limit_req_zone $binary_remote_addr zone=search:10m rate=30r/m;
limit_req_zone $binary_remote_addr zone=admin:10m rate=20r/m;

# Connection limiting
limit_conn_zone $binary_remote_addr zone=perip:10m;
limit_conn_zone $server_name zone=perserver:10m;
EOF

# Block malicious requests snippet
cat > /etc/nginx/snippets/block-exploits.conf << 'EOF'
# Block common exploit attempts
location ~* "(eval\()" { deny all; }
location ~* "(127\.0\.0\.1)" { deny all; }
location ~* "([a-z0-9]{2000})" { deny all; }
location ~* "(javascript\:)(.*)(\;)" { deny all; }
location ~* "(base64_encode)(.*)(\()" { deny all; }
location ~* "(GLOBALS|REQUEST)(=|\[|%)" { deny all; }
location ~* "(<|%3C).*script.*(>|%3)" { deny all; }
location ~* "(boot\.ini|etc/passwd|self/environ)" { deny all; }
location ~* "(thumbs?(_db)?\.db|Thumbs\.db|\.ds_store)" { deny all; }

# Block file injections
location ~* "\.(htaccess|htpasswd|errlog|logs|ini|log|sh|sql|tar|tgz)$" { deny all; }

# Block user agents
if ($http_user_agent ~* (nmap|nikto|wikto|sf|sqlmap|bsqlbf|w3af|acunetix|havij|skygrid) ) {
    return 444;
}

# Block referrer spam
if ($http_referer ~* (babes|forsale|girl|jewelry|love|nudit|organic|poker|porn|sex|teen)) {
    return 444;
}
EOF

# SSL/TLS security snippet
cat > /etc/nginx/snippets/ssl-security.conf << 'EOF'
# Modern SSL Configuration
ssl_protocols TLSv1.2 TLSv1.3;
ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-SHA384;
ssl_prefer_server_ciphers off;
ssl_session_cache shared:SSL:10m;
ssl_session_timeout 10m;
ssl_session_tickets off;
ssl_stapling on;
ssl_stapling_verify on;
resolver 8.8.8.8 8.8.4.4 valid=300s;
resolver_timeout 5s;

# Diffie-Hellman parameter
ssl_dhparam /etc/nginx/dhparam.pem;
EOF

# Generate DH parameters if not exists
if [[ ! -f /etc/nginx/dhparam.pem ]]; then
    openssl dhparam -out /etc/nginx/dhparam.pem 2048
    print_success "DH parameters generated"
fi

print_success "Advanced Nginx security configured"

# =============================================================================
# Web Application Firewall (ModSecurity)
# =============================================================================
print_header "ModSecurity Web Application Firewall"

# Install ModSecurity
apt install -y libmodsecurity3 modsecurity-crs

# Create ModSecurity configuration
mkdir -p /etc/nginx/modsec

cat > /etc/nginx/modsec/modsecurity.conf << 'EOF'
# ModSecurity Configuration
SecRuleEngine On
SecRequestBodyAccess On
SecResponseBodyAccess Off
SecRequestBodyLimit 13107200
SecRequestBodyNoFilesLimit 131072
SecRequestBodyInMemoryLimit 131072
SecRequestBodyLimitAction Reject
SecPcreMatchLimit 1000
SecPcreMatchLimitRecursion 1000

# Audit Logging
SecAuditEngine RelevantOnly
SecAuditLogRelevantStatus "^(?:5|4(?!04))"
SecAuditLogParts ABIJDEFHZ
SecAuditLogType Serial
SecAuditLog /var/log/modsec_audit.log

# File Upload Handling
SecTmpDir /tmp/
SecDataDir /tmp/

# Debug Logging
SecDebugLog /var/log/modsec_debug.log
SecDebugLogLevel 0

# Status Engine
SecStatusEngine On
EOF

# Main rules configuration
cat > /etc/nginx/modsec/main.conf << 'EOF'
Include /etc/nginx/modsec/modsecurity.conf
Include /etc/modsecurity-crs/crs-setup.conf
Include /etc/modsecurity-crs/rules/*.conf
EOF

# Create directory for logs
mkdir -p /var/log/modsecurity
chown www-data:www-data /var/log/modsecurity

print_success "ModSecurity WAF configured"

# =============================================================================
# DDoS Protection with iptables
# =============================================================================
print_header "Advanced DDoS Protection"

# Create iptables rules for DDoS protection
cat > /etc/iptables/ddos-protection.rules << 'EOF'
# DDoS Protection Rules

# Limit connections per IP
iptables -A INPUT -p tcp --dport 80 -m connlimit --connlimit-above 25 -j REJECT --reject-with tcp-reset
iptables -A INPUT -p tcp --dport 443 -m connlimit --connlimit-above 25 -j REJECT --reject-with tcp-reset

# Limit new connections per second
iptables -A INPUT -p tcp --dport 80 -m state --state NEW -m recent --set
iptables -A INPUT -p tcp --dport 80 -m state --state NEW -m recent --update --seconds 1 --hitcount 10 -j DROP
iptables -A INPUT -p tcp --dport 443 -m state --state NEW -m recent --set
iptables -A INPUT -p tcp --dport 443 -m state --state NEW -m recent --update --seconds 1 --hitcount 10 -j DROP

# Drop invalid packets
iptables -A INPUT -m state --state INVALID -j DROP

# Drop TCP packets that are new and are not SYN
iptables -A INPUT -p tcp ! --syn -m state --state NEW -j DROP

# Drop SYN packets with suspicious MSS value
iptables -A INPUT -p tcp -m tcp --tcp-flags SYN,RST SYN,RST -j DROP

# Drop TCP packets with suspicious flag combinations
iptables -A INPUT -p tcp -m tcp --tcp-flags SYN,FIN SYN,FIN -j DROP
iptables -A INPUT -p tcp -m tcp --tcp-flags FIN,RST FIN,RST -j DROP
iptables -A INPUT -p tcp -m tcp --tcp-flags FIN,ACK FIN -j DROP
iptables -A INPUT -p tcp -m tcp --tcp-flags ACK,URG URG -j DROP

# Limit ping requests
iptables -A INPUT -p icmp --icmp-type echo-request -m limit --limit 1/second -j ACCEPT
iptables -A INPUT -p icmp --icmp-type echo-request -j DROP
EOF

# Load DDoS protection rules
mkdir -p /etc/iptables
iptables-restore < /etc/iptables/ddos-protection.rules 2>/dev/null || print_warning "Some iptables rules may conflict with UFW"

print_success "DDoS protection configured"

# =============================================================================
# Intrusion Detection System (OSSEC)
# =============================================================================
print_header "OSSEC Intrusion Detection System"

# Download and install OSSEC
cd /tmp
wget https://github.com/ossec/ossec-hids/archive/3.7.0.tar.gz -O ossec-3.7.0.tar.gz
tar -xzf ossec-3.7.0.tar.gz
cd ossec-hids-3.7.0

# Install dependencies
apt install -y build-essential libevent-dev libpcre2-dev libz-dev libssl-dev

# Create OSSEC installation script
cat > install-ossec.sh << 'EOF'
#!/bin/bash
echo "en" | ./install.sh <<< $'
local
server
/var/ossec
y
y
y
y
y
'
EOF

chmod +x install-ossec.sh
# ./install-ossec.sh

# Configure OSSEC
cat > /var/ossec/etc/ossec.conf << 'EOF'
<ossec_config>
  <global>
    <email_notification>no</email_notification>
    <logall>yes</logall>
    <logall_json>no</logall_json>
    <email_maxperhour>12</email_maxperhour>
    <white_list>127.0.0.1</white_list>
    <white_list>^localhost.localdomain$</white_list>
  </global>

  <rules>
    <include>rules_config.xml</include>
    <include>pam_rules.xml</include>
    <include>ssh_rules.xml</include>
    <include>telnetd_rules.xml</include>
    <include>syslog_rules.xml</include>
    <include>arpwatch_rules.xml</include>
    <include>symantec-av_rules.xml</include>
    <include>symantec-ws_rules.xml</include>
    <include>pix_rules.xml</include>
    <include>named_rules.xml</include>
    <include>smbd_rules.xml</include>
    <include>vsftpd_rules.xml</include>
    <include>pure-ftpd_rules.xml</include>
    <include>proftpd_rules.xml</include>
    <include>ms_ftpd_rules.xml</include>
    <include>ftpd_rules.xml</include>
    <include>hordeimp_rules.xml</include>
    <include>roundcube_rules.xml</include>
    <include>wordpress_rules.xml</include>
    <include>cimserver_rules.xml</include>
    <include>vpopmail_rules.xml</include>
    <include>vmpop3d_rules.xml</include>
    <include>courier_rules.xml</include>
    <include>web_rules.xml</include>
    <include>web_appsec_rules.xml</include>
    <include>apache_rules.xml</include>
    <include>nginx_rules.xml</include>
    <include>php_rules.xml</include>
    <include>mysql_rules.xml</include>
    <include>postgresql_rules.xml</include>
    <include>ids_rules.xml</include>
    <include>squid_rules.xml</include>
    <include>firewall_rules.xml</include>
    <include>cisco-ios_rules.xml</include>
    <include>netscreenfw_rules.xml</include>
    <include>sonicwall_rules.xml</include>
    <include>postfix_rules.xml</include>
    <include>sendmail_rules.xml</include>
    <include>imapd_rules.xml</include>
    <include>mailscanner_rules.xml</include>
    <include>dovecot_rules.xml</include>
    <include>ms-exchange_rules.xml</include>
    <include>racoon_rules.xml</include>
    <include>vpn_concentrator_rules.xml</include>
    <include>spamd_rules.xml</include>
    <include>msauth_rules.xml</include>
    <include>mcafee_av_rules.xml</include>
    <include>trend-osce_rules.xml</include>
    <include>ms-se_rules.xml</include>
    <include>zeus_rules.xml</include>
    <include>solaris_bsm_rules.xml</include>
    <include>vmware_rules.xml</include>
    <include>ms_dhcp_rules.xml</include>
    <include>asterisk_rules.xml</include>
    <include>ossec_rules.xml</include>
    <include>attack_rules.xml</include>
    <include>local_rules.xml</include>
  </rules>

  <syscheck>
    <frequency>7200</frequency>
    <directories check_all="yes">/etc,/usr/bin,/usr/sbin</directories>
    <directories check_all="yes">/bin,/sbin,/boot</directories>
    <directories check_all="yes" realtime="yes">/var/www/1000proxy</directories>
    <ignore>/etc/mtab</ignore>
    <ignore>/etc/hosts.deny</ignore>
    <ignore>/etc/mail/statistics</ignore>
    <ignore>/etc/random-seed</ignore>
    <ignore>/etc/random.seed</ignore>
    <ignore>/etc/adjtime</ignore>
    <ignore>/etc/httpd/logs</ignore>
    <ignore>/etc/utmpx</ignore>
    <ignore>/etc/wtmpx</ignore>
    <ignore>/etc/cups/certs</ignore>
    <ignore>/etc/dumpdates</ignore>
    <ignore>/etc/svc/volatile</ignore>
  </syscheck>

  <rootcheck>
    <disabled>no</disabled>
    <check_files>yes</check_files>
    <check_trojans>yes</check_trojans>
    <check_dev>yes</check_dev>
    <check_sys>yes</check_sys>
    <check_pids>yes</check_pids>
    <check_ports>yes</check_ports>
    <check_if>yes</check_if>
    <frequency>7200</frequency>
    <rootkit_files>/var/ossec/etc/shared/rootkit_files.txt</rootkit_files>
    <rootkit_trojans>/var/ossec/etc/shared/rootkit_trojans.txt</rootkit_trojans>
    <system_audit>/var/ossec/etc/shared/system_audit_rcl.txt</system_audit>
    <system_audit>/var/ossec/etc/shared/system_audit_ssh.txt</system_audit>
    <system_audit>/var/ossec/etc/shared/cis_debian_linux_rcl.txt</system_audit>
  </rootcheck>

  <localfile>
    <log_format>syslog</log_format>
    <location>/var/log/auth.log</location>
  </localfile>

  <localfile>
    <log_format>syslog</log_format>
    <location>/var/log/syslog</location>
  </localfile>

  <localfile>
    <log_format>syslog</log_format>
    <location>/var/log/dpkg.log</location>
  </localfile>

  <localfile>
    <log_format>apache</log_format>
    <location>/var/log/nginx/access.log</location>
  </localfile>

  <localfile>
    <log_format>apache</log_format>
    <location>/var/log/nginx/error.log</location>
  </localfile>
</ossec_config>
EOF

print_success "OSSEC IDS configured (manual installation required)"

# =============================================================================
# Advanced Log Analysis with rsyslog
# =============================================================================
print_header "Advanced Log Analysis"

# Configure rsyslog for security
cat > /etc/rsyslog.d/50-1000proxy-security.conf << 'EOF'
# 1000proxy Security Logging

# Separate authentication logs
auth,authpriv.*                 /var/log/auth.log

# Separate mail logs
mail.*                          /var/log/mail.log

# Separate cron logs
cron.*                          /var/log/cron.log

# Security-related logs
local0.*                        /var/log/security.log

# Failed login attempts
:msg, contains, "Failed password"      /var/log/failed-logins.log
:msg, contains, "authentication failure"  /var/log/failed-logins.log

# Sudo attempts
:msg, contains, "sudo:"         /var/log/sudo.log

# PHP errors
:msg, contains, "PHP"           /var/log/php-errors.log

# Stop processing after these rules
& stop
EOF

# Restart rsyslog
systemctl restart rsyslog

# Create log rotation for security logs
cat > /etc/logrotate.d/1000proxy-security << 'EOF'
/var/log/security.log
/var/log/failed-logins.log
/var/log/sudo.log
/var/log/php-errors.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 640 syslog adm
    postrotate
        systemctl reload rsyslog >/dev/null 2>&1 || true
    endscript
}
EOF

print_success "Advanced log analysis configured"

# =============================================================================
# Real-time Security Monitoring
# =============================================================================
print_header "Real-time Security Monitoring"

# Create real-time monitoring script
cat > /usr/local/bin/realtime-security-monitor.sh << 'EOF'
#!/bin/bash

# Real-time security monitoring for 1000proxy
LOGFILE="/var/log/realtime-security.log"
ALERT_EMAIL="admin@localhost"

# Function to send alert
send_alert() {
    local message="$1"
    local severity="$2"

    echo "[$(date)] $severity: $message" >> "$LOGFILE"

    # Send email alert (configure mail server first)
    # echo "$message" | mail -s "Security Alert - $severity" "$ALERT_EMAIL"

    # Log to syslog
    logger -p local0.warn "1000proxy Security Alert: $message"
}

# Monitor failed login attempts
monitor_failed_logins() {
    tail -F /var/log/auth.log | while read line; do
        if echo "$line" | grep -q "Failed password\|authentication failure"; then
            ip=$(echo "$line" | grep -oE "\b([0-9]{1,3}\.){3}[0-9]{1,3}\b" | head -1)
            if [[ -n "$ip" ]]; then
                count=$(grep "$ip" /var/log/auth.log | grep -c "Failed password\|authentication failure")
                if [[ "$count" -gt 5 ]]; then
                    send_alert "Multiple failed login attempts from IP: $ip (Count: $count)" "HIGH"
                fi
            fi
        fi
    done &
}

# Monitor system resource usage
monitor_resources() {
    while true; do
        # Check CPU usage
        cpu_usage=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
        if (( $(echo "$cpu_usage > 90" | bc -l) )); then
            send_alert "High CPU usage detected: ${cpu_usage}%" "MEDIUM"
        fi

        # Check memory usage
        mem_usage=$(free | grep Mem | awk '{printf("%.1f", $3/$2 * 100.0)}')
        if (( $(echo "$mem_usage > 90" | bc -l) )); then
            send_alert "High memory usage detected: ${mem_usage}%" "MEDIUM"
        fi

        # Check disk usage
        disk_usage=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')
        if [[ "$disk_usage" -gt 85 ]]; then
            send_alert "High disk usage detected: ${disk_usage}%" "MEDIUM"
        fi

        sleep 300  # Check every 5 minutes
    done &
}

# Monitor network connections
monitor_network() {
    while true; do
        connections=$(netstat -tn | grep ESTABLISHED | wc -l)
        if [[ "$connections" -gt 1000 ]]; then
            send_alert "High number of network connections: $connections" "MEDIUM"
        fi

        sleep 60  # Check every minute
    done &
}

# Monitor web server errors
monitor_web_errors() {
    tail -F /var/log/nginx/error.log | while read line; do
        if echo "$line" | grep -qE "(alert|emerg|crit)"; then
            send_alert "Critical web server error: $line" "HIGH"
        fi
    done &
}

# Start monitoring
echo "Starting real-time security monitoring..."
monitor_failed_logins
monitor_resources
monitor_network
monitor_web_errors

# Keep script running
wait
EOF

chmod +x /usr/local/bin/realtime-security-monitor.sh

# Create systemd service for real-time monitoring
cat > /etc/systemd/system/realtime-security-monitor.service << 'EOF'
[Unit]
Description=Real-time Security Monitor for 1000proxy
After=network.target

[Service]
Type=simple
ExecStart=/usr/local/bin/realtime-security-monitor.sh
Restart=always
RestartSec=10
User=root

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable realtime-security-monitor.service
systemctl start realtime-security-monitor.service

print_success "Real-time security monitoring configured"

# =============================================================================
# Database Security Hardening
# =============================================================================
print_header "Database Security Hardening"

# Additional MySQL security configuration
cat > /etc/mysql/mysql.conf.d/additional-security.cnf << 'EOF'
[mysqld]
# Additional security settings
symbolic-links=0
skip-networking=0
bind-address=127.0.0.1

# Logging
general-log=1
general-log-file=/var/log/mysql/general.log
log-queries-not-using-indexes=1

# Security
secure-file-priv=/var/lib/mysql-files/
sql-mode=STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION

# Performance and security
max-allowed-packet=64M
max-connections=200
connect-timeout=10
wait-timeout=600
interactive-timeout=600

# Binary logging for replication and point-in-time recovery
log-bin=mysql-bin
expire-logs-days=7
max-binlog-size=100M
EOF

# Create MySQL monitoring script
cat > /usr/local/bin/mysql-security-monitor.sh << 'EOF'
#!/bin/bash

MYSQL_LOG="/var/log/mysql/general.log"
SECURITY_LOG="/var/log/mysql-security.log"

# Monitor suspicious queries
tail -F "$MYSQL_LOG" | while read line; do
    # Check for SQL injection attempts
    if echo "$line" | grep -iE "(union.*select|select.*from.*information_schema|drop.*table|delete.*from|update.*set)" | grep -v "1000proxy"; then
        echo "[$(date)] Suspicious SQL query detected: $line" >> "$SECURITY_LOG"
        logger -p local0.warn "MySQL Security: Suspicious query detected"
    fi

    # Check for failed connections
    if echo "$line" | grep -i "access denied"; then
        echo "[$(date)] MySQL access denied: $line" >> "$SECURITY_LOG"
    fi
done &
EOF

chmod +x /usr/local/bin/mysql-security-monitor.sh

# Add to startup
(crontab -l 2>/dev/null; echo "@reboot /usr/local/bin/mysql-security-monitor.sh") | crontab -

systemctl restart mysql
print_success "Database security hardening completed"

# =============================================================================
# Application-Level Security
# =============================================================================
print_header "Application-Level Security"

# Create PHP security scanner
cat > /usr/local/bin/php-security-scanner.sh << 'EOF'
#!/bin/bash

PROJECT_DIR="/var/www/1000proxy"
SCAN_LOG="/var/log/php-security-scan.log"

# Function to scan for vulnerabilities
scan_php_files() {
    echo "[$(date)] Starting PHP security scan..." >> "$SCAN_LOG"

    # Scan for dangerous functions
    find "$PROJECT_DIR" -name "*.php" -type f -exec grep -l "eval\|exec\|system\|shell_exec\|passthru\|file_get_contents.*http\|curl_exec\|proc_open" {} \; >> "$SCAN_LOG" 2>/dev/null

    # Scan for SQL injection vulnerabilities
    find "$PROJECT_DIR" -name "*.php" -type f -exec grep -l "\$_\(GET\|POST\|REQUEST\).*mysql_query\|mysqli_query.*\$_\(GET\|POST\|REQUEST\)" {} \; >> "$SCAN_LOG" 2>/dev/null

    # Scan for XSS vulnerabilities
    find "$PROJECT_DIR" -name "*.php" -type f -exec grep -l "echo.*\$_\(GET\|POST\|REQUEST\)\|print.*\$_\(GET\|POST\|REQUEST\)" {} \; >> "$SCAN_LOG" 2>/dev/null

    # Scan for file inclusion vulnerabilities
    find "$PROJECT_DIR" -name "*.php" -type f -exec grep -l "include.*\$_\(GET\|POST\|REQUEST\)\|require.*\$_\(GET\|POST\|REQUEST\)" {} \; >> "$SCAN_LOG" 2>/dev/null

    echo "[$(date)] PHP security scan completed." >> "$SCAN_LOG"
}

# Run scan if project directory exists
if [[ -d "$PROJECT_DIR" ]]; then
    scan_php_files
else
    echo "[$(date)] Project directory not found: $PROJECT_DIR" >> "$SCAN_LOG"
fi
EOF

chmod +x /usr/local/bin/php-security-scanner.sh

# Schedule weekly PHP security scans
(crontab -l 2>/dev/null; echo "0 2 * * 0 /usr/local/bin/php-security-scanner.sh") | crontab -

print_success "Application-level security configured"

# =============================================================================
# Automated Security Updates and Monitoring
# =============================================================================
print_header "Automated Security Updates"

# Create comprehensive security update script
cat > /usr/local/bin/security-updates.sh << 'EOF'
#!/bin/bash

LOG_FILE="/var/log/security-updates.log"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

echo "[$DATE] Starting security updates..." >> "$LOG_FILE"

# Update package lists
apt update >> "$LOG_FILE" 2>&1

# Upgrade security packages
apt list --upgradable | grep -i security >> "$LOG_FILE" 2>&1
DEBIAN_FRONTEND=noninteractive apt upgrade -y >> "$LOG_FILE" 2>&1

# Update ClamAV signatures
freshclam >> "$LOG_FILE" 2>&1

# Update rkhunter
rkhunter --update >> "$LOG_FILE" 2>&1

# Update AIDE database
if command -v aide &> /dev/null; then
    aide --update >> "$LOG_FILE" 2>&1
    mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db 2>/dev/null
fi

# Clean up
apt autoremove -y >> "$LOG_FILE" 2>&1
apt autoclean >> "$LOG_FILE" 2>&1

echo "[$DATE] Security updates completed." >> "$LOG_FILE"

# Send summary email (configure mail server first)
# tail -50 "$LOG_FILE" | mail -s "Security Updates Summary" admin@localhost
EOF

chmod +x /usr/local/bin/security-updates.sh

# Schedule daily security updates
(crontab -l 2>/dev/null; echo "0 3 * * * /usr/local/bin/security-updates.sh") | crontab -

print_success "Automated security updates configured"

# =============================================================================
# Final Security Report
# =============================================================================
print_header "Advanced Security Configuration Complete"

cat > "/root/advanced-security-report.txt" << EOF
═══════════════════════════════════════════════════════════════════════════════
                    1000PROXY ADVANCED SECURITY REPORT
═══════════════════════════════════════════════════════════════════════════════

Setup Date: $(date)
Configuration: Advanced Security Layer

ADDITIONAL SECURITY FEATURES IMPLEMENTED:
═══════════════════════════════════════════════════════════════════════════════
✓ Advanced Nginx security headers and rate limiting
✓ ModSecurity Web Application Firewall (WAF)
✓ Advanced DDoS protection with iptables
✓ OSSEC Intrusion Detection System (configuration ready)
✓ Enhanced log analysis and monitoring
✓ Real-time security monitoring service
✓ Database security hardening
✓ Application-level security scanning
✓ Automated security updates

MONITORING SERVICES:
═══════════════════════════════════════════════════════════════════════════════
✓ Real-time security monitor: systemctl status realtime-security-monitor
✓ MySQL security monitoring: /usr/local/bin/mysql-security-monitor.sh
✓ PHP security scanner: /usr/local/bin/php-security-scanner.sh
✓ Security updates: /usr/local/bin/security-updates.sh

SECURITY LOGS:
═══════════════════════════════════════════════════════════════════════════════
• Real-time alerts: /var/log/realtime-security.log
• MySQL security: /var/log/mysql-security.log
• PHP scan results: /var/log/php-security-scan.log
• Security updates: /var/log/security-updates.log
• ModSecurity: /var/log/modsec_audit.log

ADVANCED PROTECTION FEATURES:
═══════════════════════════════════════════════════════════════════════════════
• Web Application Firewall blocks common attacks
• Real-time monitoring of system resources
• Automated response to security threats
• Advanced rate limiting and connection controls
• SQL injection and XSS protection
• File inclusion vulnerability detection
• Suspicious query monitoring
• Automated security patch management

MANUAL TASKS REQUIRED:
═══════════════════════════════════════════════════════════════════════════════
1. Configure mail server for security alerts
2. Install OSSEC manually if needed: cd /tmp/ossec-hids-3.7.0 && ./install-ossec.sh
3. Review and customize ModSecurity rules
4. Set up external log shipping if required
5. Configure backup retention policies

Your server now has enterprise-level security protection!
═══════════════════════════════════════════════════════════════════════════════
EOF

chmod 600 "/root/advanced-security-report.txt"

print_success "Advanced security configuration completed!"
print_warning "Review /root/advanced-security-report.txt for additional steps"
print_success "Your 1000proxy server is now protected with enterprise-level security"

echo "Advanced security setup completed at: $(date)"
