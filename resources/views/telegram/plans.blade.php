{{-- Telegram Plans Template --}}
@php
  $page = $page ?? 1;
  $totalPages = $totalPages ?? 1;
@endphp
<div>
  <b>🌐 {{ __('telegram.plans.title') }}</b>
  <br/>
  @foreach($plans as $plan)
    @php
      $country = $plan->server->country ?? $plan->server->ip ?? '—';
      $protocol = $plan->protocol ?? $plan->server->type ?? '—';
      $term = $plan->days ? __('telegram.plans.days', ['days' => $plan->days]) : __('telegram.plans.monthly');
      $data = $plan->data_limit_gb ? ($plan->data_limit_gb.' GB') : ($plan->volume ? ($plan->volume.' GB') : __('telegram.plans.unlimited'));
    @endphp
    <br/>┏━━━━━━━━━━━━━━━━━
    <br/><b>📦 {{ $plan->name }}</b>
    <br/>📍 {{ $country }}
    <br/>🛠️ {{ $protocol }} • ⏱️ {{ $term }} • 📶 {{ $data }}
    <br/>💵 ${{ number_format((float)$plan->price, 2) }}
    <br/>🧾 ID: {{ $plan->id }}
    <br/>━━━━━━━━━━━━━━━━━┛
    <br/>
  @endforeach
</div>
