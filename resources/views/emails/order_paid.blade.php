@php /** @var \App\Models\Order $order */ @endphp
<x-mail::message>
# Payment Received

Your payment for **Order #{{ $order->id }}** has been received successfully.

**Status:** {{ ucfirst($order->order_status) }}  
**Amount:** {{ number_format($order->grand_amount, 2) }} {{ $order->currency }}  
**Items:** {{ $order->items()->count() }}

We'll now start provisioning your proxies. You'll get another email once everything is fully ready with download links and QR codes.

@if($order->isFullyProvisioned())
You can already access your clients in your dashboard.
@else
Provisioning normally completes within a minute.
@endif

<x-mail::button :url="url('/customer/orders/'.$order->id)">
View Order
</x-mail::button>

Thanks,
{{ config('app.name') }} Team
</x-mail::message>
