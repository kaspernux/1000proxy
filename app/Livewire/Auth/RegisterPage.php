<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use App\Livewire\Traits\LivewireAlertV4;

#[Title('Register - 1000 PROXIES')]
class RegisterPage extends Component
{
    use LivewireAlertV4;

    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $terms_accepted = false;
    public $is_loading = false;

    // Security features
    public $captcha_required = false;
    public $registration_attempts = 0;
    public $blocked_until = null;

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255|min:2',
            'email' => 'required|email|unique:customers,email|max:255',
            'password' => 'required|string|min:8|max:255|confirmed',
            'password_confirmation' => 'required',
            'terms_accepted' => 'accepted',
        ];
    }

    public function mount()
    {
        // Check if already authenticated with customer guard
        if (Auth::guard('customer')->check()) {
            $this->redirect('/servers', navigate: true);
            return;
        }
        $this->checkRateLimit();
    }

    private function checkRateLimit()
    {
        $key = 'register.' . request()->ip();
        $this->registration_attempts = RateLimiter::attempts($key);

        if (RateLimiter::tooManyAttempts($key, 3)) { // More restrictive for registration
            $this->blocked_until = RateLimiter::availableAt($key);
            $this->captcha_required = true;
        }
    }

    public function save()
    {
        $this->is_loading = true;

        try {
            // Check rate limiting
            $key = 'register.' . request()->ip();

            if (RateLimiter::tooManyAttempts($key, 3)) {
                $seconds = RateLimiter::availableAt($key) - time();
                throw ValidationException::withMessages([
                    'email' => ["Too many registration attempts. Please try again in {$seconds} seconds."],
                ]);
            }

            $this->validate();

            \Log::info('Registration attempt', ['email' => $this->email]);

            // Record registration attempt
            RateLimiter::hit($key, 300); // 5 minute window

            // Create customer
            $customer = Customer::create([
                'name' => trim($this->name),
                'email' => strtolower(trim($this->email)),
                'password' => Hash::make($this->password),
                'email_verified_at' => now(), // Auto-verify for now
                'is_active' => true,
            ]);

            // Clear rate limiting on success
            RateLimiter::clear($key);

            // Login customer using customer guard
            Auth::guard('customer')->login($customer);
            request()->session()->regenerate();
            session()->put('customer_last_login', now());

            \Log::info('Registration successful', ['email' => $this->email, 'customer_id' => $customer->id]);

            $this->is_loading = false;

            // Success notification
            session()->flash('success', 'Account created successfully! Welcome to 1000 PROXIES.');
            
            $this->redirect('/servers', navigate: true);
            return;

        } catch (ValidationException $e) {
            $this->is_loading = false;
            $this->checkRateLimit();
            throw $e;
        } catch (\Exception $e) {
            $this->is_loading = false;
            \Log::error('Registration error', [
                'error' => $e->getMessage(),
                'email' => $this->email,
                'ip' => request()->ip(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->addError('email', 'An error occurred during registration. Please try again.');
        }
    }

    public function render()
    {
        return view('livewire.auth.register-page', [
            'rate_limited' => $this->blocked_until && $this->blocked_until > time(),
            'attempts_remaining' => max(0, 3 - $this->registration_attempts),
        ]);
    }
}