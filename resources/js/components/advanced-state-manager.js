/**
 * Advanced State Management System for 1000proxy
 * Provides global state management, persistence, and synchronization
 * Built on top of Alpine.js with advanced reactive patterns
 */

// Global State Store
window.StateManager = {
    // Store registry for multiple stores
    stores: new Map(),

    // Global event bus for state changes
    eventBus: null,

    // Configuration
    config: {
        persistenceKey: '1000proxy_state',
        storageType: 'localStorage', // 'localStorage', 'sessionStorage', 'indexedDB'
        syncEnabled: true,
        debugMode: false,
        maxHistorySize: 50,
        validationEnabled: true
    },

    // Initialize the state management system
    init ()
    {
        this.eventBus = new EventTarget();
        this.initializeDefaults();
        this.loadPersistedState();
        this.setupSynchronization();

        if ( this.config.debugMode )
        {
            this.enableDebugMode();
        }
    },

    // Create a new store
    createStore ( name, initialState = {}, options = {} )
    {
        const store = new StateStore( name, initialState, {
            ...this.config,
            ...options,
            eventBus: this.eventBus
        } );

        this.stores.set( name, store );
        return store;
    },

    // Get a store by name
    getStore ( name )
    {
        return this.stores.get( name );
    },

    // Initialize default stores
    initializeDefaults ()
    {
        // User preferences store
        this.createStore( 'userPreferences', {
            theme: 'light',
            language: 'en',
            currency: 'USD',
            timezone: 'UTC',
            notifications: {
                email: true,
                browser: true,
                telegram: false,
                sms: false
            },
            dashboard: {
                layout: 'grid',
                widgets: [ 'servers', 'usage', 'payments', 'support' ],
                autoRefresh: true,
                refreshInterval: 30000
            },
            accessibility: {
                highContrast: false,
                reducedMotion: false,
                fontSize: 'medium',
                screenReader: false
            }
        } );

        // Application state store
        this.createStore( 'appState', {
            isLoading: false,
            isOnline: navigator.onLine,
            currentUser: null,
            activeConnections: [],
            notifications: [],
            errors: [],
            modals: {},
            sidebar: {
                collapsed: false,
                activeSection: 'dashboard'
            },
            breadcrumbs: []
        } );

        // Server management store
        this.createStore( 'serverState', {
            servers: [],
            selectedServer: null,
            serverStats: {},
            connectionLogs: [],
            maintenanceMode: false,
            loadBalancer: {
                enabled: false,
                algorithm: 'round-robin',
                healthChecks: true
            }
        } );

        // Payment and billing store
        this.createStore( 'billingState', {
            wallet: {
                balance: 0,
                currency: 'USD',
                pendingTransactions: []
            },
            paymentMethods: [],
            invoices: [],
            subscriptions: [],
            usageMetrics: {
                bandwidth: 0,
                connections: 0,
                duration: 0
            }
        } );
    },

    // Load persisted state from storage
    loadPersistedState ()
    {
        try
        {
            const persistedData = this.getFromStorage( this.config.persistenceKey );
            if ( persistedData )
            {
                Object.entries( persistedData ).forEach( ( [ storeName, storeData ] ) =>
                {
                    const store = this.stores.get( storeName );
                    if ( store )
                    {
                        store.hydrate( storeData );
                    }
                } );
            }
        } catch ( error )
        {
            console.error( 'Failed to load persisted state:', error );
        }
    },

    // Save state to storage
    persistState ()
    {
        try
        {
            const stateData = {};
            this.stores.forEach( ( store, name ) =>
            {
                if ( store.options.persist !== false )
                {
                    stateData[ name ] = store.serialize();
                }
            } );

            this.saveToStorage( this.config.persistenceKey, stateData );
        } catch ( error )
        {
            console.error( 'Failed to persist state:', error );
        }
    },

    // Setup cross-tab synchronization
    setupSynchronization ()
    {
        if ( !this.config.syncEnabled ) return;

        // Listen for storage changes (cross-tab sync)
        window.addEventListener( 'storage', ( event ) =>
        {
            if ( event.key === this.config.persistenceKey && event.newValue )
            {
                try
                {
                    const newState = JSON.parse( event.newValue );
                    this.synchronizeStores( newState );
                } catch ( error )
                {
                    console.error( 'Failed to synchronize state:', error );
                }
            }
        } );

        // Sync on window focus
        window.addEventListener( 'focus', () =>
        {
            this.loadPersistedState();
        } );

        // Persist on window unload
        window.addEventListener( 'beforeunload', () =>
        {
            this.persistState();
        } );

        // Auto-persist every 30 seconds
        setInterval( () =>
        {
            this.persistState();
        }, 30000 );
    },

    // Synchronize stores with new data
    synchronizeStores ( newState )
    {
        Object.entries( newState ).forEach( ( [ storeName, storeData ] ) =>
        {
            const store = this.stores.get( storeName );
            if ( store && store.options.sync !== false )
            {
                store.synchronize( storeData );
            }
        } );
    },

    // Storage operations
    getFromStorage ( key )
    {
        const storage = this.getStorageProvider();
        const data = storage.getItem( key );
        return data ? JSON.parse( data ) : null;
    },

    saveToStorage ( key, data )
    {
        const storage = this.getStorageProvider();
        storage.setItem( key, JSON.stringify( data ) );
    },

    getStorageProvider ()
    {
        switch ( this.config.storageType )
        {
            case 'sessionStorage':
                return sessionStorage;
            case 'localStorage':
            default:
                return localStorage;
        }
    },

    // Enable debug mode
    enableDebugMode ()
    {
        this.eventBus.addEventListener( 'stateChange', ( event ) =>
        {
            console.group( `State Change: ${ event.detail.storeName }` );
            console.log( 'Action:', event.detail.action );
            console.log( 'Path:', event.detail.path );
            console.log( 'Old Value:', event.detail.oldValue );
            console.log( 'New Value:', event.detail.newValue );
            console.log( 'Timestamp:', new Date( event.detail.timestamp ).toISOString() );
            console.groupEnd();
        } );

        window.stateManager = this; // Expose for debugging
    }
};

// Individual State Store Class
class StateStore
{
    constructor ( name, initialState = {}, options = {} )
    {
        this.name = name;
        this.state = this.createProxy( initialState );
        this.options = options;
        this.eventBus = options.eventBus;
        this.history = [];
        this.validators = new Map();
        this.computed = new Map();
        this.watchers = new Map();
        this.middleware = [];

        this.addToHistory( 'init', '', null, initialState );
    }

    // Create reactive proxy for state
    createProxy ( obj, path = '' )
    {
        return new Proxy( obj, {
            set: ( target, property, value, receiver ) =>
            {
                const fullPath = path ? `${ path }.${ property }` : property;
                const oldValue = target[ property ];

                // Run validation
                if ( this.options.validationEnabled && !this.validate( fullPath, value ) )
                {
                    throw new Error( `Validation failed for ${ fullPath }` );
                }

                // Run middleware
                const middlewareResult = this.runMiddleware( 'set', fullPath, value, oldValue );
                if ( middlewareResult === false )
                {
                    return false;
                }

                // Set the value
                if ( typeof value === 'object' && value !== null && !Array.isArray( value ) )
                {
                    target[ property ] = this.createProxy( value, fullPath );
                } else
                {
                    target[ property ] = value;
                }

                // Record in history
                this.addToHistory( 'set', fullPath, oldValue, value );

                // Emit change event
                this.emitChange( 'set', fullPath, oldValue, value );

                // Update computed properties
                this.updateComputed( fullPath );

                // Run watchers
                this.runWatchers( fullPath, value, oldValue );

                return true;
            },

            get: ( target, property, receiver ) =>
            {
                const value = target[ property ];
                if ( typeof value === 'object' && value !== null && !Array.isArray( value ) )
                {
                    return this.createProxy( value, path ? `${ path }.${ property }` : property );
                }
                return value;
            }
        } );
    }

    // Get state value by path
    get ( path )
    {
        return this.getByPath( this.state, path );
    }

    // Set state value by path
    set ( path, value )
    {
        this.setByPath( this.state, path, value );
    }

    // Update state (merge with existing)
    update ( updates )
    {
        Object.entries( updates ).forEach( ( [ key, value ] ) =>
        {
            this.set( key, value );
        } );
    }

    // Reset state to initial values
    reset ()
    {
        const initialState = this.history[ 0 ]?.newValue || {};
        Object.keys( this.state ).forEach( key =>
        {
            delete this.state[ key ];
        } );
        Object.assign( this.state, this.createProxy( initialState ) );
        this.emitChange( 'reset', '', this.state, initialState );
    }

    // Subscribe to state changes
    subscribe ( callback, path = null )
    {
        const watcherId = `watcher_${ Date.now() }_${ Math.random() }`;
        this.watchers.set( watcherId, {
            callback,
            path,
            active: true
        } );

        return () =>
        {
            this.watchers.delete( watcherId );
        };
    }

    // Add computed property
    addComputed ( name, dependencies, computeFn )
    {
        this.computed.set( name, {
            dependencies,
            computeFn,
            value: null,
            dirty: true
        } );

        // Calculate initial value
        this.updateComputed();

        return () =>
        {
            this.computed.delete( name );
        };
    }

    // Get computed property value
    getComputed ( name )
    {
        const computed = this.computed.get( name );
        if ( !computed ) return undefined;

        if ( computed.dirty )
        {
            this.updateComputed();
        }

        return computed.value;
    }

    // Add validation rule
    addValidator ( path, validatorFn, message = 'Validation failed' )
    {
        const validators = this.validators.get( path ) || [];
        validators.push( { validatorFn, message } );
        this.validators.set( path, validators );

        return () =>
        {
            const updatedValidators = this.validators.get( path )?.filter( v => v.validatorFn !== validatorFn );
            if ( updatedValidators?.length )
            {
                this.validators.set( path, updatedValidators );
            } else
            {
                this.validators.delete( path );
            }
        };
    }

    // Add middleware
    addMiddleware ( middlewareFn )
    {
        this.middleware.push( middlewareFn );

        return () =>
        {
            const index = this.middleware.indexOf( middlewareFn );
            if ( index > -1 )
            {
                this.middleware.splice( index, 1 );
            }
        };
    }

    // Undo last change
    undo ()
    {
        if ( this.history.length > 1 )
        {
            const currentIndex = this.history.length - 1;
            const previousState = this.history[ currentIndex - 1 ];

            if ( previousState )
            {
                this.setByPath( this.state, previousState.path, previousState.newValue );
                this.history.splice( currentIndex, 1 );
                this.emitChange( 'undo', previousState.path, null, previousState.newValue );
            }
        }
    }

    // Redo last undone change
    redo ()
    {
        // Implementation would require a separate redo stack
        console.warn( 'Redo functionality not implemented in this version' );
    }

    // Get state history
    getHistory ()
    {
        return [ ...this.history ];
    }

    // Clear history
    clearHistory ()
    {
        this.history = this.history.slice( -1 ); // Keep only the last state
    }

    // Serialize state for persistence
    serialize ()
    {
        return {
            state: JSON.parse( JSON.stringify( this.state ) ),
            timestamp: Date.now()
        };
    }

    // Hydrate state from serialized data
    hydrate ( data )
    {
        if ( data.state )
        {
            Object.keys( this.state ).forEach( key =>
            {
                delete this.state[ key ];
            } );
            Object.assign( this.state, this.createProxy( data.state ) );
            this.emitChange( 'hydrate', '', null, this.state );
        }
    }

    // Synchronize with external state
    synchronize ( data )
    {
        if ( data.state && data.timestamp > ( this.lastSyncTimestamp || 0 ) )
        {
            this.hydrate( data );
            this.lastSyncTimestamp = data.timestamp;
        }
    }

    // Helper methods
    getByPath ( obj, path )
    {
        return path.split( '.' ).reduce( ( current, key ) => current?.[ key ], obj );
    }

    setByPath ( obj, path, value )
    {
        const keys = path.split( '.' );
        const lastKey = keys.pop();
        const target = keys.reduce( ( current, key ) => current[ key ], obj );
        target[ lastKey ] = value;
    }

    addToHistory ( action, path, oldValue, newValue )
    {
        this.history.push( {
            action,
            path,
            oldValue: JSON.parse( JSON.stringify( oldValue ) ),
            newValue: JSON.parse( JSON.stringify( newValue ) ),
            timestamp: Date.now()
        } );

        // Limit history size
        if ( this.history.length > this.options.maxHistorySize )
        {
            this.history.shift();
        }
    }

    emitChange ( action, path, oldValue, newValue )
    {
        if ( this.eventBus )
        {
            this.eventBus.dispatchEvent( new CustomEvent( 'stateChange', {
                detail: {
                    storeName: this.name,
                    action,
                    path,
                    oldValue,
                    newValue,
                    timestamp: Date.now()
                }
            } ) );
        }
    }

    validate ( path, value )
    {
        const validators = this.validators.get( path );
        if ( !validators ) return true;

        return validators.every( validator =>
        {
            try
            {
                return validator.validatorFn( value, this.state );
            } catch ( error )
            {
                console.error( `Validation error for ${ path }:`, error );
                return false;
            }
        } );
    }

    runMiddleware ( action, path, value, oldValue )
    {
        return this.middleware.every( middleware =>
        {
            try
            {
                return middleware( action, path, value, oldValue, this.state ) !== false;
            } catch ( error )
            {
                console.error( 'Middleware error:', error );
                return false;
            }
        } );
    }

    updateComputed ( changedPath = null )
    {
        this.computed.forEach( ( computed, name ) =>
        {
            if ( !changedPath || computed.dependencies.some( dep => changedPath.startsWith( dep ) ) )
            {
                try
                {
                    const newValue = computed.computeFn( this.state );
                    const oldValue = computed.value;
                    computed.value = newValue;
                    computed.dirty = false;

                    if ( JSON.stringify( oldValue ) !== JSON.stringify( newValue ) )
                    {
                        this.emitChange( 'computed', name, oldValue, newValue );
                    }
                } catch ( error )
                {
                    console.error( `Error computing ${ name }:`, error );
                }
            }
        } );
    }

    runWatchers ( path, value, oldValue )
    {
        this.watchers.forEach( watcher =>
        {
            if ( !watcher.active ) return;

            if ( !watcher.path || path.startsWith( watcher.path ) )
            {
                try
                {
                    watcher.callback( value, oldValue, path );
                } catch ( error )
                {
                    console.error( 'Watcher error:', error );
                }
            }
        } );
    }
}

// Alpine.js integration
document.addEventListener( 'alpine:init', () =>
{
    // Initialize state manager
    window.StateManager.init();

    // Create Alpine.js magic property for state access
    Alpine.magic( 'state', () =>
    {
        return {
            get: ( storeName, path = null ) =>
            {
                const store = window.StateManager.getStore( storeName );
                return path ? store?.get( path ) : store?.state;
            },

            set: ( storeName, path, value ) =>
            {
                const store = window.StateManager.getStore( storeName );
                store?.set( path, value );
            },

            update: ( storeName, updates ) =>
            {
                const store = window.StateManager.getStore( storeName );
                store?.update( updates );
            },

            subscribe: ( storeName, callback, path = null ) =>
            {
                const store = window.StateManager.getStore( storeName );
                return store?.subscribe( callback, path );
            },

            store: ( storeName ) =>
            {
                return window.StateManager.getStore( storeName );
            }
        };
    } );

    // Global state reactive data
    Alpine.data( 'globalState', () => ( {
        userPreferences: window.StateManager.getStore( 'userPreferences' )?.state || {},
        appState: window.StateManager.getStore( 'appState' )?.state || {},
        serverState: window.StateManager.getStore( 'serverState' )?.state || {},
        billingState: window.StateManager.getStore( 'billingState' )?.state || {},

        init ()
        {
            // Subscribe to state changes
            [ 'userPreferences', 'appState', 'serverState', 'billingState' ].forEach( storeName =>
            {
                const store = window.StateManager.getStore( storeName );
                if ( store )
                {
                    store.subscribe( () =>
                    {
                        this[ storeName ] = { ...store.state };
                    } );
                }
            } );
        }
    } ) );
} );

// Export for module use
if ( typeof module !== 'undefined' && module.exports )
{
    module.exports = { StateManager, StateStore };
}
