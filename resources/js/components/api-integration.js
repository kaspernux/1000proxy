/**
 * API Integration Components
 * Comprehensive async API management system with error handling, retry logic, caching, and rate limiting
 */

// Main API Manager Class
class APIManager
{
    constructor ( options = {} )
    {
        this.baseURL = options.baseURL || '/api';
        this.timeout = options.timeout || 10000;
        this.retryAttempts = options.retryAttempts || 3;
        this.retryDelay = options.retryDelay || 1000;
        this.enableLogging = options.enableLogging || true;
        this.enableCaching = options.enableCaching || true;
        this.cacheTTL = options.cacheTTL || 300000; // 5 minutes

        // Initialize components
        this.cache = new APICache();
        this.rateLimiter = new RateLimiter();
        this.logger = new APILogger();
        this.interceptors = new InterceptorManager();
        this.errorHandler = new APIErrorHandler();

        // Request/Response transformers
        this.requestTransformers = [];
        this.responseTransformers = [];

        // Authentication
        this.authToken = null;
        this.authType = 'Bearer';

        // Statistics
        this.stats = {
            requests: 0,
            responses: 0,
            errors: 0,
            cacheHits: 0,
            avgResponseTime: 0,
            startTime: Date.now()
        };

        this.setupDefaultInterceptors();
    }

    // Setup default request/response interceptors
    setupDefaultInterceptors ()
    {
        // Request interceptors
        this.interceptors.request.use(
            config =>
            {
                // Add auth header
                if ( this.authToken )
                {
                    config.headers = config.headers || {};
                    config.headers.Authorization = `${ this.authType } ${ this.authToken }`;
                }

                // Add CSRF token
                const csrfToken = document.querySelector( 'meta[name="csrf-token"]' );
                if ( csrfToken )
                {
                    config.headers = config.headers || {};
                    config.headers[ 'X-CSRF-TOKEN' ] = csrfToken.getAttribute( 'content' );
                }

                // Add request timestamp
                config.metadata = {
                    startTime: Date.now(),
                    requestId: this.generateRequestId()
                };

                return config;
            },
            error =>
            {
                this.logger.error( 'Request interceptor error:', error );
                return Promise.reject( error );
            }
        );

        // Response interceptors
        this.interceptors.response.use(
            response =>
            {
                // Calculate response time
                const responseTime = Date.now() - response.config.metadata.startTime;
                response.metadata = {
                    responseTime,
                    requestId: response.config.metadata.requestId
                };

                // Update stats
                this.updateStats( 'response', responseTime );

                // Log successful response
                this.logger.info( `API Response [${ response.config.metadata.requestId }]:`, {
                    url: response.config.url,
                    method: response.config.method,
                    status: response.status,
                    responseTime
                } );

                return response;
            },
            error =>
            {
                this.updateStats( 'error' );
                this.logger.error( 'Response interceptor error:', error );
                return this.errorHandler.handle( error );
            }
        );
    }

    // Generate unique request ID
    generateRequestId ()
    {
        return 'req_' + Math.random().toString( 36 ).substr( 2, 9 ) + '_' + Date.now();
    }

    // Update API statistics
    updateStats ( type, responseTime = null )
    {
        this.stats[ type === 'response' ? 'responses' : 'errors' ]++;
        this.stats.requests++;

        if ( responseTime )
        {
            const totalTime = this.stats.avgResponseTime * ( this.stats.responses - 1 ) + responseTime;
            this.stats.avgResponseTime = Math.round( totalTime / this.stats.responses );
        }
    }

    // Set authentication token
    setAuthToken ( token, type = 'Bearer' )
    {
        this.authToken = token;
        this.authType = type;
    }

    // Clear authentication
    clearAuth ()
    {
        this.authToken = null;
    }

    // Add request transformer
    addRequestTransformer ( transformer )
    {
        this.requestTransformers.push( transformer );
    }

    // Add response transformer
    addResponseTransformer ( transformer )
    {
        this.responseTransformers.push( transformer );
    }

    // Main request method with full feature support
    async request ( config )
    {
        const startTime = Date.now();

        try
        {
            // Normalize config
            config = this.normalizeConfig( config );

            // Check rate limiting
            if ( !this.rateLimiter.checkLimit( config.url ) )
            {
                throw new APIError( 'Rate limit exceeded', 429, config );
            }

            // Check cache for GET requests
            if ( config.method === 'GET' && this.enableCaching )
            {
                const cached = this.cache.get( config.url, config.params );
                if ( cached )
                {
                    this.stats.cacheHits++;
                    this.logger.info( 'Cache hit for:', config.url );
                    return cached;
                }
            }

            // Apply request transformers
            config = await this.applyRequestTransformers( config );

            // Apply request interceptors
            config = await this.interceptors.request.process( config );

            // Make the actual request with retry logic
            const response = await this.makeRequestWithRetry( config );

            // Apply response transformers
            const transformedResponse = await this.applyResponseTransformers( response );

            // Apply response interceptors
            const finalResponse = await this.interceptors.response.process( transformedResponse );

            // Cache successful GET responses
            if ( config.method === 'GET' && this.enableCaching && finalResponse.status === 200 )
            {
                this.cache.set( config.url, config.params, finalResponse, this.cacheTTL );
            }

            return finalResponse;

        } catch ( error )
        {
            // Enhanced error handling
            const enhancedError = this.errorHandler.enhance( error, config );
            this.logger.error( 'API Request failed:', enhancedError );
            throw enhancedError;
        }
    }

    // Normalize request configuration
    normalizeConfig ( config )
    {
        if ( typeof config === 'string' )
        {
            config = { url: config };
        }

        return {
            method: 'GET',
            headers: {},
            timeout: this.timeout,
            ...config,
            url: this.resolveURL( config.url )
        };
    }

    // Resolve full URL
    resolveURL ( url )
    {
        if ( url.startsWith( 'http' ) )
        {
            return url;
        }
        return this.baseURL + ( url.startsWith( '/' ) ? url : '/' + url );
    }

    // Apply request transformers
    async applyRequestTransformers ( config )
    {
        for ( const transformer of this.requestTransformers )
        {
            config = await transformer( config );
        }
        return config;
    }

    // Apply response transformers
    async applyResponseTransformers ( response )
    {
        for ( const transformer of this.responseTransformers )
        {
            response = await transformer( response );
        }
        return response;
    }

    // Make request with retry logic
    async makeRequestWithRetry ( config, attempt = 1 )
    {
        try
        {
            return await this.makeRequest( config );
        } catch ( error )
        {
            if ( attempt < this.retryAttempts && this.shouldRetry( error ) )
            {
                const delay = this.calculateRetryDelay( attempt );
                this.logger.warn( `Request failed, retrying in ${ delay }ms (attempt ${ attempt }/${ this.retryAttempts })` );

                await this.delay( delay );
                return this.makeRequestWithRetry( config, attempt + 1 );
            }
            throw error;
        }
    }

    // Check if error is retryable
    shouldRetry ( error )
    {
        const retryableStatuses = [ 408, 429, 500, 502, 503, 504 ];
        const retryableNetworkErrors = [ 'timeout', 'network', 'abort' ];

        return retryableStatuses.includes( error.status ) ||
            retryableNetworkErrors.some( type => error.message?.toLowerCase().includes( type ) );
    }

    // Calculate retry delay with exponential backoff
    calculateRetryDelay ( attempt )
    {
        return this.retryDelay * Math.pow( 2, attempt - 1 );
    }

    // Delay utility
    delay ( ms )
    {
        return new Promise( resolve => setTimeout( resolve, ms ) );
    }

    // Core fetch implementation
    async makeRequest ( config )
    {
        const controller = new AbortController();
        const timeoutId = setTimeout( () => controller.abort(), config.timeout );

        try
        {
            const requestOptions = {
                method: config.method,
                headers: config.headers,
                signal: controller.signal
            };

            // Add body for non-GET requests
            if ( config.method !== 'GET' && config.data )
            {
                if ( config.data instanceof FormData )
                {
                    requestOptions.body = config.data;
                } else
                {
                    requestOptions.headers[ 'Content-Type' ] = 'application/json';
                    requestOptions.body = JSON.stringify( config.data );
                }
            }

            // Add query parameters for GET requests
            let url = config.url;
            if ( config.params )
            {
                const searchParams = new URLSearchParams( config.params );
                url += ( url.includes( '?' ) ? '&' : '?' ) + searchParams.toString();
            }

            const response = await fetch( url, requestOptions );

            clearTimeout( timeoutId );

            // Parse response
            const data = await this.parseResponse( response );

            return {
                data,
                status: response.status,
                statusText: response.statusText,
                headers: response.headers,
                config
            };

        } catch ( error )
        {
            clearTimeout( timeoutId );
            throw new APIError( error.message, error.status || 0, config, error );
        }
    }

    // Parse response based on content type
    async parseResponse ( response )
    {
        const contentType = response.headers.get( 'content-type' );

        if ( !response.ok )
        {
            let errorData;
            try
            {
                errorData = contentType?.includes( 'application/json' )
                    ? await response.json()
                    : await response.text();
            } catch ( e )
            {
                errorData = 'Failed to parse error response';
            }
            throw new APIError( `HTTP ${ response.status }: ${ response.statusText }`, response.status, null, errorData );
        }

        if ( contentType?.includes( 'application/json' ) )
        {
            return await response.json();
        } else if ( contentType?.includes( 'text/' ) )
        {
            return await response.text();
        } else
        {
            return await response.blob();
        }
    }

    // Convenience methods
    async get ( url, params = null, config = {} )
    {
        return this.request( { method: 'GET', url, params, ...config } );
    }

    async post ( url, data = null, config = {} )
    {
        return this.request( { method: 'POST', url, data, ...config } );
    }

    async put ( url, data = null, config = {} )
    {
        return this.request( { method: 'PUT', url, data, ...config } );
    }

    async patch ( url, data = null, config = {} )
    {
        return this.request( { method: 'PATCH', url, data, ...config } );
    }

    async delete ( url, config = {} )
    {
        return this.request( { method: 'DELETE', url, ...config } );
    }

    // Get API statistics
    getStats ()
    {
        return {
            ...this.stats,
            uptime: Date.now() - this.stats.startTime,
            successRate: this.stats.responses > 0 ?
                ( ( this.stats.responses / this.stats.requests ) * 100 ).toFixed( 2 ) + '%' : '0%',
            cacheHitRate: this.stats.requests > 0 ?
                ( ( this.stats.cacheHits / this.stats.requests ) * 100 ).toFixed( 2 ) + '%' : '0%'
        };
    }

    // Clear cache
    clearCache ()
    {
        this.cache.clear();
    }

    // Reset statistics
    resetStats ()
    {
        this.stats = {
            requests: 0,
            responses: 0,
            errors: 0,
            cacheHits: 0,
            avgResponseTime: 0,
            startTime: Date.now()
        };
    }
}

// API Cache Implementation
class APICache
{
    constructor ()
    {
        this.cache = new Map();
        this.timestamps = new Map();
    }

    // Generate cache key
    generateKey ( url, params = null )
    {
        const paramString = params ? new URLSearchParams( params ).toString() : '';
        return `${ url }${ paramString ? '?' + paramString : '' }`;
    }

    // Set cache entry
    set ( url, params, data, ttl )
    {
        const key = this.generateKey( url, params );
        this.cache.set( key, data );
        this.timestamps.set( key, Date.now() + ttl );
    }

    // Get cache entry
    get ( url, params = null )
    {
        const key = this.generateKey( url, params );
        const timestamp = this.timestamps.get( key );

        if ( !timestamp || Date.now() > timestamp )
        {
            this.delete( key );
            return null;
        }

        return this.cache.get( key );
    }

    // Delete cache entry
    delete ( key )
    {
        this.cache.delete( key );
        this.timestamps.delete( key );
    }

    // Clear all cache
    clear ()
    {
        this.cache.clear();
        this.timestamps.clear();
    }

    // Get cache size
    size ()
    {
        return this.cache.size;
    }
}

// Rate Limiter Implementation
class RateLimiter
{
    constructor ()
    {
        this.limits = new Map();
        this.defaultLimit = {
            requests: 100,
            window: 60000 // 1 minute
        };
    }

    // Set rate limit for specific endpoint
    setLimit ( endpoint, requests, window )
    {
        this.limits.set( endpoint, { requests, window, count: 0, resetTime: Date.now() + window } );
    }

    // Check if request is within rate limit
    checkLimit ( url )
    {
        const endpoint = this.extractEndpoint( url );
        const limit = this.limits.get( endpoint ) || { ...this.defaultLimit, count: 0, resetTime: Date.now() + this.defaultLimit.window };

        const now = Date.now();

        // Reset counter if window expired
        if ( now > limit.resetTime )
        {
            limit.count = 0;
            limit.resetTime = now + ( this.limits.get( endpoint )?.window || this.defaultLimit.window );
        }

        // Check if limit exceeded
        if ( limit.count >= ( this.limits.get( endpoint )?.requests || this.defaultLimit.requests ) )
        {
            return false;
        }

        // Increment counter
        limit.count++;
        this.limits.set( endpoint, limit );

        return true;
    }

    // Extract endpoint from URL for rate limiting
    extractEndpoint ( url )
    {
        try
        {
            const urlObj = new URL( url, window.location.origin );
            return urlObj.pathname;
        } catch ( e )
        {
            return url;
        }
    }

    // Get rate limit status
    getLimitStatus ( url )
    {
        const endpoint = this.extractEndpoint( url );
        const limit = this.limits.get( endpoint );

        if ( !limit )
        {
            return {
                requests: this.defaultLimit.requests,
                remaining: this.defaultLimit.requests,
                resetTime: Date.now() + this.defaultLimit.window
            };
        }

        return {
            requests: this.limits.get( endpoint )?.requests || this.defaultLimit.requests,
            remaining: Math.max( 0, ( this.limits.get( endpoint )?.requests || this.defaultLimit.requests ) - limit.count ),
            resetTime: limit.resetTime
        };
    }
}

// API Logger Implementation
class APILogger
{
    constructor ()
    {
        this.logs = [];
        this.maxLogs = 1000;
        this.enableConsole = true;
    }

    // Log info message
    info ( message, data = null )
    {
        this.log( 'info', message, data );
    }

    // Log warning message
    warn ( message, data = null )
    {
        this.log( 'warn', message, data );
    }

    // Log error message
    error ( message, data = null )
    {
        this.log( 'error', message, data );
    }

    // Log debug message
    debug ( message, data = null )
    {
        this.log( 'debug', message, data );
    }

    // Core logging method
    log ( level, message, data = null )
    {
        const logEntry = {
            timestamp: new Date().toISOString(),
            level,
            message,
            data
        };

        // Add to internal logs
        this.logs.push( logEntry );

        // Maintain max logs limit
        if ( this.logs.length > this.maxLogs )
        {
            this.logs.shift();
        }

        // Console logging
        if ( this.enableConsole )
        {
            const consoleMethod = console[ level ] || console.log;
            consoleMethod( `[API ${ level.toUpperCase() }] ${ message }`, data || '' );
        }
    }

    // Get logs by level
    getLogs ( level = null )
    {
        return level ? this.logs.filter( log => log.level === level ) : this.logs;
    }

    // Clear logs
    clearLogs ()
    {
        this.logs = [];
    }

    // Export logs
    exportLogs ()
    {
        return JSON.stringify( this.logs, null, 2 );
    }
}

// Interceptor Manager Implementation
class InterceptorManager
{
    constructor ()
    {
        this.request = new InterceptorChain();
        this.response = new InterceptorChain();
    }
}

// Interceptor Chain Implementation
class InterceptorChain
{
    constructor ()
    {
        this.interceptors = [];
    }

    // Add interceptor
    use ( fulfilled, rejected = null )
    {
        this.interceptors.push( { fulfilled, rejected } );
        return this.interceptors.length - 1;
    }

    // Remove interceptor
    eject ( id )
    {
        if ( this.interceptors[ id ] )
        {
            this.interceptors[ id ] = null;
        }
    }

    // Process through interceptor chain
    async process ( value )
    {
        for ( const interceptor of this.interceptors )
        {
            if ( interceptor )
            {
                try
                {
                    value = await interceptor.fulfilled( value );
                } catch ( error )
                {
                    if ( interceptor.rejected )
                    {
                        value = await interceptor.rejected( error );
                    } else
                    {
                        throw error;
                    }
                }
            }
        }
        return value;
    }
}

// Enhanced API Error Class
class APIError extends Error
{
    constructor ( message, status = 0, config = null, originalError = null )
    {
        super( message );
        this.name = 'APIError';
        this.status = status;
        this.config = config;
        this.originalError = originalError;
        this.timestamp = new Date().toISOString();
        this.isRetryable = this.determineRetryable();
    }

    // Determine if error is retryable
    determineRetryable ()
    {
        const retryableStatuses = [ 408, 429, 500, 502, 503, 504 ];
        return retryableStatuses.includes( this.status );
    }

    // Get error details
    getDetails ()
    {
        return {
            message: this.message,
            status: this.status,
            timestamp: this.timestamp,
            isRetryable: this.isRetryable,
            config: this.config,
            originalError: this.originalError
        };
    }
}

// API Error Handler Implementation
class APIErrorHandler
{
    constructor ()
    {
        this.errorHandlers = new Map();
        this.setupDefaultHandlers();
    }

    // Setup default error handlers
    setupDefaultHandlers ()
    {
        // 401 Unauthorized
        this.registerHandler( 401, ( error ) =>
        {
            console.warn( 'Unauthorized access, redirecting to login...' );
            // Could trigger auth refresh or redirect
            return Promise.reject( error );
        } );

        // 403 Forbidden
        this.registerHandler( 403, ( error ) =>
        {
            console.warn( 'Access forbidden' );
            return Promise.reject( error );
        } );

        // 404 Not Found
        this.registerHandler( 404, ( error ) =>
        {
            console.warn( 'Resource not found' );
            return Promise.reject( error );
        } );

        // 429 Rate Limited
        this.registerHandler( 429, ( error ) =>
        {
            console.warn( 'Rate limit exceeded, please retry later' );
            return Promise.reject( error );
        } );

        // 500 Internal Server Error
        this.registerHandler( 500, ( error ) =>
        {
            console.error( 'Server error occurred' );
            return Promise.reject( error );
        } );
    }

    // Register custom error handler
    registerHandler ( status, handler )
    {
        this.errorHandlers.set( status, handler );
    }

    // Handle error
    async handle ( error )
    {
        const handler = this.errorHandlers.get( error.status );
        if ( handler )
        {
            return handler( error );
        }
        return Promise.reject( error );
    }

    // Enhance error with additional information
    enhance ( error, config )
    {
        if ( error instanceof APIError )
        {
            return error;
        }

        return new APIError(
            error.message || 'Unknown API error',
            error.status || 0,
            config,
            error
        );
    }
}

// Create global API instance
window.apiManager = new APIManager( {
    baseURL: '/api',
    timeout: 15000,
    retryAttempts: 3,
    enableLogging: true,
    enableCaching: true
} );

// Alpine.js Integration
document.addEventListener( 'alpine:init', () =>
{
    Alpine.magic( 'api', () =>
    {
        return {
            // Quick access methods
            get: ( url, params, config ) => window.apiManager.get( url, params, config ),
            post: ( url, data, config ) => window.apiManager.post( url, data, config ),
            put: ( url, data, config ) => window.apiManager.put( url, data, config ),
            patch: ( url, data, config ) => window.apiManager.patch( url, data, config ),
            delete: ( url, config ) => window.apiManager.delete( url, config ),

            // Direct access to manager
            manager: window.apiManager,

            // Utility methods
            setAuth: ( token, type ) => window.apiManager.setAuthToken( token, type ),
            clearAuth: () => window.apiManager.clearAuth(),
            getStats: () => window.apiManager.getStats(),
            clearCache: () => window.apiManager.clearCache()
        };
    } );

    // API Integration Alpine Component
    Alpine.data( 'apiIntegration', () => ( {
        // Component state
        isLoading: false,
        error: null,
        data: null,
        stats: {},
        logs: [],

        // Configuration
        config: {
            endpoint: '/users',
            method: 'GET',
            data: {},
            params: {},
            timeout: 10000
        },

        // Rate limiting
        rateLimits: {},

        // Cache status
        cacheEntries: [],

        // Initialize component
        init ()
        {
            this.updateStats();
            this.updateLogs();
            this.updateCacheEntries();
            this.updateRateLimits();

            // Update every 5 seconds
            setInterval( () =>
            {
                this.updateStats();
                this.updateLogs();
                this.updateCacheEntries();
                this.updateRateLimits();
            }, 5000 );
        },

        // Make API request
        async makeRequest ()
        {
            this.isLoading = true;
            this.error = null;

            try
            {
                const response = await this.$api.manager.request( {
                    method: this.config.method,
                    url: this.config.endpoint,
                    data: this.config.method !== 'GET' ? this.config.data : undefined,
                    params: this.config.method === 'GET' ? this.config.params : undefined,
                    timeout: this.config.timeout
                } );

                this.data = response.data;
                this.$dispatch( 'api-success', { response } );

            } catch ( error )
            {
                this.error = error.message;
                this.$dispatch( 'api-error', { error } );
            } finally
            {
                this.isLoading = false;
            }
        },

        // Test different HTTP methods
        async testGet ()
        {
            this.config.method = 'GET';
            this.config.endpoint = '/test/get';
            await this.makeRequest();
        },

        async testPost ()
        {
            this.config.method = 'POST';
            this.config.endpoint = '/test/post';
            this.config.data = { message: 'Hello API', timestamp: Date.now() };
            await this.makeRequest();
        },

        async testError ()
        {
            this.config.method = 'GET';
            this.config.endpoint = '/test/error';
            await this.makeRequest();
        },

        async testTimeout ()
        {
            this.config.method = 'GET';
            this.config.endpoint = '/test/slow';
            this.config.timeout = 2000;
            await this.makeRequest();
        },

        // Update component state
        updateStats ()
        {
            this.stats = this.$api.getStats();
        },

        updateLogs ()
        {
            this.logs = this.$api.manager.logger.getLogs().slice( -10 ).reverse();
        },

        updateCacheEntries ()
        {
            this.cacheEntries = Array.from( this.$api.manager.cache.cache.entries() ).map( ( [ key, value ] ) => ( {
                key,
                size: JSON.stringify( value ).length,
                timestamp: this.$api.manager.cache.timestamps.get( key )
            } ) );
        },

        updateRateLimits ()
        {
            this.rateLimits = {
                '/test/get': this.$api.manager.rateLimiter.getLimitStatus( '/test/get' ),
                '/test/post': this.$api.manager.rateLimiter.getLimitStatus( '/test/post' )
            };
        },

        // Clear cache
        clearCache ()
        {
            this.$api.clearCache();
            this.updateCacheEntries();
        },

        // Reset stats
        resetStats ()
        {
            this.$api.manager.resetStats();
            this.updateStats();
        },

        // Clear logs
        clearLogs ()
        {
            this.$api.manager.logger.clearLogs();
            this.updateLogs();
        },

        // Set authentication
        setAuth ()
        {
            const token = prompt( 'Enter authentication token:' );
            if ( token )
            {
                this.$api.setAuth( token );
                alert( 'Authentication token set successfully' );
            }
        },

        // Clear authentication
        clearAuth ()
        {
            this.$api.clearAuth();
            alert( 'Authentication cleared' );
        },

        // Export logs
        exportLogs ()
        {
            const logs = this.$api.manager.logger.exportLogs();
            const blob = new Blob( [ logs ], { type: 'application/json' } );
            const url = URL.createObjectURL( blob );
            const a = document.createElement( 'a' );
            a.href = url;
            a.download = 'api-logs.json';
            a.click();
            URL.revokeObjectURL( url );
        },

        // Format timestamp
        formatTimestamp ( timestamp )
        {
            return new Date( timestamp ).toLocaleTimeString();
        },

        // Format file size
        formatSize ( bytes )
        {
            const sizes = [ 'B', 'KB', 'MB', 'GB' ];
            if ( bytes === 0 ) return '0 B';
            const i = Math.floor( Math.log( bytes ) / Math.log( 1024 ) );
            return Math.round( bytes / Math.pow( 1024, i ) * 100 ) / 100 + ' ' + sizes[ i ];
        },

        // Get log level color
        getLogLevelColor ( level )
        {
            const colors = {
                info: 'text-blue-600',
                warn: 'text-yellow-600',
                error: 'text-red-600',
                debug: 'text-gray-600'
            };
            return colors[ level ] || 'text-gray-600';
        }
    } ) );
} );

// Export for module systems
if ( typeof module !== 'undefined' && module.exports )
{
    module.exports = { APIManager, APICache, RateLimiter, APILogger, APIError };
}
