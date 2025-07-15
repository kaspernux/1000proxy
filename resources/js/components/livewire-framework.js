/**
 * Livewire Component Framework
 * Base component class with shared functionality, lifecycle management, and composition patterns
 */

// Base Component Class with Shared Functionality
class LivewireComponentBase
{
    constructor ( name, options = {} )
    {
        this.name = name;
        this.options = options;
        this.state = {};
        this.listeners = new Map();
        this.mixins = [];
        this.lifecycle = {
            beforeInit: [],
            afterInit: [],
            beforeUpdate: [],
            afterUpdate: [],
            beforeDestroy: [],
            afterDestroy: []
        };
        this.eventBus = new ComponentEventBus();
        this.stateManager = new ComponentStateManager();
        this.isInitialized = false;
        this.isDestroyed = false;
    }

    // Lifecycle Management
    init ()
    {
        if ( this.isInitialized ) return;

        this.executeLifecycleHooks( 'beforeInit' );
        this.initializeState();
        this.bindEvents();
        this.applyMixins();
        this.isInitialized = true;
        this.executeLifecycleHooks( 'afterInit' );

        return this;
    }

    update ( newState = {} )
    {
        if ( !this.isInitialized || this.isDestroyed ) return;

        this.executeLifecycleHooks( 'beforeUpdate' );
        this.stateManager.updateState( this.state, newState );
        this.executeLifecycleHooks( 'afterUpdate' );

        return this;
    }

    destroy ()
    {
        if ( this.isDestroyed ) return;

        this.executeLifecycleHooks( 'beforeDestroy' );
        this.unbindEvents();
        this.clearState();
        this.isDestroyed = true;
        this.executeLifecycleHooks( 'afterDestroy' );

        return this;
    }

    // State Management
    initializeState ()
    {
        this.state = {
            isLoading: false,
            error: null,
            success: null,
            data: {},
            ui: {
                showModal: false,
                selectedItems: [],
                filters: {},
                sorting: { field: 'id', direction: 'asc' }
            },
            ...this.getDefaultState()
        };
    }

    setState ( newState )
    {
        return this.stateManager.setState( this.state, newState );
    }

    getState ( key = null )
    {
        return key ? this.state[ key ] : this.state;
    }

    clearState ()
    {
        this.state = {};
    }

    // Event System
    on ( event, callback )
    {
        if ( !this.listeners.has( event ) )
        {
            this.listeners.set( event, [] );
        }
        this.listeners.get( event ).push( callback );
        return this;
    }

    off ( event, callback = null )
    {
        if ( !this.listeners.has( event ) ) return this;

        if ( callback )
        {
            const callbacks = this.listeners.get( event );
            const index = callbacks.indexOf( callback );
            if ( index > -1 )
            {
                callbacks.splice( index, 1 );
            }
        } else
        {
            this.listeners.delete( event );
        }
        return this;
    }

    emit ( event, data = null )
    {
        if ( this.listeners.has( event ) )
        {
            this.listeners.get( event ).forEach( callback => callback( data ) );
        }
        this.eventBus.emit( event, data, this.name );
        return this;
    }

    // Mixin System
    use ( mixin )
    {
        if ( typeof mixin === 'function' )
        {
            mixin = mixin();
        }
        this.mixins.push( mixin );
        return this;
    }

    applyMixins ()
    {
        this.mixins.forEach( mixin =>
        {
            Object.assign( this, mixin );
            if ( mixin.init && typeof mixin.init === 'function' )
            {
                mixin.init.call( this );
            }
        } );
    }

    // Lifecycle Hooks
    addLifecycleHook ( phase, callback )
    {
        if ( this.lifecycle[ phase ] )
        {
            this.lifecycle[ phase ].push( callback );
        }
        return this;
    }

    executeLifecycleHooks ( phase )
    {
        if ( this.lifecycle[ phase ] )
        {
            this.lifecycle[ phase ].forEach( callback => callback.call( this ) );
        }
    }

    // Utility Methods
    bindEvents ()
    {
        // Override in child classes
    }

    unbindEvents ()
    {
        this.listeners.clear();
    }

    getDefaultState ()
    {
        // Override in child classes
        return {};
    }

    // Error Handling
    handleError ( error, context = 'unknown' )
    {
        console.error( `[${ this.name }] Error in ${ context }:`, error );
        this.setState( { error: error.message || 'An error occurred' } );
        this.emit( 'error', { error, context } );
    }

    clearError ()
    {
        this.setState( { error: null } );
    }

    // Success Handling
    showSuccess ( message )
    {
        this.setState( { success: message } );
        this.emit( 'success', message );
        setTimeout( () => this.clearSuccess(), 5000 );
    }

    clearSuccess ()
    {
        this.setState( { success: null } );
    }

    // Loading State
    setLoading ( isLoading )
    {
        this.setState( { isLoading } );
        this.emit( 'loadingStateChanged', isLoading );
    }
}

// Component Event Bus for Inter-Component Communication
class ComponentEventBus
{
    constructor ()
    {
        this.events = new Map();
    }

    on ( event, callback, componentName = null )
    {
        if ( !this.events.has( event ) )
        {
            this.events.set( event, [] );
        }
        this.events.get( event ).push( { callback, componentName } );
    }

    off ( event, callback = null, componentName = null )
    {
        if ( !this.events.has( event ) ) return;

        const listeners = this.events.get( event );
        if ( callback || componentName )
        {
            const filteredListeners = listeners.filter( listener =>
            {
                if ( callback && listener.callback !== callback ) return true;
                if ( componentName && listener.componentName !== componentName ) return true;
                return false;
            } );
            this.events.set( event, filteredListeners );
        } else
        {
            this.events.delete( event );
        }
    }

    emit ( event, data = null, sourceComponent = null )
    {
        if ( this.events.has( event ) )
        {
            this.events.get( event ).forEach( listener =>
            {
                listener.callback( data, sourceComponent );
            } );
        }
    }

    clear ()
    {
        this.events.clear();
    }
}

// Component State Manager with Validation and History
class ComponentStateManager
{
    constructor ()
    {
        this.history = [];
        this.maxHistorySize = 50;
        this.validators = new Map();
    }

    setState ( currentState, newState )
    {
        const previousState = JSON.parse( JSON.stringify( currentState ) );
        this.addToHistory( previousState );

        const mergedState = this.mergeState( currentState, newState );
        const validatedState = this.validateState( mergedState );

        Object.assign( currentState, validatedState );
        return currentState;
    }

    updateState ( currentState, updates )
    {
        return this.setState( currentState, updates );
    }

    mergeState ( currentState, newState )
    {
        if ( typeof newState !== 'object' || newState === null )
        {
            return currentState;
        }

        const merged = { ...currentState };

        for ( const [ key, value ] of Object.entries( newState ) )
        {
            if ( typeof value === 'object' && value !== null && !Array.isArray( value ) )
            {
                merged[ key ] = this.mergeState( merged[ key ] || {}, value );
            } else
            {
                merged[ key ] = value;
            }
        }

        return merged;
    }

    validateState ( state )
    {
        const validated = { ...state };

        for ( const [ key, validator ] of this.validators )
        {
            if ( key in validated )
            {
                try
                {
                    validated[ key ] = validator( validated[ key ] );
                } catch ( error )
                {
                    console.warn( `State validation failed for key "${ key }":`, error.message );
                }
            }
        }

        return validated;
    }

    addValidator ( key, validator )
    {
        this.validators.set( key, validator );
    }

    addToHistory ( state )
    {
        this.history.push( {
            state: JSON.parse( JSON.stringify( state ) ),
            timestamp: Date.now()
        } );

        if ( this.history.length > this.maxHistorySize )
        {
            this.history.shift();
        }
    }

    getHistory ()
    {
        return [ ...this.history ];
    }

    restoreFromHistory ( index )
    {
        if ( index >= 0 && index < this.history.length )
        {
            return JSON.parse( JSON.stringify( this.history[ index ].state ) );
        }
        return null;
    }

    clearHistory ()
    {
        this.history = [];
    }
}

// Common Mixins
const LoadingMixin = () => ( {
    showLoading ( message = 'Loading...' )
    {
        this.setState( { isLoading: true, loadingMessage: message } );
    },

    hideLoading ()
    {
        this.setState( { isLoading: false, loadingMessage: null } );
    },

    async withLoading ( asyncFunction, message = 'Loading...' )
    {
        this.showLoading( message );
        try
        {
            const result = await asyncFunction();
            return result;
        } finally
        {
            this.hideLoading();
        }
    }
} );

const ValidationMixin = () => ( {
    validators: new Map(),

    addValidator ( field, validator )
    {
        this.validators.set( field, validator );
    },

    validate ( data )
    {
        const errors = {};

        for ( const [ field, validator ] of this.validators )
        {
            try
            {
                validator( data[ field ] );
            } catch ( error )
            {
                errors[ field ] = error.message;
            }
        }

        return {
            isValid: Object.keys( errors ).length === 0,
            errors
        };
    },

    validateField ( field, value )
    {
        if ( this.validators.has( field ) )
        {
            try
            {
                this.validators.get( field )( value );
                return { isValid: true, error: null };
            } catch ( error )
            {
                return { isValid: false, error: error.message };
            }
        }
        return { isValid: true, error: null };
    }
} );

const ApiMixin = () => ( {
    apiCache: new Map(),
    pendingRequests: new Map(),

    async makeRequest ( url, options = {} )
    {
        const cacheKey = `${ url }_${ JSON.stringify( options ) }`;

        // Return cached result if available
        if ( this.apiCache.has( cacheKey ) )
        {
            return this.apiCache.get( cacheKey );
        }

        // Return pending request if already in progress
        if ( this.pendingRequests.has( cacheKey ) )
        {
            return this.pendingRequests.get( cacheKey );
        }

        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' )?.content
            }
        };

        const request = fetch( url, { ...defaultOptions, ...options } )
            .then( response =>
            {
                if ( !response.ok )
                {
                    throw new Error( `HTTP error! status: ${ response.status }` );
                }
                return response.json();
            } )
            .then( data =>
            {
                this.apiCache.set( cacheKey, data );
                this.pendingRequests.delete( cacheKey );
                return data;
            } )
            .catch( error =>
            {
                this.pendingRequests.delete( cacheKey );
                throw error;
            } );

        this.pendingRequests.set( cacheKey, request );
        return request;
    },

    clearCache ( pattern = null )
    {
        if ( pattern )
        {
            for ( const key of this.apiCache.keys() )
            {
                if ( key.includes( pattern ) )
                {
                    this.apiCache.delete( key );
                }
            }
        } else
        {
            this.apiCache.clear();
        }
    }
} );

const PaginationMixin = () => ( {
    initPagination ()
    {
        this.setState( {
            pagination: {
                currentPage: 1,
                perPage: 10,
                total: 0,
                totalPages: 0
            }
        } );
    },

    updatePagination ( total )
    {
        const { currentPage, perPage } = this.state.pagination;
        const totalPages = Math.ceil( total / perPage );

        this.setState( {
            pagination: {
                ...this.state.pagination,
                total,
                totalPages,
                currentPage: Math.min( currentPage, totalPages || 1 )
            }
        } );
    },

    goToPage ( page )
    {
        const { totalPages } = this.state.pagination;
        const newPage = Math.max( 1, Math.min( page, totalPages ) );

        this.setState( {
            pagination: {
                ...this.state.pagination,
                currentPage: newPage
            }
        } );

        this.emit( 'pageChanged', newPage );
    },

    nextPage ()
    {
        this.goToPage( this.state.pagination.currentPage + 1 );
    },

    previousPage ()
    {
        this.goToPage( this.state.pagination.currentPage - 1 );
    },

    changePerPage ( perPage )
    {
        this.setState( {
            pagination: {
                ...this.state.pagination,
                perPage,
                currentPage: 1
            }
        } );

        this.emit( 'perPageChanged', perPage );
    }
} );

// Component Factory for Easy Creation
class ComponentFactory
{
    static create ( name, config = {} )
    {
        const component = new LivewireComponentBase( name, config.options );

        // Apply configuration
        if ( config.state )
        {
            component.getDefaultState = () => config.state;
        }

        if ( config.methods )
        {
            Object.assign( component, config.methods );
        }

        if ( config.mixins )
        {
            config.mixins.forEach( mixin => component.use( mixin ) );
        }

        if ( config.lifecycle )
        {
            Object.entries( config.lifecycle ).forEach( ( [ phase, callbacks ] ) =>
            {
                if ( Array.isArray( callbacks ) )
                {
                    callbacks.forEach( callback => component.addLifecycleHook( phase, callback ) );
                } else
                {
                    component.addLifecycleHook( phase, callbacks );
                }
            } );
        }

        if ( config.events )
        {
            Object.entries( config.events ).forEach( ( [ event, callback ] ) =>
            {
                component.on( event, callback );
            } );
        }

        return component;
    }
}

// Component Testing Utilities
class ComponentTester
{
    constructor ( component )
    {
        this.component = component;
        this.testResults = [];
    }

    test ( name, testFunction )
    {
        try
        {
            testFunction( this.component );
            this.testResults.push( { name, status: 'passed', error: null } );
        } catch ( error )
        {
            this.testResults.push( { name, status: 'failed', error: error.message } );
        }
        return this;
    }

    async asyncTest ( name, testFunction )
    {
        try
        {
            await testFunction( this.component );
            this.testResults.push( { name, status: 'passed', error: null } );
        } catch ( error )
        {
            this.testResults.push( { name, status: 'failed', error: error.message } );
        }
        return this;
    }

    expectState ( expectedState )
    {
        const actualState = this.component.getState();
        const differences = this.findStateDifferences( expectedState, actualState );

        if ( differences.length > 0 )
        {
            throw new Error( `State mismatch: ${ differences.join( ', ' ) }` );
        }

        return this;
    }

    expectEvent ( eventName, timeout = 1000 )
    {
        return new Promise( ( resolve, reject ) =>
        {
            const timer = setTimeout( () =>
            {
                reject( new Error( `Event "${ eventName }" was not emitted within ${ timeout }ms` ) );
            }, timeout );

            this.component.on( eventName, () =>
            {
                clearTimeout( timer );
                resolve();
            } );
        } );
    }

    simulateEvent ( eventName, data = null )
    {
        this.component.emit( eventName, data );
        return this;
    }

    getResults ()
    {
        return {
            total: this.testResults.length,
            passed: this.testResults.filter( r => r.status === 'passed' ).length,
            failed: this.testResults.filter( r => r.status === 'failed' ).length,
            results: this.testResults
        };
    }

    findStateDifferences ( expected, actual, path = '' )
    {
        const differences = [];

        for ( const key in expected )
        {
            const fullPath = path ? `${ path }.${ key }` : key;

            if ( !( key in actual ) )
            {
                differences.push( `Missing key: ${ fullPath }` );
            } else if ( typeof expected[ key ] === 'object' && expected[ key ] !== null )
            {
                differences.push( ...this.findStateDifferences( expected[ key ], actual[ key ], fullPath ) );
            } else if ( expected[ key ] !== actual[ key ] )
            {
                differences.push( `Value mismatch at ${ fullPath }: expected ${ expected[ key ] }, got ${ actual[ key ] }` );
            }
        }

        return differences;
    }
}

// Global Component Registry
const ComponentRegistry = {
    components: new Map(),
    eventBus: new ComponentEventBus(),

    register ( name, component )
    {
        this.components.set( name, component );
        return this;
    },

    get ( name )
    {
        return this.components.get( name );
    },

    has ( name )
    {
        return this.components.has( name );
    },

    unregister ( name )
    {
        const component = this.components.get( name );
        if ( component )
        {
            component.destroy();
            this.components.delete( name );
        }
        return this;
    },

    broadcast ( event, data = null )
    {
        this.eventBus.emit( event, data );
        this.components.forEach( component =>
        {
            component.emit( event, data );
        } );
    },

    destroyAll ()
    {
        this.components.forEach( component => component.destroy() );
        this.components.clear();
        this.eventBus.clear();
    }
};

// Export everything for use in Alpine.js components
if ( typeof window !== 'undefined' )
{
    window.LivewireFramework = {
        ComponentBase: LivewireComponentBase,
        ComponentFactory,
        ComponentTester,
        ComponentRegistry,
        mixins: {
            Loading: LoadingMixin,
            Validation: ValidationMixin,
            Api: ApiMixin,
            Pagination: PaginationMixin
        }
    };
}

export
{
    LivewireComponentBase,
    ComponentEventBus,
    ComponentStateManager,
    ComponentFactory,
    ComponentTester,
    ComponentRegistry,
    LoadingMixin,
    ValidationMixin,
    ApiMixin,
    PaginationMixin
};
