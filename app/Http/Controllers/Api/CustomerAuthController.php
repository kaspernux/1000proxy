<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomerAuthController extends Controller
{
    /**
     * Register a new customer and issue an API token.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers',
            'password' => 'required|string|min:8|confirmed',
            'locale' => 'sometimes|string|max:10',
            'timezone' => 'sometimes|string|max:64',
        ]);

        $customer = Customer::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => true,
            'locale' => $validated['locale'] ?? config('app.locale'),
            'timezone' => $validated['timezone'] ?? null,
            'email_notifications' => true,
        ]);

        $token = $customer->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Customer registered successfully',
            'data' => [
                'customer' => $customer->only(['id','name','email','locale','timezone']),
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * Customer login and token issuance.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $customer = Customer::where('email', $request->email)->first();

        if (!$customer || !Hash::check($request->password, $customer->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$customer->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is inactive',
            ], 403);
        }

        $customer->update(['last_login_at' => now()]);
        $token = $customer->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'customer' => $customer->only(['id','name','email','locale','timezone','last_login_at']),
                'token' => $token,
            ],
        ]);
    }

    /**
     * Revoke current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Refresh token for the authenticated customer.
     */
    public function refresh(Request $request): JsonResponse
    {
        $customer = $request->user();
        $request->user()->currentAccessToken()?->delete();
        $token = $customer->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => ['token' => $token],
        ]);
    }

    /**
     * Get current authenticated customer profile and wallet summary.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        $wallet = $customer->wallet;

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => $customer->only(['id','name','email','locale','timezone','last_login_at']),
                'wallet' => $wallet ? [
                    'id' => $wallet->id,
                    'balance' => $wallet->balance,
                    'currency' => 'USD',
                ] : null,
                'telegram_linked' => $customer->hasTelegramLinked(),
            ],
        ]);
    }
}
