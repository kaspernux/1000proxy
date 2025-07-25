<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Throwable;

class EnhancedErrorHandling
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): BaseResponse
    {
        try {
            $response = $next($request);

            // Log slow requests
            if (defined('LARAVEL_START')) {
                $duration = microtime(true) - LARAVEL_START;
                if ($duration > 2.0) { // Log requests taking more than 2 seconds
                    Log::warning('Slow request detected', [
                        'url' => $request->fullUrl(),
                        'method' => $request->method(),
                        'duration' => round($duration, 2) . 's',
                        'user_id' => auth()->id(),
                        'ip' => $request->ip(),
                    ]);
                }
            }

            return $response;

        } catch (Throwable $e) {
            // Log the error with context
            Log::error('Request failed with exception', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Handle API requests differently
            if ($request->expectsJson() || $request->is('api/*')) {
                return $this->handleApiError($e, $request);
            }

            // Handle web requests
            return $this->handleWebError($e, $request);
        }
    }

    /**
     * Handle API errors with structured response
     */
    protected function handleApiError(Throwable $e, Request $request): Response
    {
        $statusCode = $this->getStatusCode($e);
        $message = $this->getErrorMessage($e, $request);

        $response = [
            'success' => false,
            'message' => $message,
            'error_code' => class_basename($e),
            'timestamp' => now()->toISOString(),
        ];

        // Add debug info in development
        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ];
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Handle web errors with user-friendly messages
     */
    protected function handleWebError(Throwable $e, Request $request): Response
    {
        $statusCode = $this->getStatusCode($e);
        $message = $this->getErrorMessage($e, $request);

        // Set flash message for web responses
        session()->flash('error', $message);

        // Redirect back or to home
        if ($request->headers->get('referer')) {
            return redirect()->back()->withInput();
        }

        return redirect()->route('home');
    }

    /**
     * Get appropriate status code for the exception
     */
    protected function getStatusCode(Throwable $e): int
    {
        if (method_exists($e, 'getStatusCode')) {
            return $e->getStatusCode();
        }

        return match (get_class($e)) {
            'Illuminate\Auth\AuthenticationException' => 401,
            'Illuminate\Auth\Access\AuthorizationException' => 403,
            'Illuminate\Database\Eloquent\ModelNotFoundException' => 404,
            'Illuminate\Validation\ValidationException' => 422,
            'Illuminate\Database\QueryException' => 500,
            default => 500,
        };
    }

    /**
     * Get user-friendly error message
     */
    protected function getErrorMessage(Throwable $e, Request $request): string
    {
        // In production, return generic messages for security
        if (!config('app.debug')) {
            return match (get_class($e)) {
                'Illuminate\Auth\AuthenticationException' => 'Authentication required. Please log in.',
                'Illuminate\Auth\Access\AuthorizationException' => 'You do not have permission to perform this action.',
                'Illuminate\Database\Eloquent\ModelNotFoundException' => 'The requested resource was not found.',
                'Illuminate\Validation\ValidationException' => 'The provided data is invalid.',
                'Illuminate\Database\QueryException' => 'A database error occurred. Please try again.',
                default => 'An unexpected error occurred. Please try again later.',
            };
        }

        return $e->getMessage();
    }
}