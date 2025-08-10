import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

if ( !window.Echo )
{
    window.Echo = new Echo( {
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY || 'local',
        wsHost: import.meta.env.VITE_PUSHER_HOST || window.location.hostname,
        wsPort: import.meta.env.VITE_PUSHER_PORT || 6001,
        wssPort: import.meta.env.VITE_PUSHER_PORT || 6001,
        forceTLS: ( import.meta.env.VITE_PUSHER_SCHEME || 'https' ) === 'https',
        enabledTransports: [ 'ws', 'wss' ],
        disableStats: true,
    } );
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
