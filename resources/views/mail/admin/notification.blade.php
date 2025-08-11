@component('mail.layouts.branding')
@if($type === 'success')
# ✅ {{ $subject }}
@elseif($type === 'warning')
# ⚠️ {{ $subject }}
@elseif($type === 'error')
# ❌ {{ $subject }}
@else
# 📢 {{ $subject }}
@endif

Hello {{ $user->name }},

{!! nl2br(e($messageContent)) !!}

## Account Information

- **Name:** {{ $user->name }}
- **Email:** {{ $user->email }}
- **Account ID:** #{{ $user->id }}
- **Member Since:** {{ $user->created_at->format('F j, Y') }}

## Quick Actions

<x-mail::button :url="$dashboardUrl">
Go to Dashboard
</x-mail::button>

@if($type === 'error' || $type === 'warning')
## Need Help?

If you need assistance with this notification:

<x-mail::button :url="$supportUrl" color="gray">
Contact Support
</x-mail::button>
@endif

---

@if($type === 'success')
Thank you for being a valued 1000 PROXIES customer!
@elseif($type === 'warning')
Please take action to ensure continued service.
@elseif($type === 'error')
We apologize for any inconvenience and are here to help.
@else
This notification was sent to keep you informed about your account.
@endif

Best regards,<br>
The 1000 PROXIES Team

---

*Notification sent on {{ now()->format('F j, Y \a\t g:i A T') }}*
@endcomponent
