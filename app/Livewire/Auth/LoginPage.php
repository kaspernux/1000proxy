<?php

namespace App\Livewire\Auth;

use Filament\Panel;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
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
        // Only check customer guard
        if (Auth::guard('customer')->check()) {
            return redirect()->route('filament.customer.pages.dashboard');
        }
        $this->redirect_after_login = session()->get('url.intended', '/servers');
        $this->checkRateLimit();
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
        $this->is_loading = true;

        try {
            // Check rate limiting
            $key = 'login.' . request()->ip();

            if (RateLimiter::tooManyAttempts($key, 5)) {
                $seconds = RateLimiter::availableAt($key) - time();
                throw ValidationException::withMessages([
                    'email' => ["Too many login attempts. Please try again in {$seconds} seconds."],
                ]);
            }

            $this->validate();

            // Record login attempt
            RateLimiter::hit($key, 300); // 5 minute window

            // Only allow customer login
            if (Auth::guard('customer')->attempt(
                ['email' => $this->email, 'password' => $this->password],
                $this->remember
            )) {
                RateLimiter::clear($key);
                request()->session()->regenerate();
                session()->put('customer_last_login', now());
                
                $this->is_loading = false;
                
                // Direct redirect without alerts
                return redirect('/servers');
            }

            // If authentication attempt fails
            \Log::warning('Login failed - invalid credentials', ['email' => $this->email]);
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);

        } catch (ValidationException $e) {
            $this->is_loading = false;
            
            // Update rate limiting status
            $this->checkRateLimit();

            throw $e;
        } catch (\Exception $e) {
            $this->is_loading = false;
            \Log::error('Login error', ['error' => $e->getMessage(), 'email' => $this->email]);
            
            $this->addError('email', 'An error occurred during login. Please try again.');
        }
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
