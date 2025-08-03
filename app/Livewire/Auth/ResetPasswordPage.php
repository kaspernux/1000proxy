<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use App\Models\Customer;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;

#[Title('Reset Password - 1000 PROXIES')]
class ResetPasswordPage extends Component
{
    use LivewireAlert;

    #[Url]
    public $token;

    #[Url]
    public $email;

    public $password = '';
    public $password_confirmation = '';
    public $is_loading = false;

    // Security features
    public $reset_attempts = 0;
    public $blocked_until = null;

    protected function rules()
    {
        return [
            'token' => 'required|string',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|max:255|confirmed',
            'password_confirmation' => 'required',
        ];
    }

    public function mount()
    {
        // Validate token and email are present
        if (!$this->token || !$this->email) {
            session()->flash('error', 'Invalid password reset link.');
            return redirect('/forgot');
        }
        
        $this->checkRateLimit();
    }

    private function checkRateLimit()
    {
        $key = 'password_reset_submit.' . request()->ip();
        $this->reset_attempts = RateLimiter::attempts($key);

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->blocked_until = RateLimiter::availableAt($key);
        }
    }

    public function save()
    {
        $this->is_loading = true;

        try {
            // Check rate limiting
            $key = 'password_reset_submit.' . request()->ip();

            if (RateLimiter::tooManyAttempts($key, 3)) {
                $seconds = RateLimiter::availableAt($key) - time();
                throw ValidationException::withMessages([
                    'password' => ["Too many password reset attempts. Please try again in {$seconds} seconds."],
                ]);
            }

            $this->validate();

            // Record reset attempt
            RateLimiter::hit($key, 300); // 5 minute window

            // Always use the customers broker
            $status = Password::broker('customers')->reset([
                'email' => strtolower(trim($this->email)),
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token
            ], function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            });

            $this->is_loading = false;

            if ($status === Password::PASSWORD_RESET) {
                // Clear rate limiting on success
                RateLimiter::clear($key);
                
                // Log successful password reset
                \Log::info('Password reset successful', [
                    'email' => $this->email,
                    'ip' => request()->ip()
                ]);

                session()->flash('success', 'Password has been reset successfully! Please log in with your new password.');
                return redirect('/login');
            } else {
                // Log failed password reset
                \Log::warning('Password reset failed', [
                    'email' => $this->email,
                    'status' => $status,
                    'ip' => request()->ip()
                ]);

                session()->flash('error', 'This password reset link is invalid or has expired. Please request a new one.');
                return redirect('/forgot');
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
            
            session()->flash('error', 'An error occurred while resetting your password. Please try again.');
        }
    }

    public function render()
    {
        return view('livewire.auth.reset-password-page', [
            'rate_limited' => $this->blocked_until && $this->blocked_until > time(),
            'attempts_remaining' => max(0, 3 - $this->reset_attempts),
        ]);
    }
}
