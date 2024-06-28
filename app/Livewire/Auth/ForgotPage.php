<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use App\Models\Customer;
use App\Models\User;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

#[Title('Forgot Password - 1000 PROXIES')]
class ForgotPage extends Component
{
    public $email;

    public function save()
    {
        $this->validate([
            'email' => 'required|email|max:255'
        ]);

        // Determine which broker to use based on the email
        $broker = $this->getPasswordBroker();

        $status = Password::broker($broker)->sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            session()->flash('success', 'Password reset link has been sent to your email address!');
            $this->email = '';
        } else {
            session()->flash('error', 'Email could not be sent to this email address.');
        }
    }

    private function getPasswordBroker()
    {
        // Check if the email belongs to a User
        if (User::where('email', $this->email)->exists()) {
            return 'customers';
        }

        // Otherwise, assume it belongs to a Customer
        return 'users';
    }

    public function render()
    {
        return view('livewire.auth.forgot-page');
    }
}
