@php /** @var \App\Models\Order $order */ @endphp
<x-mail::message>
# Your Proxies Are Ready

Order **#{{ $order->id }}** has been fully provisioned.

Below are your access details and QR codes.

@php($clients = $clients ?? $order->getAllClients())
@if($clients->isEmpty())
No clients were found for this order (provisioning may still be in progress). We'll update you if anything changes.
@else
@foreach($clients as $client)
### {{ $client->email ?? 'Client' }}
Protocol: {{ optional($client->inbound)->protocol ?? 'vless' }}  
Inbound Port: {{ optional($client->inbound)->port }}  
Expires: @if($client->expiry_time) {{ \Carbon\Carbon::createFromTimestampMs($client->expiry_time)->toDateTimeString() }} @else Never @endif  
Traffic Limit: @if($client->total_gb_bytes) {{ round($client->total_gb_bytes/1073741824,2) }} GB @else Unlimited @endif

**Primary Link:**
<br>
<code style="word-break:break-all;display:block;margin:4px 0 12px;">{{ $client->client_link }}</code>

@if($client->qr_code_client)
<p><strong>Client QR:</strong><br>
<img src="{{ asset('storage/'.$client->qr_code_client) }}" alt="Client QR" width="180"></p>
@endif
@if($client->qr_code_sub)
<p><strong>Subscription QR:</strong><br>
<img src="{{ asset('storage/'.$client->qr_code_sub) }}" alt="Subscription QR" width="180"></p>
@endif
@endforeach
@endif

<x-mail::button :url="url('/customer/orders/'.$order->id)">
Manage Order
</x-mail::button>

Need help? Reply to this email or open a ticket via your dashboard.

Thanks,
{{ config('app.name') }} Team
</x-mail::message>
