📊 <b>{{ __('telegram.status.account_title') }}</b>

👤 {{ __('telegram.status.user') }}: <b>{{ $name }}</b>
💰 {{ __('telegram.status.balance') }}: <b>${{ number_format((float)$balance, 2) }}</b>
🔐 {{ __('telegram.status.active') }}: <b>{{ $activeServices }}</b>
📅 {{ __('telegram.status.member_since') }}: <b>{{ $memberSince }}</b>

🔗 <a href="{{ config('app.url') }}/account">{{ __('telegram.status.dashboard') }}</a>
💡 <code>/status [client_id]</code>
