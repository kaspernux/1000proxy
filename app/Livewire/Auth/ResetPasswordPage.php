<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Jantinnerezo\LivewireAlert\LivewireAlert;


#[Title('Reset Password - 1000 PROXIES')]
class ResetPasswordPage extends Component
{
    use LivewireAlert;

    #[Url]
    public $token;

    #[Url]
    public $email;

    public $password;
    public $password_confirmation;

    public function save()
    {
        $this->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|max:255|confirmed',
        ]);

        // Determine which broker to use based on the email
        $broker = $this->getPasswordBroker();

        $status = Password::broker($broker)->reset([
            'email' => $this->email,
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

        if ($status === Password::PASSWORD_RESET) {
            $this->alert('success', 'Password has been reset successfully!', [
                'position' => 'bottom-end',
                'timer' => 2000,
                'toast' => true,
                'timerProgressBar' => true,
            ]);

            // Pause for a moment to display the message before redirecting
            return redirect('/login');
        } else {
            $this->alert('error', 'Failed to reset password. Please try again.', [
                'position' => 'bottom-end',
                'timer' => 2000,
                'toast' => true,
                'timerProgressBar' => true,
            ]);
        }
    }

    private function getPasswordBroker()
    {
        // Check if the email belongs to a User
        if (User::where('email', $this->email)->exists()) {
            return 'users';
        }

        // Otherwise, assume it belongs to a Customer
        return 'customers';
    }

    public function render()
    {
        return view('livewire.auth.reset-password-page');
    }
}
