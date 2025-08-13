{{-- Telegram Services Template --}}
<div>
  <b>🔐 {{ __('telegram.services.title') }}</b><br/>
  @forelse($clients as $client)
    @php
      $planName = $client->plan->name ?? '—';
      $server = $client->inbound->server ?? null;
      $location = $server?->country ?? $server?->ip ?? '—';
      $status = $client->status ?? ($client->enable ? 'active' : 'inactive');
      $statusLabel = $status === 'active' ? __('telegram.status.active_label') : ($status === 'inactive' ? __('telegram.status.inactive_label') : $status);
      $used = $usedMap[$client->id] ?? '—';
      $expires = $client->expired_at?->format('M j, Y') ?? '—';
    @endphp
    <br/>┏━━━━━━━━━━━━━━━━━
    <br/><b>📦 {{ $planName }}</b>
    <br/>📍 {{ $location }}
    <br/>📊 {{ __('telegram.services.status') }}: {{ $statusLabel }}
    <br/>📈 {{ __('telegram.services.traffic') }}: {{ $used }}
    <br/>📅 {{ __('telegram.services.expires') }}: {{ $expires }}
    <br/>🆔 {{ $client->id }}
    <br/>━━━━━━━━━━━━━━━━━┛
    <br/>
  @empty
    <i>�️ {{ __('telegram.services.empty') }}</i>
  @endforelse
</div>
