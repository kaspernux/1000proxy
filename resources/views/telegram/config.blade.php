🔐 <b>{{ __('telegram.config.title') }}</b>

📦 {{ __('telegram.config.plan') }}: <b>{{ $planName ?? '—' }}</b>
🌐 {{ __('telegram.config.server') }}: <b>{{ $server ?? '—' }}</b>

@if(!empty($clientLink))
🔗 {{ __('telegram.config.client_link') }}: <a href="{{ $clientLink }}">{{ __('telegram.common.open') }}</a>
@endif
@if(!empty($subscriptionLink))
📩 {{ __('telegram.config.subscription') }}: <a href="{{ $subscriptionLink }}">{{ __('telegram.common.open') }}</a>
@endif
@if(!empty($jsonLink))
🧾 {{ __('telegram.config.json') }}: <a href="{{ $jsonLink }}">{{ __('telegram.common.open') }}</a>
@endif

🧭 {{ __('telegram.config.dashboard') }}
<a href="{{ config('app.url') }}/account">{{ __('telegram.common.open_dashboard') }}</a>
