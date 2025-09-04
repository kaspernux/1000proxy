<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CustomerRegistrationController extends Controller
{
    public function store(Request $request)
    {
        Log::info('Registration (controller) attempt', [
            'email' => $request->input('email'),
            'ip' => $request->ip(),
        ]);

        // Honeypot: block obvious bots (tests expect a 422 when 'website' is filled)
        if ($request->filled('website')) {
            return response()->json(['message' => 'Spam detected'], 422);
        }

        // Accept either 'terms' (used by tests/legacy forms) or 'terms_accepted' (used by Livewire)
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'min:2'],
            'email' => ['required', 'email', 'max:255', 'unique:customers,email'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'password_confirmation' => ['required'],
        ]);

        // Ensure we report a 'terms' error even when other fields also fail validation.
        $validator->after(function ($v) use ($request) {
            if (! ($request->boolean('terms') || $request->boolean('terms_accepted'))) {
                $v->errors()->add('terms', 'You must accept the Terms of Service to register.');
            }

            // Prevent registration if the email already exists as a staff/web user.
            // This ensures staff accounts cannot be recreated via the public /register endpoint.
            try {
                $email = (string) $request->input('email');
                if (! empty($email)) {
                    $existsInUsers = DB::table('users')->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->exists();
                    if ($existsInUsers) {
                        $v->errors()->add('email', 'The email has already been taken.');
                    }
                }
            } catch (\Throwable $_) {
                // If DB is not available for some reason in the environment, don't block validation here.
            }
        });

        $data = $validator->validate();

        // Normalize so downstream code can rely on a single key name
        if (! array_key_exists('terms_accepted', $data)) {
            $data['terms_accepted'] = $request->boolean('terms') || $request->boolean('terms_accepted');
        }

        // enforce terms acceptance: if neither checkbox was accepted, return validation error
        if (! ($request->boolean('terms') || $request->boolean('terms_accepted') || (! empty($data['terms_accepted'])))) {
            if (method_exists($request, 'hasSession') && $request->hasSession()) {
                return redirect()->back()->withInput($request->except('password'))->withErrors(['terms' => 'You must accept the Terms of Service to register.']);
            }

            return response()->json([
                'success' => false,
                'error' => 'You must accept the Terms of Service to register.'
            ], 422);
        }

        // Create the customer (password hashed via model cast) and guard against unexpected errors.
        try {
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
                Log::warning('Verification email dispatch failed (controller)', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Auto-login with customer guard
            Auth::guard('customer')->login($customer);
            // Only regenerate session if request has one and the application has a session store
            // This prevents "Session store not set on request" errors in CLI/test and other non-session contexts.
            if (method_exists($request, 'hasSession') && $request->hasSession() && app()->has('session.store')) {
                try {
                    $request->session()->regenerate();
                } catch (\Throwable $e) {
                    // Log silently and continue - regeneration is best-effort in edge environments
                    Log::warning('Session regeneration skipped during registration: ' . $e->getMessage());
                }
            }

            // If request has a session (normal web request) and app has a session store, redirect to verification notice with flash.
            if (method_exists($request, 'hasSession') && $request->hasSession() && app()->has('session.store')) {
                return redirect()->route('verification.notice')->with('status', 'verification-link-sent');
            }

            // Fallback for non-session requests (API/CLI simulation) - return JSON success
            return response()->json([
                'success' => true,
                'message' => 'Customer created. Verification email queued.',
                'customer_id' => $customer->id,
            ], 201);
        } catch (\Throwable $e) {
            // Log full exception for diagnosis, but return a friendly response to users.
            Log::error('Customer registration failed', [
                'email' => $request->input('email'),
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            if (method_exists($request, 'hasSession') && $request->hasSession() && app()->has('session.store')) {
                // Redirect back with an error in session so UI shows a controlled message
                return redirect()->back()->withInput($request->except('password'))->withErrors(['registration' => 'Registration failed due to a server error. Please try again later.']);
            }

            // No session available - return JSON error for programmatic callers
            return response()->json([
                'success' => false,
                'error' => 'Registration failed due to a server error. Please try again later.',
            ], 500);
        }
    }
}
