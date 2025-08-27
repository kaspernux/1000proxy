<?php

return [
    // Daily cap for "system" emails (verification, password reset, critical alerts).
    // Default: 50 (1/3 of Mailtrap's 150/day free tier). Override via env.
    'daily_limit' => (int) env('SYSTEM_MAIL_DAILY_QUOTA', 50),

    // Cache key prefix; counter will be per-day.
    'cache_key' => env('SYSTEM_MAIL_QUOTA_CACHE_KEY', 'system_mail_quota'),

    // Treat these notification classes as "system" and subject to quota.
    'system_notifications' => [
        Illuminate\Auth\Notifications\VerifyEmail::class,
        Illuminate\Auth\Notifications\ResetPassword::class,
        // Add your app-specific critical notifications here
        // App\Notifications\CriticalAlert::class,
    ],
];
