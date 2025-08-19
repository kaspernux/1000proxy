import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

if ( !window.Echo )
{
    const options = {
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY || 'local',
        // Pusher Channels requires a cluster when using their service. Provide a safe default.
        // This is ignored by self-hosted websocket backends but satisfies pusher-js checks.
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
        // For Pusher Cloud, force TLS and allow the library to choose transports/fallbacks.
        forceTLS: true,
    };

    // Only set custom host/ports when explicitly configured. If not set,
    // pusher-js will connect to Pusher Cloud using key+cluster automatically.
    if ( import.meta.env.VITE_PUSHER_HOST )
    {
    options.wsHost = import.meta.env.VITE_PUSHER_HOST;
    options.wsPort = import.meta.env.VITE_PUSHER_PORT || 6001;
    options.wssPort = import.meta.env.VITE_PUSHER_PORT || 6001;
    options.enabledTransports = [ 'ws', 'wss' ];
    options.disableStats = true;
    options.forceTLS = ( import.meta.env.VITE_PUSHER_SCHEME || 'https' ) === 'https';
    }

    window.Echo = new Echo( options );
}

window.Echo.channel( 'exports' )
    .listen( '.export.ready', ( e ) =>
    {
        // Trigger Livewire refresh
        if ( window.Livewire )
        {
            window.Livewire.dispatch( 'refreshExportNotifications' );
        }
        // Optional toast
        if ( window.dispatchEvent )
        {
            window.dispatchEvent( new CustomEvent( 'notify', { detail: { message: 'Export ready: ' + ( e.path || '' ) } } ) );
        }
    } );

// Server status updates
window.Echo.channel( 'servers' )
    .listen( '.server.status', ( e ) =>
    {
        if ( window.Livewire )
        {
            window.Livewire.dispatch( 'serverStatusUpdated', { id: e.id, status: e.status, metrics: e.metrics || {} } );
            // Optionally refresh infra widget
            window.Livewire.dispatch( 'refreshInfrastructureHealth' );
        }
    } );

// Order paid events (e.g., update revenue widgets, KPIs)
window.Echo.channel( 'orders' )
    .listen( '.order.paid', ( e ) =>
    {
        if ( window.Livewire )
        {
            window.Livewire.dispatch( 'orderPaid', { order: e.order || null } );
            window.Livewire.dispatch( 'refreshRevenueMetrics' );
        }
        if ( window.dispatchEvent )
        {
            window.dispatchEvent( new CustomEvent( 'notify', { detail: { message: 'Order paid: #' + ( e.order?.id || '' ) } } ) );
        }
    } );
