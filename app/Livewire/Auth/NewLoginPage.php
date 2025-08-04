<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\LivewireAlert;

#[Title('Sign In - 1000 PROXIES')]
class NewLoginPage extends Component
{
    use LivewireAlert;

    public $email = '';
    public $password = '';
    public $remember = false;
    public $is_loading = false;
    public $processing = false;

    public function mount()
    {
        Log::info('🆕 NEW LOGIN PAGE MOUNTED 🆕');
        
        if (Auth::guard('customer')->check()) {
            Log::info('Customer already authenticated, redirecting');
            return redirect('/servers');
        }
    }

    public function save()
    {
        Log::info('🚀 NEW LOGIN PAGE - SAVE METHOD CALLED 🚀', [
            'email' => $this->email,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
        
        if ($this->processing) {
            Log::info('Login attempt blocked - already processing');
            return;
        }
        
        $this->processing = true;
        $this->is_loading = true;
        
        try {
            // Validate input - same as CustomerLoginController
            $this->validate([
                'email' => 'required|email|max:255',
                'password' => 'required|min:6|max:255',
            ]);
            
            Log::info('✅ Validation passed', ['email' => $this->email]);
            
            // Rate limiting - same as CustomerLoginController
            $key = 'login.' . request()->ip();
            if (RateLimiter::tooManyAttempts($key, 5)) {
                $seconds = RateLimiter::availableIn($key);
                $this->processing = false;
                $this->is_loading = false;
                $this->addError('email', "Too many login attempts. Please try again in {$seconds} seconds.");
                return;
            }
            
            // Find customer - same as CustomerLoginController
            $customer = \App\Models\Customer::where('email', $this->email)->first();
            
            if (!$customer || !Hash::check($this->password, $customer->password)) {
                RateLimiter::hit($key, 300);
                
                Log::warning('❌ New login page - authentication failed', [
                    'email' => $this->email,
                    'customer_found' => $customer ? 'yes' : 'no'
                ]);
                
                $this->processing = false;
                $this->is_loading = false;
                $this->addError('email', 'These credentials do not match our records.');
                return;
            }
            
            // Login the customer - same as CustomerLoginController
            Auth::guard('customer')->login($customer, $this->remember);
            
            // Clear rate limiter - same as CustomerLoginController
            RateLimiter::clear($key);
            
            Log::info('🎉 NEW LOGIN PAGE - LOGIN SUCCESS 🎉', [
                'email' => $this->email,
                'customer_id' => $customer->id,
                'session_id' => session()->getId()
            ]);
            
            // Use Laravel redirect like CustomerLoginController
            return redirect()->intended('/servers');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->processing = false;
            $this->is_loading = false;
            Log::info('Validation failed', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            $this->processing = false;
            $this->is_loading = false;
            Log::error('New login page error', [
                'error' => $e->getMessage(), 
                'email' => $this->email
            ]);
            
            $this->addError('email', 'An error occurred during login. Please try again.');
        }
        
        $this->processing = false;
        Log::info('=== NEW LOGIN PAGE ATTEMPT END ===');
    }

    public function render()
    {
        return view('livewire.auth.login-page');
    }
}
