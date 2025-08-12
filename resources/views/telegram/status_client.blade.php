📊 <b>{{ __('telegram.status.service_title') }}</b>

📦 {{ __('telegram.config.plan') }}: <b>{{ $planName ?? '—' }}</b>
🌐 {{ __('telegram.config.server') }}: <b>{{ $server ?? '—' }}</b>
🔌 {{ __('telegram.status.connection') }}: <b>{{ $connection ? 'Active' : 'Inactive' }}</b>
📈 {{ __('telegram.status.upload') }}: <b>{{ $upload }}</b>
📉 {{ __('telegram.status.download') }}: <b>{{ $download }}</b>
📊 {{ __('telegram.status.total') }}: <b>{{ $total }}</b>
🔄 {{ __('telegram.status.resets') }}: <b>{{ $resets }}</b>
📅 {{ __('telegram.status.created') }}: <b>{{ $created }}</b>

🔗 <code>/config_{{ $clientId }}</code> — {{ __('telegram.status.config_cmd') }}
🔄 <code>/reset_{{ $clientId }}</code> — {{ __('telegram.status.reset_cmd') }}
