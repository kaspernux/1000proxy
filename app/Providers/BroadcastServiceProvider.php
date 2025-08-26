<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Broadcast::routes(['middleware' => ['web']]);

        Broadcast::resolveAuthenticatedUserUsing(function ($request) {
            return Auth::user() ?: Auth::guard('customer')->user();
        });

        require base_path('routes/channels.php');
    }
}
