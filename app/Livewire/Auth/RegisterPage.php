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
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;

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
        // Capture referral code from query if present
        $ref = request()->query('ref');
        if (is_string($ref) && strlen($ref) >= 4) {
            session()->put('referral_code_used', strtoupper(trim($ref)));
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

    // Log the incoming terms_accepted value for debugging
    \Log::debug('Livewire registration save called', ['email' => $this->email, 'terms_accepted' => $this->terms_accepted]);

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

            // Create customer (password will be hashed via model cast)
            $customer = Customer::create([
                'name' => trim($this->name),
                'email' => strtolower(trim($this->email)),
                'password' => $this->password,
                'is_active' => true,
            ]);

            // Send email verification via built-in MustVerifyEmail method (guard-safe)
            try {
                $customer->sendEmailVerificationNotification();
                \Log::info('Verification email dispatched (livewire)', ['customer_id' => $customer->id, 'email' => $customer->email]);
            } catch (\Throwable $e) {
                \Log::error('Verification email dispatch failed (livewire)', ['email' => $this->email, 'error' => $e->getMessage()]);
            }

            // Attach referrer if a valid referral code was used
            try {
                $code = session()->pull('referral_code_used');
                if ($code) {
                    $referrer = Customer::where('referral_code', $code)->first();
                    if ($referrer && $referrer->id !== $customer->id) {
                        $customer->update(['refered_by' => $referrer->id]);
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning('Failed attaching referrer on registration', ['email' => $this->email, 'error' => $e->getMessage()]);
            }

            // Clear rate limiting on success
            RateLimiter::clear($key);

            // Login customer using customer guard
            Auth::guard('customer')->login($customer);
            request()->session()->regenerate();
            session()->put('customer_last_login', now());

            \Log::info('Registration successful (email verification pending)', ['email' => $this->email, 'customer_id' => $customer->id]);

            $this->is_loading = false;

            // Notify and redirect to verification notice
            session()->flash('success', 'Account created! Please verify your email address.');

            $this->redirect(route('verification.notice'), navigate: true);
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