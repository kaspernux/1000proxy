# 🔧 Troubleshooting Guide

## Table of Contents

-   [Connection Issues](#connection-issues)
-   [Payment Problems](#payment-problems)
-   [Performance Issues](#performance-issues)
-   [Client Configuration](#client-configuration)
-   [Account Issues](#account-issues)
-   [Advanced Troubleshooting](#advanced-troubleshooting)

---

## Connection Issues

### Problem: Cannot connect to proxy server

**Step 1: Verify Configuration**

```bash
# Check if configuration is correct
1. Compare settings with provided configuration
2. Verify server address, port, and protocol
3. Ensure UUID/password is correctly entered
```

**Step 2: Test Network Connectivity**

```bash
# Test basic connectivity
ping [server-ip]
telnet [server-ip] [port]
```

**Step 3: Check Firewall/Antivirus**

-   Temporarily disable firewall
-   Add proxy client to antivirus exceptions
-   Check corporate network restrictions

**Step 4: Try Different Protocols**

1. VLESS (recommended for best performance)
2. VMESS (if VLESS doesn't work)
3. TROJAN (for restricted networks)
4. SHADOWSOCKS (for mobile devices)

**Step 5: Check Client App**

-   Update to latest version
-   Try different client app
-   Clear app cache/data

### Problem: Connection drops frequently

**Possible Causes & Solutions:**

1. **Network Instability**

    - Switch to mobile data/different WiFi
    - Check local internet stability
    - Contact ISP if issues persist

2. **Server Load**

    - Try different server location
    - Use during off-peak hours
    - Upgrade to premium server

3. **Protocol Issues**
    - Switch from VMESS to VLESS
    - Enable/disable mux settings
    - Adjust connection timeout

**Configuration Example:**

```json
{
    "protocol": "vless",
    "settings": {
        "clients": [
            {
                "id": "your-uuid",
                "level": 0,
                "alterId": 0
            }
        ]
    },
    "streamSettings": {
        "network": "ws",
        "wsSettings": {
            "path": "/path"
        }
    }
}
```

---

## Payment Problems

### Problem: Cryptocurrency payment not confirming

**Step 1: Check Transaction Status**

```bash
# For Bitcoin
1. Go to blockchain.info
2. Search for your transaction hash
3. Check confirmation count (need 1-3 confirmations)
```

**Step 2: Verify Payment Amount**

-   Check if exact amount was sent
-   Account for network fees
-   Verify correct wallet address

**Step 3: Contact Support**
If payment is confirmed on blockchain but not in your account:

-   Provide transaction hash
-   Include wallet address used
-   Specify amount and currency

### Problem: Credit card payment declined

**Common Solutions:**

1. **Check Card Details**

    - Verify expiry date
    - Check CVV code
    - Ensure sufficient funds

2. **Bank Restrictions**

    - Contact bank about international transactions
    - Ask about crypto-related payment blocks
    - Try different card

3. **Billing Address**
    - Ensure billing address matches card
    - Use address format of card issuing country

### Problem: Wallet balance not updating

**Troubleshooting Steps:**

1. **Refresh Page**: Hard refresh (Ctrl+F5)
2. **Check Transaction**: Verify payment was completed
3. **Wait Period**: Allow 10-15 minutes for processing
4. **Contact Support**: Provide payment receipt/hash

---

## Performance Issues

### Problem: Slow proxy speeds

**Speed Optimization Steps:**

1. **Server Selection**

    ```bash
    # Choose server closest to your location
    Asia-Pacific: Singapore, Tokyo, Hong Kong
    Europe: London, Frankfurt, Amsterdam
    Americas: New York, Los Angeles, Toronto
    ```

2. **Protocol Optimization**

    - **VLESS**: Best performance, newest protocol
    - **VMESS**: Good compatibility, moderate speed
    - **TROJAN**: Good for restricted networks
    - **SHADOWSOCKS**: Optimized for mobile

3. **Client Settings**
    ```json
    {
        "mux": {
            "enabled": true,
            "concurrency": 8
        },
        "routing": {
            "strategy": "rules",
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

### Problem: High latency/ping

**Latency Reduction Tips:**

1. **Geographic Proximity**

    - Choose server closest to your location
    - Test multiple servers in same region
    - Consider ISP routing to different locations

2. **Network Path Optimization**

    - Use direct connection (no cascading)
    - Disable unnecessary routing rules
    - Use UDP over TCP when possible

3. **Client Configuration**
    ```json
    {
        "freedom": {
            "timeout": 10,
            "redirect": "127.0.0.1:3366"
        }
    }
    ```

---

## Client Configuration

### Problem: QR code not scanning

**Solutions:**

1. **QR Code Issues**

    - Ensure QR code is fully visible
    - Increase brightness on screen
    - Try different QR scanner app

2. **Manual Configuration**

    ```
    Protocol: vless
    Server: your-server.com
    Port: 443
    UUID: your-uuid-here
    Security: none
    Transport: ws
    Path: /path
    ```

3. **Alternative Methods**
    - Copy configuration link
    - Use JSON import
    - Manual parameter entry

### Problem: Invalid configuration format

**Configuration Validation:**

1. **Check JSON Syntax**

    ```bash
    # Validate JSON online or use:
    python -m json.tool config.json
    ```

2. **Common Format Issues**

    - Missing commas or brackets
    - Incorrect quotation marks
    - Invalid UUID format

3. **Protocol-Specific Settings**

    ```json
    // VLESS
    {
      "protocol": "vless",
      "settings": {
        "clients": [{"id": "uuid", "level": 0}]
      }
    }

    // VMESS
    {
      "protocol": "vmess",
      "settings": {
        "clients": [{"id": "uuid", "level": 0, "alterId": 0}]
      }
    }
    ```

---

## Account Issues

### Problem: Cannot login

**Step-by-Step Resolution:**

1. **Password Reset**

    - Use "Forgot Password" link
    - Check spam folder for reset email
    - Use temporary password to login

2. **Email Verification**

    - Check if email is verified
    - Resend verification email
    - Check spam/junk folders

3. **Account Status**
    - Verify account is not suspended
    - Check for payment issues
    - Contact support for account status

### Problem: Email not receiving

**Email Delivery Issues:**

1. **Check Spam Filters**

    - Look in spam/junk folder
    - Add noreply@1000proxy.com to contacts
    - Check email provider's filters

2. **Domain Blocking**

    - Corporate email may block automated emails
    - Try personal email account
    - Contact IT department about whitelist

3. **Alternative Contact**
    - Use support ticket system
    - Contact through alternative email
    - Use social media support channels

---

## Advanced Troubleshooting

### Network Diagnostic Commands

```bash
# Check DNS resolution
nslookup your-server.com
dig your-server.com

# Test port connectivity
telnet your-server.com 443
nc -zv your-server.com 443

# Trace network path
traceroute your-server.com
pathping your-server.com  # Windows

# Check local proxy settings
curl -x socks5://127.0.0.1:1080 http://httpbin.org/ip
```

### Log Analysis

**Client Logs Location:**

-   **Windows**: `%USERPROFILE%\AppData\Roaming\v2ray\`
-   **macOS**: `~/Library/Application Support/v2ray/`
-   **Linux**: `~/.config/v2ray/`

**Common Log Patterns:**

```
# Connection successful
[Info] transport/internet: listening TCP on 127.0.0.1:1080

# Authentication failed
[Warning] proxy/vmess: invalid user: UUID

# Network timeout
[Error] transport/internet: failed to dial to tcp:server:443
```

### Performance Monitoring

**Speed Test Commands:**

```bash
# Test download speed
curl -o /dev/null -s -w "%{speed_download}\n" http://speedtest.wdc01.softlayer.com/downloads/test100.zip

# Test with proxy
curl --proxy socks5://127.0.0.1:1080 -o /dev/null -s -w "%{speed_download}\n" http://speedtest.wdc01.softlayer.com/downloads/test100.zip
```

### Configuration Backup

**Export Settings:**

```bash
# V2Ray configuration
cp ~/.config/v2ray/config.json ~/backup/v2ray-config-$(date +%Y%m%d).json

# Client settings
# Export from client app settings > backup/export
```

---

## Getting Additional Help

### When to Contact Support

Contact support when:

-   Multiple troubleshooting steps failed
-   Payment issues persist
-   Account-specific problems
-   Server-side configuration issues

### Information to Include

Always provide:

-   **Account email address**
-   **Order ID** (if applicable)
-   **Client application** name and version
-   **Operating system** and version
-   **Error messages** (exact text)
-   **Steps already attempted**
-   **Screenshots** (if relevant)

### Support Channels

-   **Email**: support@1000proxy.com
-   **Ticket System**: Through your account dashboard
-   **Documentation**: Check docs/ folder for guides
-   **FAQ**: Common issues and solutions

---

## Emergency Procedures

### Service Outage

1. **Check Status Page**: Monitor system status
2. **Try Alternative Servers**: Use backup configurations
3. **Contact Support**: Report widespread issues
4. **Follow Updates**: Check announcements

### Account Compromise

1. **Change Password**: Immediately update credentials
2. **Review Activity**: Check recent login/order history
3. **Contact Support**: Report suspicious activity
4. **Secure Email**: Update email account security

### Payment Disputes

1. **Document Issue**: Screenshot error messages
2. **Contact Support**: Provide transaction details
3. **Bank/Card Company**: If necessary for chargebacks
4. **Follow Up**: Track resolution progress

---

_Last updated: July 8, 2025_

For immediate assistance, contact our support team with detailed information about your issue.
