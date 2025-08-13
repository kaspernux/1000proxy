{{-- Telegram /help content: Commands + Setup Guides --}}

<b>🤖 1000proxy Bot Help</b>

Use the menu below or type commands. You can browse plans, buy, and manage your services here.

<b>Commands</b>
• /menu — Open the main menu
• /help — Show this help
• /login — Get a magic login link (if your account exists)
• /signup — Create and link a new account
• /balance — Show wallet balance
• /topup — Quick wallet top-up options
• /plans — Browse available plans
• /orders — View your orders
• /buy <code>[plan_id]</code> — Purchase a plan by ID
• /myproxies — List your active services
• /config <code>[client_id|order_item_id]</code> — Get config and links for a service
• /qrcode <code>[client_id]</code> — Show QR code for subscription link
• /reset <code>[client_id]</code> — Reset traffic (with confirmation)
• /status — Account summary (or /status <code>[client_id]</code> for a specific service)
• /support <code>[your message]</code> — Contact support

<b>Where to find your configuration</b>
• In Telegram: /myproxies → tap a service → “Config” (shows links, QR, JSON)
• Quick: /config <code>[client_id]</code> or /qrcode <code>[client_id]</code>
• Dashboard: <a href="{{ rtrim(config('app.url'), '/') }}/account/my-active-servers">My Active Servers</a> (full QR/links)

<b>Setup Guides</b>

1) iOS — V2Box
• Install: App Store → “V2Box”.
• Import via QR: Open V2Box → “+” → Scan QR (from /myproxies or dashboard).
• Import via URL: In V2Box, add subscription URL from your config details.
• Connect: Select your profile → Allow VPN permission → Connect.

2) macOS — V2Box
• Install: Mac App Store → “V2Box”.
• Import via URL/QR: Open V2Box → Add subscription (or scan QR from another screen).
• Connect: Choose the imported profile → Toggle on.

3) Android — V2Box
• Install: V2Box for Android (per your device store guidelines).
• Import: “Profiles” → “+” → Import from URL or Scan QR.
• Connect: Select the profile and start.
• Tip: If V2Box isn’t available in your region, use V2rayNG with the same subscription link.

4) Windows — v2rayN
• Install: Download v2rayN from GitHub (2dust/v2rayN → Releases), install requirements (.NET ≥ 4.6).
• Import via URL: Right‑click tray → “Add subscription” → paste subscription URL → Update.
• Import via QR: “Server” → “Import from QR code”.
• Connect: Right‑click tray → “System Proxy” → choose Global or PAC Mode.

5) Android — V2rayNG
• Install: Google Play or GitHub (2dust/v2rayNG → Releases).
• Import via QR: Tap “+” → “Scan QR code” and scan from /myproxies or dashboard.
• Import via URL: Tap “+” → “Import from clipboard” (copy your subscription URL first).
• Connect: Select the profile → Tap the power button.

6) Android — Clash for Android
• Install: GitHub (Kr328/ClashForAndroid) or trusted source.
• Import: Profiles → “+” → Import from URL (paste subscription) or file.
• Enable: Go to Home → Turn on service → Allow VPN permission.

7) Windows — Clash for Windows
• Install: GitHub (Fndroid/clash_for_windows_pkg → Releases).
• Import: Profiles → “New Profile” → Remote → paste subscription URL → Download.
• Enable: Proxies tab → select a proxy; turn on System Proxy.

<b>Subscription/Config Types you may see</b>
• Subscription link (recommended): keeps your client updated automatically.
• Single client link: one configuration for a single proxy inbound.
• JSON link/file: raw configuration for advanced clients.

<b>Tips & Troubleshooting</b>
• Always allow VPN/proxy permissions when prompted by the OS.
• If connection fails, try another server/plan or re‑import the subscription.
• Check that date/time is correct on your device and TLS/WS settings match your config.
• Test IP after connecting: search “what is my IP” in a browser.
• Need help? Use /support with a brief description of your issue.

<b>Useful links</b>
• Dashboard: <a href="{{ rtrim(config('app.url'), '/') }}/account">{{ rtrim(config('app.url'), '/') }}/account</a>
• Orders: <a href="{{ rtrim(config('app.url'), '/') }}/account/order-management">Order Management</a>
• Wallet: <a href="{{ rtrim(config('app.url'), '/') }}/wallet/usd/top-up">Wallet</a>
🤖 <b>{{ __('telegram.help.title') }}</b>

👤 <b>{{ __('telegram.help.section_account') }}</b>
/start — {{ __('telegram.help.start') }}
/balance — {{ __('telegram.help.balance') }}
/topup — {{ __('telegram.help.topup') }}
/signup — {{ __('telegram.help.signup') }}
/profile — {{ __('telegram.help.profile') }}

🧰 <b>{{ __('telegram.help.section_services') }}</b>
/myproxies — {{ __('telegram.help.myproxies') }}
/config <i>[client_id]</i> — {{ __('telegram.help.config') }}
/reset <i>[client_id]</i> — {{ __('telegram.help.reset') }}
/status <i>[client_id]</i> — {{ __('telegram.help.status') }}

🛒 <b>{{ __('telegram.help.section_plans') }}</b>
/plans — {{ __('telegram.help.plans') }}
/orders — {{ __('telegram.help.orders') }}
/buy <i>[plan_id]</i> — {{ __('telegram.help.buy') }}

🆘 <b>{{ __('telegram.help.section_support') }}</b>
/support <i>[message]</i> — {{ __('telegram.help.support') }}
/help — {{ __('telegram.help.help') }}

🔗 <a href="{{ config('app.url') }}">{{ __('telegram.common.open_dashboard') }}</a>
