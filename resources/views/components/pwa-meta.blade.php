{{-- PWA Meta Tags Component --}}
{{-- Include this in your app layout head section --}}

<!-- PWA Meta Tags -->
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#1f2937">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name', '1000Proxy') }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="application-name" content="{{ config('app.name', '1000Proxy') }}">
<meta name="msapplication-TileColor" content="#1f2937">
<meta name="msapplication-config" content="/browserconfig.xml">

<!-- PWA Manifest -->
<link rel="manifest" href="/manifest.json">

<!-- Apple Touch Icons -->
<link rel="apple-touch-icon" href="/images/icons/icon-192x192.png">
<link rel="apple-touch-icon-precomposed" href="/images/icons/icon-192x192.png">

<!-- Standard Favicon -->
<link rel="icon" type="image/png" href="/images/icons/icon-192x192.png">
<link rel="shortcut icon" href="/favicon.ico">

<!-- Microsoft Tiles -->
<meta name="msapplication-square70x70logo" content="/images/icons/icon-72x72.png">
<meta name="msapplication-square150x150logo" content="/images/icons/icon-152x152.png">
<meta name="msapplication-square310x310logo" content="/images/icons/icon-384x384.png">

<!-- PWA iOS Splash Screens -->
<!-- iPhone X, XS -->
<link rel="apple-touch-startup-image" href="/images/splash/iphone-x.png"
      media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3)">

<!-- iPhone XS Max, XR -->
<link rel="apple-touch-startup-image" href="/images/splash/iphone-xr.png"
      media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2)">

<!-- iPhone 8, 7, 6s, 6 -->
<link rel="apple-touch-startup-image" href="/images/splash/iphone-8.png"
      media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2)">

<!-- iPhone 8 Plus, 7 Plus, 6s Plus, 6 Plus -->
<link rel="apple-touch-startup-image" href="/images/splash/iphone-8-plus.png"
      media="(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3)">

<!-- iPad -->
<link rel="apple-touch-startup-image" href="/images/splash/ipad.png"
      media="(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2)">

<!-- iPad Pro 12.9" -->
<link rel="apple-touch-startup-image" href="/images/splash/ipad-pro.png"
      media="(device-width: 1024px) and (device-height: 1366px) and (-webkit-device-pixel-ratio: 2)">

<!-- SEO and Social Meta Tags -->
<meta name="description" content="Professional proxy services with real-time management and analytics. Get high-speed, secure proxies for gaming, streaming, and business use.">
<meta name="keywords" content="proxy, vpn, privacy, security, anonymous browsing, gaming proxy, streaming proxy, business proxy">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="{{ config('app.url') }}">
<meta property="og:title" content="{{ config('app.name', '1000Proxy') }} - Professional Proxy Services">
<meta property="og:description" content="Professional proxy services with real-time management and analytics. Get high-speed, secure proxies for gaming, streaming, and business use.">
<meta property="og:image" content="{{ config('app.url') }}/images/og-image.png">
<meta property="og:site_name" content="{{ config('app.name', '1000Proxy') }}">

<!-- Twitter -->
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:url" content="{{ config('app.url') }}">
<meta property="twitter:title" content="{{ config('app.name', '1000Proxy') }} - Professional Proxy Services">
<meta property="twitter:description" content="Professional proxy services with real-time management and analytics. Get high-speed, secure proxies for gaming, streaming, and business use.">
<meta property="twitter:image" content="{{ config('app.url') }}/images/twitter-image.png">

@push('scripts')
<!-- PWA Manager Script -->
<script src="{{ asset('js/components/pwa-manager.js') }}" defer></script>

<!-- PWA Service Worker Registration -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if PWA is supported
    if ('serviceWorker' in navigator) {
        console.log('[PWA] Service Worker support detected');

        // Additional PWA setup can go here
        // The pwa-manager.js will handle the main functionality

        // Add iOS-specific CSS for standalone mode
        if (window.navigator.standalone === true) {
            document.body.classList.add('pwa-ios-standalone');

            // Add iOS standalone styles
            const style = document.createElement('style');
            style.textContent = `
                .pwa-ios-standalone {
                    padding-top: env(safe-area-inset-top);
                    padding-bottom: env(safe-area-inset-bottom);
                    padding-left: env(safe-area-inset-left);
                    padding-right: env(safe-area-inset-right);
                }

                .pwa-ios-standalone .navbar,
                .pwa-ios-standalone .header {
                    padding-top: calc(env(safe-area-inset-top) + 1rem);
                }

                .pwa-ios-standalone .footer {
                    padding-bottom: calc(env(safe-area-inset-bottom) + 1rem);
                }
            `;
            document.head.appendChild(style);
        }

        // Detect if app is installed
        if (window.matchMedia('(display-mode: standalone)').matches) {
            document.body.classList.add('pwa-installed');
            console.log('[PWA] App is running in standalone mode');
        }

        // Analytics for PWA usage
        if (typeof gtag !== 'undefined') {
            gtag('event', 'pwa_page_view', {
                'event_category': 'PWA',
                'event_label': window.location.pathname,
                'value': 1
            });
        }

        // Track PWA installation from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('source') === 'pwa') {
            console.log('[PWA] User accessed via PWA');

            if (typeof gtag !== 'undefined') {
                gtag('event', 'pwa_access', {
                    'event_category': 'PWA',
                    'event_label': 'app_access',
                    'value': 1
                });
            }
        }
    } else {
        console.log('[PWA] Service Worker not supported');
    }
});

// Handle navigation in PWA
function handlePWANavigation() {
    // Override external links to open in system browser
    document.addEventListener('click', function(event) {
        const link = event.target.closest('a');

        if (link && link.hostname !== window.location.hostname) {
            // External link - let it open in system browser
            if (window.navigator.standalone === true) {
                event.preventDefault();
                window.open(link.href, '_blank');
            }
        }
    });
}

// Call navigation handler
handlePWANavigation();
</script>
@endpush

@push('styles')
<!-- PWA Specific Styles -->
<style>
    /* PWA Install Button Positioning */
    .pwa-install-btn {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
    }

    /* Hide install button if already installed */
    .pwa-installed .pwa-install-btn {
        display: none !important;
    }

    /* iOS Safari specific adjustments */
    .ios-safari-pwa {
        padding-top: 1rem;
        padding-bottom: 1rem;
    }

    /* Prevent iOS bounce scroll */
    .pwa-ios-standalone body {
        position: fixed;
        overflow: hidden;
        width: 100%;
        height: 100vh;
    }

    .pwa-ios-standalone .main-content {
        overflow-y: auto;
        height: 100vh;
        -webkit-overflow-scrolling: touch;
    }

    /* PWA Status Bar */
    .pwa-status-bar {
        height: env(safe-area-inset-top);
        background: #1f2937;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 9999;
    }

    /* Offline Mode Styling */
    .pwa-offline .offline-only {
        display: block;
    }

    .pwa-offline .online-only {
        display: none;
    }

    .pwa-online .offline-only {
        display: none;
    }

    .pwa-online .online-only {
        display: block;
    }

    /* PWA Loading Skeleton */
    .pwa-loading-skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading-skeleton 1.5s infinite;
    }

    @keyframes loading-skeleton {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* PWA Theme Transitions */
    .pwa-theme-transition * {
        transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
    }
</style>
@endpush
