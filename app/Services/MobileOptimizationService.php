<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MobileOptimizationService
{
    public function __construct()
    {
        // Device detection will be done using request headers and user agent parsing
    }

    /**
     * Get device information and optimization settings
     */
    public function getDeviceInfo(): array
    {
        return [
            'device_type' => $this->getDeviceType(),
            'screen_size' => $this->getScreenSize(),
            'touch_enabled' => $this->isTouchDevice(),
            'performance_level' => $this->getPerformanceLevel(),
            'connection_type' => $this->getConnectionType(),
            'optimizations' => $this->getOptimizations(),
        ];
    }

    /**
     * Determine device type
     */
    private function getDeviceType(): string
    {
        $userAgent = request()->header('User-Agent', '');

        // Mobile patterns
        $mobilePatterns = [
            'Mobile', 'Android', 'iPhone', 'iPod', 'BlackBerry', 'Windows Phone',
            'Opera Mini', 'IEMobile', 'Mobile Safari'
        ];

        // Tablet patterns
        $tabletPatterns = [
            'iPad', 'Android.*Tablet', 'Kindle', 'Silk', 'PlayBook', 'Tablet'
        ];

        foreach ($tabletPatterns as $pattern) {
            if (preg_match("/$pattern/i", $userAgent)) {
                return 'tablet';
            }
        }

        foreach ($mobilePatterns as $pattern) {
            if (preg_match("/$pattern/i", $userAgent)) {
                return 'mobile';
            }
        }

        return 'desktop';
    }

    /**
     * Estimate screen size category
     */
    private function getScreenSize(): string
    {
        $userAgent = request()->header('User-Agent', '');
        $deviceType = $this->getDeviceType();

        // Mobile devices
        if ($deviceType === 'mobile') {
            // Check for specific small screen devices
            if (strpos($userAgent, 'iPhone') !== false) {
                if (strpos($userAgent, 'iPhone SE') !== false || strpos($userAgent, 'iPhone 5') !== false) {
                    return 'small'; // < 375px
                } elseif (strpos($userAgent, 'iPhone 12') !== false || strpos($userAgent, 'iPhone 13') !== false) {
                    return 'medium'; // 375-414px
                } else {
                    return 'large'; // > 414px
                }
            }

            // Android devices
            if (strpos($userAgent, 'Android') !== false) {
                if (strpos($userAgent, 'Mobile') !== false) {
                    return 'medium'; // Most Android phones
                }
            }

            return 'medium'; // Default for mobile
        }

        // Tablets
        if ($deviceType === 'tablet') {
            return 'xlarge'; // 768px+
        }

        // Desktop
        return 'xxlarge'; // 1024px+
    }

    /**
     * Check if device supports touch
     */
    private function isTouchDevice(): bool
    {
        $deviceType = $this->getDeviceType();
        return $deviceType === 'mobile' || $deviceType === 'tablet';
    }

    /**
     * Estimate device performance level
     */
    private function getPerformanceLevel(): string
    {
        $userAgent = request()->header('User-Agent', '');

        // High-end devices
        $highEndPatterns = [
            'iPhone 1[2-9]', 'iPhone [2-9][0-9]', // iPhone 12+
            'iPad Pro', 'iPad Air [4-9]', 'iPad [8-9]', // Modern iPads
            'SM-G9[0-9][0-9]', 'SM-N9[0-9][0-9]', // Samsung Galaxy S9+, Note 9+
            'Pixel [4-9]', 'Pixel [1-9][0-9]', // Google Pixel 4+
            'OnePlus [7-9]', 'OnePlus [1-9][0-9]', // OnePlus 7+
        ];

        foreach ($highEndPatterns as $pattern) {
            if (preg_match("/$pattern/", $userAgent)) {
                return 'high';
            }
        }

        // Low-end device patterns
        $lowEndPatterns = [
            'Android [4-6]\.', // Old Android versions
            'iPhone [3-6]', // Old iPhones
            'SAMSUNG-SM-[A-J]', // Samsung budget series
        ];

        foreach ($lowEndPatterns as $pattern) {
            if (preg_match("/$pattern/", $userAgent)) {
                return 'low';
            }
        }

        return 'medium'; // Default
    }

    /**
     * Estimate connection type
     */
    private function getConnectionType(): string
    {
        // Check for connection hints if available
        $connectionHeader = request()->header('Connection');
        $saveDataHeader = request()->header('Save-Data');

        if ($saveDataHeader === 'on') {
            return 'slow';
        }

        // Default based on device type
        $deviceType = $this->getDeviceType();
        if ($deviceType === 'mobile') {
            return 'mobile'; // Could be slow
        }

        return 'fast'; // Desktop/tablet assumption
    }

    /**
     * Get optimization recommendations
     */
    private function getOptimizations(): array
    {
        $deviceType = $this->getDeviceType();
        $performanceLevel = $this->getPerformanceLevel();
        $connectionType = $this->getConnectionType();

        $optimizations = [
            'lazy_loading' => true,
            'image_compression' => true,
            'minify_assets' => true,
            'prefetch_critical' => true,
        ];

        // Mobile-specific optimizations
        if ($deviceType === 'mobile') {
            $optimizations['touch_targets'] = true;
            $optimizations['swipe_gestures'] = true;
            $optimizations['simplified_ui'] = true;
            $optimizations['reduce_animations'] = $performanceLevel === 'low';
        }

        // Performance-based optimizations
        if ($performanceLevel === 'low') {
            $optimizations['reduce_images'] = true;
            $optimizations['disable_animations'] = true;
            $optimizations['limit_concurrent_requests'] = true;
        }

        // Connection-based optimizations
        if ($connectionType === 'slow') {
            $optimizations['compress_images'] = true;
            $optimizations['defer_non_critical'] = true;
            $optimizations['reduce_bundle_size'] = true;
        }

        return $optimizations;
    }

    /**
     * Generate responsive CSS classes
     */
    public function getResponsiveClasses(): array
    {
        $deviceInfo = $this->getDeviceInfo();

        $classes = [
            'device-' . $deviceInfo['device_type'],
            'screen-' . $deviceInfo['screen_size'],
            'performance-' . $deviceInfo['performance_level'],
        ];

        if ($deviceInfo['touch_enabled']) {
            $classes[] = 'touch-enabled';
        } else {
            $classes[] = 'no-touch';
        }

        return $classes;
    }

    /**
     * Get touch gesture configuration
     */
    public function getTouchGestureConfig(): array
    {
        if (!$this->isTouchDevice()) {
            return [];
        }

        return [
            'swipe_threshold' => 50, // pixels
            'tap_threshold' => 10, // pixels
            'long_press_duration' => 500, // milliseconds
            'double_tap_delay' => 300, // milliseconds
            'pinch_threshold' => 0.1, // scale difference
            'gesture_prevention' => [
                'context_menu' => true,
                'text_selection' => false,
                'drag_drop' => true,
            ],
        ];
    }

    /**
     * Get performance optimization settings
     */
    public function getPerformanceSettings(): array
    {
        $deviceInfo = $this->getDeviceInfo();

        $settings = [
            'image_quality' => 85,
            'max_image_width' => 1200,
            'lazy_loading_threshold' => 200, // pixels before viewport
            'preload_count' => 3, // number of items to preload
            'animation_duration' => 300, // milliseconds
        ];

        // Adjust based on performance level
        switch ($deviceInfo['performance_level']) {
            case 'low':
                $settings['image_quality'] = 70;
                $settings['max_image_width'] = 800;
                $settings['lazy_loading_threshold'] = 100;
                $settings['preload_count'] = 1;
                $settings['animation_duration'] = 150;
                break;

            case 'high':
                $settings['image_quality'] = 95;
                $settings['max_image_width'] = 1600;
                $settings['lazy_loading_threshold'] = 400;
                $settings['preload_count'] = 5;
                $settings['animation_duration'] = 400;
                break;
        }

        // Adjust based on connection
        if ($deviceInfo['connection_type'] === 'slow') {
            $settings['image_quality'] -= 15;
            $settings['max_image_width'] = min($settings['max_image_width'], 600);
            $settings['preload_count'] = 1;
        }

        return $settings;
    }

    /**
     * Generate mobile-optimized asset URLs
     */
    public function optimizeAssetUrl(string $asset, array $options = []): string
    {
        $deviceInfo = $this->getDeviceInfo();
        $performanceSettings = $this->getPerformanceSettings();

        // For images, apply optimization parameters
        if (preg_match('/\.(jpg|jpeg|png|webp)$/i', $asset)) {
            $params = [];

            // Quality setting
            $params['q'] = $options['quality'] ?? $performanceSettings['image_quality'];

            // Width constraint
            if (isset($options['width'])) {
                $params['w'] = min($options['width'], $performanceSettings['max_image_width']);
            } elseif ($deviceInfo['device_type'] === 'mobile') {
                $params['w'] = $deviceInfo['screen_size'] === 'small' ? 320 : 414;
            }

            // Format optimization
            if ($deviceInfo['connection_type'] === 'slow') {
                $params['f'] = 'webp'; // Use WebP for better compression
            }

            // Add optimization parameters to URL
            if (!empty($params)) {
                $separator = strpos($asset, '?') !== false ? '&' : '?';
                $asset .= $separator . http_build_query($params);
            }
        }

        return $asset;
    }

    /**
     * Get CSS for mobile optimizations
     */
    public function getMobileCSS(): string
    {
        $deviceInfo = $this->getDeviceInfo();
        $touchConfig = $this->getTouchGestureConfig();

        $css = '';

        // Touch target optimization
        if ($deviceInfo['touch_enabled']) {
            $css .= "
            /* Touch target optimization */
            .btn, button, [role='button'], input[type='submit'], input[type='button'] {
                min-height: 44px;
                min-width: 44px;
                touch-action: manipulation;
            }

            /* Prevent zooming on inputs */
            input, select, textarea {
                font-size: 16px;
            }

            /* Remove tap highlight */
            * {
                -webkit-tap-highlight-color: transparent;
            }

            /* Smooth scrolling */
            * {
                -webkit-overflow-scrolling: touch;
            }
            ";
        }

        // Performance-based optimizations
        if ($deviceInfo['performance_level'] === 'low') {
            $css .= "
            /* Reduce animations for low-end devices */
            *, *::before, *::after {
                animation-duration: 0.1s !important;
                transition-duration: 0.1s !important;
            }

            /* Disable complex effects */
            .shadow, .box-shadow {
                box-shadow: none !important;
            }

            .gradient {
                background: var(--fallback-color, #f5f5f5) !important;
            }
            ";
        }

        // Screen size specific optimizations
        switch ($deviceInfo['screen_size']) {
            case 'small':
                $css .= "
                /* Small screen optimizations */
                .container {
                    padding: 8px;
                }

                .btn {
                    padding: 12px 16px;
                    font-size: 14px;
                }

                .table-responsive {
                    font-size: 12px;
                }
                ";
                break;

            case 'medium':
                $css .= "
                /* Medium screen optimizations */
                .container {
                    padding: 12px;
                }

                .btn {
                    padding: 14px 20px;
                    font-size: 16px;
                }
                ";
                break;
        }

        return $css;
    }

    /**
     * Get JavaScript for mobile optimizations
     */
    public function getMobileJS(): string
    {
        $deviceInfo = $this->getDeviceInfo();
        $touchConfig = $this->getTouchGestureConfig();
        $performanceSettings = $this->getPerformanceSettings();

        return "
        window.mobileOptimization = {
            deviceInfo: " . json_encode($deviceInfo) . ",
            touchConfig: " . json_encode($touchConfig) . ",
            performanceSettings: " . json_encode($performanceSettings) . "
        };
        ";
    }

    /**
     * Log mobile analytics
     */
    public function logMobileAnalytics(array $data = []): void
    {
        $deviceInfo = $this->getDeviceInfo();

        $analyticsData = array_merge([
            'timestamp' => now(),
            'user_agent' => request()->header('User-Agent'),
            'device_info' => $deviceInfo,
            'viewport' => request()->header('Viewport'),
            'connection' => request()->header('Connection'),
            'url' => request()->url(),
        ], $data);

        // Cache analytics for batch processing
        $cacheKey = 'mobile_analytics_' . date('Y-m-d-H');
        $existing = Cache::get($cacheKey, []);
        $existing[] = $analyticsData;
        Cache::put($cacheKey, $existing, now()->addHours(2));

        Log::info('Mobile analytics logged', $analyticsData);
    }

    /**
     * Generate Progressive Web App configuration
     */
    public function getPWAConfig(): array
    {
        return [
            'name' => config('app.name'),
            'short_name' => config('app.name'),
            'description' => 'Advanced Proxy Management Platform',
            'start_url' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'icons' => [
                [
                    'src' => '/images/icons/icon-72x72.png',
                    'sizes' => '72x72',
                    'type' => 'image/png',
                ],
                [
                    'src' => '/images/icons/icon-96x96.png',
                    'sizes' => '96x96',
                    'type' => 'image/png',
                ],
                [
                    'src' => '/images/icons/icon-128x128.png',
                    'sizes' => '128x128',
                    'type' => 'image/png',
                ],
                [
                    'src' => '/images/icons/icon-144x144.png',
                    'sizes' => '144x144',
                    'type' => 'image/png',
                ],
                [
                    'src' => '/images/icons/icon-152x152.png',
                    'sizes' => '152x152',
                    'type' => 'image/png',
                ],
                [
                    'src' => '/images/icons/icon-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                ],
                [
                    'src' => '/images/icons/icon-384x384.png',
                    'sizes' => '384x384',
                    'type' => 'image/png',
                ],
                [
                    'src' => '/images/icons/icon-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                ],
            ],
        ];
    }
}
