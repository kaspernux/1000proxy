{{-- Telegram Orders Template --}}
<div>
  <b>📋 {{ __('telegram.orders.title') }}</b>
  <br/>
  @forelse($orders as $order)
    @php
      $statusMap = [
        'pending' => '⏳',
        'processing' => '🔄',
        'completed' => '✅',
        'failed' => '❌',
        'cancelled' => '⏹️',
      ];
      $icon = $statusMap[$order->order_status ?? ''] ?? '📋';
      $firstItem = $order->items()->with('serverPlan.server')->first();
      $serverLabel = $firstItem?->serverPlan?->server?->country ?? $firstItem?->serverPlan?->server?->ip ?? '—';
      $amount = $order->grand_amount ?? $order->total_amount ?? 0;
    @endphp
    <b>{{ $icon }} Order #{{ $order->id }}</b><br/>
    🌐 {{ $serverLabel }}<br/>
    💰 ${{ number_format((float)$amount, 2) }}<br/>
    📅 {{ $order->created_at->format('M j, Y') }}<br/>
    📊 {{ $order->payment_status ?? '—' }} / {{ $order->order_status ?? '—' }}
    <br/><br/>
  @empty
    <i>�️ {{ __('telegram.orders.empty') }}</i>
  @endforelse
</div>
