@component('mail.layouts.branding')
# 🚀 Your Service is Now Active!

Hello {{ $order->user->name }},

Excellent news! Your **1000 PROXIES** service is now **fully activated** and ready to use.

## Service Details

- **Order ID:** #{{ $order->id }}
- **Service Type:** {{ $order->orderItems->first()->serverPlan->name ?? 'Premium Proxy Service' }}
- **Activation Date:** {{ now()->format('F j, Y \a\t g:i A') }}
- **Status:** ✅ **ACTIVE**

@if(!empty($serverDetails))
## Server Information

@foreach($serverDetails as $server)
- **Server:** {{ $server['name'] ?? 'Server' }}
- **Location:** {{ $server['location'] ?? 'Global' }}
- **Protocol:** {{ $server['protocol'] ?? 'VLESS/VMess' }}
@endforeach
@endif

## Get Connected Now

Your proxy configurations are ready for download:

<x-mail::button :url="$downloadConfigUrl">
Download Configurations
</x-mail::button>

## Connection Methods

Your service supports multiple connection methods:

### 📱 **Mobile Apps**
- Scan QR codes for instant setup
- Compatible with V2Box, V2RayNG, Shadowrocket, and more

### 💻 **Desktop Apps**
- Import configuration files
- Works with V2Ray, Clash, and other clients

### 🔧 **Manual Setup**
- Full configuration details included
- Step-by-step setup guides available

## Getting Started

1. **Download** your configuration files
2. **Import** them into your preferred app
3. **Connect** and start browsing securely
4. **Enjoy** high-speed, reliable proxy service

## Need Help Getting Connected?

Our comprehensive documentation and support team are here to help:

<x-mail::button :url="$docsUrl" color="success">
Setup Guides
</x-mail::button>

<x-mail::button :url="$supportUrl" color="gray">
Contact Support
</x-mail::button>

## Service Features

✅ **High-Speed Connections** - Optimized for performance<br>
✅ **Global Servers** - Multiple locations available<br>
✅ **24/7 Support** - We're here when you need us<br>
✅ **Multiple Protocols** - VLESS, VMess, Trojan support<br>
✅ **Easy Setup** - QR codes and config files included

---

Welcome to the 1000 PROXIES family! We're excited to provide you with premium proxy services.

Best regards,<br>
The 1000 PROXIES Team

---

*Service activated on {{ now()->format('F j, Y \a\t g:i A T') }}*
@endcomponent
