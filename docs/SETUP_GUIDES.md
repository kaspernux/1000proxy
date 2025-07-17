# 📱 Client Setup Guides

## Table of Contents

-   [Android Setup](#android-setup)
-   [iOS Setup](#ios-setup)
-   [Windows Setup](#windows-setup)
-   [macOS Setup](#macos-setup)
-   [Linux Setup](#linux-setup)
-   [Router Setup](#router-setup)

---

## Android Setup

### Recommended Apps

-   **V2rayNG** (Most popular, free)
-   **Clash for Android** (Advanced features)
-   **Shadowsocks** (For SHADOWSOCKS protocol only)

### V2rayNG Setup

**Step 1: Install V2rayNG**

```bash
# Download from:
# - Google Play Store
# - GitHub releases: https://github.com/2dust/v2rayNG/releases
# - APK file from official source
```

**Step 2: Import Configuration**

**Method A: QR Code**

1. Open V2rayNG
2. Tap "+" button
3. Select "Scan QR code"
4. Scan the QR code from your 1000proxy dashboard

**Method B: Manual Configuration**

1. Open V2rayNG
2. Tap "+" → "Manual Input"
3. Enter configuration:
    ```
    Alias: My 1000proxy
    Address: your-server.com
    Port: 443
    UUID: your-uuid-here
    Security: none
    Network: ws
    Path: /your-path
    ```

**Step 3: Connect**

1. Select your configuration
2. Tap the power button to connect
3. Grant VPN permission when prompted
4. Verify connection with IP check

**Advanced Settings:**

```json
{
    "routing": {
        "rules": [
            {
                "type": "field",
                "ip": ["geoip:private"],
                "outboundTag": "direct"
            }
        ]
    }
}
```

### Clash for Android Setup

**Step 1: Install Clash**

-   Download from GitHub: https://github.com/Kr328/ClashForAndroid

**Step 2: Import Configuration**

1. Open Clash for Android
2. Tap "Profiles"
3. Tap "+" → "Import from URL"
4. Enter your subscription URL
5. Or use "Import from file" for manual config

**Step 3: Configure Rules**

```yaml
proxies:
    - name: "1000proxy"
      type: vless
      server: your-server.com
      port: 443
      uuid: your-uuid
      network: ws
      ws-path: /your-path
```

---

## iOS Setup

### Recommended Apps

-   **Shadowrocket** (Paid, most features)
-   **Quantumult X** (Advanced, paid)
-   **Potatso Lite** (Free, basic features)

### Shadowrocket Setup

**Step 1: Install Shadowrocket**

-   Purchase from App Store ($2.99)
-   Requires non-Chinese Apple ID

**Step 2: Import Configuration**

**Method A: QR Code**

1. Open Shadowrocket
2. Tap "+" in top right
3. Select "QR Code"
4. Scan your 1000proxy QR code

**Method B: Manual Configuration**

1. Open Shadowrocket
2. Tap "+" → "Type"
3. Select "VLESS" or your protocol
4. Enter details:
    ```
    Server: your-server.com
    Port: 443
    UUID: your-uuid
    Method: none
    Plugin: v2ray-plugin
    Transport: websocket
    Path: /your-path
    ```

**Step 3: Connect**

1. Toggle switch next to your configuration
2. Tap "Allow" for VPN configuration
3. Use Touch ID/Face ID to confirm

**Advanced Rules:**

```
# Bypass China mainland
^.*\.cn$ DIRECT
^.*\.com\.cn$ DIRECT
GEOIP,CN,DIRECT
FINAL,PROXY
```

### Quantumult X Setup

**Step 1: Install Quantumult X**

-   Purchase from App Store ($7.99)

**Step 2: Configuration**

1. Open Quantumult X
2. Long press "Server" tab
3. Select "Server" → "Add"
4. Choose "VLESS" or appropriate protocol
5. Enter server details

**Configuration Example:**

```
[server_local]
vless=your-server.com:443, method=none, password=your-uuid, obfs=wss, obfs-host=your-server.com, obfs-uri=/your-path

[policy]
static=Proxy, vless-server, img-url=https://raw.githubusercontent.com/Koolson/Qure/master/IconSet/Color/Proxy.png
```

---

## Windows Setup

### Recommended Apps

-   **V2rayN** (Most popular, free)
-   **Clash for Windows** (User-friendly interface)
-   **Qv2ray** (Advanced, open source)

### V2rayN Setup

**Step 1: Install V2rayN**

```bash
# Download from:
# https://github.com/2dust/v2rayN/releases
#
# Requirements:
# - .NET Framework 4.6 or later
# - Windows 7 or later
```

**Step 2: Import Configuration**

**Method A: QR Code**

1. Open V2rayN
2. Click "Server" → "Add VLESS server"
3. Click "Import from QR code"
4. Scan QR code from your screen

**Method B: Manual Configuration**

1. Right-click V2rayN tray icon
2. Select "Add VLESS server"
3. Enter configuration:
    ```
    Alias: 1000proxy Server
    Address: your-server.com
    Port: 443
    UUID: your-uuid
    Encryption: none
    Transport: ws
    Path: /your-path
    TLS: tls
    ```

**Step 3: System Proxy Setup**

1. Right-click tray icon
2. Select "System Proxy" → "Global Mode"
3. Or use "PAC Mode" for automatic rules

**Advanced Configuration:**

```json
{
    "log": {
        "loglevel": "warning"
    },
    "inbounds": [
        {
            "tag": "proxy",
            "port": 10808,
            "listen": "127.0.0.1",
            "protocol": "socks"
        }
    ],
    "outbounds": [
        {
            "tag": "proxy",
            "protocol": "vless",
            "settings": {
                "vnext": [
                    {
                        "address": "your-server.com",
                        "port": 443,
                        "users": [
                            {
                                "id": "your-uuid",
                                "encryption": "none"
                            }
                        ]
                    }
                ]
            },
            "streamSettings": {
                "network": "ws",
                "security": "tls",
                "wsSettings": {
                    "path": "/your-path"
                }
            }
        }
    ]
}
```

### Clash for Windows Setup

**Step 1: Install Clash for Windows**

-   Download from: https://github.com/Fndroid/clash_for_windows_pkg/releases

**Step 2: Import Configuration**

1. Open Clash for Windows
2. Click "Profiles"
3. Paste your subscription URL
4. Click "Download"

**Step 3: Configure Proxy**

1. Go to "Proxies" tab
2. Select your 1000proxy server
3. Enable "System Proxy"

**Rule Configuration:**

```yaml
rules:
    - DOMAIN-SUFFIX,googleapis.com,PROXY
    - DOMAIN-SUFFIX,google.com,PROXY
    - DOMAIN-KEYWORD,google,PROXY
    - GEOIP,CN,DIRECT
    - MATCH,PROXY
```

---

## macOS Setup

### Recommended Apps

-   **V2rayU** (Free, simple interface)
-   **ClashX** (User-friendly, free)
-   **Qv2ray** (Advanced features)

### V2rayU Setup

**Step 1: Install V2rayU**

```bash
# Download from:
# https://github.com/yanue/V2rayU/releases
#
# Or install via Homebrew:
brew install --cask v2rayu
```

**Step 2: Import Configuration**

1. Click V2rayU icon in menu bar
2. Select "Configure..." → "Add Server"
3. Choose "VLESS" protocol
4. Enter server details:
    ```
    Address: your-server.com
    Port: 443
    UUID: your-uuid
    Security: none
    Network: ws
    Path: /your-path
    TLS: true
    ```

**Step 3: Connect**

1. Click V2rayU menu bar icon
2. Select "Turn v2ray-core On"
3. Choose "PAC Mode" or "Global Mode"

### ClashX Setup

**Step 1: Install ClashX**

```bash
# Download from:
# https://github.com/yichengchen/clashX/releases
#
# Or install via Homebrew:
brew install --cask clashx
```

**Step 2: Import Configuration**

1. Click ClashX icon in menu bar
2. Select "Config" → "Remote Config"
3. Enter your subscription URL
4. Click "OK"

**Step 3: Enable Proxy**

1. Click ClashX menu bar icon
2. Select "Set as System Proxy"
3. Choose your proxy server

**Configuration Example:**

```yaml
proxies:
    - name: "1000proxy"
      type: vless
      server: your-server.com
      port: 443
      uuid: your-uuid
      network: ws
      ws-path: /your-path
      tls: true
```

---

## Linux Setup

### Command Line Setup

**Step 1: Install V2Ray**

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install v2ray

# CentOS/RHEL
sudo yum install v2ray

# Arch Linux
sudo pacman -S v2ray

# Or install from script:
bash <(curl -L https://raw.githubusercontent.com/v2fly/fhs-install-v2ray/master/install-release.sh)
```

**Step 2: Configure V2Ray**

```bash
# Edit configuration file
sudo nano /etc/v2ray/config.json
```

**Configuration:**

```json
{
    "log": {
        "loglevel": "warning"
    },
    "inbounds": [
        {
            "tag": "proxy",
            "port": 10808,
            "listen": "127.0.0.1",
            "protocol": "socks"
        }
    ],
    "outbounds": [
        {
            "tag": "proxy",
            "protocol": "vless",
            "settings": {
                "vnext": [
                    {
                        "address": "your-server.com",
                        "port": 443,
                        "users": [
                            {
                                "id": "your-uuid",
                                "encryption": "none"
                            }
                        ]
                    }
                ]
            },
            "streamSettings": {
                "network": "ws",
                "security": "tls",
                "wsSettings": {
                    "path": "/your-path"
                }
            }
        }
    ]
}
```

**Step 3: Start V2Ray**

```bash
# Start service
sudo systemctl start v2ray
sudo systemctl enable v2ray

# Check status
sudo systemctl status v2ray

# Test connection
curl --proxy socks5://127.0.0.1:10808 http://httpbin.org/ip
```

### GUI Applications

**Qv2ray Setup:**

```bash
# Install via AppImage
wget https://github.com/Qv2ray/Qv2ray/releases/download/v2.7.0/Qv2ray-v2.7.0-linux-x64.AppImage
chmod +x Qv2ray-v2.7.0-linux-x64.AppImage
./Qv2ray-v2.7.0-linux-x64.AppImage
```

---

## Router Setup

### OpenWrt Setup

**Step 1: Install V2Ray**

```bash
# Update package list
opkg update

# Install V2Ray
opkg install v2ray-core

# Install web interface (optional)
opkg install luci-app-v2ray
```

**Step 2: Configure V2Ray**

```bash
# Edit configuration
vi /etc/config/v2ray
```

**Configuration:**

```
config v2ray 'main'
    option enabled '1'
    option config_file '/etc/v2ray/config.json'

config server 'your_server'
    option alias '1000proxy'
    option server 'your-server.com'
    option server_port '443'
    option password 'your-uuid'
    option security 'none'
    option network 'ws'
    option ws_path '/your-path'
    option tls '1'
```

**Step 3: Set Up Transparent Proxy**

```bash
# Configure firewall rules
iptables -t nat -A OUTPUT -p tcp --dport 80,443 -j REDIRECT --to-ports 12345
iptables -t nat -A PREROUTING -p tcp --dport 80,443 -j REDIRECT --to-ports 12345
```

### DD-WRT Setup

**Step 1: Enable SSH**

1. Go to router admin panel
2. Enable SSH service
3. Connect via SSH

**Step 2: Install Entware**

```bash
# Install Entware package manager
wget -O - http://pkg.entware.net/binaries/armv7/installer/generic.sh | sh
```

**Step 3: Install V2Ray**

```bash
# Install V2Ray
opkg install v2ray-core

# Create configuration
mkdir -p /opt/etc/v2ray
vi /opt/etc/v2ray/config.json
```

---

## Testing Your Setup

### Connection Test

```bash
# Test proxy connection
curl --proxy socks5://127.0.0.1:1080 http://httpbin.org/ip

# Test speed
curl --proxy socks5://127.0.0.1:1080 -o /dev/null -s -w "Speed: %{speed_download} bytes/sec\n" http://speedtest.example.com/test.zip
```

### IP Verification

1. Visit https://whatismyipaddress.com
2. Verify your IP has changed
3. Check for DNS leaks at https://dnsleaktest.com

### Performance Test

-   Test download/upload speeds
-   Check latency/ping times
-   Verify stable connection

---

## Common Issues & Solutions

### Issue: Cannot connect

-   Verify server address and port
-   Check firewall settings
-   Try different client applications

### Issue: Slow speeds

-   Try different server locations
-   Switch protocols (VLESS recommended)
-   Check local network conditions

### Issue: Frequent disconnections

-   Enable auto-reconnect in client
-   Check network stability
-   Try different transport methods

---

## Support

For additional help with client setup:

-   Check our [FAQ](FAQ.md)
-   Visit our [Troubleshooting Guide](TROUBLESHOOTING.md)
-   Contact support at support@1000proxy.io

_Last updated: July 8, 2025_
