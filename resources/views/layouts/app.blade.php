<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', '1000Proxy') }} - Premium Proxy Solutions</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Livewire Styles -->
        @livewireStyles

        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
            body {
                font-family: 'Inter', sans-serif;
                background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            }

            /* Custom scrollbar */
            ::-webkit-scrollbar {
                width: 8px;
            }

            ::-webkit-scrollbar-track {
                background: #1e293b;
            }

            ::-webkit-scrollbar-thumb {
                background: #10b981;
                border-radius: 4px;
            }

            ::-webkit-scrollbar-thumb:hover {
                background: #059669;
            }

            /* Enhanced navbar animations */
            .navbar-item {
                position: relative;
                overflow: hidden;
            }

            .navbar-item::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
                transition: left 0.5s;
            }

            .navbar-item:hover::before {
                left: 100%;
            }

            /* Enhanced cart badge animations */
            .cart-badge {
                animation: cartPulse 2s infinite;
            }

            @keyframes cartPulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.1); }
            }

            /* Enhanced button hover effects */
            .btn-enhanced {
                position: relative;
                overflow: hidden;
                transition: all 0.3s ease;
            }

            .btn-enhanced::after {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                width: 0;
                height: 0;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 50%;
                transform: translate(-50%, -50%);
                transition: width 0.3s, height 0.3s;
            }

            .btn-enhanced:hover::after {
                width: 300px;
                height: 300px;
            }

            /* Enhanced dropdown animations */
            .dropdown-enhanced {
                transform-origin: top right;
            }

            /* Enhanced mobile menu */
            .mobile-menu-backdrop {
                backdrop-filter: blur(8px);
            }

            /* Cart notification styles */
            .cart-notification {
                animation: slideInRight 0.3s ease-out;
            }

            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            /* Enhanced glassmorphism effects */
            .glass-effect {
                background: rgba(255, 255, 255, 0.05);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }

            /* Loading animation for cart updates */
            .cart-loading {
                position: relative;
            }

            .cart-loading::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.3), transparent);
                animation: loading 1.5s infinite;
            }

            @keyframes loading {
                0% { transform: translateX(-100%); }
                100% { transform: translateX(100%); }
            }

            /* Enhanced user avatar */
            .user-avatar {
                background: linear-gradient(135deg, #3b82f6, #fbbf24);
                box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
            }

            /* Enhanced active link indicator */
            .active-link {
                background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(251, 191, 36, 0.2));
                border: 1px solid rgba(59, 130, 246, 0.3);
            }
        </style>
    </head>
    <body class="font-sans antialiased text-white">
        <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
            @livewire('partials.navbar')

            <!-- Page Content -->
            <main>
                @yield('content')
                {{ $slot ?? '' }}
            </main>

            @livewire('partials.footer')
        </div>

        <!-- Livewire Scripts -->
        @livewireScripts
        
        <!-- SweetAlert2 and Livewire Alert -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <x-livewire-alert::scripts />

        <!-- Cart Update JavaScript -->
        <script>
            // Function to get cart count from cookies
            function getCartCount() {
                const cartItems = getCookie('cart_items');
                if (cartItems) {
                    try {
                        const items = JSON.parse(decodeURIComponent(cartItems));
                        return items.length;
                    } catch (e) {
                        return 0;
                    }
                }
                return 0;
            }

            // Function to get cookie value
            function getCookie(name) {
                const value = `; ${document.cookie}`;
                const parts = value.split(`; ${name}=`);
                if (parts.length === 2) return parts.pop().split(';').shift();
                return null;
            }

            // Function to update cart display
            function updateCartDisplay() {
                const cartCount = getCartCount();
                const cartCountElements = document.querySelectorAll('[data-cart-count]');
                
                cartCountElements.forEach(element => {
                    element.textContent = cartCount;
                    // Update visibility based on cart count
                    const cartBadge = element.closest('.absolute');
                    if (cartBadge) {
                        cartBadge.style.display = cartCount > 0 ? 'flex' : 'none';
                    }
                });

                // Update any ping animation elements
                const pingElements = document.querySelectorAll('.animate-ping');
                pingElements.forEach(element => {
                    if (element.classList.contains('bg-yellow-400')) {
                        element.style.display = cartCount > 0 ? 'block' : 'none';
                    }
                });
            }

            // Enhanced cart update functionality
            document.addEventListener('DOMContentLoaded', function() {
                // Update cart count on page load
                updateCartDisplay();
                
                // Listen for Livewire events
                if (typeof Livewire !== 'undefined') {
                    // Listen for various cart update events
                    Livewire.on('cart-updated', (event) => {
                        updateCartDisplay();
                    });

                    Livewire.on('update-cart-count', (event) => {
                        updateCartDisplay();
                    });

                    Livewire.on('cart-count-updated', (event) => {
                        updateCartDisplay();
                    });

                    // Global cart update listener
                    document.addEventListener('livewire:init', () => {
                        Livewire.on('cartUpdated', (event) => {
                            updateCartDisplay();
                        });
                    });
                }

                // Monitor cookies for changes every 2 seconds as fallback
                let lastCartCount = getCartCount();
                setInterval(() => {
                    const currentCartCount = getCartCount();
                    if (currentCartCount !== lastCartCount) {
                        lastCartCount = currentCartCount;
                        updateCartDisplay();
                        
                        // Dispatch Livewire event to update server-side cart count
                        if (typeof Livewire !== 'undefined') {
                            Livewire.dispatch('refresh-cart-count');
                        }
                    }
                }, 2000);
            });

            // Add to cart animation enhancement
            function addToCartAnimation(button) {
                if (button) {
                    button.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        button.style.transform = 'scale(1)';
                    }, 150);
                }
            }

            // Enhanced cart feedback
            function showCartAddedFeedback() {
                // Create a temporary notification
                const notification = document.createElement('div');
                notification.className = 'fixed top-20 right-4 bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-3 rounded-xl shadow-lg z-50 transform translate-x-full transition-transform duration-300';
                notification.innerHTML = `
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="font-medium">Added to cart!</span>
                    </div>
                `;
                
                document.body.appendChild(notification);
                
                // Animate in
                setTimeout(() => {
                    notification.style.transform = 'translateX(0)';
                }, 100);
                
                // Animate out and remove
                setTimeout(() => {
                    notification.style.transform = 'translateX(full)';
                    setTimeout(() => {
                        document.body.removeChild(notification);
                    }, 300);
                }, 3000);
            }
        </script>
    </body>
</html>
