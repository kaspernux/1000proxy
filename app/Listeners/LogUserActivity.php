<?php
namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Request;

class LogUserActivity
{
    public function handle($event)
    {
        $user = $event->user ?? null;
        if (! $user) return;
        $ip = Request::ip();
        if ($event instanceof Login) {
            $user->logActivity('login', 'User logged in', $ip);
        } elseif ($event instanceof Logout) {
            $user->logActivity('logout', 'User logged out', $ip);
        } elseif ($event instanceof PasswordReset) {
            $user->logActivity('password_reset', 'Password was reset', $ip);
        } elseif ($event instanceof Registered) {
            $user->logActivity('registered', 'User registered', $ip);
        }
    }
}
