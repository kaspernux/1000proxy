<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class CustomerLoginController extends Controller
{
    private function sanitizeRedirectTarget(?string $target): string
    {
        $fallback = '/servers';
        if (!$target) return $fallback;
        $path = $target;
        if (str_starts_with($target, 'http://') || str_starts_with($target, 'https://')) {
            $parsed = parse_url($target);
            $path = $parsed['path'] ?? '/';
        }
        $pathLower = strtolower($path);
        if ($pathLower === '/login' || $pathLower === '/auth/login') return $fallback;
        if ($pathLower === '/admin' || str_starts_with($pathLower, '/admin/')) return $fallback;
        if (!str_starts_with($path, '/')) return $fallback;
        return $path ?: $fallback;
    }

    public function store(Request $request)
    {
        Log::info('Fallback customer login POST received', [
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
            'session_id' => $request->session()->getId(),
        ]);
        $data = $request->validate([
            'email' => ['required','email','max:255'],
            'password' => ['required','min:6','max:255'],
            'remember' => ['nullable']
        ]);

        $remember = (bool) ($data['remember'] ?? false);

        $rateKey = 'login.' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $seconds = RateLimiter::availableIn($rateKey);
            return back()->withErrors(['email' => "Too many login attempts. Try again in {$seconds} seconds."])->withInput(['email' => $data['email']]);
        }

        // Prevent admin accounts from signing in on customer page
        $admin = \App\Models\User::where('email', $data['email'])->where('role', 'admin')->first();
        if ($admin) {
            return back()->withErrors(['email' => 'Please use the admin login page at /admin/login.'])->withInput(['email' => $data['email']]);
        }

        // Attempt customer auth
        $credentials = ['email' => $data['email'], 'password' => $data['password']];
        if (!Auth::guard('customer')->attempt($credentials, $remember)) {
            Log::warning('Fallback login failed', ['email' => $data['email']]);
            RateLimiter::hit($rateKey, 300);
            return back()->withErrors(['email' => 'These credentials do not match our records.'])->withInput(['email' => $data['email']]);
        }

        // Successful login
        Log::info('Fallback login success', [
            'email' => $data['email'],
            'customer_id' => Auth::guard('customer')->id(),
        ]);
        RateLimiter::clear($rateKey);
        $request->session()->regenerate();

        $intended = $request->session()->pull('url.intended');
        $target = $this->sanitizeRedirectTarget($intended);
        Log::info('Redirecting after fallback login', [
            'intended_raw' => $intended,
            'target_sanitized' => $target,
        ]);

        return redirect()->to($target);
    }
}
