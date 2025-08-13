{{-- Mobile-First Responsive Layout --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ implode(' ', $mobileClasses ?? []) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#1976d2">

    {{-- Preconnect to external domains --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">

    {{-- Critical CSS inlined --}}
    <style>
        /* Critical CSS - Above the fold */
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.5;
            background: #f8fafc;
            color: #1a202c;
        }

        /* Mobile-first responsive grid */
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: 1fr;
        }

        /* Responsive breakpoints */
        @media (min-width: 640px) {
            .container {
                padding: 0 1.5rem;
            }

            .grid-sm-2 {
                grid-template-columns: repeat(2, 1fr);
            }

            .grid-sm-3 {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 768px) {
            .container {
                padding: 0 2rem;
            }

            .grid-md-2 {
                grid-template-columns: repeat(2, 1fr);
            }

            .grid-md-3 {
                grid-template-columns: repeat(3, 1fr);
            }

            .grid-md-4 {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .grid-lg-2 {
                grid-template-columns: repeat(2, 1fr);
            }

            .grid-lg-3 {
                grid-template-columns: repeat(3, 1fr);
            }

            .grid-lg-4 {
                grid-template-columns: repeat(4, 1fr);
            }

            .grid-lg-5 {
                grid-template-columns: repeat(5, 1fr);
            }
        }

        /* Touch-friendly buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            min-width: 44px;
            padding: 0.75rem 1.5rem;
            background: #1976d2;
            color: white;
            border: none;
            border-radius: 0.375rem;
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        }

        .btn:hover,
        .btn:focus {
            background: #1565c0;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover,
        .btn-secondary:focus {
            background: #5a6268;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover,
        .btn-success:focus {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover,
        .btn-danger:focus {
            background: #c82333;
        }

        /* Mobile navigation */
        .mobile-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .mobile-nav-toggle {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.25rem;
        }

        .mobile-nav-menu {
            position: fixed;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-bottom: 1px solid #e2e8f0;
            transform: translateY(-100%);
            transition: transform 0.3s ease;
            z-index: 999;
        }

        .mobile-nav-menu.open {
            transform: translateY(0);
        }

        .mobile-nav-menu ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .mobile-nav-menu li {
            border-bottom: 1px solid #f1f5f9;
        }

        .mobile-nav-menu a {
            display: block;
            padding: 1rem 1.5rem;
            color: #374151;
            text-decoration: none;
            font-weight: 500;
        }

        .mobile-nav-menu a:hover,
        .mobile-nav-menu a:focus {
            background: #f8fafc;
            color: #1976d2;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .card-body {
            padding: 1.5rem;
        }

        .card-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        /* Form inputs */
        .form-input {
            width: 100%;
            min-height: 44px;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 1rem;
            background: white;
            transition: border-color 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #1976d2;
            box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .table th,
        .table td {
            padding: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }

        .table th {
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        /* Loading states */
        .loading {
            position: relative;
            color: transparent;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #e2e8f0;
            border-top-color: #1976d2;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Utility classes */
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }

        .hidden { display: none; }
        .visible { display: block; }

        .mt-0 { margin-top: 0; }
        .mt-1 { margin-top: 0.25rem; }
        .mt-2 { margin-top: 0.5rem; }
        .mt-3 { margin-top: 0.75rem; }
        .mt-4 { margin-top: 1rem; }
        .mt-5 { margin-top: 1.25rem; }
        .mt-6 { margin-top: 1.5rem; }

        .mb-0 { margin-bottom: 0; }
        .mb-1 { margin-bottom: 0.25rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-3 { margin-bottom: 0.75rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-5 { margin-bottom: 1.25rem; }
        .mb-6 { margin-bottom: 1.5rem; }

        .p-0 { padding: 0; }
        .p-1 { padding: 0.25rem; }
        .p-2 { padding: 0.5rem; }
        .p-3 { padding: 0.75rem; }
        .p-4 { padding: 1rem; }
        .p-5 { padding: 1.25rem; }
        .p-6 { padding: 1.5rem; }

        /* Device-specific optimizations */
        .device-mobile .container {
            padding: 0 0.75rem;
        }

        .device-mobile .btn {
            font-size: 1.125rem;
            padding: 1rem 1.5rem;
        }

        .device-mobile .form-input {
            font-size: 1.125rem;
            padding: 1rem;
        }

        .performance-low * {
            animation: none !important;
            transition: none !important;
        }

        .performance-low .card {
            box-shadow: none;
            border: 1px solid #e2e8f0;
        }

        /* High contrast support */
        @media (prefers-contrast: high) {
            .btn {
                border: 2px solid currentColor;
            }

            .card {
                border: 2px solid #000;
            }

            .form-input {
                border: 2px solid #000;
            }
        }

        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Print styles */
        @media print {
            .mobile-nav,
            .btn,
            .mobile-nav-menu {
                display: none !important;
            }

            .card {
                box-shadow: none;
                border: 1px solid #000;
            }
        }
    </style>

    <title>{{ config('app.name') }}</title>

    {{-- PWA Manifest --}}
    <link rel="manifest" href="/manifest.json">

    {{-- Icons --}}
    <link rel="icon" type="image/png" sizes="32x32" href="/images/icons/icon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/icons/icon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/icons/icon-180x180.png">

    {{-- Additional styles will be loaded below the fold --}}
    @stack('styles')
</head>
<body class="{{ implode(' ', $mobileClasses ?? []) }}">
    {{-- Skip links for accessibility --}}
    <a href="#main-content" class="skip-link">Skip to main content</a>
    <a href="#navigation" class="skip-link">Skip to navigation</a>

    {{-- Mobile navigation --}}
    <nav class="mobile-nav" id="navigation" role="navigation" aria-label="Main navigation">
        <div class="mobile-nav-brand">
            <a href="/" class="text-xl font-bold text-primary">
                {{ config('app.name') }}
            </a>
        </div>

        <button class="mobile-nav-toggle"
                aria-label="Toggle navigation menu"
                aria-expanded="false"
                aria-controls="mobile-menu"
                onclick="toggleMobileMenu()">
            <span class="hamburger-icon">☰</span>
        </button>

        <div class="mobile-nav-menu" id="mobile-menu" role="menu">
            <ul>
                <li><a href="/account" role="menuitem">Dashboard</a></li>
                <li><a href="/servers" role="menuitem">Servers</a></li>
                <li><a href="/account/my-active-servers" role="menuitem">Services</a></li>
                <li><a href="/account/order-management" role="menuitem">Orders</a></li>
                <li><a href="/support" role="menuitem">Support</a></li>
                <li><a href="/account/user-profile" role="menuitem">Profile</a></li>
                <li><a href="/logout" role="menuitem">Logout</a></li>
            </ul>
        </div>
    </nav>

    {{-- Main content area --}}
    <main id="main-content" role="main" style="margin-top: 4rem;">
        <div class="container">
            @yield('content')
        </div>
    </main>

    {{-- Footer --}}
    <footer role="contentinfo" class="mt-6 p-4 text-center border-t">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </footer>

    {{-- Mobile-specific JavaScript --}}
    <script>
        // Mobile navigation toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            const toggle = document.querySelector('.mobile-nav-toggle');
            const isOpen = menu.classList.contains('open');

            if (isOpen) {
                menu.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.querySelector('.hamburger-icon').textContent = '☰';
            } else {
                menu.classList.add('open');
                toggle.setAttribute('aria-expanded', 'true');
                toggle.querySelector('.hamburger-icon').textContent = '✕';
            }
        }

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            const nav = document.querySelector('.mobile-nav');
            const menu = document.getElementById('mobile-menu');

            if (!nav.contains(e.target) && menu.classList.contains('open')) {
                toggleMobileMenu();
            }
        });

        // Touch gesture handling
        let touchStartX = 0;
        let touchStartY = 0;

        document.addEventListener('touchstart', function(e) {
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
        }, { passive: true });

        document.addEventListener('touchend', function(e) {
            const touchEndX = e.changedTouches[0].clientX;
            const touchEndY = e.changedTouches[0].clientY;

            const deltaX = touchEndX - touchStartX;
            const deltaY = touchEndY - touchStartY;

            // Swipe detection
            if (Math.abs(deltaX) > Math.abs(deltaY)) {
                if (Math.abs(deltaX) > 50) {
                    if (deltaX > 0) {
                        // Swipe right
                        handleSwipeRight();
                    } else {
                        // Swipe left
                        handleSwipeLeft();
                    }
                }
            }
        }, { passive: true });

        function handleSwipeRight() {
            // Open menu on right swipe from left edge
            if (touchStartX < 50) {
                const menu = document.getElementById('mobile-menu');
                if (!menu.classList.contains('open')) {
                    toggleMobileMenu();
                }
            }
        }

        function handleSwipeLeft() {
            // Close menu on left swipe
            const menu = document.getElementById('mobile-menu');
            if (menu.classList.contains('open')) {
                toggleMobileMenu();
            }
        }

        // Performance optimizations
        function optimizeForPerformance() {
            const isLowPerformance = document.body.classList.contains('performance-low');

            if (isLowPerformance) {
                // Disable animations
                const style = document.createElement('style');
                style.textContent = `
                    *, *::before, *::after {
                        animation: none !important;
                        transition: none !important;
                    }
                `;
                document.head.appendChild(style);

                // Lazy load images more aggressively
                lazyLoadImages();
            }
        }

        function lazyLoadImages() {
            const images = document.querySelectorAll('img[data-src]');
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        observer.unobserve(img);
                    }
                });
            });

            images.forEach(img => imageObserver.observe(img));
        }

        // Initialize optimizations
        document.addEventListener('DOMContentLoaded', function() {
            optimizeForPerformance();
            lazyLoadImages();

            // Add viewport height fix for mobile browsers
            function setVH() {
                const vh = window.innerHeight * 0.01;
                document.documentElement.style.setProperty('--vh', `${vh}px`);
            }

            setVH();
            window.addEventListener('resize', setVH);
            window.addEventListener('orientationchange', setVH);
        });

        // Service Worker registration for PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('SW registered: ', registration);
                    })
                    .catch(function(registrationError) {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }
    </script>

    @stack('scripts')
</body>
</html>
