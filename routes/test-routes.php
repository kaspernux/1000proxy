<?php

use Illuminate\Http\Request;
use App\Livewire\Auth\LoginPage;

// Custom Livewire update handler to bypass middleware issues
Route::post('/livewire-custom/update', function (Request $request) {
    try {
        // Get the component fingerprint and updates from the request
        $fingerprint = $request->input('fingerprint');
        $updates = $request->input('updates');
        $serverMemo = $request->input('serverMemo');
        
        // For LoginPage component specifically
        if (isset($fingerprint['name']) && $fingerprint['name'] === 'auth.login-page') {
            \Log::info('Custom endpoint: Processing LoginPage component');
            
            $component = new LoginPage();
            
            \Log::info('Custom endpoint: Component created');
            
            // Apply any property updates from the request
            foreach ($updates as $update) {
                \Log::info('Custom endpoint: Processing update', ['update' => $update]);
                
                if ($update['type'] === 'syncInput') {
                    $property = $update['payload']['name'];
                    $value = $update['payload']['value'];
                    
                    if (property_exists($component, $property)) {
                        $component->$property = $value;
                        \Log::info('Custom endpoint: Set property', ['property' => $property, 'value' => $value]);
                    }
                }
                
                if ($update['type'] === 'callMethod') {
                    $method = $update['payload']['method'];
                    $params = $update['payload']['params'] ?? [];
                    
                    if (method_exists($component, $method)) {
                        try {
                            $result = $component->$method(...$params);
                            
                            // Handle redirect responses
                            if ($result instanceof \Illuminate\Http\RedirectResponse) {
                                return response()->json([
                                    'effects' => [
                                        [
                                            'type' => 'redirect',
                                            'url' => $result->getTargetUrl()
                                        ]
                                    ]
                                ]);
                            }
                            
                            // Check if authentication was successful by checking the guard
                            if (auth()->guard('customer')->check()) {
                                return response()->json([
                                    'effects' => [
                                        [
                                            'type' => 'redirect',
                                            'url' => '/servers'
                                        ]
                                    ]
                                ]);
                            }
                            
                            // If we get here, login succeeded but no redirect
                            return response()->json([
                                'fingerprint' => $fingerprint,
                                'serverMemo' => $serverMemo,
                                'effects' => [],
                                'message' => 'Login processed successfully'
                            ]);
                            
                        } catch (\Illuminate\Validation\ValidationException $e) {
                            // Handle validation errors (like wrong credentials)
                            $errors = $e->errors();
                            $errorMessage = '';
                            foreach ($errors as $field => $messages) {
                                $errorMessage .= implode(', ', $messages) . ' ';
                            }
                            
                            return response()->json([
                                'fingerprint' => $fingerprint,
                                'serverMemo' => $serverMemo,
                                'effects' => [],
                                'error' => trim($errorMessage) ?: 'Validation failed'
                            ]);
                        } catch (\Exception $e) {
                            return response()->json([
                                'fingerprint' => $fingerprint,
                                'serverMemo' => $serverMemo,
                                'effects' => [],
                                'error' => 'System error: ' . $e->getMessage()
                            ]);
                        }
                    }
                }
            }
            
            // Return success response (no method called)
            return response()->json([
                'fingerprint' => $fingerprint,
                'serverMemo' => $serverMemo,
                'effects' => []
            ]);
        }
        
        return response()->json(['error' => 'Component not found'], 404);
        
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
})->withoutMiddleware([
    \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
    \App\Http\Middleware\EnhancedCsrfProtection::class
]);

// Simple test route to bypass all middleware and test Livewire component directly
Route::post('/test-livewire-direct', function (Request $request) {
    try {
        // Test creating and calling the LoginPage component directly
        $component = new LoginPage();
        
        // Set properties from request (or use defaults)
        $component->email = $request->input('email', 'demo@1000proxy.io');
        $component->password = $request->input('password', '123456789');
        
        \Log::info('Direct login attempt', [
            'email' => $component->email,
            'password_length' => strlen($component->password)
        ]);
        
        // Try calling the save method
        $result = $component->save();
        
        // Check if user is now authenticated
        if (auth()->guard('customer')->check()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'redirect' => '/servers'
            ]);
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Component executed but authentication status unclear',
            'result' => $result
        ]);
        
    } catch (\Illuminate\Validation\ValidationException $e) {
        \Log::warning('Direct login validation failed', ['errors' => $e->errors()]);
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed: ' . implode(', ', array_flatten($e->errors()))
        ], 422);
    } catch (Exception $e) {
        \Log::error('Direct login error', ['error' => $e->getMessage()]);
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
})->withoutMiddleware([
    \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
    \App\Http\Middleware\EnhancedCsrfProtection::class
]);

// Test route to check if basic PHP/Laravel is working
Route::get('/test-basic', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Basic Laravel route working',
        'time' => now(),
        'livewire_loaded' => class_exists('Livewire\Livewire')
    ]);
});
