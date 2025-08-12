📩 <b>{{ __('telegram.support.sent_title') }}</b>

{{ __('telegram.support.sent_hint') }}

📋 <b>{{ __('telegram.support.ticket') }}</b>
👤 {{ __('telegram.support.user') }}: {{ $customerName }}
📝 {{ __('telegram.support.message') }}: {{ $messageText }}

🔗 {{ __('telegram.support.more') }}: <a href="{{ config('app.url') }}/support">{{ __('telegram.support.web') }}</a>
