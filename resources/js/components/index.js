/**
 * Advanced UI Components Index
 * Centralized registration for all Alpine.js components
 */

// Import all component modules
import dropdown from './dropdown.js';
import toggle from './toggle.js';
import modal from './modal.js';
import progress from './progress.js';
import toast from './toast.js';
import fileUpload from './file-upload.js';
import datePicker from './date-picker.js';
import themeSwitcher from './theme-switcher.js';

// Dashboard Components
import dashboardChart from './dashboard-chart.js';
import serverMap from './server-map.js';
import trafficMonitor from './traffic-monitor.js';
import revenueAnalytics from './revenue-analytics.js';
import activityTimeline from './activity-timeline.js';
import systemHealth from './system-health.js';

// Component registration function
export function registerComponents ()
{
    // UI Components
    Alpine.data( 'dropdown', dropdown );
    Alpine.data( 'toggle', toggle );
    Alpine.data( 'modal', modal );
    Alpine.data( 'progress', progress );
    Alpine.data( 'toast', toast );
    Alpine.data( 'fileUpload', fileUpload );
    Alpine.data( 'datePicker', datePicker );
    Alpine.data( 'themeSwitcher', themeSwitcher );

    // Dashboard Components
    Alpine.data( 'dashboardChart', dashboardChart );
    Alpine.data( 'serverMap', serverMap );
    Alpine.data( 'trafficMonitor', trafficMonitor );
    Alpine.data( 'revenueAnalytics', revenueAnalytics );
    Alpine.data( 'activityTimeline', activityTimeline );
    Alpine.data( 'systemHealth', systemHealth );
}

// Auto-register components when Alpine starts
document.addEventListener( 'alpine:init', () =>
{
    registerComponents();
} );

// Export individual components for direct use
export
{
    dropdown,
    toggle,
    modal,
    progress,
    toast,
    fileUpload,
    datePicker,
    themeSwitcher,
    // Dashboard Components
    dashboardChart,
    serverMap,
    trafficMonitor,
    revenueAnalytics,
    activityTimeline,
    systemHealth
};

// Global toast notification helper
window.showNotification = function ( type, message, options = {} )
{
    const event = new CustomEvent( 'show-notification', {
        detail: { type, message, ...options }
    } );
    window.dispatchEvent( event );
};

// Convenience methods for notifications
window.showSuccess = ( message, options ) => window.showNotification( 'success', message, options );
window.showError = ( message, options ) => window.showNotification( 'error', message, options );
window.showWarning = ( message, options ) => window.showNotification( 'warning', message, options );
window.showInfo = ( message, options ) => window.showNotification( 'info', message, options );

// Component utilities
export const ComponentUtils = {
    // Generate unique component ID
    generateId ( prefix = 'component' )
    {
        return `${ prefix }-${ Math.random().toString( 36 ).substring( 2 ) }-${ Date.now().toString( 36 ) }`;
    },

    // Format file size
    formatFileSize ( bytes )
    {
        if ( bytes === 0 ) return '0 Bytes';
        const k = 1024;
        const sizes = [ 'Bytes', 'KB', 'MB', 'GB', 'TB' ];
        const i = Math.floor( Math.log( bytes ) / Math.log( k ) );
        return parseFloat( ( bytes / Math.pow( k, i ) ).toFixed( 2 ) ) + ' ' + sizes[ i ];
    },

    // Debounce function
    debounce ( func, wait, immediate = false )
    {
        let timeout;
        return function executedFunction ( ...args )
        {
            const later = () =>
            {
                timeout = null;
                if ( !immediate ) func.apply( this, args );
            };
            const callNow = immediate && !timeout;
            clearTimeout( timeout );
            timeout = setTimeout( later, wait );
            if ( callNow ) func.apply( this, args );
        };
    },

    // Throttle function
    throttle ( func, limit )
    {
        let inThrottle;
        return function ()
        {
            const args = arguments;
            const context = this;
            if ( !inThrottle )
            {
                func.apply( context, args );
                inThrottle = true;
                setTimeout( () => inThrottle = false, limit );
            }
        };
    },

    // Check if element is in viewport
    isInViewport ( element )
    {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= ( window.innerHeight || document.documentElement.clientHeight ) &&
            rect.right <= ( window.innerWidth || document.documentElement.clientWidth )
        );
    },

    // Smooth scroll to element
    scrollToElement ( element, offset = 0 )
    {
        const elementPosition = element.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.pageYOffset - offset;

        window.scrollTo( {
            top: offsetPosition,
            behavior: 'smooth'
        } );
    },

    // Copy text to clipboard
    async copyToClipboard ( text )
    {
        try
        {
            await navigator.clipboard.writeText( text );
            return true;
        } catch ( err )
        {
            // Fallback for older browsers
            const textArea = document.createElement( 'textarea' );
            textArea.value = text;
            document.body.appendChild( textArea );
            textArea.focus();
            textArea.select();
            try
            {
                document.execCommand( 'copy' );
                return true;
            } catch ( err )
            {
                return false;
            } finally
            {
                document.body.removeChild( textArea );
            }
        }
    },

    // Local storage helpers
    storage: {
        set ( key, value )
        {
            try
            {
                localStorage.setItem( key, JSON.stringify( value ) );
                return true;
            } catch ( e )
            {
                return false;
            }
        },

        get ( key, defaultValue = null )
        {
            try
            {
                const item = localStorage.getItem( key );
                return item ? JSON.parse( item ) : defaultValue;
            } catch ( e )
            {
                return defaultValue;
            }
        },

        remove ( key )
        {
            try
            {
                localStorage.removeItem( key );
                return true;
            } catch ( e )
            {
                return false;
            }
        },

        clear ()
        {
            try
            {
                localStorage.clear();
                return true;
            } catch ( e )
            {
                return false;
            }
        }
    },

    // Cookie helpers
    cookies: {
        set ( name, value, days = 7 )
        {
            const expires = new Date();
            expires.setTime( expires.getTime() + ( days * 24 * 60 * 60 * 1000 ) );
            document.cookie = `${ name }=${ value };expires=${ expires.toUTCString() };path=/`;
        },

        get ( name )
        {
            const nameEQ = name + "=";
            const ca = document.cookie.split( ';' );
            for ( let i = 0; i < ca.length; i++ )
            {
                let c = ca[ i ];
                while ( c.charAt( 0 ) === ' ' ) c = c.substring( 1, c.length );
                if ( c.indexOf( nameEQ ) === 0 ) return c.substring( nameEQ.length, c.length );
            }
            return null;
        },

        remove ( name )
        {
            document.cookie = `${ name }=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;`;
        }
    }
};

// Make utilities globally available
window.ComponentUtils = ComponentUtils;
