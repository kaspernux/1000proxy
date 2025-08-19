
// Service Worker for 1000Proxy PWA vv1.2.0
// Generated: 2025-07-14T16:51:41.413472Z

const CACHE_NAME = 'pwa-sw-v1.2.0';
const OFFLINE_URL = '/offline';
const API_CACHE_NAME = 'api-cache-v1.2.0';
const STATIC_CACHE_NAME = 'static-cache-v1.2.0';
const DYNAMIC_CACHE_NAME = 'dynamic-cache-v1.2.0';

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
self.addEventListener( 'install', event =>
{
    console.log( '[SW] Install event' );

    event.waitUntil(
        caches.open( STATIC_CACHE_NAME )
            .then( cache =>
            {
                console.log( '[SW] Precaching static resources' );
                return cache.addAll( PRECACHE_URLS );
            } )
            .then( () =>
            {
                return self.skipWaiting();
            } )
            .catch( error =>
            {
                console.error( '[SW] Install failed:', error );
            } )
    );
} );

// Activate event - clean up old caches
self.addEventListener( 'activate', event =>
{
    console.log( '[SW] Activate event' );

    event.waitUntil(
        caches.keys().then( cacheNames =>
        {
            return Promise.all(
                cacheNames.map( cacheName =>
                {
                    if ( cacheName.startsWith( 'pwa-sw-' ) &&
                        cacheName !== CACHE_NAME &&
                        cacheName !== STATIC_CACHE_NAME &&
                        cacheName !== API_CACHE_NAME &&
                        cacheName !== DYNAMIC_CACHE_NAME )
                    {
                        console.log( '[SW] Deleting old cache:', cacheName );
                        return caches.delete( cacheName );
                    }
                } )
            );
        } ).then( () =>
        {
            console.log( '[SW] Claiming clients' );
            return self.clients.claim();
        } )
    );
} );

// Fetch event - serve cached content when offline
self.addEventListener( 'fetch', event =>
{
    const { request } = event;
    const url = new URL( request.url );

    // Skip non-GET requests
    if ( request.method !== 'GET' ) return;

    // Skip chrome-extension requests
    if ( url.protocol === 'chrome-extension:' ) return;

    // Handle different request types
    if ( url.pathname.startsWith( '/api/' ) )
    {
        // API requests - Network first, cache fallback
        event.respondWith( handleApiRequest( request ) );
    } else if ( isStaticAsset( url.pathname ) )
    {
        // Static assets - Cache first
        event.respondWith( handleStaticAsset( request ) );
    } else if ( url.pathname.startsWith( '/admin' ) || url.pathname.startsWith( '/account' ) || url.pathname === '/' )
    {
        // App pages - Network first with offline fallback
        event.respondWith( handleAppPage( request ) );
    } else
    {
        // Other requests - Network first
        event.respondWith( handleOtherRequest( request ) );
    }
} );

// Handle API requests with network-first strategy
async function handleApiRequest ( request )
{
    try
    {
        const networkResponse = await fetch( request );

        if ( networkResponse.ok )
        {
            const cache = await caches.open( API_CACHE_NAME );
            cache.put( request, networkResponse.clone() );
        }

        return networkResponse;
    } catch ( error )
    {
        console.log( '[SW] API network failed, trying cache:', error );

        const cachedResponse = await caches.match( request );
        if ( cachedResponse )
        {
            return cachedResponse;
        }

        // Return offline API response
        return new Response( JSON.stringify( {
            error: 'Offline',
            message: 'This feature is not available offline'
        } ), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        } );
    }
}

// Handle static assets with cache-first strategy
async function handleStaticAsset ( request )
{
    const cachedResponse = await caches.match( request );

    if ( cachedResponse )
    {
        return cachedResponse;
    }

    try
    {
        const networkResponse = await fetch( request );

        if ( networkResponse.ok )
        {
            const cache = await caches.open( STATIC_CACHE_NAME );
            cache.put( request, networkResponse.clone() );
        }

        return networkResponse;
    } catch ( error )
    {
        console.log( '[SW] Static asset failed:', error );

        // Return placeholder for images
        if ( request.destination === 'image' )
        {
            return new Response(
                '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200"><rect width="200" height="200" fill="#f3f4f6"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#6b7280">Offline</text></svg>',
                { headers: { 'Content-Type': 'image/svg+xml' } }
            );
        }

        throw error;
    }
}

// Handle app pages with network-first strategy
async function handleAppPage ( request )
{
    try
    {
        const networkResponse = await fetch( request );

        if ( networkResponse.ok )
        {
            const cache = await caches.open( DYNAMIC_CACHE_NAME );
            cache.put( request, networkResponse.clone() );
        }

        return networkResponse;
    } catch ( error )
    {
        console.log( '[SW] App page network failed, trying cache:', error );

        const cachedResponse = await caches.match( request );
        if ( cachedResponse )
        {
            return cachedResponse;
        }

        // Return offline page
        return caches.match( OFFLINE_URL );
    }
}

// Handle other requests
async function handleOtherRequest ( request )
{
    try
    {
        return await fetch( request );
    } catch ( error )
    {
        const cachedResponse = await caches.match( request );
        if ( cachedResponse )
        {
            return cachedResponse;
        }

        throw error;
    }
}

// Check if URL is a static asset
function isStaticAsset ( pathname )
{
    return /\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/i.test( pathname );
}

// Push notification event
self.addEventListener( 'push', event =>
{
    console.log( '[SW] Push received:', event );

    const options = {
        body: 'Default notification body',
        icon: '/images/icons/icon-192x192.png',
        badge: '/images/icons/badge-72x72.png',
        vibrate: [ 100, 50, 100 ],
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

    if ( event.data )
    {
        const data = event.data.json();
        options.title = data.title || 'New notification';
        options.body = data.body || options.body;
        options.icon = data.icon || options.icon;
        options.data = { ...options.data, ...data };
    }

    event.waitUntil(
        self.registration.showNotification( options.title || '1000Proxy', options )
    );
} );

// Notification click event
self.addEventListener( 'notificationclick', event =>
{
    console.log( '[SW] Notification click:', event );

    event.notification.close();

    const action = event.action;
    const data = event.notification.data;

    if ( action === 'close' )
    {
        return;
    }

    let url = '/';
    if ( action === 'explore' && data.url )
    {
        url = data.url;
    }

    event.waitUntil(
        clients.matchAll( { type: 'window' } ).then( clientList =>
        {
            // Check if there's already a window/tab open with the target URL
            for ( const client of clientList )
            {
                if ( client.url === url && 'focus' in client )
                {
                    return client.focus();
                }
            }

            // If not, open new window/tab
            if ( clients.openWindow )
            {
                return clients.openWindow( url );
            }
        } )
    );
} );

// Background sync event
self.addEventListener( 'sync', event =>
{
    console.log( '[SW] Background sync:', event.tag );

    if ( event.tag === 'background-sync' )
    {
        event.waitUntil( doBackgroundSync() );
    }
} );

// Perform background sync
async function doBackgroundSync ()
{
    try
    {
        // Sync offline data when back online
        const cache = await caches.open( API_CACHE_NAME );
        const requests = await cache.keys();

        for ( const request of requests )
        {
            if ( request.url.includes( '/api/' ) )
            {
                try
                {
                    await fetch( request );
                } catch ( error )
                {
                    console.log( '[SW] Background sync failed for:', request.url );
                }
            }
        }

        console.log( '[SW] Background sync completed' );
    } catch ( error )
    {
        console.error( '[SW] Background sync error:', error );
    }
}

// Message event for communication with app
self.addEventListener( 'message', event =>
{
    console.log( '[SW] Message received:', event.data );

    if ( event.data && event.data.type === 'SKIP_WAITING' )
    {
        self.skipWaiting();
    }

    if ( event.data && event.data.type === 'CACHE_URLS' )
    {
        const urls = event.data.urls;
        caches.open( DYNAMIC_CACHE_NAME ).then( cache =>
        {
            cache.addAll( urls );
        } );
    }
} );

console.log( '[SW] Service Worker loaded successfully' );
