<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Title;


#[Title('Register - 1000 PROXIES')]
class RegisterPage extends Component
{
    public $name;
    public $email;
    public $password;

    // Register Customer
    public function save()
    {
        $this->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:customers|max:255',
            'password' => 'required|min:6|max:255',
        ]);

        // Save to database
        $customer = Customer::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        // Login Customer
        auth()->login($customer);

        // Redirect to Home page
        return redirect()->intended();
    }

    public function render()
    {
        return view('livewire.auth.register-page');
    }
}