<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class CustomerLoginController extends Controller
{
    public function showLoginForm()
    {
        // Check if already authenticated
        if (Auth::guard('customer')->check()) {
            return redirect('/servers');
        }
        
        return view('auth.customer-login');
    }
    
    public function login(Request $request)
    {
        Log::info(' STANDARD CONTROLLER LOGIN ATTEMPT ', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        // Validate the request
        $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|min:6|max:255',
        ]);
        
        // Rate limiting
        $key = 'login.' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds."
            ])->withInput($request->only('email'));
        }
        
        // Find customer
        $customer = Customer::where('email', $request->email)->first();
        
        if (!$customer || !Hash::check($request->password, $customer->password)) {
            RateLimiter::hit($key, 300);
            
            Log::warning('Standard controller login failed', [
                'email' => $request->email,
                'customer_found' => $customer ? 'yes' : 'no'
            ]);
            
            return back()->withErrors([
                'email' => 'These credentials do not match our records.',
            ])->withInput($request->only('email'));
        }
        
        // Login the customer
        Auth::guard('customer')->login($customer, $request->filled('remember'));
        
        // Clear rate limiter
        RateLimiter::clear($key);
        
        Log::info('✅ STANDARD CONTROLLER LOGIN SUCCESS ✅', [
            'email' => $request->email,
            'customer_id' => $customer->id,
            'session_id' => session()->getId()
        ]);
        
        // Redirect to intended page
        return redirect()->intended('/servers');
    }
}
