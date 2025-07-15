<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

/**
 * Progressive Web App (PWA) Service
 *
 * Comprehensive PWA functionality including service worker management,
 * offline capabilities, push notifications, and app installation prompts.
 *
 * @package App\Services
 */
class ProgressiveWebAppService
{
    private const CACHE_VERSION = 'v1.2.0';
    private const SW_CACHE_PREFIX = 'pwa-sw-';
    private const MANIFEST_CACHE_KEY = 'pwa-manifest';
    private const NOTIFICATION_CACHE_KEY = 'pwa-notifications';

    private array $cacheStrategies = [
        'cache-first' => ['images', 'fonts', 'icons'],
        'network-first' => ['api', 'ajax'],
        'stale-while-revalidate' => ['css', 'js'],
        'cache-only' => ['offline']
    ];

    /**
     * Generate PWA manifest.json
     */
    public function generateManifest(): array
    {
        return Cache::remember(self::MANIFEST_CACHE_KEY, 3600, function () {
            return [
                'name' => config('app.name', '1000Proxy'),
                'short_name' => '1000Proxy',
                'description' => 'Professional proxy services with real-time management and analytics',
                'theme_color' => '#1f2937',
                'background_color' => '#ffffff',
                'display' => 'standalone',
                'orientation' => 'portrait-primary',
                'scope' => '/',
                'start_url' => '/',
                'id' => '/',
                'prefer_related_applications' => false,
                'lang' => 'en',
                'dir' => 'ltr',
                'categories' => ['business', 'productivity', 'utilities'],
                'screenshots' => [
                    [
                        'src' => '/images/screenshots/mobile-dashboard.png',
                        'sizes' => '390x844',
                        'type' => 'image/png',
                        'form_factor' => 'narrow',
                        'label' => 'Mobile Dashboard'
                    ],
                    [
                        'src' => '/images/screenshots/desktop-dashboard.png',
                        'sizes' => '1920x1080',
                        'type' => 'image/png',
                        'form_factor' => 'wide',
                        'label' => 'Desktop Dashboard'
                    ]
                ],
                'icons' => [
                    [
                        'src' => '/images/icons/icon-72x72.png',
                        'sizes' => '72x72',
                        'type' => 'image/png',
                        'purpose' => 'maskable any'
                    ],
                    [
                        'src' => '/images/icons/icon-96x96.png',
                        'sizes' => '96x96',
                        'type' => 'image/png',
                        'purpose' => 'maskable any'
                    ],
                    [
                        'src' => '/images/icons/icon-128x128.png',
                        'sizes' => '128x128',
                        'type' => 'image/png',
                        'purpose' => 'maskable any'
                    ],
                    [
                        'src' => '/images/icons/icon-144x144.png',
                        'sizes' => '144x144',
                        'type' => 'image/png',
                        'purpose' => 'maskable any'
                    ],
                    [
                        'src' => '/images/icons/icon-152x152.png',
                        'sizes' => '152x152',
                        'type' => 'image/png',
                        'purpose' => 'maskable any'
                    ],
                    [
                        'src' => '/images/icons/icon-192x192.png',
                        'sizes' => '192x192',
                        'type' => 'image/png',
                        'purpose' => 'maskable any'
                    ],
                    [
                        'src' => '/images/icons/icon-384x384.png',
                        'sizes' => '384x384',
                        'type' => 'image/png',
                        'purpose' => 'maskable any'
                    ],
                    [
                        'src' => '/images/icons/icon-512x512.png',
                        'sizes' => '512x512',
                        'type' => 'image/png',
                        'purpose' => 'maskable any'
                    ]
                ],
                'shortcuts' => [
                    [
                        'name' => 'My Proxies',
                        'short_name' => 'Proxies',
                        'description' => 'View and manage your proxy services',
                        'url' => '/dashboard/proxies',
                        'icons' => [
                            [
                                'src' => '/images/icons/shortcut-proxies.png',
                                'sizes' => '96x96'
                            ]
                        ]
                    ],
                    [
                        'name' => 'Buy Proxy',
                        'short_name' => 'Buy',
                        'description' => 'Purchase new proxy services',
                        'url' => '/products',
                        'icons' => [
                            [
                                'src' => '/images/icons/shortcut-buy.png',
                                'sizes' => '96x96'
                            ]
                        ]
                    ],
                    [
                        'name' => 'Account',
                        'short_name' => 'Account',
                        'description' => 'Manage your account settings',
                        'url' => '/dashboard/profile',
                        'icons' => [
                            [
                                'src' => '/images/icons/shortcut-account.png',
                                'sizes' => '96x96'
                            ]
                        ]
                    ]
                ],
                'related_applications' => [
                    [
                        'platform' => 'webapp',
                        'url' => config('app.url') . '/manifest.json'
                    ]
                ],
                'protocol_handlers' => [
                    [
                        'protocol' => 'web+proxy',
                        'url' => '/handle-proxy?url=%s'
                    ]
                ]
            ];
        });
    }

    /**
     * Generate service worker JavaScript
     */
    public function generateServiceWorker(): string
    {
        $version = self::CACHE_VERSION;
        $cacheName = self::SW_CACHE_PREFIX . $version;

        return "
// Service Worker for 1000Proxy PWA v{$version}
// Generated: " . now()->toISOString() . "

const CACHE_NAME = '{$cacheName}';
const OFFLINE_URL = '/offline';
const API_CACHE_NAME = 'api-cache-{$version}';
const STATIC_CACHE_NAME = 'static-cache-{$version}';
const DYNAMIC_CACHE_NAME = 'dynamic-cache-{$version}';

// Resources to cache on install
const PRECACHE_URLS = [
    '/',
    '/offline',
    '/css/app.css',
    '/js/app.js',
    '/images/icons/icon-192x192.png',
    '/images/icons/icon-512x512.png',
    '/manifest.json'
];

// Install event - cache resources
self.addEventListener('install', event => {
    console.log('[SW] Install event');

    event.waitUntil(
        caches.open(STATIC_CACHE_NAME)
            .then(cache => {
                console.log('[SW] Precaching static resources');
                return cache.addAll(PRECACHE_URLS);
            })
            .then(() => {
                return self.skipWaiting();
            })
            .catch(error => {
                console.error('[SW] Install failed:', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    console.log('[SW] Activate event');

    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName.startsWith('" . self::SW_CACHE_PREFIX . "') &&
                        cacheName !== CACHE_NAME &&
                        cacheName !== STATIC_CACHE_NAME &&
                        cacheName !== API_CACHE_NAME &&
                        cacheName !== DYNAMIC_CACHE_NAME) {
                        console.log('[SW] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            console.log('[SW] Claiming clients');
            return self.clients.claim();
        })
    );
});

// Fetch event - serve cached content when offline
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') return;

    // Skip chrome-extension requests
    if (url.protocol === 'chrome-extension:') return;

    // Handle different request types
    if (url.pathname.startsWith('/api/')) {
        // API requests - Network first, cache fallback
        event.respondWith(handleApiRequest(request));
    } else if (isStaticAsset(url.pathname)) {
        // Static assets - Cache first
        event.respondWith(handleStaticAsset(request));
    } else if (url.pathname.startsWith('/dashboard') || url.pathname === '/') {
        // App pages - Network first with offline fallback
        event.respondWith(handleAppPage(request));
    } else {
        // Other requests - Network first
        event.respondWith(handleOtherRequest(request));
    }
});

// Handle API requests with network-first strategy
async function handleApiRequest(request) {
    try {
        const networkResponse = await fetch(request);

        if (networkResponse.ok) {
            const cache = await caches.open(API_CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        console.log('[SW] API network failed, trying cache:', error);

        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }

        // Return offline API response
        return new Response(JSON.stringify({
            error: 'Offline',
            message: 'This feature is not available offline'
        }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

// Handle static assets with cache-first strategy
async function handleStaticAsset(request) {
    const cachedResponse = await caches.match(request);

    if (cachedResponse) {
        return cachedResponse;
    }

    try {
        const networkResponse = await fetch(request);

        if (networkResponse.ok) {
            const cache = await caches.open(STATIC_CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        console.log('[SW] Static asset failed:', error);

        // Return placeholder for images
        if (request.destination === 'image') {
            return new Response(
                '<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"200\" height=\"200\" viewBox=\"0 0 200 200\"><rect width=\"200\" height=\"200\" fill=\"#f3f4f6\"/><text x=\"50%\" y=\"50%\" text-anchor=\"middle\" dy=\".3em\" fill=\"#6b7280\">Offline</text></svg>',
                { headers: { 'Content-Type': 'image/svg+xml' } }
            );
        }

        throw error;
    }
}

// Handle app pages with network-first strategy
async function handleAppPage(request) {
    try {
        const networkResponse = await fetch(request);

        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        console.log('[SW] App page network failed, trying cache:', error);

        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }

        // Return offline page
        return caches.match(OFFLINE_URL);
    }
}

// Handle other requests
async function handleOtherRequest(request) {
    try {
        return await fetch(request);
    } catch (error) {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }

        throw error;
    }
}

// Check if URL is a static asset
function isStaticAsset(pathname) {
    return /\\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/i.test(pathname);
}

// Push notification event
self.addEventListener('push', event => {
    console.log('[SW] Push received:', event);

    const options = {
        body: 'Default notification body',
        icon: '/images/icons/icon-192x192.png',
        badge: '/images/icons/badge-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'View Details',
                icon: '/images/icons/action-explore.png'
            },
            {
                action: 'close',
                title: 'Close',
                icon: '/images/icons/action-close.png'
            }
        ]
    };

    if (event.data) {
        const data = event.data.json();
        options.title = data.title || 'New notification';
        options.body = data.body || options.body;
        options.icon = data.icon || options.icon;
        options.data = { ...options.data, ...data };
    }

    event.waitUntil(
        self.registration.showNotification(options.title || '1000Proxy', options)
    );
});

// Notification click event
self.addEventListener('notificationclick', event => {
    console.log('[SW] Notification click:', event);

    event.notification.close();

    const action = event.action;
    const data = event.notification.data;

    if (action === 'close') {
        return;
    }

    let url = '/';
    if (action === 'explore' && data.url) {
        url = data.url;
    }

    event.waitUntil(
        clients.matchAll({ type: 'window' }).then(clientList => {
            // Check if there's already a window/tab open with the target URL
            for (const client of clientList) {
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }

            // If not, open new window/tab
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});

// Background sync event
self.addEventListener('sync', event => {
    console.log('[SW] Background sync:', event.tag);

    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

// Perform background sync
async function doBackgroundSync() {
    try {
        // Sync offline data when back online
        const cache = await caches.open(API_CACHE_NAME);
        const requests = await cache.keys();

        for (const request of requests) {
            if (request.url.includes('/api/')) {
                try {
                    await fetch(request);
                } catch (error) {
                    console.log('[SW] Background sync failed for:', request.url);
                }
            }
        }

        console.log('[SW] Background sync completed');
    } catch (error) {
        console.error('[SW] Background sync error:', error);
    }
}

// Message event for communication with app
self.addEventListener('message', event => {
    console.log('[SW] Message received:', event.data);

    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }

    if (event.data && event.data.type === 'CACHE_URLS') {
        const urls = event.data.urls;
        caches.open(DYNAMIC_CACHE_NAME).then(cache => {
            cache.addAll(urls);
        });
    }
});

console.log('[SW] Service Worker loaded successfully');
";
    }

    /**
     * Generate offline page HTML
     */
    public function generateOfflinePage(): string
    {
        return View::make('pwa.offline')->render();
    }

    /**
     * Install PWA files
     */
    public function installPWAFiles(): array
    {
        $results = [];

        try {
            // Create manifest.json
            $manifest = $this->generateManifest();
            File::put(public_path('manifest.json'), json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $results['manifest'] = 'Created manifest.json';

            // Create service worker
            $serviceWorker = $this->generateServiceWorker();
            File::put(public_path('sw.js'), $serviceWorker);
            $results['service_worker'] = 'Created service worker';

            // Create offline page view
            $this->createOfflinePageView();
            $results['offline_page'] = 'Created offline page view';

            // Create PWA icons directory
            $iconsDir = public_path('images/icons');
            if (!File::exists($iconsDir)) {
                File::makeDirectory($iconsDir, 0755, true);
                $results['icons_dir'] = 'Created icons directory';
            }

            // Create screenshots directory
            $screenshotsDir = public_path('images/screenshots');
            if (!File::exists($screenshotsDir)) {
                File::makeDirectory($screenshotsDir, 0755, true);
                $results['screenshots_dir'] = 'Created screenshots directory';
            }

            // Clear caches
            Cache::forget(self::MANIFEST_CACHE_KEY);
            Cache::tags(['pwa'])->flush();

            Log::info('PWA files installed successfully', $results);

        } catch (\Exception $e) {
            Log::error('PWA installation failed: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Create offline page view
     */
    private function createOfflinePageView(): void
    {
        $viewsPath = resource_path('views/pwa');
        if (!File::exists($viewsPath)) {
            File::makeDirectory($viewsPath, 0755, true);
        }

        $offlineContent = $this->getOfflinePageTemplate();
        File::put($viewsPath . '/offline.blade.php', $offlineContent);
    }

    /**
     * Get offline page template
     */
    private function getOfflinePageTemplate(): string
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline - {{ config("app.name") }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .offline-container {
            text-align: center;
            padding: 2rem;
            max-width: 500px;
        }

        .offline-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }

        .offline-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .offline-message {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        .offline-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .offline-button {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .offline-button:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }

        .retry-button {
            background: rgba(76, 175, 80, 0.3);
            border-color: rgba(76, 175, 80, 0.5);
        }

        .retry-button:hover {
            background: rgba(76, 175, 80, 0.5);
        }

        @media (max-width: 480px) {
            .offline-title {
                font-size: 2rem;
            }

            .offline-actions {
                flex-direction: column;
                align-items: center;
            }

            .offline-button {
                min-width: 200px;
            }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 0.8; }
            50% { opacity: 1; }
            100% { opacity: 0.8; }
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="offline-icon pulse">📡</div>
        <h1 class="offline-title">You\'re Offline</h1>
        <p class="offline-message">
            It looks like you\'re not connected to the internet. Some features may not be available,
            but you can still browse previously loaded content.
        </p>
        <div class="offline-actions">
            <button onclick="window.location.reload()" class="offline-button retry-button">
                Try Again
            </button>
            <a href="/" class="offline-button">
                Go Home
            </a>
        </div>
    </div>

    <script>
        // Auto-retry when back online
        window.addEventListener("online", () => {
            window.location.reload();
        });

        // Check connection status
        if (navigator.onLine) {
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    </script>
</body>
</html>';
    }

    /**
     * Get PWA installation stats
     */
    public function getInstallationStats(): array
    {
        return Cache::remember('pwa-stats', 300, function () {
            return [
                'manifest_exists' => File::exists(public_path('manifest.json')),
                'service_worker_exists' => File::exists(public_path('sw.js')),
                'offline_page_exists' => File::exists(resource_path('views/pwa/offline.blade.php')),
                'icons_directory_exists' => File::exists(public_path('images/icons')),
                'screenshots_directory_exists' => File::exists(public_path('images/screenshots')),
                'cache_version' => self::CACHE_VERSION,
                'last_updated' => Carbon::now()->toISOString(),
                'supported_features' => [
                    'offline_support' => true,
                    'push_notifications' => true,
                    'background_sync' => true,
                    'app_shortcuts' => true,
                    'installation_prompt' => true,
                    'protocol_handlers' => true
                ]
            ];
        });
    }

    /**
     * Update PWA cache version
     */
    public function updateCacheVersion(): string
    {
        $newVersion = 'v' . Carbon::now()->format('Y.m.d.His');

        // Update service worker with new version
        $serviceWorker = str_replace(
            self::CACHE_VERSION,
            $newVersion,
            $this->generateServiceWorker()
        );

        File::put(public_path('sw.js'), $serviceWorker);

        // Clear all PWA caches
        Cache::tags(['pwa'])->flush();
        Cache::forget(self::MANIFEST_CACHE_KEY);
        Cache::forget(self::NOTIFICATION_CACHE_KEY);

        Log::info("PWA cache version updated to: {$newVersion}");

        return $newVersion;
    }

    /**
     * Send push notification
     */
    public function sendPushNotification(array $data): bool
    {
        try {
            // Store notification for service worker
            $notifications = Cache::get(self::NOTIFICATION_CACHE_KEY, []);
            $notifications[] = [
                'id' => uniqid(),
                'title' => $data['title'] ?? 'New Notification',
                'body' => $data['body'] ?? '',
                'icon' => $data['icon'] ?? '/images/icons/icon-192x192.png',
                'url' => $data['url'] ?? '/',
                'timestamp' => now()->toISOString(),
                'data' => $data['data'] ?? []
            ];

            Cache::put(self::NOTIFICATION_CACHE_KEY, $notifications, 3600);

            Log::info('Push notification queued', $data);
            return true;

        } catch (\Exception $e) {
            Log::error('Push notification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get cached notifications
     */
    public function getCachedNotifications(): array
    {
        return Cache::get(self::NOTIFICATION_CACHE_KEY, []);
    }

    /**
     * Clear cached notifications
     */
    public function clearCachedNotifications(): void
    {
        Cache::forget(self::NOTIFICATION_CACHE_KEY);
    }

    /**
     * Generate PWA meta tags for HTML head
     */
    public function getMetaTags(): array
    {
        return [
            // PWA meta tags
            'viewport' => '<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">',
            'theme-color' => '<meta name="theme-color" content="#1f2937">',
            'apple-mobile-web-app-capable' => '<meta name="apple-mobile-web-app-capable" content="yes">',
            'apple-mobile-web-app-status-bar-style' => '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">',
            'apple-mobile-web-app-title' => '<meta name="apple-mobile-web-app-title" content="1000Proxy">',
            'mobile-web-app-capable' => '<meta name="mobile-web-app-capable" content="yes">',
            'application-name' => '<meta name="application-name" content="1000Proxy">',
            'msapplication-TileColor' => '<meta name="msapplication-TileColor" content="#1f2937">',
            'msapplication-config' => '<meta name="msapplication-config" content="/browserconfig.xml">',

            // Manifest link
            'manifest' => '<link rel="manifest" href="/manifest.json">',

            // Apple touch icons
            'apple-touch-icon' => '<link rel="apple-touch-icon" href="/images/icons/icon-192x192.png">',
            'apple-touch-icon-precomposed' => '<link rel="apple-touch-icon-precomposed" href="/images/icons/icon-192x192.png">',

            // Favicon
            'icon' => '<link rel="icon" type="image/png" href="/images/icons/icon-192x192.png">',
            'shortcut-icon' => '<link rel="shortcut icon" href="/favicon.ico">',

            // Microsoft tiles
            'msapplication-square70x70logo' => '<meta name="msapplication-square70x70logo" content="/images/icons/icon-72x72.png">',
            'msapplication-square150x150logo' => '<meta name="msapplication-square150x150logo" content="/images/icons/icon-152x152.png">',
            'msapplication-square310x310logo' => '<meta name="msapplication-square310x310logo" content="/images/icons/icon-384x384.png">',

            // SEO and social
            'description' => '<meta name="description" content="Professional proxy services with real-time management and analytics">',
            'keywords' => '<meta name="keywords" content="proxy, vpn, privacy, security, anonymous browsing">'
        ];
    }
}
