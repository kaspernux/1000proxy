<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;


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

        if (auth('customer')->check() && request()->isMethod('post')) {
            $user = auth('customer')->user();
            session()->put('locale', $user->locale ?? config('app.locale'));
            session()->put('theme_mode', $user->theme_mode ?? 'system');
            session()->put('filament.dark_mode', match ($user->theme_mode) {
                'dark' => true,
                'light' => false,
                default => null,
            });
        
            app()->setLocale(session('locale'));
        }
                
    }

}