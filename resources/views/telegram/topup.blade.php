💳 <b>{{ __('telegram.topup.title') }}</b>

💰 {{ __('telegram.topup.current') }}: <b>${{ number_format((float)($currentBalance ?? 0), 2) }}</b>

🔗 <a href="{{ config('app.url') }}/wallet">{{ __('telegram.common.open_dashboard') }}</a>

💡 {{ __('telegram.topup.methods') }}:
• 💳 {{ __('telegram.topup.method_cards') }}
• 🅿️ {{ __('telegram.topup.method_paypal') }}
• ₿ {{ __('telegram.topup.method_bitcoin') }}
• 🔒 {{ __('telegram.topup.method_monero') }}
• ☀️ {{ __('telegram.topup.method_solana') }}

⚡ {{ __('telegram.topup.instant') }}
💰 {{ __('telegram.topup.min') }}
