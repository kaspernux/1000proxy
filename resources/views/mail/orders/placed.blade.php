<x-mail::message>
# Order Placed successfully

Thank you for your order. Your order number is: {{ $order->id }}.

Go back to your account dashboard to download the configuration files and connect to the Proxy.


<x-mail::button :url="$url">
Button Text
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
