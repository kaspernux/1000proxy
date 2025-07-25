<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Services\MobileAppDevelopmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * Mobile Authentication Controller
 *
 * Handles mobile app authentication and registration
 */
class MobileAuthController extends Controller
{
    protected $mobileService;

    public function __construct(MobileAppDevelopmentService $mobileService)
    {
        $this->mobileService = $mobileService;
    }

    /**
     * Mobile user login
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required_without_all:phone,username|email',
            'phone' => 'required_without_all:email,username|string',
            'username' => 'required_without_all:email,phone|string',
            'password' => 'required|string|min:6',
            'device_info' => 'array',
            'device_info.device_id' => 'required|string',
            'device_info.device_name' => 'string',
            'device_info.platform' => 'string',
            'device_info.app_version' => 'string',
            'device_info.push_token' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only(['email', 'phone', 'username', 'password']);
        $deviceInfo = $request->input('device_info', []);

        $result = $this->mobileService->handleMobileAuthentication($credentials, $deviceInfo);

        if ($result['success']) {
            return response()->json($result, 200);
        } else {
            return response()->json($result, 401);
        }
    }

    /**
     * Mobile user registration
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
            'device_info' => 'array',
            'device_info.device_id' => 'required|string',
            'device_info.device_name' => 'string',
            'device_info.platform' => 'string',
            'device_info.app_version' => 'string',
            'device_info.push_token' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userData = $request->only(['name', 'email', 'phone', 'password']);
        $deviceInfo = $request->input('device_info', []);

        $result = $this->mobileService->handleMobileRegistration($userData, $deviceInfo);

        if ($result['success']) {
            return response()->json($result, 201);
        } else {
            return response()->json($result, 400);
        }
    }

    /**
     * Verify mobile account
     */
    public function verify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'verification_token' => 'required|string',
            'verification_code' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Mock verification for now
        return response()->json([
            'success' => true,
            'message' => 'Account verified successfully',
            'verified' => true
        ], 200);
    }

    /**
     * Mobile logout
     */
    public function logout(Request $request): JsonResponse
    {
        $deviceId = $request->input('device_id');

        if ($deviceId) {
            // Invalidate mobile sessions for device
            Log::info('Mobile logout', ['device_id' => $deviceId]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ], 200);
    }

    /**
     * Refresh mobile token
     */
    public function refresh(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
            'device_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Mock token refresh for now
        return response()->json([
            'success' => true,
            'token' => 'new_mock_token_' . time(),
            'expires_at' => now()->addDays(30)->toISOString()
        ], 200);
    }
}
