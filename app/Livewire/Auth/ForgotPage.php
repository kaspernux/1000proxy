<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use App\Models\Customer;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;

#[Title('Forgot Password - 1000 PROXIES')]
class ForgotPage extends Component
{
    use LivewireAlert;

    public $email = '';
    public $is_loading = false;

    // Security features
    public $reset_attempts = 0;
    public $blocked_until = null;

    protected function rules()
    {
        return [
            'email' => 'required|email|max:255',
        ];
    }

    public function mount()
    {
        $this->checkRateLimit();
    }

    private function checkRateLimit()
    {
        $key = 'password_reset.' . request()->ip();
        $this->reset_attempts = RateLimiter::attempts($key);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->blocked_until = RateLimiter::availableAt($key);
        }
    }

    public function save()
    {
        $this->is_loading = true;

        try {
            // Check rate limiting
            $key = 'password_reset.' . request()->ip();

            if (RateLimiter::tooManyAttempts($key, 5)) {
                $seconds = RateLimiter::availableAt($key) - time();
                throw ValidationException::withMessages([
                    'email' => ["Too many password reset attempts. Please try again in {$seconds} seconds."],
                ]);
            }

            $this->validate();

            // Record reset attempt
            RateLimiter::hit($key, 300); // 5 minute window

            // Always use the customers broker
            $status = Password::broker('customers')->sendResetLink([
                'email' => strtolower(trim($this->email))
            ]);

            $this->is_loading = false;

            if ($status === Password::RESET_LINK_SENT) {
                // Clear rate limiting on successful send
                RateLimiter::clear($key);
                
                session()->flash('success', 'Password reset link has been sent to your email address!');
                $this->email = '';
                
                // Log successful password reset request (security)
                \Log::info('Password reset link sent', [
                    'email' => $this->email,
                    'ip' => request()->ip()
                ]);
            } else {
                // Don't reveal if email exists or not for security
                session()->flash('success', 'If this email address exists in our system, you will receive a password reset link.');
                $this->email = '';
                
                // Log failed password reset attempt
                \Log::warning('Password reset attempted for non-existent email', [
                    'email' => $this->email,
                    'ip' => request()->ip()
                ]);
            }

        } catch (ValidationException $e) {
            $this->is_loading = false;
            $this->checkRateLimit();
            throw $e;
        } catch (\Exception $e) {
            $this->is_loading = false;
            \Log::error('Password reset error', [
                'error' => $e->getMessage(),
                'email' => $this->email,
                'ip' => request()->ip()
            ]);
            
            session()->flash('error', 'An error occurred. Please try again later.');
        }
    }

    public function render()
    {
        return view('livewire.auth.forgot-page', [
            'rate_limited' => $this->blocked_until && $this->blocked_until > time(),
            'attempts_remaining' => max(0, 5 - $this->reset_attempts),
        ]);
    }
}