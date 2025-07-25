<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProgressiveWebAppService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

/**
 * PWA Controller
 *
 * Handles Progressive Web App functionality including manifest generation,
 * service worker management, push notifications, and installation tracking.
 */
class PWAController extends Controller
{
    private ProgressiveWebAppService $pwaService;

    public function __construct(ProgressiveWebAppService $pwaService)
    {
        $this->pwaService = $pwaService;
    }

    /**
     * Serve PWA manifest.json
     */
    public function manifest(): JsonResponse
    {
        try {
            $manifest = $this->pwaService->generateManifest();

            return response()->json($manifest)
                ->header('Content-Type', 'application/manifest+json')
                ->header('Cache-Control', 'public, max-age=3600');

        } catch (\Exception $e) {
            Log::error('PWA manifest generation failed: ' . $e->getMessage());

            return response()->json([
                'error' => 'Manifest generation failed'
            ], 500);
        }
    }

    /**
     * Serve service worker
     */
    public function serviceWorker(): \Illuminate\Http\Response
    {
        try {
            $serviceWorker = $this->pwaService->generateServiceWorker();

            return response($serviceWorker)
                ->header('Content-Type', 'application/javascript')
                ->header('Cache-Control', 'public, max-age=0')
                ->header('Service-Worker-Allowed', '/');

        } catch (\Exception $e) {
            Log::error('Service worker generation failed: ' . $e->getMessage());

            return response('console.error("Service worker generation failed");', 500)
                ->header('Content-Type', 'application/javascript');
        }
    }

    /**
     * Show offline page
     */
    public function offline()
    {
        return view('pwa.offline');
    }

    /**
     * Get PWA installation status
     */
    public function status(): JsonResponse
    {
        try {
            $stats = $this->pwaService->getInstallationStats();

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('PWA status check failed: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Status check failed'
            ], 500);
        }
    }

    /**
     * Install PWA files
     */
    public function install(): JsonResponse
    {
        try {
            $results = $this->pwaService->installPWAFiles();

            return response()->json([
                'status' => 'success',
                'message' => 'PWA files installed successfully',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('PWA installation failed: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Installation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update cache version
     */
    public function updateCache(): JsonResponse
    {
        try {
            $newVersion = $this->pwaService->updateCacheVersion();

            return response()->json([
                'status' => 'success',
                'message' => 'Cache version updated',
                'version' => $newVersion
            ]);

        } catch (\Exception $e) {
            Log::error('PWA cache update failed: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Cache update failed'
            ], 500);
        }
    }

    /**
     * Send push notification
     */
    public function sendNotification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:500',
            'icon' => 'nullable|string|url',
            'url' => 'nullable|string|url',
            'data' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $success = $this->pwaService->sendPushNotification($request->all());

            if ($success) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Notification sent successfully'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to send notification'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Push notification failed: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Notification sending failed'
            ], 500);
        }
    }

    /**
     * Get cached notifications
     */
    public function getNotifications(): JsonResponse
    {
        try {
            $notifications = $this->pwaService->getCachedNotifications();

            return response()->json([
                'status' => 'success',
                'data' => $notifications
            ]);

        } catch (\Exception $e) {
            Log::error('Getting notifications failed: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get notifications'
            ], 500);
        }
    }

    /**
     * Clear cached notifications
     */
    public function clearNotifications(): JsonResponse
    {
        try {
            $this->pwaService->clearCachedNotifications();

            return response()->json([
                'status' => 'success',
                'message' => 'Notifications cleared successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Clearing notifications failed: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to clear notifications'
            ], 500);
        }
    }

    /**
     * Track PWA installation
     */
    public function trackInstallation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'event' => 'required|string|in:beforeinstallprompt,appinstalled,installed,dismissed',
            'platform' => 'nullable|string',
            'user_agent' => 'nullable|string',
            'timestamp' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = [
                'event' => $request->input('event'),
                'platform' => $request->input('platform', $request->header('User-Agent')),
                'user_agent' => $request->input('user_agent', $request->header('User-Agent')),
                'timestamp' => $request->input('timestamp', now()->timestamp),
                'ip_address' => $request->ip(),
                'user_id' => Auth::id()
            ];

            // Log installation event
            Log::info('PWA installation event tracked', $data);

            // You could store this in a database table for analytics
            // PWAInstallationEvent::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Installation event tracked'
            ]);

        } catch (\Exception $e) {
            Log::error('PWA installation tracking failed: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Tracking failed'
            ], 500);
        }
    }

    /**
     * Get PWA meta tags for HTML head
     */
    public function getMetaTags(): JsonResponse
    {
        try {
            $metaTags = $this->pwaService->getMetaTags();

            return response()->json([
                'status' => 'success',
                'data' => $metaTags
            ]);

        } catch (\Exception $e) {
            Log::error('Getting PWA meta tags failed: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get meta tags'
            ], 500);
        }
    }

    /**
     * Handle protocol handlers (for web+proxy:// URLs)
     */
    public function handleProtocol(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid URL provided'
            ], 422);
        }

        try {
            $url = $request->input('url');

            // Parse the protocol URL (e.g., web+proxy://server.example.com:8080)
            if (str_starts_with($url, 'web+proxy://')) {
                $proxyUrl = str_replace('web+proxy://', '', $url);

                // Redirect to proxy configuration page
                return response()->json([
                    'status' => 'success',
                    'redirect' => '/dashboard/proxies/configure?url=' . urlencode($proxyUrl)
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Unsupported protocol'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Protocol handling failed: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Protocol handling failed'
            ], 500);
        }
    }

    /**
     * Check if request is from PWA
     */
    public function isPWA(Request $request): bool
    {
        return $request->header('X-Requested-With') === 'PWA' ||
               str_contains($request->header('User-Agent', ''), 'PWA') ||
               $request->query('source') === 'pwa';
    }

    /**
     * Get PWA capabilities
     */
    public function getCapabilities(): JsonResponse
    {
        try {
            $capabilities = [
                'offline_support' => true,
                'push_notifications' => true,
                'background_sync' => true,
                'install_prompt' => true,
                'app_shortcuts' => true,
                'protocol_handlers' => true,
                'share_target' => false, // Could be implemented
                'file_handling' => false, // Could be implemented
                'web_share' => true,
                'contact_picker' => false,
                'payment_request' => false, // Could be implemented
                'screen_wake_lock' => false,
                'device_memory' => false,
                'network_information' => true,
                'geolocation' => false,
                'camera' => false,
                'microphone' => false
            ];

            return response()->json([
                'status' => 'success',
                'data' => $capabilities
            ]);

        } catch (\Exception $e) {
            Log::error('Getting PWA capabilities failed: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get capabilities'
            ], 500);
        }
    }
}
