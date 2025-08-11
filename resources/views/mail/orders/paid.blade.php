@php /** @var \App\Models\Order $order */ @endphp
@component('mail.layouts.branding')
<x-slot:subcopy>
Payment reference: #{{ $order->id }} | If you did not authorize this payment contact support immediately.
</x-slot:subcopy>

# ✅ Payment Received

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

Thanks,<br>
{{ config('app.name') }} Team
@endcomponent
