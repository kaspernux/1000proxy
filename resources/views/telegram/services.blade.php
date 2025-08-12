{{-- Telegram Services Template --}}
<div>
  <b>🔐 {{ __('telegram.services.title') }}</b><br/>
  @forelse($clients as $client)
    @php
      $planName = $client->plan->name ?? '—';
      $location = $client->inbound->server->country ?? $client->inbound->server->ip ?? '—';
      $status = $client->status ?? ($client->enable ? 'active' : 'inactive');
      $used = $usedMap[$client->id] ?? '—';
    @endphp
    • <b>{{ $planName }}</b><br/>
    📍 {{ $location }}<br/>
    📊 {{ __('telegram.services.status') }}: {{ $status }}<br/>
    📈 {{ __('telegram.services.traffic') }}: {{ $used }}<br/>
    🔗 /config_{{ $client->id }} • 🔄 /reset_{{ $client->id }}
    <br/><br/>
  @empty
    <i>�️ {{ __('telegram.services.empty') }}</i>
  @endforelse
</div>
