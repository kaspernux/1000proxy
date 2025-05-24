# 🔒 1000PROXY Admin-Only Technical Guide

> ⚠️ **Strictly Confidential – Do NOT share this with customers.**
> Customers interact only with the 1000PROXY brand. The use of X-UI and internal protocol names must remain hidden.

---

## 🔧 Internal Overview

1000PROXY operates a fully automated backend using **X-UI panels** to create proxy clients across multiple secure protocols. The system is built in Laravel 10 and uses Xray-core-based configurations (via X-UI) to deliver encrypted, obfuscated access to users under the 1000PROXY brand.

---

## 💼 Supported Proxy Types (Customer-Facing vs Internal Mapping)

| Display Name | Internal Protocol | Notes                                         |
| ------------ | ----------------- | --------------------------------------------- |
| Fast+Secure  | VLESS + Reality   | Obfuscated, TLS-based, works behind firewalls |
| Encrypted    | VMESS             | Legacy secure protocol                        |
| HTTPS Cloak  | TROJAN            | TLS mimicry                                   |
| Ultra Speed  | SHADOWSOCKS       | High performance                              |
| Legacy Tools | SOCKS5 / HTTP     | Use only when explicitly required             |

Customers only see branded labels. Never expose `VLESS`, `Xray`, or `UUID` terminology.

---

## 🧩 Apps & Setup Guide by Platform

### 📱 Android

* **Apps**: `v2rayNG`, `Clash for Android`, `NapsternetV` (Reality)
* **Setup**: Import QR or link, connect
* **Tips**: v2rayNG supports all protocols; NapsternetV for Reality obfuscation

### 📱 iOS

* **Apps**: `V2Box`, `Shadowrocket` (paid), `Stash`, `Quantumult X`
* **Setup**: Tap QR or paste sub link > Activate
* **Tips**: All support subscription sync; great UX

### 💻 Windows

* **Apps**: `V2RayN`, `Clash for Windows`
* **Setup**: Paste sub link or scan QR > Start service

### 💻 macOS

* **Apps**: `V2Box`, `ClashX`, `V2RayU`
* **Setup**: Import config > Activate

### 🌐 Routers

* **Compatible**: OpenWRT (Passwall/Xray), Asus (Merlin)
* **Setup**: Manual import or JSON
* **Note**: For advanced users only

---

## 🛠️ Internal Admin Procedures

* **Order → Client Sync**: After payment confirmation, the queue system auto-creates clients on X-UI and generates QR codes/links.
* **QR + Links**: Stored per client: `client_link`, `sub_link`, `json_link`
* **X-UI Servers**: Use `Server` + `ServerConfig` models to connect via API securely
* **Reality Parameters**: `pbk`, `sid`, `fp`, `sni` pulled from `streamSettings.realitySettings`

---

## 🧠 Support Answer Templates (Do Not Mention Tech Internals)

**DO:**

* "Scan this QR code with your camera or import it into your Client App or this link to connect to your secure 1000PROXY access."
* "Download the recommended app for your device."
* "If the connection fails, try switching to HTTPS Cloak mode."

**DON'T:**

* Never say “This is VLESS, VMESS, or Xray.”
* Never expose technical settings like `pbk`, `sid`, `uuid`, etc.

## ❌ Common Customer Issues

| Customer Question                    | Suggested Admin Answer                                   |
| ------------------------------------ | -------------------------------------------------------- |
| What is this link/QR?                | “It connects you securely to our Fast+Secure service.”   |
| My app says invalid config           | “Please re-import the QR or use our recommended app.”    |
| Which app should I use?              | “We recommend v2rayNG (Android), Shadowrocket (iOS).”    |
| I can’t connect / timeout            | “Try switching to HTTPS Cloak mode or another location.” |
| What’s the difference between types? | “Fast+Secure is best for performance and privacy.”       |

Never use: X-UI, VLESS, VMESS, Xray, Inbound, UUID
Always use: Fast+Secure, Encrypted, HTTPS Mode, Private Access

---

## 📦 Admin Troubleshooting

* Test all links via V2BOX, V2RayN or Clash locally.
* Use a generic domain in Reality to bypass censorship (e.g., `cdn.cloudflare.com`).
* Reset or re-issue QR if a user complains.

| Problem                    | Cause / Fix                              |
| -------------------------- | ---------------------------------------- |
| QR not scanning            | Recommend link import manually           |
| Link expired               | Reissue or renew customer’s subscription |
| Customer says app crashes  | Recommend alternative app                |
| XUI sync fails             | Check API auth, server status, and logs  |
| Missing Reality parameters | Verify streamSettings parsing            |

---

## 🛡️ Branding Rules

✅ Say:

* “1000PROXY Secure Gateway”
* “Private, Encrypted Access”
* “Built for Freedom & Privacy”

🚫 Never Say:

* “VLESS”, “VMESS”, “UUID”, “Xray”, “X-UI”

---

## 🚀 Sales Tips for Support Agents

* Promote **Fast+Secure** for censorship-heavy countries
* Emphasize: “Access any content, anywhere, privately”
* Offer trial links for skeptical users
* Renewals = new QR (not reactivating old ones)

---

## ⚖️ Final Notes

* All client creation is handled via X-UI API (invisible to customers)
* QR links are permanent until expiration
* Subscriptions auto-update when imported into apps

Maintain strict confidentiality. 1000PROXY is the brand — X-UI is the backend engine.

> Internal document maintained by Osimorph - For staff only.
