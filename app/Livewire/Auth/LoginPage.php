<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Livewire\Attributes\Title;
use Illuminate\Validation\ValidationException;

#[Title('Login - 1000 PROXIES')]
class LoginPage extends Component
{
    public $email;
    public $password;

    public function save()
    {
        try {
            $this->validate([
                'email' => 'required|email|exists:customers,email|max:255',
                'password' => 'required|min:6|max:255',
            ]);

            if (!auth()->attempt(['email' => $this->email, 'password' => $this->password])) {
                session()->flash('error', 'Invalid credentials');
                return;
            }

            return redirect()->intended();
        } catch (ValidationException $e) {
            session()->flash('error', 'Invalid credentials');
            $this->resetValidation(); // Clear the validation errors
        }
    }

    public function render()
    {
        return view('livewire.auth.login-page');
    }
}