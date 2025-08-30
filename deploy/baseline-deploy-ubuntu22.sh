#!/usr/bin/env bash
# Baseline deploy script for Ubuntu 22.04 / Debian 12
# - Installs Docker, docker-compose plugin, ufw, certbot
# - Applies network/sysctl tuning suitable for XUI/proxy workloads
# - Leaves example docker-compose and a systemd service template in ./deploy

set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
  echo "Please run as root or with sudo"
  exit 1
fi

TIER="${1:-baseline}"
# allow installation of 3x-ui via env or flag
INSTALL_3XUI=false
if [ "${2:-}" = "--install-3xui" ] || [ "${INSTALL_3XUI:-false}" = "true" ]; then
  INSTALL_3XUI=true
fi
# optional admin IPs (comma separated) to restrict UI access. Pass as 3rd arg or set ADMIN_IPS env
ADMIN_IPS="${3:-${ADMIN_IPS:-}}"
INSTALL_MONITORING="${INSTALL_MONITORING:-}" # set to 'netdata' to auto-install Netdata
case "$TIER" in
  baseline|recommended|high)
    ;;
  *)
    echo "Usage: $0 [baseline|recommended|high]"
    exit 2
    ;;
esac

echo "Starting provisioning (tier=$TIER)..."

export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get upgrade -y

echo "Installing packages: docker, docker-compose-plugin, ufw, certbot, fail2ban"
apt-get install -y --no-install-recommends \
  ca-certificates \
  curl \
  gnupg \
  lsb-release \
  docker.io \
  docker-compose-plugin \
  ufw \
  certbot \
  python3-certbot-nginx \
  nginx \
  fail2ban

systemctl enable --now nginx

systemctl enable --now docker

# common tuning values
  if [ "${INSTALL_3XUI}" = "true" ]; then
FS_FILE_MAX=200000
SOMAXCONN=65535
NETDEV_MAX_BACKLOG=250000
TCP_MAX_SYN=65536
IP_LOCAL_PORT_RANGE="1024 65535"
TCP_RMEM="4096 87380 6291456"
TCP_WMEM="4096 16384 4194304"
RBUF_MAX=16777216
WBUF_MAX=16777216
    echo "Installing 3x-ui using baked-in or provided release..."
    # Preferred: provide a tar.gz archive URL or include the file in /opt/deploy/releases
    # Environment variables supported:
    #  X3UI_RELEASE_URL (http(s) URL to the tar.gz)
    #  X3UI_RELEASE_SHA256 (optional sha256sum to verify)
    #  OR the deploy bundle may include ./deploy/releases/3x-ui.tar.gz which will be used

    RELEASE_LOCAL="/opt/deploy/deploy/releases/3x-ui.tar.gz"
    RELEASE_URL="${X3UI_RELEASE_URL:-}"
    RELEASE_SHA="${X3UI_RELEASE_SHA256:-}"

    if [ -f "$RELEASE_LOCAL" ]; then
      echo "Found local release at $RELEASE_LOCAL"
      src="$RELEASE_LOCAL"
    elif [ -n "$RELEASE_URL" ]; then
      echo "Downloading release from $RELEASE_URL"
      tmpdl=$(mktemp)
      curl -fsSL "$RELEASE_URL" -o "$tmpdl"
      src="$tmpdl"
    else
      echo "No release provided locally or via X3UI_RELEASE_URL; falling back to upstream installer (less safe)"
      bash <(curl -fsSL "${X3UI_INSTALL_URL:-https://raw.githubusercontent.com/MHSanaei/3x-ui/master/install.sh}")
      src=""
    fi

    if [ -n "$src" ] && [ -f "$src" ]; then
      if [ -n "$RELEASE_SHA" ]; then
        echo "Verifying sha256 checksum..."
        calc=$(sha256sum "$src" | awk '{print $1}')
        if [ "$calc" != "$RELEASE_SHA" ]; then
          echo "Checksum mismatch! expected $RELEASE_SHA got $calc"
          exit 1
        fi
      fi
      echo "Installing 3x-ui from archive $src"
      # Example: unpack and run any installer inside; adjust to repo's packaging
      tmpdir=$(mktemp -d)
      tar -xzf "$src" -C "$tmpdir"
      if [ -f "$tmpdir/install.sh" ]; then
        bash "$tmpdir/install.sh"
      else
        echo "No installer found inside archive; aborting"
        exit 1
      fi
      rm -rf "$tmpdir"
      [ "$src" != "$RELEASE_LOCAL" ] && rm -f "$src" || true
      echo "3x-ui installation finished."
    fi
  fi
QDISC=fq
CC=bbr
NOFILE_SOFT=100000
NOFILE_HARD=100000

if [ "$TIER" = "recommended" ]; then
  FS_FILE_MAX=400000
  SOMAXCONN=90000
  NETDEV_MAX_BACKLOG=350000
  TCP_MAX_SYN=131072
  TCP_RMEM="4096 87380 12582912"
  TCP_WMEM="4096 16384 8388608"
  RBUF_MAX=33554432
  WBUF_MAX=33554432
  NOFILE_SOFT=200000
  NOFILE_HARD=200000
fi

if [ "$TIER" = "high" ]; then
  FS_FILE_MAX=1000000
  SOMAXCONN=100000
  NETDEV_MAX_BACKLOG=500000
  TCP_MAX_SYN=262144
  IP_LOCAL_PORT_RANGE="1024 65535"
  TCP_RMEM="4096 87380 25165824"
  TCP_WMEM="4096 16384 16777216"
  RBUF_MAX=67108864
  WBUF_MAX=67108864
  QDISC=fq
  CC=bbr
  NOFILE_SOFT=300000
  NOFILE_HARD=300000
  # Install helpful tools for high-throughput tuning
  apt-get install -y --no-install-recommends irqbalance ethtool
  systemctl enable --now irqbalance || true
fi

echo "Applying kernel / network tuning (/etc/sysctl.d/99-xui-tuning.conf)"
cat > /etc/sysctl.d/99-xui-tuning.conf <<EOF
# XUI / proxy tuning (tier=${TIER})
fs.file-max = ${FS_FILE_MAX}
net.core.somaxconn = ${SOMAXCONN}
net.core.netdev_max_backlog = ${NETDEV_MAX_BACKLOG}
net.ipv4.tcp_max_syn_backlog = ${TCP_MAX_SYN}
net.ipv4.ip_local_port_range = ${IP_LOCAL_PORT_RANGE}
net.ipv4.tcp_tw_reuse = 1
net.ipv4.tcp_fin_timeout = 15
net.ipv4.tcp_keepalive_time = 120
net.ipv4.tcp_rmem = ${TCP_RMEM}
net.ipv4.tcp_wmem = ${TCP_WMEM}
net.core.rmem_max = ${RBUF_MAX}
net.core.wmem_max = ${WBUF_MAX}
net.ipv4.tcp_mtu_probing = 1
net.core.default_qdisc = ${QDISC}
net.ipv4.tcp_congestion_control = ${CC}
EOF

sysctl --system

echo "Setting file descriptor limits for services (limits.d)"
cat > /etc/security/limits.d/99-xui.conf <<EOF
# Increased limits for XUI/docker
* soft nofile ${NOFILE_SOFT}
* hard nofile ${NOFILE_HARD}
EOF

echo "Configuring UFW (allow OpenSSH, HTTP, HTTPS)"
ufw default deny incoming
ufw default allow outgoing
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp

# If ADMIN_IPS provided, we will create an nginx-level allow file; do NOT open internal UI ports via UFW.
if [ -n "${ADMIN_IPS}" ]; then
  echo "Admin IPs provided; nginx will restrict access to the UI via /etc/nginx/conf.d/xui_admin_allow.conf"
  # create nginx include to restrict access
  ADMIN_CONF="/etc/nginx/conf.d/xui_admin_allow.conf"
  echo "# Generated admin allow file" > "$ADMIN_CONF"
  IFS=',' read -ra ADDR <<< "${ADMIN_IPS}"
  for ip in "${ADDR[@]}"; do
    ip_trimmed=$(echo "$ip" | xargs)
    echo "allow ${ip_trimmed};" >> "$ADMIN_CONF"
  done
  echo "deny all;" >> "$ADMIN_CONF"
  chmod 644 "$ADMIN_CONF"
else
  # Ensure any stale admin allow file is removed
  rm -f /etc/nginx/conf.d/xui_admin_allow.conf || true
fi

ufw --force enable

echo "Provisioning complete for tier=${TIER}."
echo "Files in ./deploy include example docker-compose and systemd unit template."
echo "Next steps: copy deploy/docker-compose.xui.yml to /opt/xui/docker-compose.yml, edit as needed, then install the systemd unit:"
echo "  sudo cp deploy/xui.service.template /etc/systemd/system/xui.service && sudo systemctl daemon-reload && sudo systemctl enable --now xui.service"

if [ "${INSTALL_3XUI}" = "true" ]; then
  echo "Installing 3x-ui using upstream installer..."
  # upstream quick-install
  # Run installer from upstream but pinned to known tag/commit is safer.
  # By default use master raw URL; override via env 3XUI_INSTALL_URL
  INSTALL_URL="${3XUI_INSTALL_URL:-https://raw.githubusercontent.com/MHSanaei/3x-ui/master/install.sh}"
  bash <(curl -fsSL "${INSTALL_URL}")
  echo "3x-ui installation finished. Check logs or run 'systemctl status x-ui' per upstream docs."
fi

# Configure nginx site from bundle template if XUI_DOMAIN is set
if [ -n "${XUI_DOMAIN:-}" ]; then
  echo "Configuring nginx for domain ${XUI_DOMAIN}"
  mkdir -p /var/www/certbot
  # prefer template from extracted bundle if present
  if [ -f "/opt/deploy/deploy/nginx/xui.conf.template" ]; then
    tpl="/opt/deploy/deploy/nginx/xui.conf.template"
  else
    tpl="/var/www/xui_nginx_template.conf"
  fi
  sed "s|YOUR_XUI_DOMAIN|${XUI_DOMAIN}|g" "$tpl" > /etc/nginx/sites-available/xui.conf
  ln -sf /etc/nginx/sites-available/xui.conf /etc/nginx/sites-enabled/xui.conf
  nginx -t && systemctl reload nginx

  # Obtain certificate if email provided
  if [ -n "${CERTBOT_EMAIL:-}" ]; then
    echo "Requesting Let's Encrypt certificate for ${XUI_DOMAIN}"
    certbot --nginx --non-interactive --agree-tos -m "${CERTBOT_EMAIL}" -d "${XUI_DOMAIN}" || true
    systemctl reload nginx || true
  else
    echo "No CERTBOT_EMAIL provided; skipped certbot."
  fi
fi

# If Cloudflare Origin cert provided (PEM contents), write to /etc/ssl and reload nginx
if [ -n "${CLOUDFLARE_ORIGIN_CERT:-}" ] && [ -n "${CLOUDFLARE_ORIGIN_KEY:-}" ]; then
  echo "Writing Cloudflare Origin cert to /etc/ssl/cf_origin.pem and key"
  echo "$CLOUDFLARE_ORIGIN_CERT" > /etc/ssl/cf_origin.pem
  echo "$CLOUDFLARE_ORIGIN_KEY" > /etc/ssl/cf_origin.key
  chmod 600 /etc/ssl/cf_origin.key
  chmod 644 /etc/ssl/cf_origin.pem
  systemctl reload nginx || true
fi

if [ "${INSTALL_MONITORING}" = "netdata" ]; then
  echo "Installing Netdata monitoring agent..."
  # official quick installer
  bash <(curl -fsSL https://my-netdata.io/kickstart.sh) --disable-telemetry || true
  echo "Netdata installed (listening on 19999). Secure access via firewall or reverse proxy as needed."
fi

### install backup script for /opt/xui/xui-data
cat > /usr/local/bin/backup-xui.sh <<'BKP'
#!/usr/bin/env bash
# Backup script: archives /opt/xui/xui-data to /var/backups/xui, GPG-encrypts, and optionally uploads to S3/Spaces
set -euo pipefail
BACKUP_DIR="/var/backups/xui"
SRC_DIR="/opt/xui/xui-data"
mkdir -p "$BACKUP_DIR"
timestamp=$(date -u +"%Y%m%dT%H%M%SZ")
plain="$BACKUP_DIR/xui-data-$timestamp.tar.gz"
enc="$BACKUP_DIR/xui-data-$timestamp.tar.gz.gpg"

tar -czf "$plain" -C "$SRC_DIR" .

# GPG encrypt: if BACKUP_GPG_RECIPIENT is set, do public-key encryption; otherwise symmetric with passphrase BACKUP_GPG_PASSPHRASE
if [ -n "${BACKUP_GPG_RECIPIENT:-}" ]; then
  gpg --batch --yes --output "$enc" --encrypt --recipient "$BACKUP_GPG_RECIPIENT" "$plain"
elif [ -n "${BACKUP_GPG_PASSPHRASE:-}" ]; then
  gpg --batch --yes --passphrase "$BACKUP_GPG_PASSPHRASE" --symmetric --cipher-algo AES256 --output "$enc" "$plain"
else
  echo "No GPG recipient or passphrase set; creating unencrypted backup (not recommended)"
  mv "$plain" "$enc"
fi

# remove plaintext if encrypted
if [ -f "$enc" ] && [ "$enc" != "$plain" ]; then
  rm -f "$plain"
fi

# Optional upload to S3/Spaces (AWS CLI configured via env)
if [ -n "${BACKUP_S3_BUCKET:-}" ]; then
  if command -v aws >/dev/null 2>&1; then
    aws s3 cp "$enc" "${BACKUP_S3_BUCKET}/$(basename "$enc")"
  else
    echo "aws cli not installed; skipping upload"
  fi
fi

# keep last 7 backups locally
ls -1t "$BACKUP_DIR"/xui-data-*.tar.gz* | sed -e '1,7d' | xargs -r rm --
BKP
chmod +x /usr/local/bin/backup-xui.sh

# Install a daily cron job at 02:30 UTC
cat > /etc/cron.d/xui-backup <<'CRON'
# m h dom mon dow user command
30 2 * * * root /usr/local/bin/backup-xui.sh >/dev/null 2>&1
CRON

echo "Installed /usr/local/bin/backup-xui.sh and cron job /etc/cron.d/xui-backup"

exit 0
