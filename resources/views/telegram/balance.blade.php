💰 <b>{{ __('telegram.wallet.title') }}</b>

{{ __('telegram.wallet.balance') }}: <b>${{ number_format((float)($balance ?? 0), 2) }}</b>

💡 {{ __('telegram.wallet.menu_hint') }}
💳 {{ __('telegram.wallet.topup_hint') }}

🔘 <a href="{{ rtrim(config('app.url'), '/') }}/wallet/usd/top-up">{{ __('telegram.buttons.topup_wallet') }}</a>
