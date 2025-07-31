<?php

namespace App\Livewire\Auth;

use Filament\Panel;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Rule;
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

    #[Rule('required|email|max:255')]
    public $email = '';

    #[Rule('required|min:6|max:255')]
    public $password = '';

    public $remember = false;
    public $show_password = false;
    public $is_loading = false;
    public $redirect_after_login = '/';

    // Security features
    public $captcha_required = false;
    public $login_attempts = 0;
    public $blocked_until = null;

    protected $listeners = [
        'socialLoginSuccess' => 'handleSocialLogin',
        'captchaVerified' => 'proceedWithLogin'
    ];

    public function mount()
    {
        // Check if user is already authenticated
        if (Auth::guard('web')->check()) {
            return redirect('/admin');
        }

        if (Auth::guard('customer')->check()) {
            return redirect('/customer');
        }

        // Get intended redirect URL
        $this->redirect_after_login = session()->get('url.intended', '/');

        // Check rate limiting
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

            // If the email exists in the staff (User) table, redirect to /admin/login
            if (\App\Models\User::where('email', $this->email)->exists()) {
                $this->alert('info', 'Staff accounts must log in via the admin panel.', [
                    'position' => 'top-end',
                    'timer' => 4000,
                    'toast' => true,
                ]);
                return redirect('/admin/login');
            }

            // Attempt to authenticate as Customer only
            if (Auth::guard('customer')->attempt(
                ['email' => $this->email, 'password' => $this->password],
                $this->remember
            )) {
                // Clear rate limit on successful login
                RateLimiter::clear($key);

                // Regenerate session
                request()->session()->regenerate();

                // Store customer login info
                session()->put('customer_last_login', now());

                $this->alert('success', 'Welcome back! Redirecting to your dashboard...', [
                    'position' => 'top-end',
                    'timer' => 2000,
                    'toast' => true,
                ]);

                // Use Livewire event to trigger JS redirect to Filament customer dashboard
                $this->dispatch('redirectToFilamentCustomerPanel');
                return;
            }

            // If authentication attempt fails
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);

        } catch (ValidationException $e) {
            $this->is_loading = false;

            // Show error alert
            $this->alert('error', 'Login failed. Please check your credentials.', [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
            ]);

            // Update rate limiting status
            $this->checkRateLimit();

            throw $e;
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
            'filament_customer_dashboard_url' => route('filament.customer.pages.dashboard'),
        ]);
    }
}
