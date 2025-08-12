📊 <b>{{ __('telegram.admin.system_stats_title') }}</b>

👥 {{ __('telegram.admin.total_users') }}: <b>{{ $totalUsers }}</b>
📋 {{ __('telegram.admin.total_orders') }}: <b>{{ $totalOrders }}</b>
✅ {{ __('telegram.admin.completed') }}: <b>{{ $completedOrders }}</b>
⏳ {{ __('telegram.admin.pending') }}: <b>{{ $pendingOrders }}</b>

💰 {{ __('telegram.admin.total_revenue') ?? 'Total Revenue' }}: <b>${{ number_format((float)$totalRevenue, 2) }}</b>
📅 {{ __('telegram.admin.today_revenue') }}: <b>${{ number_format((float)$todayRevenue, 2) }}</b>
📋 {{ __('telegram.admin.today_orders') }}: <b>{{ $todayOrders }}</b>

🌐 <b>{{ __('telegram.admin.server_stats') }}</b>
{{ __('telegram.admin.total_servers') }}: <b>{{ $totalServers }}</b>
{{ __('telegram.admin.active') }}: <b>{{ $activeServers }}</b>
{{ __('telegram.admin.avg_load') }}: <b>{{ number_format((float)$avgLoad, 1) }}%</b>

🔄 {{ __('telegram.admin.last_updated') }}: <b>{{ $updatedAt }}</b>
