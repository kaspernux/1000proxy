/**
 * WebSocket Integration System for 1000proxy
 * Provides real-time communication, notifications, and live updates
 * Built with automatic reconnection, heartbeat, and event management
 */

// WebSocket Manager Class
class WebSocketManager
{
    constructor ( options = {} )
    {
        this.options = {
            url: options.url || this.getWebSocketUrl(),
            reconnectInterval: options.reconnectInterval || 3000,
            maxReconnectAttempts: options.maxReconnectAttempts || 10,
            heartbeatInterval: options.heartbeatInterval || 30000,
            debug: options.debug || false,
            enableCompression: options.enableCompression || true,
            protocols: options.protocols || [],
            ...options
        };

        this.socket = null;
        this.isConnected = false;
        this.reconnectAttempts = 0;
        this.heartbeatTimer = null;
        this.reconnectTimer = null;
        this.eventListeners = new Map();
        this.messageQueue = [];
        this.connectionId = null;
        this.lastPingTime = null;
        this.latency = 0;

        // Event emitter for custom events
        this.eventBus = new EventTarget();

        // Bind methods
        this.connect = this.connect.bind( this );
        this.disconnect = this.disconnect.bind( this );
        this.reconnect = this.reconnect.bind( this );
        this.send = this.send.bind( this );
        this.handleMessage = this.handleMessage.bind( this );
        this.handleOpen = this.handleOpen.bind( this );
        this.handleClose = this.handleClose.bind( this );
        this.handleError = this.handleError.bind( this );
    }

    // Get WebSocket URL based on current location
    getWebSocketUrl ()
    {
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const host = window.location.host;
        return `${ protocol }//${ host }/ws`;
    }

    // Connect to WebSocket server
    connect ()
    {
        if ( this.socket && this.socket.readyState === WebSocket.OPEN )
        {
            this.log( 'Already connected' );
            return Promise.resolve();
        }

        return new Promise( ( resolve, reject ) =>
        {
            try
            {
                this.log( 'Connecting to WebSocket...', this.options.url );

                this.socket = new WebSocket( this.options.url, this.options.protocols );

                // Set up event listeners
                this.socket.onopen = ( event ) =>
                {
                    this.handleOpen( event );
                    resolve();
                };

                this.socket.onmessage = this.handleMessage;
                this.socket.onclose = this.handleClose;
                this.socket.onerror = ( error ) =>
                {
                    this.handleError( error );
                    reject( error );
                };

            } catch ( error )
            {
                this.log( 'Connection error:', error );
                reject( error );
            }
        } );
    }

    // Disconnect from WebSocket server
    disconnect ()
    {
        this.isConnected = false;
        this.clearTimers();

        if ( this.socket )
        {
            this.socket.onopen = null;
            this.socket.onmessage = null;
            this.socket.onclose = null;
            this.socket.onerror = null;

            if ( this.socket.readyState === WebSocket.OPEN )
            {
                this.socket.close( 1000, 'Client disconnect' );
            }

            this.socket = null;
        }

        this.emit( 'disconnected' );
        this.log( 'Disconnected from WebSocket' );
    }

    // Handle connection open
    handleOpen ( event )
    {
        this.log( 'Connected to WebSocket' );
        this.isConnected = true;
        this.reconnectAttempts = 0;

        // Start heartbeat
        this.startHeartbeat();

        // Send queued messages
        this.processMessageQueue();

        // Send authentication if available
        this.authenticate();

        this.emit( 'connected', { event } );
    }

    // Handle incoming messages
    handleMessage ( event )
    {
        try
        {
            const data = JSON.parse( event.data );
            this.log( 'Received message:', data );

            // Handle system messages
            switch ( data.type )
            {
                case 'pong':
                    this.handlePong( data );
                    break;
                case 'auth_response':
                    this.handleAuthResponse( data );
                    break;
                case 'notification':
                    this.handleNotification( data );
                    break;
                case 'server_status':
                    this.handleServerStatus( data );
                    break;
                case 'user_presence':
                    this.handleUserPresence( data );
                    break;
                case 'chat_message':
                    this.handleChatMessage( data );
                    break;
                case 'live_update':
                    this.handleLiveUpdate( data );
                    break;
                default:
                    // Emit custom event
                    this.emit( data.type, data );
                    break;
            }

            // Always emit raw message event
            this.emit( 'message', data );

        } catch ( error )
        {
            this.log( 'Error parsing message:', error );
            this.emit( 'error', { type: 'parse_error', error, data: event.data } );
        }
    }

    // Handle connection close
    handleClose ( event )
    {
        this.log( 'WebSocket closed:', event.code, event.reason );
        this.isConnected = false;
        this.clearTimers();

        this.emit( 'disconnected', {
            code: event.code,
            reason: event.reason,
            wasClean: event.wasClean
        } );

        // Auto-reconnect if not a clean close
        if ( !event.wasClean && this.reconnectAttempts < this.options.maxReconnectAttempts )
        {
            this.scheduleReconnect();
        }
    }

    // Handle connection error
    handleError ( error )
    {
        this.log( 'WebSocket error:', error );
        this.emit( 'error', { type: 'connection_error', error } );
    }

    // Schedule reconnection
    scheduleReconnect ()
    {
        if ( this.reconnectTimer )
        {
            clearTimeout( this.reconnectTimer );
        }

        const delay = this.options.reconnectInterval * Math.pow( 1.5, this.reconnectAttempts );
        this.log( `Scheduling reconnect in ${ delay }ms (attempt ${ this.reconnectAttempts + 1 })` );

        this.reconnectTimer = setTimeout( () =>
        {
            this.reconnectAttempts++;
            this.reconnect();
        }, delay );
    }

    // Reconnect to WebSocket
    reconnect ()
    {
        this.log( 'Attempting to reconnect...' );
        this.connect().catch( ( error ) =>
        {
            this.log( 'Reconnect failed:', error );
            if ( this.reconnectAttempts < this.options.maxReconnectAttempts )
            {
                this.scheduleReconnect();
            } else
            {
                this.emit( 'max_reconnect_attempts_reached' );
            }
        } );
    }

    // Send message
    send ( type, data = {} )
    {
        const message = {
            type,
            data,
            timestamp: Date.now(),
            id: this.generateMessageId()
        };

        if ( this.isConnected && this.socket.readyState === WebSocket.OPEN )
        {
            try
            {
                this.socket.send( JSON.stringify( message ) );
                this.log( 'Sent message:', message );
                return true;
            } catch ( error )
            {
                this.log( 'Error sending message:', error );
                this.queueMessage( message );
                return false;
            }
        } else
        {
            this.queueMessage( message );
            return false;
        }
    }

    // Queue message for later sending
    queueMessage ( message )
    {
        this.messageQueue.push( message );
        this.log( 'Message queued:', message );

        // Limit queue size
        if ( this.messageQueue.length > 100 )
        {
            this.messageQueue.shift();
        }
    }

    // Process queued messages
    processMessageQueue ()
    {
        if ( this.messageQueue.length === 0 ) return;

        this.log( `Processing ${ this.messageQueue.length } queued messages` );

        const messages = [ ...this.messageQueue ];
        this.messageQueue = [];

        messages.forEach( message =>
        {
            try
            {
                this.socket.send( JSON.stringify( message ) );
            } catch ( error )
            {
                this.log( 'Error sending queued message:', error );
                this.queueMessage( message );
            }
        } );
    }

    // Start heartbeat
    startHeartbeat ()
    {
        if ( this.heartbeatTimer )
        {
            clearInterval( this.heartbeatTimer );
        }

        this.heartbeatTimer = setInterval( () =>
        {
            if ( this.isConnected )
            {
                this.ping();
            }
        }, this.options.heartbeatInterval );
    }

    // Send ping
    ping ()
    {
        this.lastPingTime = Date.now();
        this.send( 'ping', { timestamp: this.lastPingTime } );
    }

    // Handle pong response
    handlePong ( data )
    {
        if ( this.lastPingTime )
        {
            this.latency = Date.now() - this.lastPingTime;
            this.emit( 'latency_update', { latency: this.latency } );
        }
    }

    // Authenticate with server
    authenticate ()
    {
        const token = this.getAuthToken();
        if ( token )
        {
            this.send( 'auth', { token } );
        }
    }

    // Handle authentication response
    handleAuthResponse ( data )
    {
        if ( data.success )
        {
            this.connectionId = data.connectionId;
            this.emit( 'authenticated', data );
        } else
        {
            this.emit( 'auth_failed', data );
        }
    }

    // Handle notification
    handleNotification ( data )
    {
        this.emit( 'notification', data );

        // Show browser notification if supported
        if ( 'Notification' in window && Notification.permission === 'granted' )
        {
            new Notification( data.title || 'New Notification', {
                body: data.message,
                icon: data.icon || '/favicon.ico',
                tag: data.tag || 'default'
            } );
        }
    }

    // Handle server status update
    handleServerStatus ( data )
    {
        this.emit( 'server_status_update', data );
    }

    // Handle user presence update
    handleUserPresence ( data )
    {
        this.emit( 'user_presence_update', data );
    }

    // Handle chat message
    handleChatMessage ( data )
    {
        this.emit( 'chat_message', data );
    }

    // Handle live update
    handleLiveUpdate ( data )
    {
        this.emit( 'live_update', data );
    }

    // Event listener management
    on ( event, callback )
    {
        if ( !this.eventListeners.has( event ) )
        {
            this.eventListeners.set( event, new Set() );
        }
        this.eventListeners.get( event ).add( callback );

        // Also add to EventTarget for compatibility
        this.eventBus.addEventListener( event, callback );

        return () => this.off( event, callback );
    }

    off ( event, callback )
    {
        if ( this.eventListeners.has( event ) )
        {
            this.eventListeners.get( event ).delete( callback );
        }
        this.eventBus.removeEventListener( event, callback );
    }

    emit ( event, data = {} )
    {
        // Emit to custom listeners
        if ( this.eventListeners.has( event ) )
        {
            this.eventListeners.get( event ).forEach( callback =>
            {
                try
                {
                    callback( data );
                } catch ( error )
                {
                    this.log( 'Error in event listener:', error );
                }
            } );
        }

        // Emit to EventTarget
        this.eventBus.dispatchEvent( new CustomEvent( event, { detail: data } ) );
    }

    // Utility methods
    getAuthToken ()
    {
        return localStorage.getItem( 'auth_token' ) ||
            document.querySelector( 'meta[name="csrf-token"]' )?.getAttribute( 'content' );
    }

    generateMessageId ()
    {
        return `msg_${ Date.now() }_${ Math.random().toString( 36 ).substr( 2, 9 ) }`;
    }

    clearTimers ()
    {
        if ( this.heartbeatTimer )
        {
            clearInterval( this.heartbeatTimer );
            this.heartbeatTimer = null;
        }

        if ( this.reconnectTimer )
        {
            clearTimeout( this.reconnectTimer );
            this.reconnectTimer = null;
        }
    }

    log ( ...args )
    {
        if ( this.options.debug )
        {
            console.log( '[WebSocket]', ...args );
        }
    }

    // Public API methods
    getStatus ()
    {
        return {
            isConnected: this.isConnected,
            connectionId: this.connectionId,
            reconnectAttempts: this.reconnectAttempts,
            latency: this.latency,
            queuedMessages: this.messageQueue.length
        };
    }

    // Specialized messaging methods
    sendNotification ( title, message, options = {} )
    {
        return this.send( 'send_notification', {
            title,
            message,
            ...options
        } );
    }

    sendChatMessage ( message, channel = 'general' )
    {
        return this.send( 'chat_message', {
            message,
            channel,
            timestamp: Date.now()
        } );
    }

    updateUserPresence ( status = 'online' )
    {
        return this.send( 'user_presence', {
            status,
            timestamp: Date.now()
        } );
    }

    requestServerStatus ( serverId = null )
    {
        return this.send( 'request_server_status', {
            serverId
        } );
    }

    subscribeToUpdates ( channels = [] )
    {
        return this.send( 'subscribe', {
            channels
        } );
    }

    unsubscribeFromUpdates ( channels = [] )
    {
        return this.send( 'unsubscribe', {
            channels
        } );
    }
}

// Global WebSocket instance
window.WebSocketManager = null;

// Initialize WebSocket when DOM is ready
document.addEventListener( 'DOMContentLoaded', () =>
{
    // Initialize WebSocket manager
    window.WebSocketManager = new WebSocketManager( {
        debug: true,
        reconnectInterval: 3000,
        maxReconnectAttempts: 10,
        heartbeatInterval: 30000
    } );

    // Auto-connect
    window.WebSocketManager.connect().catch( error =>
    {
        console.error( 'Failed to connect to WebSocket:', error );
    } );
} );

// Alpine.js integration
document.addEventListener( 'alpine:init', () =>
{
    // WebSocket magic property
    Alpine.magic( 'ws', () =>
    {
        return {
            send: ( type, data ) => window.WebSocketManager?.send( type, data ),
            on: ( event, callback ) => window.WebSocketManager?.on( event, callback ),
            off: ( event, callback ) => window.WebSocketManager?.off( event, callback ),
            getStatus: () => window.WebSocketManager?.getStatus(),
            sendNotification: ( title, message, options ) => window.WebSocketManager?.sendNotification( title, message, options ),
            sendChatMessage: ( message, channel ) => window.WebSocketManager?.sendChatMessage( message, channel ),
            updatePresence: ( status ) => window.WebSocketManager?.updateUserPresence( status ),
            requestServerStatus: ( serverId ) => window.WebSocketManager?.requestServerStatus( serverId ),
            subscribe: ( channels ) => window.WebSocketManager?.subscribeToUpdates( channels ),
            unsubscribe: ( channels ) => window.WebSocketManager?.unsubscribeFromUpdates( channels )
        };
    } );

    // Real-time data store
    Alpine.data( 'realTimeData', () => ( {
        isConnected: false,
        connectionStatus: 'disconnected',
        latency: 0,
        notifications: [],
        serverStatuses: {},
        userPresence: {},
        chatMessages: [],
        liveUpdates: [],

        init ()
        {
            this.setupWebSocketListeners();
        },

        setupWebSocketListeners ()
        {
            if ( !window.WebSocketManager ) return;

            // Connection status
            window.WebSocketManager.on( 'connected', () =>
            {
                this.isConnected = true;
                this.connectionStatus = 'connected';
            } );

            window.WebSocketManager.on( 'disconnected', () =>
            {
                this.isConnected = false;
                this.connectionStatus = 'disconnected';
            } );

            // Latency updates
            window.WebSocketManager.on( 'latency_update', ( data ) =>
            {
                this.latency = data.latency;
            } );

            // Notifications
            window.WebSocketManager.on( 'notification', ( data ) =>
            {
                this.notifications.unshift( {
                    id: Date.now(),
                    ...data,
                    timestamp: Date.now()
                } );

                // Limit notifications
                if ( this.notifications.length > 50 )
                {
                    this.notifications = this.notifications.slice( 0, 50 );
                }
            } );

            // Server status updates
            window.WebSocketManager.on( 'server_status_update', ( data ) =>
            {
                this.serverStatuses[ data.serverId ] = data;
            } );

            // User presence updates
            window.WebSocketManager.on( 'user_presence_update', ( data ) =>
            {
                this.userPresence[ data.userId ] = data;
            } );

            // Chat messages
            window.WebSocketManager.on( 'chat_message', ( data ) =>
            {
                this.chatMessages.push( {
                    id: Date.now(),
                    ...data
                } );

                // Limit chat history
                if ( this.chatMessages.length > 100 )
                {
                    this.chatMessages = this.chatMessages.slice( -100 );
                }
            } );

            // Live updates
            window.WebSocketManager.on( 'live_update', ( data ) =>
            {
                this.liveUpdates.unshift( {
                    id: Date.now(),
                    ...data,
                    timestamp: Date.now()
                } );

                // Limit updates
                if ( this.liveUpdates.length > 30 )
                {
                    this.liveUpdates = this.liveUpdates.slice( 0, 30 );
                }
            } );
        }
    } ) );
} );

// Export for module use
if ( typeof module !== 'undefined' && module.exports )
{
    module.exports = WebSocketManager;
}
