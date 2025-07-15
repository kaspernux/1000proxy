/**
 * XUI Integration Interface Components
 * Advanced Alpine.js components for XUI panel management and integration
 * 
 * Components included:
 * - LiveXUIServerBrowser: Real-time server monitoring and health status
 * - XUIInboundManager: Drag-and-drop inbound management
 * - ClientConfigurationBuilder: Live preview configuration builder
 * - XUIConnectionTester: Connection testing with status indicators
 * - InboundTrafficMonitor: Live traffic monitoring with charts
 * - XUIServerSelector: Auto-recommendation server selection
 * - ClientUsageAnalyzer: Detailed usage metrics and analytics
 */

// Core XUI Service for API communication
class XUIAPIService
{
    constructor ()
    {
        this.baseUrl = '/api/xui';
        this.cache = new Map();
        this.cacheTimeout = 30000; // 30 seconds
        this.retryCount = 3;
        this.retryDelay = 1000;
    }

    async request ( endpoint, options = {} )
    {
        const url = `${ this.baseUrl }${ endpoint }`;
        const cacheKey = `${ url }_${ JSON.stringify( options ) }`;

        // Check cache for GET requests
        if ( !options.method || options.method === 'GET' )
        {
            const cached = this.cache.get( cacheKey );
            if ( cached && Date.now() - cached.timestamp < this.cacheTimeout )
            {
                return cached.data;
            }
        }

        let attempt = 0;
        while ( attempt < this.retryCount )
        {
            try
            {
                const response = await fetch( url, {
                    method: options.method || 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' )?.getAttribute( 'content' ),
                        ...options.headers
                    },
                    ...options
                } );

                if ( !response.ok )
                {
                    throw new Error( `HTTP ${ response.status }: ${ response.statusText }` );
                }

                const data = await response.json();

                // Cache GET responses
                if ( !options.method || options.method === 'GET' )
                {
                    this.cache.set( cacheKey, {
                        data,
                        timestamp: Date.now()
                    } );
                }

                return data;
            } catch ( error )
            {
                attempt++;
                if ( attempt >= this.retryCount )
                {
                    throw error;
                }
                await this.delay( this.retryDelay * attempt );
            }
        }
    }

    delay ( ms )
    {
        return new Promise( resolve => setTimeout( resolve, ms ) );
    }

    clearCache ()
    {
        this.cache.clear();
    }
}

// Global XUI API service instance
const xuiAPI = new XUIAPIService();

// 1. Live XUI Server Browser Component
document.addEventListener( 'alpine:init', () =>
{
    Alpine.data( 'liveXUIServerBrowser', () => ( {
        servers: [],
        loading: false,
        error: null,
        searchQuery: '',
        sortBy: 'health',
        sortDirection: 'desc',
        filterStatus: 'all',
        autoRefresh: true,
        refreshInterval: 30000,
        refreshTimer: null,
        selectedServers: new Set(),

        init ()
        {
            this.loadServers();
            if ( this.autoRefresh )
            {
                this.startAutoRefresh();
            }
        },

        async loadServers ()
        {
            this.loading = true;
            this.error = null;

            try
            {
                const response = await xuiAPI.request( '/servers' );
                this.servers = response.data.map( server => ( {
                    ...server,
                    health: this.calculateHealth( server ),
                    lastChecked: new Date().toISOString()
                } ) );
            } catch ( error )
            {
                this.error = `Failed to load servers: ${ error.message }`;
                console.error( 'Server loading error:', error );
            } finally
            {
                this.loading = false;
            }
        },

        calculateHealth ( server )
        {
            if ( !server.online ) return 0;

            let health = 100;

            // CPU usage impact
            if ( server.cpu_usage > 80 ) health -= 30;
            else if ( server.cpu_usage > 60 ) health -= 15;

            // Memory usage impact
            if ( server.memory_usage > 90 ) health -= 25;
            else if ( server.memory_usage > 70 ) health -= 10;

            // Response time impact
            if ( server.response_time > 5000 ) health -= 20;
            else if ( server.response_time > 2000 ) health -= 10;

            // Active connections impact
            const connectionRatio = server.active_connections / server.max_connections;
            if ( connectionRatio > 0.9 ) health -= 15;
            else if ( connectionRatio > 0.7 ) health -= 5;

            return Math.max( 0, health );
        },

        getHealthColor ( health )
        {
            if ( health >= 80 ) return 'text-green-500';
            if ( health >= 60 ) return 'text-yellow-500';
            if ( health >= 40 ) return 'text-orange-500';
            return 'text-red-500';
        },

        getHealthIcon ( health )
        {
            if ( health >= 80 ) return '🟢';
            if ( health >= 60 ) return '🟡';
            if ( health >= 40 ) return '🟠';
            return '🔴';
        },

        get filteredServers ()
        {
            let filtered = this.servers;

            // Filter by search query
            if ( this.searchQuery )
            {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter( server =>
                    server.name.toLowerCase().includes( query ) ||
                    server.location.toLowerCase().includes( query ) ||
                    server.ip.includes( query )
                );
            }

            // Filter by status
            switch ( this.filterStatus )
            {
                case 'online':
                    filtered = filtered.filter( s => s.online );
                    break;
                case 'offline':
                    filtered = filtered.filter( s => !s.online );
                    break;
                case 'healthy':
                    filtered = filtered.filter( s => s.health >= 80 );
                    break;
                case 'warning':
                    filtered = filtered.filter( s => s.health >= 40 && s.health < 80 );
                    break;
                case 'critical':
                    filtered = filtered.filter( s => s.health < 40 );
                    break;
            }

            // Sort servers
            filtered.sort( ( a, b ) =>
            {
                let aValue = a[ this.sortBy ];
                let bValue = b[ this.sortBy ];

                if ( typeof aValue === 'string' )
                {
                    aValue = aValue.toLowerCase();
                    bValue = bValue.toLowerCase();
                }

                if ( this.sortDirection === 'asc' )
                {
                    return aValue > bValue ? 1 : -1;
                } else
                {
                    return aValue < bValue ? 1 : -1;
                }
            } );

            return filtered;
        },

        sortServers ( field )
        {
            if ( this.sortBy === field )
            {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else
            {
                this.sortBy = field;
                this.sortDirection = 'desc';
            }
        },

        toggleServerSelection ( serverId )
        {
            if ( this.selectedServers.has( serverId ) )
            {
                this.selectedServers.delete( serverId );
            } else
            {
                this.selectedServers.add( serverId );
            }
        },

        selectAllFiltered ()
        {
            this.filteredServers.forEach( server =>
            {
                this.selectedServers.add( server.id );
            } );
        },

        clearSelection ()
        {
            this.selectedServers.clear();
        },

        async performBulkAction ( action )
        {
            if ( this.selectedServers.size === 0 )
            {
                alert( 'No servers selected' );
                return;
            }

            const serverIds = Array.from( this.selectedServers );

            try
            {
                this.loading = true;
                await xuiAPI.request( `/servers/bulk-${ action }`, {
                    method: 'POST',
                    body: JSON.stringify( { server_ids: serverIds } )
                } );

                await this.loadServers();
                this.clearSelection();

                this.$dispatch( 'notification', {
                    type: 'success',
                    message: `Bulk ${ action } completed successfully`
                } );
            } catch ( error )
            {
                this.$dispatch( 'notification', {
                    type: 'error',
                    message: `Bulk ${ action } failed: ${ error.message }`
                } );
            } finally
            {
                this.loading = false;
            }
        },

        async testConnection ( serverId )
        {
            const server = this.servers.find( s => s.id === serverId );
            if ( !server ) return;

            try
            {
                server.testing = true;
                const response = await xuiAPI.request( `/servers/${ serverId }/test` );

                server.online = response.success;
                server.response_time = response.response_time;
                server.lastChecked = new Date().toISOString();
                server.health = this.calculateHealth( server );

                this.$dispatch( 'notification', {
                    type: response.success ? 'success' : 'error',
                    message: response.success ? 'Connection successful' : 'Connection failed'
                } );
            } catch ( error )
            {
                server.online = false;
                this.$dispatch( 'notification', {
                    type: 'error',
                    message: `Connection test failed: ${ error.message }`
                } );
            } finally
            {
                server.testing = false;
            }
        },

        startAutoRefresh ()
        {
            this.refreshTimer = setInterval( () =>
            {
                this.loadServers();
            }, this.refreshInterval );
        },

        stopAutoRefresh ()
        {
            if ( this.refreshTimer )
            {
                clearInterval( this.refreshTimer );
                this.refreshTimer = null;
            }
        },

        toggleAutoRefresh ()
        {
            this.autoRefresh = !this.autoRefresh;
            if ( this.autoRefresh )
            {
                this.startAutoRefresh();
            } else
            {
                this.stopAutoRefresh();
            }
        },

        destroy ()
        {
            this.stopAutoRefresh();
        }
    } ) );

    // 2. XUI Inbound Manager Component
    Alpine.data( 'xuiInboundManager', () => ( {
        inbounds: [],
        selectedServer: null,
        draggedInbound: null,
        dropZones: [ 'active', 'inactive', 'delete' ],
        loading: false,
        error: null,
        showCreateModal: false,
        editingInbound: null,

        init ()
        {
            this.setupDragAndDrop();
        },

        async loadInbounds ( serverId )
        {
            if ( !serverId ) return;

            this.loading = true;
            this.error = null;

            try
            {
                const response = await xuiAPI.request( `/servers/${ serverId }/inbounds` );
                this.inbounds = response.data;
                this.selectedServer = serverId;
            } catch ( error )
            {
                this.error = `Failed to load inbounds: ${ error.message }`;
            } finally
            {
                this.loading = false;
            }
        },

        setupDragAndDrop ()
        {
            // Drag start
            this.$nextTick( () =>
            {
                document.addEventListener( 'dragstart', ( e ) =>
                {
                    if ( e.target.classList.contains( 'draggable-inbound' ) )
                    {
                        this.draggedInbound = parseInt( e.target.dataset.inboundId );
                        e.dataTransfer.effectAllowed = 'move';
                    }
                } );

                // Drag end
                document.addEventListener( 'dragend', ( e ) =>
                {
                    this.draggedInbound = null;
                    document.querySelectorAll( '.drop-zone' ).forEach( zone =>
                    {
                        zone.classList.remove( 'drag-over' );
                    } );
                } );

                // Drop handling
                document.addEventListener( 'drop', async ( e ) =>
                {
                    e.preventDefault();

                    if ( !this.draggedInbound ) return;

                    const dropZone = e.target.closest( '.drop-zone' );
                    if ( !dropZone ) return;

                    const action = dropZone.dataset.action;
                    await this.handleInboundDrop( this.draggedInbound, action );
                } );

                // Drag over
                document.addEventListener( 'dragover', ( e ) =>
                {
                    e.preventDefault();
                    const dropZone = e.target.closest( '.drop-zone' );
                    if ( dropZone )
                    {
                        dropZone.classList.add( 'drag-over' );
                    }
                } );

                // Drag leave
                document.addEventListener( 'dragleave', ( e ) =>
                {
                    const dropZone = e.target.closest( '.drop-zone' );
                    if ( dropZone && !dropZone.contains( e.relatedTarget ) )
                    {
                        dropZone.classList.remove( 'drag-over' );
                    }
                } );
            } );
        },

        async handleInboundDrop ( inboundId, action )
        {
            const inbound = this.inbounds.find( i => i.id === inboundId );
            if ( !inbound ) return;

            try
            {
                switch ( action )
                {
                    case 'active':
                        if ( !inbound.enable )
                        {
                            await this.toggleInbound( inboundId, true );
                        }
                        break;
                    case 'inactive':
                        if ( inbound.enable )
                        {
                            await this.toggleInbound( inboundId, false );
                        }
                        break;
                    case 'delete':
                        if ( confirm( `Are you sure you want to delete inbound "${ inbound.remark }"?` ) )
                        {
                            await this.deleteInbound( inboundId );
                        }
                        break;
                }
            } catch ( error )
            {
                this.$dispatch( 'notification', {
                    type: 'error',
                    message: `Action failed: ${ error.message }`
                } );
            }
        },

        async toggleInbound ( inboundId, enable )
        {
            try
            {
                await xuiAPI.request( `/inbounds/${ inboundId }/toggle`, {
                    method: 'POST',
                    body: JSON.stringify( { enable } )
                } );

                const inbound = this.inbounds.find( i => i.id === inboundId );
                if ( inbound )
                {
                    inbound.enable = enable;
                }

                this.$dispatch( 'notification', {
                    type: 'success',
                    message: `Inbound ${ enable ? 'enabled' : 'disabled' } successfully`
                } );
            } catch ( error )
            {
                throw error;
            }
        },

        async deleteInbound ( inboundId )
        {
            try
            {
                await xuiAPI.request( `/inbounds/${ inboundId }`, {
                    method: 'DELETE'
                } );

                this.inbounds = this.inbounds.filter( i => i.id !== inboundId );

                this.$dispatch( 'notification', {
                    type: 'success',
                    message: 'Inbound deleted successfully'
                } );
            } catch ( error )
            {
                throw error;
            }
        },

        async createInbound ( inboundData )
        {
            try
            {
                this.loading = true;
                const response = await xuiAPI.request( '/inbounds', {
                    method: 'POST',
                    body: JSON.stringify( {
                        ...inboundData,
                        server_id: this.selectedServer
                    } )
                } );

                this.inbounds.push( response.data );
                this.showCreateModal = false;

                this.$dispatch( 'notification', {
                    type: 'success',
                    message: 'Inbound created successfully'
                } );
            } catch ( error )
            {
                this.$dispatch( 'notification', {
                    type: 'error',
                    message: `Failed to create inbound: ${ error.message }`
                } );
            } finally
            {
                this.loading = false;
            }
        },

        async updateInbound ( inboundId, inboundData )
        {
            try
            {
                const response = await xuiAPI.request( `/inbounds/${ inboundId }`, {
                    method: 'PUT',
                    body: JSON.stringify( inboundData )
                } );

                const index = this.inbounds.findIndex( i => i.id === inboundId );
                if ( index !== -1 )
                {
                    this.inbounds[ index ] = response.data;
                }

                this.editingInbound = null;

                this.$dispatch( 'notification', {
                    type: 'success',
                    message: 'Inbound updated successfully'
                } );
            } catch ( error )
            {
                this.$dispatch( 'notification', {
                    type: 'error',
                    message: `Failed to update inbound: ${ error.message }`
                } );
            }
        },

        getInboundsByStatus ( status )
        {
            return this.inbounds.filter( inbound =>
            {
                switch ( status )
                {
                    case 'active':
                        return inbound.enable;
                    case 'inactive':
                        return !inbound.enable;
                    default:
                        return true;
                }
            } );
        },

        formatTraffic ( bytes )
        {
            if ( bytes === 0 ) return '0 B';
            const k = 1024;
            const sizes = [ 'B', 'KB', 'MB', 'GB', 'TB' ];
            const i = Math.floor( Math.log( bytes ) / Math.log( k ) );
            return parseFloat( ( bytes / Math.pow( k, i ) ).toFixed( 2 ) ) + ' ' + sizes[ i ];
        }
    } ) );

    // 3. Client Configuration Builder Component
    Alpine.data( 'clientConfigurationBuilder', () => ( {
        config: {
            protocol: 'vless',
            security: 'reality',
            network: 'tcp',
            port: 443,
            uuid: '',
            flow: '',
            encryption: 'none',
            headerType: 'none',
            host: '',
            path: '',
            serviceName: '',
            alpn: [],
            fingerprint: 'chrome',
            publicKey: '',
            shortId: '',
            spiderX: '/'
        },
        protocols: [ 'vless', 'vmess', 'trojan', 'shadowsocks' ],
        securities: [ 'none', 'tls', 'reality' ],
        networks: [ 'tcp', 'ws', 'grpc', 'kcp', 'quic' ],
        headerTypes: [ 'none', 'http' ],
        fingerprints: [ 'chrome', 'firefox', 'safari', 'ios', 'android', 'edge', 'random' ],
        generatedLink: '',
        qrCode: '',
        loading: false,
        validationErrors: {},

        init ()
        {
            this.generateUUID();
            this.updateConfiguration();
        },

        generateUUID ()
        {
            this.config.uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace( /[xy]/g, function ( c )
            {
                const r = Math.random() * 16 | 0;
                const v = c == 'x' ? r : ( r & 0x3 | 0x8 );
                return v.toString( 16 );
            } );
        },

        generateKeys ()
        {
            if ( this.config.security === 'reality' )
            {
                // Generate reality keys (simplified for demo)
                this.config.publicKey = btoa( Math.random().toString() ).substring( 0, 43 ) + '=';
                this.config.shortId = Math.random().toString( 16 ).substring( 2, 10 );
            }
        },

        validateConfiguration ()
        {
            this.validationErrors = {};

            if ( !this.config.uuid )
            {
                this.validationErrors.uuid = 'UUID is required';
            }

            if ( !this.config.port || this.config.port < 1 || this.config.port > 65535 )
            {
                this.validationErrors.port = 'Valid port number is required (1-65535)';
            }

            if ( this.config.network === 'ws' && !this.config.path )
            {
                this.validationErrors.path = 'Path is required for WebSocket';
            }

            if ( this.config.network === 'grpc' && !this.config.serviceName )
            {
                this.validationErrors.serviceName = 'Service name is required for gRPC';
            }

            if ( this.config.security === 'reality' && !this.config.publicKey )
            {
                this.validationErrors.publicKey = 'Public key is required for Reality';
            }

            return Object.keys( this.validationErrors ).length === 0;
        },

        async updateConfiguration ()
        {
            if ( !this.validateConfiguration() )
            {
                return;
            }

            this.loading = true;

            try
            {
                const response = await xuiAPI.request( '/config/generate', {
                    method: 'POST',
                    body: JSON.stringify( this.config )
                } );

                this.generatedLink = response.data.link;
                this.qrCode = response.data.qr_code;
            } catch ( error )
            {
                this.$dispatch( 'notification', {
                    type: 'error',
                    message: `Configuration generation failed: ${ error.message }`
                } );
            } finally
            {
                this.loading = false;
            }
        },

        copyToClipboard ( text )
        {
            navigator.clipboard.writeText( text ).then( () =>
            {
                this.$dispatch( 'notification', {
                    type: 'success',
                    message: 'Copied to clipboard'
                } );
            } ).catch( () =>
            {
                // Fallback for older browsers
                const textArea = document.createElement( 'textarea' );
                textArea.value = text;
                document.body.appendChild( textArea );
                textArea.select();
                document.execCommand( 'copy' );
                document.body.removeChild( textArea );

                this.$dispatch( 'notification', {
                    type: 'success',
                    message: 'Copied to clipboard'
                } );
            } );
        },

        downloadQR ()
        {
            if ( !this.qrCode ) return;

            const link = document.createElement( 'a' );
            link.href = this.qrCode;
            link.download = `${ this.config.protocol }-config-qr.png`;
            link.click();
        },

        exportConfig ()
        {
            const configData = {
                ...this.config,
                generated_link: this.generatedLink,
                created_at: new Date().toISOString()
            };

            const blob = new Blob( [ JSON.stringify( configData, null, 2 ) ], {
                type: 'application/json'
            } );

            const link = document.createElement( 'a' );
            link.href = URL.createObjectURL( blob );
            link.download = `${ this.config.protocol }-config.json`;
            link.click();
        },

        loadPreset ( presetName )
        {
            const presets = {
                'vless-reality': {
                    protocol: 'vless',
                    security: 'reality',
                    network: 'tcp',
                    port: 443,
                    flow: '',
                    encryption: 'none',
                    fingerprint: 'chrome',
                    spiderX: '/'
                },
                'vmess-ws-tls': {
                    protocol: 'vmess',
                    security: 'tls',
                    network: 'ws',
                    port: 443,
                    headerType: 'none',
                    path: '/websocket',
                    host: 'example.com'
                },
                'trojan-tcp': {
                    protocol: 'trojan',
                    security: 'tls',
                    network: 'tcp',
                    port: 443,
                    headerType: 'none'
                }
            };

            if ( presets[ presetName ] )
            {
                this.config = { ...this.config, ...presets[ presetName ] };
                this.generateUUID();
                this.updateConfiguration();
            }
        }
    } ) );

    // 4. XUI Connection Tester Component
    Alpine.data( 'xuiConnectionTester', () => ( {
        servers: [],
        testResults: new Map(),
        isTestingAll: false,
        batchSize: 3,
        testTimeout: 10000,

        init ()
        {
            this.loadServers();
        },

        async loadServers ()
        {
            try
            {
                const response = await xuiAPI.request( '/servers' );
                this.servers = response.data;
            } catch ( error )
            {
                console.error( 'Failed to load servers:', error );
            }
        },

        async testSingleConnection ( serverId )
        {
            const server = this.servers.find( s => s.id === serverId );
            if ( !server ) return;

            this.testResults.set( serverId, {
                status: 'testing',
                startTime: Date.now()
            } );

            try
            {
                const response = await Promise.race( [
                    xuiAPI.request( `/servers/${ serverId }/test` ),
                    new Promise( ( _, reject ) =>
                        setTimeout( () => reject( new Error( 'Timeout' ) ), this.testTimeout )
                    )
                ] );

                const duration = Date.now() - this.testResults.get( serverId ).startTime;

                this.testResults.set( serverId, {
                    status: response.success ? 'success' : 'failed',
                    duration,
                    details: response,
                    timestamp: new Date().toISOString()
                } );
            } catch ( error )
            {
                const duration = Date.now() - this.testResults.get( serverId ).startTime;

                this.testResults.set( serverId, {
                    status: 'error',
                    duration,
                    error: error.message,
                    timestamp: new Date().toISOString()
                } );
            }
        },

        async testAllConnections ()
        {
            this.isTestingAll = true;
            this.testResults.clear();

            try
            {
                // Test servers in batches to avoid overwhelming the system
                for ( let i = 0; i < this.servers.length; i += this.batchSize )
                {
                    const batch = this.servers.slice( i, i + this.batchSize );
                    const promises = batch.map( server => this.testSingleConnection( server.id ) );
                    await Promise.all( promises );
                }
            } finally
            {
                this.isTestingAll = false;
            }
        },

        getStatusIcon ( serverId )
        {
            const result = this.testResults.get( serverId );
            if ( !result ) return '⚫';

            switch ( result.status )
            {
                case 'testing':
                    return '🔄';
                case 'success':
                    return '✅';
                case 'failed':
                    return '❌';
                case 'error':
                    return '⚠️';
                default:
                    return '⚫';
            }
        },

        getStatusColor ( serverId )
        {
            const result = this.testResults.get( serverId );
            if ( !result ) return 'text-gray-500';

            switch ( result.status )
            {
                case 'testing':
                    return 'text-blue-500';
                case 'success':
                    return 'text-green-500';
                case 'failed':
                    return 'text-red-500';
                case 'error':
                    return 'text-yellow-500';
                default:
                    return 'text-gray-500';
            }
        },

        formatDuration ( ms )
        {
            if ( ms < 1000 ) return `${ ms }ms`;
            return `${ ( ms / 1000 ).toFixed( 2 ) }s`;
        },

        exportResults ()
        {
            const results = Array.from( this.testResults.entries() ).map( ( [ serverId, result ] ) =>
            {
                const server = this.servers.find( s => s.id === serverId );
                return {
                    server_id: serverId,
                    server_name: server?.name || 'Unknown',
                    server_location: server?.location || 'Unknown',
                    ...result
                };
            } );

            const blob = new Blob( [ JSON.stringify( results, null, 2 ) ], {
                type: 'application/json'
            } );

            const link = document.createElement( 'a' );
            link.href = URL.createObjectURL( blob );
            link.download = `connection-test-results-${ new Date().toISOString().slice( 0, 10 ) }.json`;
            link.click();
        }
    } ) );
} );

// Chart.js integration for traffic monitoring (if Chart.js is available)
if ( typeof Chart !== 'undefined' )
{
    // 5. Inbound Traffic Monitor Component
    document.addEventListener( 'alpine:init', () =>
    {
        Alpine.data( 'inboundTrafficMonitor', () => ( {
            selectedInbound: null,
            timeRange: '24h',
            chartData: null,
            chart: null,
            realTimeData: [],
            maxDataPoints: 50,
            updateInterval: 5000,
            autoUpdate: true,
            updateTimer: null,

            init ()
            {
                this.$nextTick( () =>
                {
                    this.initializeChart();
                } );
            },

            initializeChart ()
            {
                const ctx = this.$refs.trafficChart?.getContext( '2d' );
                if ( !ctx ) return;

                this.chart = new Chart( ctx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [ {
                            label: 'Upload (MB/s)',
                            data: [],
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            tension: 0.4
                        }, {
                            label: 'Download (MB/s)',
                            data: [],
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4
                        } ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Traffic (MB/s)'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Time'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false
                            }
                        },
                        interaction: {
                            mode: 'nearest',
                            axis: 'x',
                            intersect: false
                        }
                    }
                } );

                if ( this.autoUpdate )
                {
                    this.startRealTimeUpdates();
                }
            },

            async loadTrafficData ( inboundId )
            {
                if ( !inboundId ) return;

                try
                {
                    const response = await xuiAPI.request( `/inbounds/${ inboundId }/traffic?range=${ this.timeRange }` );
                    this.chartData = response.data;
                    this.updateChart();
                } catch ( error )
                {
                    console.error( 'Failed to load traffic data:', error );
                }
            },

            updateChart ()
            {
                if ( !this.chart || !this.chartData ) return;

                this.chart.data.labels = this.chartData.labels;
                this.chart.data.datasets[ 0 ].data = this.chartData.upload;
                this.chart.data.datasets[ 1 ].data = this.chartData.download;
                this.chart.update();
            },

            async addRealTimeData ()
            {
                if ( !this.selectedInbound ) return;

                try
                {
                    const response = await xuiAPI.request( `/inbounds/${ this.selectedInbound }/traffic/current` );
                    const timestamp = new Date().toLocaleTimeString();

                    this.realTimeData.push( {
                        timestamp,
                        upload: response.data.upload_speed,
                        download: response.data.download_speed
                    } );

                    // Keep only the last maxDataPoints
                    if ( this.realTimeData.length > this.maxDataPoints )
                    {
                        this.realTimeData = this.realTimeData.slice( -this.maxDataPoints );
                    }

                    // Update chart with real-time data
                    if ( this.chart )
                    {
                        this.chart.data.labels = this.realTimeData.map( d => d.timestamp );
                        this.chart.data.datasets[ 0 ].data = this.realTimeData.map( d => d.upload );
                        this.chart.data.datasets[ 1 ].data = this.realTimeData.map( d => d.download );
                        this.chart.update( 'none' ); // No animation for real-time updates
                    }
                } catch ( error )
                {
                    console.error( 'Failed to get real-time data:', error );
                }
            },

            startRealTimeUpdates ()
            {
                this.updateTimer = setInterval( () =>
                {
                    this.addRealTimeData();
                }, this.updateInterval );
            },

            stopRealTimeUpdates ()
            {
                if ( this.updateTimer )
                {
                    clearInterval( this.updateTimer );
                    this.updateTimer = null;
                }
            },

            toggleRealTime ()
            {
                this.autoUpdate = !this.autoUpdate;
                if ( this.autoUpdate )
                {
                    this.startRealTimeUpdates();
                } else
                {
                    this.stopRealTimeUpdates();
                }
            },

            changeTimeRange ( range )
            {
                this.timeRange = range;
                if ( this.selectedInbound )
                {
                    this.loadTrafficData( this.selectedInbound );
                }
            },

            selectInbound ( inboundId )
            {
                this.selectedInbound = inboundId;
                this.realTimeData = [];
                this.loadTrafficData( inboundId );
            },

            exportChartData ()
            {
                if ( !this.chartData && this.realTimeData.length === 0 ) return;

                const data = this.chartData || {
                    labels: this.realTimeData.map( d => d.timestamp ),
                    upload: this.realTimeData.map( d => d.upload ),
                    download: this.realTimeData.map( d => d.download )
                };

                const blob = new Blob( [ JSON.stringify( data, null, 2 ) ], {
                    type: 'application/json'
                } );

                const link = document.createElement( 'a' );
                link.href = URL.createObjectURL( blob );
                link.download = `traffic-data-${ this.selectedInbound }-${ new Date().toISOString().slice( 0, 10 ) }.json`;
                link.click();
            },

            destroy ()
            {
                this.stopRealTimeUpdates();
                if ( this.chart )
                {
                    this.chart.destroy();
                }
            }
        } ) );
    } );
}

// 6. XUI Server Selector Component
document.addEventListener( 'alpine:init', () =>
{
    Alpine.data( 'xuiServerSelector', () => ( {
        servers: [],
        userPreferences: {
            location: '',
            protocol: '',
            maxLatency: 200,
            minSpeed: 10,
            loadBalancing: true
        },
        recommendations: [],
        selectedServer: null,
        loading: false,
        testingRecommendations: false,

        init ()
        {
            this.loadServers();
            this.loadUserPreferences();
        },

        async loadServers ()
        {
            this.loading = true;
            try
            {
                const response = await xuiAPI.request( '/servers' );
                this.servers = response.data;
                this.generateRecommendations();
            } catch ( error )
            {
                console.error( 'Failed to load servers:', error );
            } finally
            {
                this.loading = false;
            }
        },

        loadUserPreferences ()
        {
            const saved = localStorage.getItem( 'xuiServerPreferences' );
            if ( saved )
            {
                this.userPreferences = { ...this.userPreferences, ...JSON.parse( saved ) };
            }
        },

        saveUserPreferences ()
        {
            localStorage.setItem( 'xuiServerPreferences', JSON.stringify( this.userPreferences ) );
            this.generateRecommendations();
        },

        generateRecommendations ()
        {
            if ( this.servers.length === 0 ) return;

            // Score each server based on user preferences
            const scoredServers = this.servers.map( server =>
            {
                let score = 0;
                let reasons = [];

                // Location preference
                if ( this.userPreferences.location &&
                    server.location.toLowerCase().includes( this.userPreferences.location.toLowerCase() ) )
                {
                    score += 30;
                    reasons.push( 'Preferred location' );
                }

                // Protocol preference
                if ( this.userPreferences.protocol &&
                    server.supported_protocols.includes( this.userPreferences.protocol ) )
                {
                    score += 20;
                    reasons.push( 'Supports preferred protocol' );
                }

                // Latency check
                if ( server.avg_latency <= this.userPreferences.maxLatency )
                {
                    score += 25;
                    reasons.push( 'Low latency' );
                } else
                {
                    score -= 10;
                    reasons.push( 'High latency' );
                }

                // Speed check
                if ( server.avg_speed >= this.userPreferences.minSpeed )
                {
                    score += 20;
                    reasons.push( 'High speed' );
                }

                // Server health
                const health = this.calculateServerHealth( server );
                score += health * 0.1;
                if ( health >= 80 ) reasons.push( 'Excellent health' );
                else if ( health >= 60 ) reasons.push( 'Good health' );
                else reasons.push( 'Poor health' );

                // Load balancing
                if ( this.userPreferences.loadBalancing )
                {
                    const loadRatio = server.active_connections / server.max_connections;
                    if ( loadRatio < 0.5 )
                    {
                        score += 15;
                        reasons.push( 'Low load' );
                    } else if ( loadRatio > 0.8 )
                    {
                        score -= 15;
                        reasons.push( 'High load' );
                    }
                }

                // Availability bonus
                if ( server.uptime >= 99 )
                {
                    score += 10;
                    reasons.push( 'High uptime' );
                }

                return {
                    ...server,
                    score: Math.max( 0, score ),
                    reasons
                };
            } );

            // Sort by score and take top recommendations
            this.recommendations = scoredServers
                .sort( ( a, b ) => b.score - a.score )
                .slice( 0, 5 );
        },

        calculateServerHealth ( server )
        {
            if ( !server.online ) return 0;

            let health = 100;

            // CPU usage impact
            if ( server.cpu_usage > 80 ) health -= 30;
            else if ( server.cpu_usage > 60 ) health -= 15;

            // Memory usage impact
            if ( server.memory_usage > 90 ) health -= 25;
            else if ( server.memory_usage > 70 ) health -= 10;

            // Response time impact
            if ( server.response_time > 5000 ) health -= 20;
            else if ( server.response_time > 2000 ) health -= 10;

            return Math.max( 0, health );
        },

        async testRecommendations ()
        {
            this.testingRecommendations = true;

            try
            {
                for ( const server of this.recommendations )
                {
                    server.testing = true;

                    try
                    {
                        const response = await xuiAPI.request( `/servers/${ server.id }/test` );
                        server.testResult = {
                            success: response.success,
                            latency: response.response_time,
                            speed: response.speed_test?.download || null
                        };
                    } catch ( error )
                    {
                        server.testResult = {
                            success: false,
                            error: error.message
                        };
                    } finally
                    {
                        server.testing = false;
                    }
                }

                // Re-sort based on test results
                this.recommendations.sort( ( a, b ) =>
                {
                    const aSuccess = a.testResult?.success ? 1 : 0;
                    const bSuccess = b.testResult?.success ? 1 : 0;
                    return bSuccess - aSuccess || b.score - a.score;
                } );

            } finally
            {
                this.testingRecommendations = false;
            }
        },

        selectServer ( server )
        {
            this.selectedServer = server;
            this.$dispatch( 'server-selected', { server } );
        },

        getScoreColor ( score )
        {
            if ( score >= 80 ) return 'text-green-500';
            if ( score >= 60 ) return 'text-yellow-500';
            if ( score >= 40 ) return 'text-orange-500';
            return 'text-red-500';
        },

        getScoreBadge ( score )
        {
            if ( score >= 80 ) return 'Excellent';
            if ( score >= 60 ) return 'Good';
            if ( score >= 40 ) return 'Fair';
            return 'Poor';
        }
    } ) );

    // 7. Client Usage Analyzer Component
    Alpine.data( 'clientUsageAnalyzer', () => ( {
        clients: [],
        usageData: new Map(),
        selectedClient: null,
        timeRange: '7d',
        sortBy: 'total_usage',
        sortDirection: 'desc',
        filters: {
            status: 'all',
            usage_threshold: 0,
            protocol: 'all'
        },
        analytics: {
            totalUsage: 0,
            activeClients: 0,
            avgUsagePerClient: 0,
            topProtocol: ''
        },
        loading: false,

        init ()
        {
            this.loadClients();
        },

        async loadClients ()
        {
            this.loading = true;
            try
            {
                const response = await xuiAPI.request( '/clients' );
                this.clients = response.data;
                await this.loadUsageData();
                this.calculateAnalytics();
            } catch ( error )
            {
                console.error( 'Failed to load clients:', error );
            } finally
            {
                this.loading = false;
            }
        },

        async loadUsageData ()
        {
            try
            {
                const response = await xuiAPI.request( `/clients/usage?range=${ this.timeRange }` );
                this.usageData = new Map( response.data.map( item => [ item.client_id, item ] ) );

                // Merge usage data with client data
                this.clients.forEach( client =>
                {
                    const usage = this.usageData.get( client.id );
                    if ( usage )
                    {
                        client.usage = usage;
                    }
                } );
            } catch ( error )
            {
                console.error( 'Failed to load usage data:', error );
            }
        },

        calculateAnalytics ()
        {
            const activeClients = this.clients.filter( c => c.usage?.total_usage > 0 );

            this.analytics = {
                totalUsage: this.clients.reduce( ( sum, c ) => sum + ( c.usage?.total_usage || 0 ), 0 ),
                activeClients: activeClients.length,
                avgUsagePerClient: activeClients.length > 0 ?
                    activeClients.reduce( ( sum, c ) => sum + c.usage.total_usage, 0 ) / activeClients.length : 0,
                topProtocol: this.getTopProtocol()
            };
        },

        getTopProtocol ()
        {
            const protocolUsage = {};
            this.clients.forEach( client =>
            {
                if ( client.usage?.total_usage > 0 )
                {
                    protocolUsage[ client.protocol ] = ( protocolUsage[ client.protocol ] || 0 ) + client.usage.total_usage;
                }
            } );

            return Object.entries( protocolUsage )
                .sort( ( a, b ) => b[ 1 ] - a[ 1 ] )[ 0 ]?.[ 0 ] || 'N/A';
        },

        get filteredClients ()
        {
            let filtered = [ ...this.clients ];

            // Filter by status
            if ( this.filters.status !== 'all' )
            {
                filtered = filtered.filter( client =>
                {
                    switch ( this.filters.status )
                    {
                        case 'active':
                            return client.enable && ( client.usage?.total_usage || 0 ) > 0;
                        case 'inactive':
                            return !client.enable || ( client.usage?.total_usage || 0 ) === 0;
                        case 'expired':
                            return client.expiry_time && new Date( client.expiry_time ) < new Date();
                        default:
                            return true;
                    }
                } );
            }

            // Filter by usage threshold
            if ( this.filters.usage_threshold > 0 )
            {
                filtered = filtered.filter( client =>
                    ( client.usage?.total_usage || 0 ) >= this.filters.usage_threshold
                );
            }

            // Filter by protocol
            if ( this.filters.protocol !== 'all' )
            {
                filtered = filtered.filter( client => client.protocol === this.filters.protocol );
            }

            // Sort clients
            filtered.sort( ( a, b ) =>
            {
                let aValue = this.getSortValue( a, this.sortBy );
                let bValue = this.getSortValue( b, this.sortBy );

                if ( typeof aValue === 'string' )
                {
                    aValue = aValue.toLowerCase();
                    bValue = bValue.toLowerCase();
                }

                if ( this.sortDirection === 'asc' )
                {
                    return aValue > bValue ? 1 : -1;
                } else
                {
                    return aValue < bValue ? 1 : -1;
                }
            } );

            return filtered;
        },

        getSortValue ( client, field )
        {
            switch ( field )
            {
                case 'total_usage':
                    return client.usage?.total_usage || 0;
                case 'upload':
                    return client.usage?.upload || 0;
                case 'download':
                    return client.usage?.download || 0;
                case 'last_seen':
                    return client.usage?.last_seen || '';
                case 'expiry_time':
                    return client.expiry_time || '';
                default:
                    return client[ field ] || '';
            }
        },

        sortClients ( field )
        {
            if ( this.sortBy === field )
            {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else
            {
                this.sortBy = field;
                this.sortDirection = 'desc';
            }
        },

        async viewClientDetails ( clientId )
        {
            try
            {
                const response = await xuiAPI.request( `/clients/${ clientId }/details?range=${ this.timeRange }` );
                this.selectedClient = response.data;
            } catch ( error )
            {
                console.error( 'Failed to load client details:', error );
            }
        },

        formatBytes ( bytes )
        {
            if ( bytes === 0 ) return '0 B';
            const k = 1024;
            const sizes = [ 'B', 'KB', 'MB', 'GB', 'TB' ];
            const i = Math.floor( Math.log( bytes ) / Math.log( k ) );
            return parseFloat( ( bytes / Math.pow( k, i ) ).toFixed( 2 ) ) + ' ' + sizes[ i ];
        },

        formatDate ( dateString )
        {
            if ( !dateString ) return 'Never';
            return new Date( dateString ).toLocaleString();
        },

        getUsagePercentage ( used, total )
        {
            if ( !total || total === 0 ) return 0;
            return Math.min( 100, ( used / total ) * 100 );
        },

        getStatusColor ( client )
        {
            if ( !client.enable ) return 'text-gray-500';
            if ( client.expiry_time && new Date( client.expiry_time ) < new Date() ) return 'text-red-500';
            if ( ( client.usage?.total_usage || 0 ) > 0 ) return 'text-green-500';
            return 'text-yellow-500';
        },

        async exportUsageReport ()
        {
            const report = {
                generated_at: new Date().toISOString(),
                time_range: this.timeRange,
                analytics: this.analytics,
                clients: this.filteredClients.map( client => ( {
                    id: client.id,
                    email: client.email,
                    protocol: client.protocol,
                    enable: client.enable,
                    expiry_time: client.expiry_time,
                    usage: client.usage || {}
                } ) )
            };

            const blob = new Blob( [ JSON.stringify( report, null, 2 ) ], {
                type: 'application/json'
            } );

            const link = document.createElement( 'a' );
            link.href = URL.createObjectURL( blob );
            link.download = `usage-report-${ this.timeRange }-${ new Date().toISOString().slice( 0, 10 ) }.json`;
            link.click();
        }
    } ) );
} );

// Export the XUI API service for use in other components
window.xuiAPI = xuiAPI;
