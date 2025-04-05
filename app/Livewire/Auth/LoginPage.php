<?php

namespace App\Livewire\Auth;

use Filament\Panel;
use Livewire\Component;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
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
                'email' => 'required|email|max:255',
                'password' => 'required|min:6|max:255',
            ]);

            // Attempt to authenticate as a User (Admin)
            if (Auth::guard('web')->attempt(['email' => $this->email, 'password' => $this->password])) {
                if (Auth::user()->canAccessPanel(new Panel())) {
                    return redirect()->intended('/admin'); // Redirect to admin dashboard
                } else {
                    // Handle user without access to the dashboard
                    Auth::logout(); // Logout user
                    throw ValidationException::withMessages([
                        'email' => ['You do not have access to the dashboard.'],
                    ]);
                }
            }

            // Attempt to authenticate as a Customer
            if (Auth::guard('customer')->attempt(['email' => $this->email, 'password' => $this->password])) {
                return redirect()->intended('/'); // Redirect to customer dashboard
            }

            // If neither authentication attempt succeeds, show error
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
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