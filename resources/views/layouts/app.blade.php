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

    {{-- Team & Support Chat widget (public) --}}
    @include('components.chat.widget')

        <!-- Livewire Scripts -->
        @livewireScripts
        
        <!-- SweetAlert2 and Livewire Alert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/vendor/livewire-alert/livewire-alert.js"></script>

        <script>
            // Normalize incoming 'alert' events that some Livewire transports
            // serialize as an array [payload] instead of payload. We handle
            // this in the capture phase, replace the event with a normalized
            // one and stop propagation of the original so downstream
            // listeners (vendor script) receive a consistent object shape.
            try {
                // Capture-phase listener: normalize incoming 'alert' payloads and
                // enforce toast defaults for vendor SweetAlert2 consumers.
                window.addEventListener('alert', function(e) {
                    try {
                        const d = e && e.detail ? e.detail : null;

                        const isArrayWrapped = Array.isArray(d) && d.length === 1 && typeof d[0] === 'object';
                        const isObject = d && typeof d === 'object' && !isArrayWrapped;

                        if (isArrayWrapped || isObject) {
                            // Prevent other listeners from receiving the original
                            e.stopImmediatePropagation();

                            // Use the wrapped object or the object itself
                            const normalized = isArrayWrapped ? d[0] : d;

                            // Ensure an options object exists and enforce toast defaults
                            normalized.options = Object.assign({}, normalized.options || {}, {
                                toast: true,
                                position: 'top-end', // top-right
                                showConfirmButton: false
                            });

                            try { console.debug('[alert normalized]', normalized); } catch (err) {}

                            // If this is a toast-style alert prefer our dark stacked UI
                            // and do not let the vendor script render the white SweetAlert2 toast.
                            const isToast = (normalized.toast !== undefined) ? Boolean(normalized.toast) : Boolean(normalized.options && normalized.options.toast);
                            if (isToast && typeof window.showNotification === 'function') {
                                try {
                                    const payloadTitle = normalized.title || normalized.heading || '';
                                    const payloadMessage = normalized.text ?? normalized.message ?? (normalized.html ? String(normalized.html).replace(/<[^>]*>/g, '') : '');
                                    const payloadType = normalized.icon || normalized.type || null;
                                    const ttl = (normalized.options && (normalized.options.timer || normalized.options.timeout)) || 4000;
                                    window.showNotification({ type: payloadType, title: payloadTitle, message: payloadMessage, timeout: ttl });
                                } catch (err) {
                                    // If showNotification fails, fall back to re-dispatching to vendor.
                                    const ev = new CustomEvent('alert', { detail: normalized });
                                    window.dispatchEvent(ev);
                                }

                                // Force a cart display sync in case the server-side
                                // add-to-cart updated cookies during the same action.
                                try { updateCartDisplay(); } catch (err) { /* ignore */ }
                                return;
                            }

                            // Non-toast: re-dispatch the normalized event so vendor can show modal
                            const ev = new CustomEvent('alert', { detail: normalized });
                            window.dispatchEvent(ev);

                            // Force a cart display sync in case the server-side
                            // add-to-cart updated cookies during the same action.
                            try { updateCartDisplay(); } catch (err) { /* ignore */ }
                            return;
                        }

                        try { console.debug('[alert event received]', d); } catch (err) {}
                    } catch (err) { /* ignore */ }
                }, true);
            } catch (e) { /* ignore */ }

            // Vendor Livewire Alert will handle 'alert' DOM events directly.
            // We dispatch 'alert' from server-side Livewire components so the
            // vendor script can render SweetAlert2 toasts. Layout interception
            // was removed to avoid double-rendering and preserve vendor behavior.

            // Global SweetAlert2 listener for Livewire Alert fallback
            // This listener maps common payload shapes into a visible
            // SweetAlert2 modal or toast and also bridges to the
            // stacked `showNotification()` UI so text is always visible.
            window.addEventListener('swal', (event) => {
                const detail = event.detail || {};

                    // Debug: log incoming payload so we can inspect why wrong icons appear
                    try { console.debug('[swal payload]', detail); } catch(e){}

                    // Dedupe: ignore repeated alerts with identical key within short window
                    try {
                        window.__swal_recent = window.__swal_recent || {};
                        const dedupeKey = ((detail.title||'') + '||' + (detail.text||detail.message||'') + '||' + (detail.icon||detail.type||'')).trim();
                        const now = Date.now();
                        const last = window.__swal_recent[dedupeKey] || 0;
                        if (now - last < 400) {
                            // Duplicate within 400ms - ignore
                            try { console.debug('[swal] duplicate ignored', dedupeKey); } catch(e){}
                            return;
                        }
                        window.__swal_recent[dedupeKey] = now;
                        // Cleanup old keys periodically
                        setTimeout(() => { if (window.__swal_recent[dedupeKey] === now) delete window.__swal_recent[dedupeKey]; }, 5000);
                    } catch(e) { /* continue gracefully */ }

                // Normalize icon/type and text fields. Prefer explicit fields from the
                // payload (icon/type/status/level/variant) — only run the textual
                // heuristic when absolutely no explicit indicator exists.
                const explicitIcon = (detail.icon || detail.type || detail.status || detail.level || detail.variant || '').toString().trim().toLowerCase();
                const title = detail.title || detail.heading || '';
                const text = detail.text ?? detail.message ?? '';
                const html = (typeof detail.html === 'string' && detail.html.length) ? detail.html : null;
                const isToast = (typeof detail.toast !== 'undefined') ? Boolean(detail.toast) : true;
                const position = detail.position || (isToast ? 'top-end' : 'center');
                const timer = (typeof detail.timer !== 'undefined') ? detail.timer : (isToast ? 3000 : null);

                // Respect explicit icon/type fields from the server. Do NOT infer
                // from the message text here — that can be surprising. If the
                // server didn't send an icon, leave it null and let the UI render
                // a neutral toast.
                const icon = (detail.icon || detail.type || detail.status || detail.level || detail.variant) || null;

                // Build SweetAlert2 options with sensible defaults. Only include
                // `icon` if we have a resolved value so we don't override the
                // caller's intended rendering (or force 'info').
                const opts = {
                    toast: isToast,
                    position: position,
                    timer: timer,
                    showConfirmButton: !isToast,
                };

                if (icon) opts.icon = icon;
                if (title) opts.title = title;

                if (html) {
                    opts.html = html;
                } else if (text) {
                    opts.text = text;
                }

                // If SweetAlert2 is available, prefer routing toast-style calls
                // through our stacked `showNotification` bridge to keep UI
                // consistent and readable. Only non-toast modals call the
                // original Swal.fire implementation.
                if (window.Swal && typeof window.Swal.fire === 'function') {
                    try {
                        // Preserve original fire for non-toast usages
                        if (!window.__original_swal_fire) {
                            window.__original_swal_fire = window.Swal.fire.bind(window.Swal);
                        }

                        // If the options indicate a toast, intercept and render
                        // using our accessible stacked toast UI instead of the
                        // default SweetAlert2 toast (white background).
                        if (opts.toast) {
                            try {
                                // Use original server-provided fields where possible
                                const payloadTitle = detail.title || detail.heading || '';
                                const payloadMessage = detail.text ?? detail.message ?? (detail.html ? String(detail.html).replace(/<[^>]*>/g, '') : '');
                                const payloadType = detail.icon || detail.type || null;
                                const ttl = opts.timer || 4000;
                                if (window.showNotification) {
                                    window.showNotification({ type: payloadType, title: payloadTitle, message: payloadMessage, timeout: ttl });
                                }
                                // Return a resolved Promise mimicking Swal.fire
                                return Promise.resolve({ isConfirmed: false, isDenied: false, dismiss: 'auto' });
                            } catch (e) {
                                return window.__original_swal_fire(opts);
                            }
                        }

                        // Non-toast modals: call original
                        return window.__original_swal_fire(opts);
                    } catch (e) {
                        // ignore and fall through to showNotification bridge
                    }
                }

                // Also surface a plain stacked toast for accessibility and
                // to guarantee visible text even when the modal is a toast.
                try {
                    const message = text || (html ? html.replace(/<[^>]*>/g, '') : '');
                    // For the stacked toast, map our resolved icon to the `type`
                    // field only when present. Otherwise omit it so the toast UI
                    // can choose a neutral styling instead of forcing 'info'.
                    const toastPayload = { title: title || '', message: message, timeout: timer || 4000 };
                    if (icon) toastPayload.type = icon;
                    if (window.showNotification) {
                        window.showNotification(toastPayload);
                    }
                } catch (e) {
                    // swallow - non-critical
                }
            });
        </script>

        <!-- Cart Update JavaScript -->
    <script>
            // Function to get cart count from cookies. The server-side
            // CartManagement helper writes the 'order_items' cookie.
            function getCartCount() {
                const cartItems = getCookie('order_items');
                if (cartItems) {
                    try {
                        const items = JSON.parse(decodeURIComponent(cartItems));
                        return Array.isArray(items) ? items.length : 0;
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

            // Function to update cart display; accepts optional explicit count
            function updateCartDisplay(explicitCount) {
                let cartCount = 0;
                if (typeof explicitCount !== 'undefined' && explicitCount !== null) {
                    cartCount = Number(explicitCount) || 0;
                } else {
                    cartCount = getCartCount();
                }

                const cartCountElements = document.querySelectorAll('[data-cart-count]');
                cartCountElements.forEach(element => {
                    element.textContent = cartCount;
                    // Keep a machine-readable attribute so scripts can read it
                    element.setAttribute('data-count', cartCount);
                    // Update visibility based on cart count
                    const cartBadge = element.closest('.absolute');
                    if (cartBadge) {
                        cartBadge.style.display = cartCount > 0 ? 'flex' : 'none';
                    }
                });

                // Update any ping animation elements (explicit data attribute preferred)
                const pingElements = document.querySelectorAll('[data-cart-ping], .animate-ping');
                pingElements.forEach(element => {
                    // If element uses data-cart-ping, show/hide accordingly
                    if (element.hasAttribute('data-cart-ping') || element.classList.contains('animate-ping')) {
                        element.style.display = cartCount > 0 ? (element.tagName === 'SPAN' ? 'block' : 'inline-block') : 'none';
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
                    // Support multiple event spellings (historic and v3) and bridge Livewire events
                    const cartEventHandler = (payload) => {
                        try {
                            console.debug('[cartEventHandler] payload:', payload);
                            // Payload may be a number or an object with count/total_count
                            let count = null;
                            if (typeof payload === 'number') count = payload;
                            else if (payload && typeof payload === 'object') {
                                count = payload.count ?? payload.total_count ?? payload.totalCount ?? null;
                            }
                            // If count is null, re-read cookie
                            updateCartDisplay(count);
                        } catch (e) { console.debug('[cartEventHandler] error', e); }
                    };

                    Livewire.on('cart-updated', cartEventHandler);
                    Livewire.on('update-cart-count', cartEventHandler);
                    Livewire.on('cart-count-updated', cartEventHandler);
                    Livewire.on('cartUpdated', cartEventHandler);

                    // Bridge 'swal' Livewire events to DOM so the existing window.addEventListener('swal') picks them up

                    // DOM-level fallback listeners: some environments may not receive Livewire.on bindings reliably.
                    // Listen for custom DOM events dispatched by the server or other scripts and update the cart display.
                    window.addEventListener('cart-count-updated', (e) => { try { console.debug('[dom event] cart-count-updated', e && e.detail); updateCartDisplay(e && e.detail ? (e.detail.count ?? e.detail) : null); } catch (err) { console.debug(err); } });
                    window.addEventListener('cartUpdated', (e) => { try { console.debug('[dom event] cartUpdated', e && e.detail); updateCartDisplay(); } catch (err) { console.debug(err); } });
                    window.addEventListener('cart-updated', (e) => { try { console.debug('[dom event] cart-updated', e && e.detail); updateCartDisplay(); } catch (err) { console.debug(err); } });
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

            // Stacked, accessible toast bridge used by the global swal handler.
            // Usage: window.showNotification({ type: 'success'|'error'|'warning'|'info', title: '...', message: '...', timeout: 4000 })
            (function(){
                const MAX_TOASTS = 5;

                function ensureContainer() {
                    let container = document.getElementById('stacked-toasts-container');
                    if (!container) {
                        container = document.createElement('div');
                        container.id = 'stacked-toasts-container';
                        container.setAttribute('aria-live', 'polite');
                        container.style.position = 'fixed';
                        container.style.top = '1rem';
                        container.style.right = '1rem';
                        container.style.zIndex = 99999;
                        container.style.display = 'flex';
                        container.style.flexDirection = 'column';
                        container.style.gap = '0.5rem';
                        container.style.alignItems = 'flex-end';
                        document.body.appendChild(container);
                    }
                    return container;
                }

                function typeColors(type) {
                    switch ((type || '').toLowerCase()) {
                        case 'success': return { bg: '#ecfdf5', border: '#10b981', text: '#064e3b' };
                        case 'error': return { bg: '#fff1f2', border: '#ef4444', text: '#7f1d1d' };
                        case 'warning': return { bg: '#fffbeb', border: '#f59e0b', text: '#7c2d12' };
                        case 'info': return { bg: '#eff6ff', border: '#3b82f6', text: '#1e3a8a' };
                        default: return { bg: '#0f172a', border: '#334155', text: '#ffffff' };
                    }
                }

                window.showNotification = function({ type=null, title='', message='', timeout=4000 } = {}) {
                    try {
                        const container = ensureContainer();

                        // Limit stack
                        while (container.children.length >= MAX_TOASTS) {
                            const oldest = container.children[0];
                            if (oldest) container.removeChild(oldest);
                        }

                        const colors = typeColors(type);

                        const toast = document.createElement('div');
                        toast.className = 'stacked-toast';
                        toast.setAttribute('role', 'status');
                        toast.setAttribute('aria-atomic', 'true');
                        toast.style.minWidth = '220px';
                        toast.style.maxWidth = '420px';
                        toast.style.background = colors.bg;
                        toast.style.color = colors.text;
                        toast.style.border = '1px solid ' + colors.border;
                        toast.style.padding = '0.65rem 0.9rem';
                        toast.style.borderRadius = '0.6rem';
                        toast.style.boxShadow = '0 6px 18px rgba(2,6,23,0.6)';
                        toast.style.opacity = '0';
                        toast.style.transform = 'translateY(-6px)';
                        toast.style.transition = 'opacity 220ms ease, transform 220ms ease';
                        toast.style.display = 'flex';
                        toast.style.alignItems = 'flex-start';
                        toast.style.gap = '0.6rem';

                        // Icon area
                        const iconEl = document.createElement('div');
                        iconEl.style.flex = '0 0 28px';
                        iconEl.style.height = '28px';
                        iconEl.style.display = 'flex';
                        iconEl.style.alignItems = 'center';
                        iconEl.style.justifyContent = 'center';
                        iconEl.style.borderRadius = '6px';
                        iconEl.style.background = colors.border;
                        iconEl.style.color = '#fff';
                        iconEl.style.fontSize = '14px';
                        iconEl.style.lineHeight = '1';
                        // Render a small SVG icon based on the toast type for clearer visuals
                        try {
                            function iconSvgFor(t) {
                                const tt = (t || '').toString().toLowerCase();
                                switch (tt) {
                                    case 'success':
                                        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>';
                                    case 'error':
                                    case 'danger':
                                        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>';
                                    case 'warning':
                                        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" stroke="currentColor" stroke-width="0" fill="currentColor"/><path d="M12 9v4M12 17h.01" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                                    case 'info':
                                        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/><path d="M12 8h.01M11 12h1v4h1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                                    default:
                                        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 2a10 10 0 100 20 10 10 0 000-20z" stroke="currentColor" stroke-width="0" fill="currentColor"/><path d="M12 8v4M12 16h.01" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                                }
                            }

                            iconEl.innerHTML = iconSvgFor(type);
                            iconEl.setAttribute('aria-hidden', 'true');
                        } catch (e) {
                            iconEl.innerText = (type || '').charAt(0).toUpperCase() || '';
                        }

                        // Content
                        const content = document.createElement('div');
                        content.style.flex = '1';
                        content.style.overflow = 'hidden';

                        if (title) {
                            const t = document.createElement('div');
                            t.style.fontWeight = '600';
                            t.style.marginBottom = message ? '0.15rem' : '0';
                            t.style.fontSize = '0.95rem';
                            t.textContent = title;
                            content.appendChild(t);
                        }

                        if (message) {
                            const m = document.createElement('div');
                            m.style.fontSize = '0.88rem';
                            m.style.opacity = '0.95';
                            m.style.lineHeight = '1.2';
                            m.textContent = message;
                            content.appendChild(m);
                        }

                        // Close button
                        const closeBtn = document.createElement('button');
                        closeBtn.setAttribute('aria-label', 'Close notification');
                        closeBtn.style.background = 'transparent';
                        closeBtn.style.border = 'none';
                        closeBtn.style.color = colors.text;
                        closeBtn.style.cursor = 'pointer';
                        closeBtn.style.fontSize = '14px';
                        closeBtn.style.padding = '0.2rem';
                        closeBtn.innerHTML = '&times;';

                        // Progress bar
                        const progress = document.createElement('div');
                        progress.style.position = 'absolute';
                        progress.style.left = '0';
                        progress.style.right = '0';
                        progress.style.bottom = '0';
                        progress.style.height = '3px';
                        progress.style.background = 'rgba(0,0,0,0.08)';

                        const progressInner = document.createElement('div');
                        progressInner.style.height = '100%';
                        progressInner.style.width = '100%';
                        progressInner.style.background = colors.border;
                        progressInner.style.transition = 'width linear';
                        progress.appendChild(progressInner);

                        // wrapper to position progress
                        const toastWrapper = document.createElement('div');
                        toastWrapper.style.position = 'relative';
                        toastWrapper.style.display = 'flex';
                        toastWrapper.style.alignItems = 'stretch';
                        toastWrapper.style.gap = '0.6rem';
                        toastWrapper.appendChild(iconEl);
                        toastWrapper.appendChild(content);
                        toastWrapper.appendChild(closeBtn);
                        toast.appendChild(toastWrapper);
                        toast.appendChild(progress);

                        // Insert at end (top-right stacking)
                        container.appendChild(toast);

                        // Animate in
                        requestAnimationFrame(() => {
                            toast.style.opacity = '1';
                            toast.style.transform = 'translateY(0)';
                        });

                        let remaining = Number(timeout) || 4000;
                        let start = Date.now();
                        let paused = false;

                        function tick() {
                            if (!paused) {
                                const elapsed = Date.now() - start;
                                const pct = Math.max(0, 1 - elapsed / remaining);
                                progressInner.style.width = (pct * 100) + '%';
                                if (elapsed >= remaining) {
                                    removeToast();
                                } else {
                                    requestAnimationFrame(tick);
                                }
                            } else {
                                requestAnimationFrame(tick);
                            }
                        }

                        function removeToast() {
                            try {
                                toast.style.opacity = '0';
                                toast.style.transform = 'translateY(-6px)';
                                setTimeout(() => {
                                    if (toast && toast.parentNode) toast.parentNode.removeChild(toast);
                                }, 220);
                            } catch (e) {}
                        }

                        closeBtn.addEventListener('click', () => removeToast());
                        toast.addEventListener('mouseenter', () => { paused = true; });
                        toast.addEventListener('mouseleave', () => { paused = false; start = Date.now(); });

                        // Start progress
                        requestAnimationFrame(tick);
                    } catch (e) {
                        // Non-critical - if anything goes wrong don't break the page
                        try { console.debug('showNotification error', e); } catch (err) {}
                    }
                };
            })();
        </script>
    </body>
</html>
