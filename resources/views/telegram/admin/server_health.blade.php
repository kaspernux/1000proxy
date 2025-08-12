🌐 <b>{{ __('telegram.admin.server_health_title') }}</b>

📊 {{ __('telegram.admin.total_servers') }}: <b>{{ $totalServers }}</b>
✅ {{ __('telegram.admin.active') }}: <b>{{ $activeServers }}</b>
❌ {{ __('telegram.admin.inactive') }}: <b>{{ $inactiveServers }}</b>

🔍 <b>{{ __('telegram.admin.server_details') }}</b> (max 10):
@foreach($servers as $server)
{{ $server['statusIcon'] }} {{ $server['location'] }}
   {{ $server['loadIcon'] }} {{ __('telegram.admin.avg_load') }}: {{ $server['load'] }}%
   💰 {{ __('telegram.admin.balance') }}: ${{ number_format((float)$server['price'], 2) }}

@endforeach
@if($remaining > 0)
{{ __('telegram.admin.more_servers', ['count' => $remaining]) }}
@endif

🔗 {{ __('telegram.common.open_dashboard') }}
