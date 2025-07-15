/**
 * Progressive Web App (PWA) Manager
 *
 * Handles PWA installation, service worker registration,
 * offline detection, and push notifications.
 */
class PWAManager
{
    constructor ()
    {
        this.deferredPrompt = null;
        this.isInstalled = false;
        this.isOnline = navigator.onLine;
        this.serviceWorker = null;
        this.notificationPermission = 'default';
        this.installButton = null;
        this.updateButton = null;
        this.offlineIndicator = null;

        this.init();
    }

    /**
     * Initialize PWA functionality
     */
    async init ()
    {
        try
        {
            await this.registerServiceWorker();
            this.setupInstallPrompt();
            this.setupOfflineDetection();
            this.setupNotifications();
            this.setupUI();
            this.checkInstallationStatus();
            this.setupKeyboardShortcuts();

            console.log( '[PWA] PWA Manager initialized successfully' );
        } catch ( error )
        {
            console.error( '[PWA] Initialization failed:', error );
        }
    }

    /**
     * Register service worker
     */
    async registerServiceWorker ()
    {
        if ( 'serviceWorker' in navigator )
        {
            try
            {
                const registration = await navigator.serviceWorker.register( '/sw.js', {
                    scope: '/'
                } );

                this.serviceWorker = registration;

                // Handle updates
                registration.addEventListener( 'updatefound', () =>
                {
                    const newWorker = registration.installing;
                    newWorker.addEventListener( 'statechange', () =>
                    {
                        if ( newWorker.state === 'installed' && navigator.serviceWorker.controller )
                        {
                            this.showUpdatePrompt();
                        }
                    } );
                } );

                // Listen for messages from service worker
                navigator.serviceWorker.addEventListener( 'message', event =>
                {
                    this.handleServiceWorkerMessage( event.data );
                } );

                console.log( '[PWA] Service Worker registered:', registration );
                return registration;
            } catch ( error )
            {
                console.error( '[PWA] Service Worker registration failed:', error );
                throw error;
            }
        } else
        {
            throw new Error( 'Service Workers not supported' );
        }
    }

    /**
     * Setup install prompt handling
     */
    setupInstallPrompt ()
    {
        // Listen for beforeinstallprompt event
        window.addEventListener( 'beforeinstallprompt', ( event ) =>
        {
            event.preventDefault();
            this.deferredPrompt = event;

            this.trackInstallationEvent( 'beforeinstallprompt' );
            this.showInstallButton();

            console.log( '[PWA] Install prompt available' );
        } );

        // Listen for appinstalled event
        window.addEventListener( 'appinstalled', () =>
        {
            this.isInstalled = true;
            this.deferredPrompt = null;

            this.trackInstallationEvent( 'appinstalled' );
            this.hideInstallButton();
            this.showInstalledNotification();

            console.log( '[PWA] App installed successfully' );
        } );
    }

    /**
     * Setup offline detection
     */
    setupOfflineDetection ()
    {
        window.addEventListener( 'online', () =>
        {
            this.isOnline = true;
            this.updateOfflineIndicator();
            this.syncOfflineData();
            this.showNotification( 'Back online', 'You\'re connected to the internet again.' );
        } );

        window.addEventListener( 'offline', () =>
        {
            this.isOnline = false;
            this.updateOfflineIndicator();
            this.showNotification( 'You\'re offline', 'Some features may not be available.' );
        } );

        this.updateOfflineIndicator();
    }

    /**
     * Setup push notifications
     */
    async setupNotifications ()
    {
        if ( 'Notification' in window )
        {
            this.notificationPermission = Notification.permission;

            if ( this.notificationPermission === 'default' )
            {
                // Request permission automatically for PWA
                setTimeout( () =>
                {
                    this.requestNotificationPermission();
                }, 5000 );
            }
        }
    }

    /**
     * Setup UI elements
     */
    setupUI ()
    {
        this.createInstallButton();
        this.createUpdateButton();
        this.createOfflineIndicator();
        this.addPWAStyles();
    }

    /**
     * Create install button
     */
    createInstallButton ()
    {
        this.installButton = document.createElement( 'button' );
        this.installButton.id = 'pwa-install-button';
        this.installButton.className = 'pwa-install-btn hidden';
        this.installButton.innerHTML = `
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8l-8-8-8 8"/>
            </svg>
            Install App
        `;

        this.installButton.addEventListener( 'click', () =>
        {
            this.promptInstall();
        } );

        // Add to page (you might want to customize the placement)
        document.body.appendChild( this.installButton );
    }

    /**
     * Create update button
     */
    createUpdateButton ()
    {
        this.updateButton = document.createElement( 'button' );
        this.updateButton.id = 'pwa-update-button';
        this.updateButton.className = 'pwa-update-btn hidden';
        this.updateButton.innerHTML = `
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Update Available
        `;

        this.updateButton.addEventListener( 'click', () =>
        {
            this.updateApp();
        } );

        document.body.appendChild( this.updateButton );
    }

    /**
     * Create offline indicator
     */
    createOfflineIndicator ()
    {
        this.offlineIndicator = document.createElement( 'div' );
        this.offlineIndicator.id = 'pwa-offline-indicator';
        this.offlineIndicator.className = 'pwa-offline-indicator hidden';
        this.offlineIndicator.innerHTML = `
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-12.728 12.728m0-12.728l12.728 12.728"/>
            </svg>
            Offline Mode
        `;

        document.body.appendChild( this.offlineIndicator );
    }

    /**
     * Add PWA-specific styles
     */
    addPWAStyles ()
    {
        const style = document.createElement( 'style' );
        style.textContent = `
            .pwa-install-btn, .pwa-update-btn {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: #3b82f6;
                color: white;
                border: none;
                border-radius: 12px;
                padding: 12px 20px;
                font-weight: 500;
                display: flex;
                align-items: center;
                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
                z-index: 1000;
                transition: all 0.3s ease;
                cursor: pointer;
            }

            .pwa-update-btn {
                bottom: 80px;
                background: #10b981;
            }

            .pwa-install-btn:hover, .pwa-update-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
            }

            .pwa-update-btn:hover {
                box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
            }

            .pwa-offline-indicator {
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: #ef4444;
                color: white;
                padding: 8px 16px;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 500;
                display: flex;
                align-items: center;
                z-index: 1000;
                animation: slideDown 0.3s ease;
            }

            .pwa-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                padding: 16px;
                max-width: 320px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                z-index: 1000;
                animation: slideIn 0.3s ease;
            }

            .pwa-notification.success {
                border-left: 4px solid #10b981;
            }

            .pwa-notification.info {
                border-left: 4px solid #3b82f6;
            }

            .pwa-notification.warning {
                border-left: 4px solid #f59e0b;
            }

            .hidden {
                display: none !important;
            }

            @keyframes slideDown {
                from { transform: translateX(-50%) translateY(-100%); }
                to { transform: translateX(-50%) translateY(0); }
            }

            @keyframes slideIn {
                from { transform: translateX(100%); }
                to { transform: translateX(0); }
            }

            @media (max-width: 768px) {
                .pwa-install-btn, .pwa-update-btn {
                    bottom: 10px;
                    right: 10px;
                    padding: 10px 16px;
                    font-size: 14px;
                }

                .pwa-update-btn {
                    bottom: 60px;
                }

                .pwa-offline-indicator {
                    top: 10px;
                    font-size: 12px;
                    padding: 6px 12px;
                }

                .pwa-notification {
                    top: 10px;
                    right: 10px;
                    left: 10px;
                    max-width: none;
                }
            }
        `;

        document.head.appendChild( style );
    }

    /**
     * Show install button
     */
    showInstallButton ()
    {
        if ( this.installButton && !this.isInstalled )
        {
            this.installButton.classList.remove( 'hidden' );
        }
    }

    /**
     * Hide install button
     */
    hideInstallButton ()
    {
        if ( this.installButton )
        {
            this.installButton.classList.add( 'hidden' );
        }
    }

    /**
     * Show update prompt
     */
    showUpdatePrompt ()
    {
        if ( this.updateButton )
        {
            this.updateButton.classList.remove( 'hidden' );
        }
    }

    /**
     * Update offline indicator
     */
    updateOfflineIndicator ()
    {
        if ( this.offlineIndicator )
        {
            if ( this.isOnline )
            {
                this.offlineIndicator.classList.add( 'hidden' );
            } else
            {
                this.offlineIndicator.classList.remove( 'hidden' );
            }
        }
    }

    /**
     * Prompt app installation
     */
    async promptInstall ()
    {
        if ( this.deferredPrompt )
        {
            try
            {
                this.deferredPrompt.prompt();
                const { outcome } = await this.deferredPrompt.userChoice;

                this.trackInstallationEvent( outcome === 'accepted' ? 'installed' : 'dismissed' );

                if ( outcome === 'accepted' )
                {
                    console.log( '[PWA] User accepted install prompt' );
                } else
                {
                    console.log( '[PWA] User dismissed install prompt' );
                }

                this.deferredPrompt = null;
                this.hideInstallButton();
            } catch ( error )
            {
                console.error( '[PWA] Install prompt failed:', error );
            }
        }
    }

    /**
     * Update app
     */
    updateApp ()
    {
        if ( this.serviceWorker && this.serviceWorker.waiting )
        {
            this.serviceWorker.waiting.postMessage( { type: 'SKIP_WAITING' } );

            // Reload page after update
            window.location.reload();
        }
    }

    /**
     * Request notification permission
     */
    async requestNotificationPermission ()
    {
        if ( 'Notification' in window )
        {
            try
            {
                const permission = await Notification.requestPermission();
                this.notificationPermission = permission;

                if ( permission === 'granted' )
                {
                    this.showNotification( 'Notifications enabled', 'You\'ll receive updates about your proxy services.' );
                }

                return permission;
            } catch ( error )
            {
                console.error( '[PWA] Notification permission request failed:', error );
                return 'denied';
            }
        }
        return 'denied';
    }

    /**
     * Show notification
     */
    showNotification ( title, body, options = {} )
    {
        // Show web notification if available and permitted
        if ( 'Notification' in window && this.notificationPermission === 'granted' )
        {
            const notification = new Notification( title, {
                body,
                icon: '/images/icons/icon-192x192.png',
                badge: '/images/icons/badge-72x72.png',
                ...options
            } );

            // Auto-close after 5 seconds
            setTimeout( () =>
            {
                notification.close();
            }, 5000 );
        }

        // Also show in-app notification
        this.showInAppNotification( title, body, options.type || 'info' );
    }

    /**
     * Show in-app notification
     */
    showInAppNotification ( title, body, type = 'info' )
    {
        const notification = document.createElement( 'div' );
        notification.className = `pwa-notification ${ type }`;
        notification.innerHTML = `
            <div class="font-medium">${ title }</div>
            <div class="text-sm text-gray-600 mt-1">${ body }</div>
            <button class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        `;

        // Add close button functionality
        const closeButton = notification.querySelector( 'button' );
        closeButton.addEventListener( 'click', () =>
        {
            notification.remove();
        } );

        document.body.appendChild( notification );

        // Auto-remove after 5 seconds
        setTimeout( () =>
        {
            if ( notification.parentNode )
            {
                notification.remove();
            }
        }, 5000 );
    }

    /**
     * Show installed notification
     */
    showInstalledNotification ()
    {
        this.showNotification(
            'App Installed Successfully!',
            'You can now use 1000Proxy as a native app.',
            { type: 'success' }
        );
    }

    /**
     * Check installation status
     */
    checkInstallationStatus ()
    {
        // Check if running in standalone mode (installed)
        if ( window.matchMedia( '(display-mode: standalone)' ).matches ||
            window.navigator.standalone === true )
        {
            this.isInstalled = true;
            this.hideInstallButton();
        }

        // Check for iOS Safari standalone
        if ( window.navigator.standalone === true )
        {
            document.body.classList.add( 'pwa-ios-standalone' );
        }
    }

    /**
     * Sync offline data
     */
    async syncOfflineData ()
    {
        if ( 'serviceWorker' in navigator && navigator.serviceWorker.controller )
        {
            try
            {
                await navigator.serviceWorker.ready;

                if ( 'sync' in window.ServiceWorkerRegistration.prototype )
                {
                    await this.serviceWorker.sync.register( 'background-sync' );
                    console.log( '[PWA] Background sync registered' );
                }
            } catch ( error )
            {
                console.error( '[PWA] Background sync failed:', error );
            }
        }
    }

    /**
     * Handle service worker messages
     */
    handleServiceWorkerMessage ( data )
    {
        console.log( '[PWA] Message from service worker:', data );

        if ( data.type === 'CACHE_UPDATED' )
        {
            this.showNotification( 'App Updated', 'New content is available.' );
        }

        if ( data.type === 'OFFLINE_FALLBACK' )
        {
            this.showNotification( 'Offline Mode', 'You\'re viewing cached content.' );
        }
    }

    /**
     * Track installation events
     */
    async trackInstallationEvent ( event )
    {
        try
        {
            await fetch( '/api/pwa/track-installation', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'PWA'
                },
                body: JSON.stringify( {
                    event,
                    platform: navigator.platform,
                    user_agent: navigator.userAgent,
                    timestamp: Date.now()
                } )
            } );
        } catch ( error )
        {
            console.error( '[PWA] Failed to track installation event:', error );
        }
    }

    /**
     * Setup keyboard shortcuts
     */
    setupKeyboardShortcuts ()
    {
        document.addEventListener( 'keydown', ( event ) =>
        {
            // Ctrl/Cmd + Shift + I for install prompt
            if ( ( event.ctrlKey || event.metaKey ) && event.shiftKey && event.key === 'I' )
            {
                event.preventDefault();
                if ( this.deferredPrompt )
                {
                    this.promptInstall();
                }
            }

            // Ctrl/Cmd + Shift + U for update
            if ( ( event.ctrlKey || event.metaKey ) && event.shiftKey && event.key === 'U' )
            {
                event.preventDefault();
                if ( this.serviceWorker && this.serviceWorker.waiting )
                {
                    this.updateApp();
                }
            }
        } );
    }

    /**
     * Cache important URLs
     */
    async cacheUrls ( urls )
    {
        if ( 'serviceWorker' in navigator && navigator.serviceWorker.controller )
        {
            navigator.serviceWorker.controller.postMessage( {
                type: 'CACHE_URLS',
                urls
            } );
        }
    }

    /**
     * Get PWA status
     */
    getStatus ()
    {
        return {
            isInstalled: this.isInstalled,
            isOnline: this.isOnline,
            serviceWorkerReady: !!this.serviceWorker,
            notificationPermission: this.notificationPermission,
            canInstall: !!this.deferredPrompt,
            canUpdate: !!( this.serviceWorker && this.serviceWorker.waiting )
        };
    }

    /**
     * Enable debug mode
     */
    enableDebug ()
    {
        window.PWA_DEBUG = true;
        console.log( '[PWA] Debug mode enabled' );
        console.log( '[PWA] Current status:', this.getStatus() );
    }
}

// Initialize PWA when DOM is ready
if ( document.readyState === 'loading' )
{
    document.addEventListener( 'DOMContentLoaded', () =>
    {
        window.pwaManager = new PWAManager();
    } );
} else
{
    window.pwaManager = new PWAManager();
}

// Export for use in other scripts
if ( typeof module !== 'undefined' && module.exports )
{
    module.exports = PWAManager;
}
