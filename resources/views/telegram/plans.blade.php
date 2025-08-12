{{-- Telegram Plans Template --}}
@php
  $page = $page ?? 1;
  $totalPages = $totalPages ?? 1;
@endphp
<div>
  <b>🌐 {{ __('telegram.plans.title') }} ({{ __('telegram.plans.page', ['page' => $page, 'total' => $totalPages]) }})</b>
  <br/>
  @foreach($plans as $plan)
    <b>• {{ $plan->name }}</b><br/>
    <span>📍 {{ $plan->server->country ?? $plan->server->ip ?? '—' }}</span><br/>
    <span>🛠️ {{ $plan->protocol ?? $plan->server->type ?? '—' }}</span> • 
    <span>⏱️ {{ $plan->days ? __('telegram.plans.days', ['days' => $plan->days]) : __('telegram.plans.monthly') }}</span> • 
    <span>📶 {{ $plan->data_limit_gb ? ($plan->data_limit_gb.' GB') : ($plan->volume ? ($plan->volume.' GB') : __('telegram.plans.unlimited')) }}</span><br/>
    <span>💵 ${{ number_format((float)$plan->price, 2) }}</span>
    <br/><br/>
  @endforeach
</div>
