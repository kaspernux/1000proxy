<x-mail::message>
# ❌ Payment Failed

Hello {{ $user->name }},

We encountered an issue processing your payment for Order #{{ $orderId }}.

## Payment Details

- **Order ID:** #{{ $orderId }}
- **Amount:** ${{ number_format($amount, 2) }}
- **Failure Reason:** {{ $reason }}
- **Attempt Date:** {{ now()->format('F j, Y \a\t g:i A') }}

## What Happened?

Your payment could not be processed due to the following reason:

**{{ $reason }}**

## Next Steps

Don't worry! You can easily retry your payment or try a different payment method:

<x-mail::button :url="$retryPaymentUrl">
Retry Payment
</x-mail::button>

## Common Solutions

Here are some common reasons for payment failures and how to fix them:

### 💳 **Card Issues**
- Insufficient funds
- Expired card
- Incorrect card details
- Card blocked by bank

### 🔒 **Security Checks**
- Bank security verification needed
- International transaction blocked
- Unusual activity detected

### 🌐 **Technical Issues**
- Network connectivity problems
- Payment gateway temporary issues

## Alternative Payment Methods

If your current payment method isn't working, try:
- Different credit/debit card
- PayPal
- Cryptocurrency payments
- Bank transfer

## Need Immediate Help?

Our support team can help resolve payment issues quickly:

<x-mail::button :url="$supportUrl" color="gray">
Contact Support
</x-mail::button>

**Support Hours:** 24/7<br>
**Average Response Time:** Under 30 minutes

## Order Reservation

Don't worry - we've reserved your order for **48 hours**. You have time to resolve the payment issue without losing your service selection.

---

We apologize for any inconvenience. Our team is here to help you complete your purchase successfully.

Best regards,<br>
The 1000 PROXIES Team

---

*Order reservation expires in 48 hours from {{ now()->format('F j, Y \a\t g:i A T') }}*
</x-mail::message>
