<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CustomerRegistrationController extends Controller
{
    public function store(Request $request)
    {
        Log::info('Registration (controller) attempt', [
            'email' => $request->input('email'),
            'ip' => $request->ip(),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'min:2'],
            'email' => ['required', 'email', 'max:255', 'unique:customers,email'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'password_confirmation' => ['required'],
            'terms_accepted' => ['accepted'],
        ]);

        // Create the customer (password hashed via model cast)
        $customer = Customer::create([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'password' => $data['password'],
            'is_active' => true,
        ]);

        // Send verification email using built-in method for MustVerifyEmail
        try {
            $customer->sendEmailVerificationNotification();
            Log::info('Verification email dispatched (controller)', ['customer_id' => $customer->id]);
        } catch (\Throwable $e) {
            Log::error('Verification email dispatch failed (controller)', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Auto-login with customer guard
        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('verification.notice')->with('status', 'verification-link-sent');
    }
}
