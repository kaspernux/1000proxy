<x-mail::message>
# ✅ Payment Received Successfully!

Hello {{ $order->user->name }},

Great news! We've successfully received your payment for Order #{{ $order->id }}.

## Payment Details

- **Order ID:** #{{ $order->id }}
- **Amount:** ${{ number_format($order->grand_total ?? 0, 2) }}
- **Payment Method:** {{ ucfirst($paymentMethod) }}
@if($transactionId)
- **Transaction ID:** {{ $transactionId }}
@endif
- **Payment Date:** {{ now()->format('F j, Y \a\t g:i A') }}

## What's Next?

Your service is now being activated. Here's what happens next:

1. **Service Activation** - Your proxy service is being set up (usually takes 5-10 minutes)
2. **Configuration Ready** - You'll receive another email once your configurations are ready
3. **Download & Connect** - Use your configurations to connect to your new proxy service

## Quick Actions

<x-mail::button :url="$viewOrderUrl">
View Order Details
</x-mail::button>

<x-mail::button :url="$downloadConfigUrl" color="success">
Download Configurations
</x-mail::button>

## Service Information

Your new proxy service includes:
- **High-speed connections** with 99.9% uptime
- **Multiple protocols** (VLESS, VMess, Trojan)
- **24/7 support** whenever you need help
- **Easy configuration** with QR codes and config files

## Need Help?

Our support team is here to help you get connected:
- 📧 Email support available 24/7
- 📱 Live chat on our website
- 📚 Comprehensive setup guides

---

Thank you for choosing 1000 PROXIES! Your service will be ready shortly.

Best regards,<br>
The 1000 PROXIES Team

---

*Transaction processed on {{ now()->format('F j, Y \a\t g:i A T') }}*
</x-mail::message>
