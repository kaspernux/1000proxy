@component('mail::message')
# Health Alert

A health alert has been triggered in your 1000proxy application.

@isset($alertMessage)
**Message:** {{ is_string($alertMessage) ? $alertMessage : '' }}
@endisset

@isset($details)
**Details:**
{{ is_string($details) ? $details : '' }}
@endisset

Thanks,<br>
{{ config('app.name') }}
@endcomponent
