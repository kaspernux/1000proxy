<x-mail::message>
# ⚠️ Service Expiring Soon

Hello {{ $order->user->name }},

Your **1000 PROXIES** service is set to expire in **{{ $daysUntilExpiry }} day{{ $daysUntilExpiry > 1 ? 's' : '' }}**.

## Order Details

- **Order ID:** #{{ $order->id }}
- **Service:** {{ $order->orderItems->first()->serverPlan->name ?? 'Proxy Service' }}
- **Expiry Date:** {{ $order->expires_at ? $order->expires_at->format('F j, Y \a\t g:i A') : 'N/A' }}
- **Total Amount:** ${{ number_format($order->grand_amount ?? 0, 2) }}

## Don't Let Your Service Expire!

To ensure uninterrupted access to your proxy service, renew now:

<x-mail::button :url="$renewUrl">
Renew Service
</x-mail::button>

## Why Renew?

✅ **Uninterrupted Service** - Keep your connections active<br>
✅ **Same Configuration** - No need to reconfigure your applications<br>
✅ **Priority Support** - Continue receiving premium support<br>
✅ **Best Pricing** - Renewal rates are often better than new subscriptions

## Need Help?

If you have any questions about renewal or need assistance:

<x-mail::button :url="$viewOrderUrl" color="gray">
View Order Details
</x-mail::button>

---

**Important:** If your service expires, you'll lose access to your proxy connections immediately. Renew now to avoid any service interruption.

Thanks,<br>
The 1000 PROXIES Team

---

*Order expires: {{ $order->expires_at ? $order->expires_at->format('F j, Y \a\t g:i A T') : 'N/A' }}*
</x-mail::message>
