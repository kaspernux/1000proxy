💳 <b>{{ __('telegram.topup.title') }}</b>

💰 {{ __('telegram.topup.current') }}: <b>${{ number_format((float)($currentBalance ?? 0), 2) }}</b>

🔗 <a href="{{ config('app.url') }}/wallet">{{ __('telegram.common.open_dashboard') }}</a>

💡 {{ __('telegram.topup.methods') }}:
• 💳 Cards (Stripe)
• 🅿️ PayPal
• ₿ Bitcoin (BTC)
• 🔒 Monero (XMR)
• ☀️ Solana (SOL)

⚡ {{ __('telegram.topup.instant') }}
💰 {{ __('telegram.topup.min') }}
