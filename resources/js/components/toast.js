/**
 * Advanced Toast Notification Component
 * Supports multiple types, positions, and animations
 */
export default () => ( {
    // Component State
    notifications: [],
    nextId: 1,

    // Configuration
    position: 'top-right', // top-left, top-right, bottom-left, bottom-right, top-center, bottom-center
    maxNotifications: 5,
    defaultDuration: 5000,
    allowDismiss: true,
    showProgress: true,

    // Animation
    enterAnimation: 'slide-in',
    exitAnimation: 'slide-out',

    // Lifecycle
    init ()
    {
        this.setupEventListeners();
    },

    // Public API
    show ( options = {} )
    {
        const notification = {
            id: this.nextId++,
            type: options.type || 'info',
            title: options.title || '',
            message: options.message || '',
            duration: options.duration !== undefined ? options.duration : this.defaultDuration,
            persistent: options.persistent || false,
            actions: options.actions || [],
            icon: options.icon || null,
            html: options.html || false,
            timestamp: Date.now(),
            progress: 100,
            paused: false,
            removing: false
        };

        // Add to notifications array
        this.notifications.unshift( notification );

        // Limit notifications
        if ( this.notifications.length > this.maxNotifications )
        {
            this.notifications = this.notifications.slice( 0, this.maxNotifications );
        }

        // Start auto-dismiss timer if not persistent
        if ( !notification.persistent && notification.duration > 0 )
        {
            this.startProgressTimer( notification );
        }

        this.$dispatch( 'notification-shown', notification );
        return notification.id;
    },

    // Convenience methods
    success ( message, options = {} )
    {
        return this.show( {
            type: 'success',
            message,
            icon: 'check-circle',
            ...options
        } );
    },

    error ( message, options = {} )
    {
        return this.show( {
            type: 'error',
            message,
            icon: 'x-circle',
            duration: 0, // Don't auto-dismiss errors
            ...options
        } );
    },

    warning ( message, options = {} )
    {
        return this.show( {
            type: 'warning',
            message,
            icon: 'exclamation-triangle',
            ...options
        } );
    },

    info ( message, options = {} )
    {
        return this.show( {
            type: 'info',
            message,
            icon: 'information-circle',
            ...options
        } );
    },

    // Notification Management
    dismiss ( id )
    {
        const notification = this.notifications.find( n => n.id === id );
        if ( !notification || notification.removing ) return;

        notification.removing = true;

        setTimeout( () =>
        {
            this.notifications = this.notifications.filter( n => n.id !== id );
            this.$dispatch( 'notification-dismissed', { id } );
        }, 300 );
    },

    dismissAll ()
    {
        this.notifications.forEach( notification =>
        {
            if ( !notification.removing )
            {
                this.dismiss( notification.id );
            }
        } );
    },

    // Progress Timer
    startProgressTimer ( notification )
    {
        const interval = 50; // Update every 50ms
        const step = ( interval / notification.duration ) * 100;

        const timer = setInterval( () =>
        {
            if ( notification.paused || notification.removing )
            {
                return;
            }

            notification.progress -= step;

            if ( notification.progress <= 0 )
            {
                clearInterval( timer );
                this.dismiss( notification.id );
            }
        }, interval );

        notification.timer = timer;
    },

    pauseTimer ( notification )
    {
        notification.paused = true;
    },

    resumeTimer ( notification )
    {
        notification.paused = false;
    },

    // Event Listeners
    setupEventListeners ()
    {
        // Listen for global notification events
        window.addEventListener( 'show-notification', ( e ) =>
        {
            this.show( e.detail );
        } );

        window.addEventListener( 'dismiss-notification', ( e ) =>
        {
            if ( e.detail.id )
            {
                this.dismiss( e.detail.id );
            } else
            {
                this.dismissAll();
            }
        } );
    },

    // Style Getters
    getContainerClasses ()
    {
        const baseClasses = [
            'fixed z-50 pointer-events-none',
            'flex flex-col gap-2 p-4'
        ];

        // Position classes
        const positionClasses = {
            'top-left': 'top-0 left-0',
            'top-right': 'top-0 right-0',
            'bottom-left': 'bottom-0 left-0',
            'bottom-right': 'bottom-0 right-0',
            'top-center': 'top-0 left-1/2 transform -translate-x-1/2',
            'bottom-center': 'bottom-0 left-1/2 transform -translate-x-1/2'
        };

        return [
            ...baseClasses,
            positionClasses[ this.position ]
        ].join( ' ' );
    },

    getNotificationClasses ( notification )
    {
        const baseClasses = [
            'relative pointer-events-auto w-80 bg-white rounded-lg shadow-lg border',
            'transform transition-all duration-300 ease-in-out overflow-hidden'
        ];

        // Type classes
        const typeClasses = {
            success: 'border-green-200 text-green-800',
            error: 'border-red-200 text-red-800',
            warning: 'border-yellow-200 text-yellow-800',
            info: 'border-blue-200 text-blue-800'
        };

        // Animation classes
        const animationClasses = [];
        if ( notification.removing )
        {
            animationClasses.push( 'opacity-0', 'scale-95', '-translate-y-2' );
        } else
        {
            animationClasses.push( 'opacity-100', 'scale-100', 'translate-y-0' );
        }

        return [
            ...baseClasses,
            typeClasses[ notification.type ],
            ...animationClasses
        ].join( ' ' );
    },

    getProgressClasses ( notification )
    {
        const baseClasses = [
            'absolute bottom-0 left-0 h-1 transition-all duration-75 ease-linear'
        ];

        // Type-specific progress colors
        const typeClasses = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };

        return [
            ...baseClasses,
            typeClasses[ notification.type ]
        ].join( ' ' );
    },

    getIconClasses ( notification )
    {
        const baseClasses = [ 'w-5 h-5 flex-shrink-0' ];

        // Type-specific icon colors
        const typeClasses = {
            success: 'text-green-500',
            error: 'text-red-500',
            warning: 'text-yellow-500',
            info: 'text-blue-500'
        };

        return [
            ...baseClasses,
            typeClasses[ notification.type ]
        ].join( ' ' );
    },

    // Helper Methods
    getIcon ( notification )
    {
        const icons = {
            'check-circle': '✓',
            'x-circle': '✕',
            'exclamation-triangle': '⚠',
            'information-circle': 'ℹ'
        };

        return icons[ notification.icon ] || '';
    },

    formatTimestamp ( timestamp )
    {
        const now = Date.now();
        const diff = now - timestamp;

        if ( diff < 60000 ) return 'just now';
        if ( diff < 3600000 ) return `${ Math.floor( diff / 60000 ) }m ago`;
        if ( diff < 86400000 ) return `${ Math.floor( diff / 3600000 ) }h ago`;
        return `${ Math.floor( diff / 86400000 ) }d ago`;
    },

    // Action Handlers
    handleAction ( notification, action )
    {
        if ( action.handler )
        {
            action.handler( notification );
        }

        if ( action.dismiss !== false )
        {
            this.dismiss( notification.id );
        }

        this.$dispatch( 'notification-action', {
            notification,
            action
        } );
    },

    // Accessibility
    getAriaLabel ( notification )
    {
        return `${ notification.type } notification: ${ notification.title || notification.message }`;
    },

    getAriaLive ( notification )
    {
        return notification.type === 'error' ? 'assertive' : 'polite';
    }
} );
