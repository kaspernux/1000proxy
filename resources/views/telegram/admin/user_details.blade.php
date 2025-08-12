👤 <b>{{ __('telegram.admin.user_details_title') }}</b>

📧 {{ __('telegram.admin.email') }}: <b>{{ $email }}</b>
👤 {{ __('telegram.admin.name') }}: <b>{{ $name }}</b>
💰 {{ __('telegram.admin.balance') }}: <b>${{ number_format((float)$balance, 2) }}</b>
📋 {{ __('telegram.admin.total_orders') }}: <b>{{ $orders }}</b>
✅ {{ __('telegram.admin.active_proxies') }}: <b>{{ $activeOrders }}</b>
📱 {{ __('telegram.admin.telegram') }}: <b>{{ $telegramLinked ? 'Linked' : 'Not Linked' }}</b>
📅 {{ __('telegram.admin.joined') }}: <b>{{ $joined }}</b>
🔄 {{ __('telegram.admin.last_login') }}: <b>{{ $lastLogin }}</b>
