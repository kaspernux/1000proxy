<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();
        // share wallet balance with all views
        View::composer('*', function ($view) {
            if ($customer = Auth::guard('customer')->user()) {
                // getWallet() will create one if missing
                $balance = $customer->getWallet()->balance;
                $view->with('walletBalance', number_format($balance, 2));
            }
        });
    }

}