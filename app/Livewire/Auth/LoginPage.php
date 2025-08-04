<?php

namespace App\Livewire\Auth;

use Filament\Panel;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;

    #[Title('Sign In - 1000 PROXIES')]
class LoginPage extends Component
{
    use LivewireAlert;

    public $email = '';
    public $password = '';

    public $remember = false;
    public $show_password = false;
    public $is_loading = false;
    public $redirect_after_login = '/servers';
    public $processing = false; // Add processing flag

    // Security features
    public $captcha_required = false;
    public $login_attempts = 0;
    public $blocked_until = null;

    protected function rules()
    {
        return [
            'email' => 'required|email|max:255',
            'password' => 'required|min:6|max:255',
        ];
    }    protected $listeners = [
        'socialLoginSuccess' => 'handleSocialLogin',
        'captchaVerified' => 'proceedWithLogin'
    ];

    public function mount()
    {
        Log::info('LoginPage mount started', [
            'customer_guard_check' => Auth::guard('customer')->check(),
            'session_id' => session()->getId()
        ]);
        
        // Only check customer guard
        if (Auth::guard('customer')->check()) {
            Log::info('Customer already authenticated, redirecting', [
                'customer_id' => Auth::guard('customer')->id()
            ]);
            $this->redirect('/servers', navigate: true);
            return;
        }
        
        $this->redirect_after_login = session()->get('url.intended', '/servers');
        $this->checkRateLimit();
        
        Log::info('Login page mounted successfully', [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'redirect_after_login' => $this->redirect_after_login
        ]);
    }

    private function checkRateLimit()
    {
        $key = 'login.' . request()->ip();
        $this->login_attempts = RateLimiter::attempts($key);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->blocked_until = RateLimiter::availableAt($key);
            $this->captcha_required = true;
        }
    }

    public function togglePasswordVisibility()
    {
        $this->show_password = !$this->show_password;
    }

    public function save()
    {
        \Log::emergency('🚨 LIVEWIRE SAVE METHOD CALLED 🚨');
        
        Log::info('🔥 LIVEWIRE LOGIN ATTEMPT (UPDATED LOGIC) 🔥', [
            'email' => $this->email,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
        
        // Prevent double submission
        if ($this->processing) {
            Log::info('Login attempt blocked - already processing', [
                'email' => $this->email,
                'session_id' => session()->getId()
            ]);
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
            
            Log::info('Validation passed', ['email' => $this->email]);
            
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
            
            if (!$customer || !\Hash::check($this->password, $customer->password)) {
                RateLimiter::hit($key, 300);
                
                Log::warning('Livewire login failed', [
                    'email' => $this->email,
                    'customer_found' => $customer ? 'yes' : 'no'
                ]);
                
                $this->processing = false;
                $this->is_loading = false;
                $this->dispatch('login-error', ['reason' => 'invalid_credentials']);
                $this->addError('email', 'These credentials do not match our records.');
                return;
            }
            
            // Login the customer - same as CustomerLoginController
            Auth::guard('customer')->login($customer, $this->remember);
            
            // Clear rate limiter - same as CustomerLoginController
            RateLimiter::clear($key);
            
            Log::info('✅ LIVEWIRE LOGIN SUCCESS (UPDATED LOGIC) ✅', [
                'email' => $this->email,
                'customer_id' => $customer->id,
                'session_id' => session()->getId()
            ]);
            
            // Clear login attempts
            session()->forget('login_attempts');
            
            // Dispatch success event
            $this->dispatch('login-success', ['customer_id' => $customer->id]);
            
            // Use Laravel redirect like CustomerLoginController
            return redirect()->intended('/servers');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->processing = false;
            $this->is_loading = false;
            $this->dispatch('login-error', ['reason' => 'validation_failed']);
            Log::info('Validation failed', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            $this->processing = false;
            $this->is_loading = false;
            Log::error('Livewire login error', [
                'error' => $e->getMessage(), 
                'email' => $this->email,
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->dispatch('login-error', ['reason' => 'system_error', 'message' => $e->getMessage()]);
            $this->addError('email', 'An error occurred during login. Please try again.');
        }
        
        $this->processing = false;
        Log::info('=== LIVEWIRE LOGIN ATTEMPT END ===');
    }

    public function loginWithGoogle()
    {
        // Implement Google OAuth login
        $this->dispatch('initGoogleLogin');
    }

    public function loginWithGithub()
    {
        // Implement GitHub OAuth login
        return redirect()->route('auth.github.redirect');
    }

    #[On('socialLoginSuccess')]
    public function handleSocialLogin($provider, $userData)
    {
        // Handle successful social login
        $this->alert('success', "Successfully logged in with {$provider}!", [
            'position' => 'top-end',
            'timer' => 2000,
            'toast' => true,
        ]);

        return redirect()->intended('/customer');
    }

    public function requestPasswordReset()
    {
        if (empty($this->email)) {
            $this->alert('warning', 'Please enter your email address first', [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
            ]);
            return;
        }

        return redirect()->route('auth.forgot', ['email' => $this->email]);
    }

    public function redirectToRegister()
    {
        return redirect()->route('auth.register');
    }

    public function render()
    {
        return view('livewire.auth.login-page', [
            'rate_limited' => $this->blocked_until && $this->blocked_until > time(),
            'attempts_remaining' => max(0, 5 - $this->login_attempts),
        ]);
    }
}
