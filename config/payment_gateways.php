<?php

return [
    // Map of gateway key => service class. Only add keys you want exposed.
    'gateways' => [
        'stripe' => App\Services\PaymentGateways\StripePaymentService::class,
        'paypal' => App\Services\PaymentGateways\PayPalPaymentService::class,
        'mir' => App\Services\PaymentGateways\MirPaymentService::class,
        'nowpayments' => App\Services\PaymentGateways\NowPaymentsService::class,
        // Newly added optional gateways
        'paystack' => App\Services\PaymentGateways\PaystackPaymentService::class,
        'flutterwave' => App\Services\PaymentGateways\FlutterwavePaymentService::class,
        'razorpay' => App\Services\PaymentGateways\RazorpayPaymentService::class,
        'coinbase' => App\Services\PaymentGateways\CoinbaseCommercePaymentService::class,
    ],
];
