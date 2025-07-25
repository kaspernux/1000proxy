/**
 * XUI Integration Interface Components
 * Real-time server management and monitoring interface
 */

// LiveXUIServerBrowser Component
window.liveXUIServerBrowser = function ()
{
    return {
        servers: [],
        selectedServer: null,
        healthStatus: {},
        isLoading: false,
        refreshInterval: null,
        autoRefreshEnabled: true,
        searchQuery: '',
        sortBy: 'name',
        sortDirection: 'asc',
        filters: {
            status: 'all',
            protocol: 'all',
            region: 'all'
        },

        init ()
        {
            this.loadServers();
            if ( this.autoRefreshEnabled )
            {
                this.startAutoRefresh();
            }
            this.setupWebSocketConnection();
        },

        async loadServers ()
        {
            this.isLoading = true;
            try
            {
                const response = await fetch( '/api/xui/servers' );
                this.servers = await response.json();
                await this.checkServerHealth();
            } catch ( error )
            {
                console.error( 'Failed to load servers:', error );
                this.$dispatch( 'notification', {
                    type: 'error',
                    message: 'Failed to load XUI servers'
                } );
            } finally
            {
                this.isLoading = false;
            }
        },

        async checkServerHealth ()
        {
            for ( const server of this.servers )
            {
                try
                {
                    const response = await fetch( `/api/xui/health/${ server.id }` );
                    const health = await response.json();
                    this.healthStatus[ server.id ] = {
                        status: health.status,
                        latency: health.latency,
                        lastCheck: new Date(),
                        inboundCount: health.inboundCount,
                        clientCount: health.clientCount,
                        uptime: health.uptime
                    };
                } catch ( error )
                {
                    this.healthStatus[ server.id ] = {
                        status: 'error',
                        latency: null,
                        lastCheck: new Date(),
                        error: error.message
                    };
                }
            }
        },

        setupWebSocketConnection ()
        {
            if ( window.Echo )
            {
                window.Echo.channel( 'xui-health' )
                    .listen( 'ServerHealthUpdated', ( event ) =>
                    {
                        this.healthStatus[ event.serverId ] = event.health;
                    } );
            }
        },

        startAutoRefresh ()
        {
            this.refreshInterval = setInterval( () =>
            {
                this.checkServerHealth();
            }, 30000 ); // 30 seconds
        },

        stopAutoRefresh ()
        {
            if ( this.refreshInterval )
            {
                clearInterval( this.refreshInterval );
                this.refreshInterval = null;
            }
        },

        toggleAutoRefresh ()
        {
            this.autoRefreshEnabled = !this.autoRefreshEnabled;
            if ( this.autoRefreshEnabled )
            {
                this.startAutoRefresh();
            } else
            {
                this.stopAutoRefresh();
            }
        },

        selectServer ( server )
        {
            this.selectedServer = server;
            this.$dispatch( 'server-selected', { server } );
        },

        getFilteredServers ()
        {
            let filtered = this.servers;

            // Apply search filter
            if ( this.searchQuery )
            {
                filtered = filtered.filter( server =>
                    server.name.toLowerCase().includes( this.searchQuery.toLowerCase() ) ||
                    server.host.toLowerCase().includes( this.searchQuery.toLowerCase() ) ||
                    server.location.toLowerCase().includes( this.searchQuery.toLowerCase() )
                );
            }

            // Apply status filter
            if ( this.filters.status !== 'all' )
            {
                filtered = filtered.filter( server =>
                    this.healthStatus[ server.id ]?.status === this.filters.status
                );
            }

            // Apply protocol filter
            if ( this.filters.protocol !== 'all' )
            {
                filtered = filtered.filter( server =>
                    server.protocols.includes( this.filters.protocol )
                );
            }

            // Apply region filter
            if ( this.filters.region !== 'all' )
            {
                filtered = filtered.filter( server =>
                    server.region === this.filters.region
                );
            }

            // Apply sorting
            filtered.sort( ( a, b ) =>
            {
                let aValue = a[ this.sortBy ];
                let bValue = b[ this.sortBy ];

                if ( this.sortBy === 'health' )
                {
                    aValue = this.healthStatus[ a.id ]?.latency || 9999;
                    bValue = this.healthStatus[ b.id ]?.latency || 9999;
                }

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

        setSortBy ( field )
        {
            if ( this.sortBy === field )
            {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else
            {
                this.sortBy = field;
                this.sortDirection = 'asc';
            }
        },

        getHealthStatusClass ( serverId )
        {
            const status = this.healthStatus[ serverId ]?.status;
            switch ( status )
            {
                case 'online': return 'status-online';
                case 'offline': return 'status-offline';
                case 'maintenance': return 'status-maintenance';
                case 'error': return 'status-unknown';
                default: return 'status-unknown';
            }
        },

        getLatencyClass ( serverId )
        {
            const latency = this.healthStatus[ serverId ]?.latency;
            if ( !latency ) return 'performance-poor';
            if ( latency < 50 ) return 'performance-excellent';
            if ( latency < 100 ) return 'performance-good';
            if ( latency < 200 ) return 'performance-fair';
            return 'performance-poor';
        },

        async testConnection ( server )
        {
            try
            {
                this.$dispatch( 'notification', {
                    type: 'info',
                    message: `Testing connection to ${ server.name }...`
                } );

                const response = await fetch( `/api/xui/test/${ server.id }`, {
                    method: 'POST'
                } );
                const result = await response.json();

                this.$dispatch( 'notification', {
                    type: result.success ? 'success' : 'error',
                    message: result.message
                } );

                if ( result.success )
                {
                    await this.checkServerHealth();
                }
            } catch ( error )
            {
                this.$dispatch( 'notification', {
                    type: 'error',
                    message: `Connection test failed: ${ error.message }`
                } );
            }
        },

        destroy ()
        {
            this.stopAutoRefresh();
            if ( window.Echo )
            {
                window.Echo.leaveChannel( 'xui-health' );
            }
        }
    };
};

// XUIInboundManager Component with Drag-and-Drop
window.xuiInboundManager = function ()
{
    return {
        inbounds: [],
        selectedInbound: null,
        draggedInbound: null,
        isLoading: false,
        showCreateModal: false,
        showEditModal: false,
        editingInbound: null,
        protocols: [ 'vless', 'vmess', 'trojan', 'shadowsocks' ],
        networks: [ 'tcp', 'ws', 'grpc', 'h2' ],

        init ()
        {
            this.loadInbounds();
        },

        async loadInbounds ()
        {
            this.isLoading = true;
            try
            {
                const serverId = this.selectedServer?.id;
                if ( !serverId ) return;

                const response = await fetch( `/api/xui/inbounds/${ serverId }` );
                this.inbounds = await response.json();
            } catch ( error )
            {
                console.error( 'Failed to load inbounds:', error );
                this.$dispatch( 'notification', {
                    type: 'error',
                    message: 'Failed to load inbounds'
                } );
            } finally
            {
                this.isLoading = false;
            }
        },

        // Drag and Drop functionality
        startDrag ( event, inbound )
        {
            this.draggedInbound = inbound;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData( 'text/html', event.target.outerHTML );
        },

        allowDrop ( event )
        {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
        },

        drop ( event, targetIndex )
        {
            event.preventDefault();

            if ( !this.draggedInbound ) return;

            const draggedIndex = this.inbounds.findIndex( i => i.id === this.draggedInbound.id );

            if ( draggedIndex === targetIndex ) return;

            // Reorder inbounds array
            const reorderedInbounds = [ ...this.inbounds ];
            const [ draggedItem ] = reorderedInbounds.splice( draggedIndex, 1 );
            reorderedInbounds.splice( targetIndex, 0, draggedItem );

            this.inbounds = reorderedInbounds;
            this.draggedInbound = null;

            // Save new order to backend
            this.saveInboundOrder();
        },

        async saveInboundOrder ()
        {
            try
            {
                const orderData = this.inbounds.map( ( inbound, index ) => ( {
                    id: inbound.id,
                    order: index + 1
                } ) );

                await fetch( '/api/xui/inbounds/reorder', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).content
                    },
                    body: JSON.stringify( { order: orderData } )
                } );

                this.$dispatch( 'notification', {
                    type: 'success',
                    message: 'Inbound order updated successfully'
                } );
            } catch ( error )
            {
                this.$dispatch( 'notification', {
                    type: 'error',
                    message: 'Failed to update inbound order'
                } );
            }
        },

        selectInbound ( inbound )
        {
            this.selectedInbound = inbound;
            this.$dispatch( 'inbound-selected', { inbound } );
        },

        openCreateModal ()
        {
            this.showCreateModal = true;
            this.editingInbound = {
                port: '',
                protocol: 'vless',
                network: 'tcp',
                remark: '',
                enable: true
            };
        },

        openEditModal ( inbound )
        {
            this.editingInbound = { ...inbound };
            this.showEditModal = true;
        },

        async saveInbound ()
        {
            try
            {
                const url = this.editingInbound.id
                    ? `/api/xui/inbounds/${ this.editingInbound.id }`
                    : '/api/xui/inbounds';

                const method = this.editingInbound.id ? 'PUT' : 'POST';

                const response = await fetch( url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).content
                    },
                    body: JSON.stringify( this.editingInbound )
                } );

                if ( response.ok )
                {
                    await this.loadInbounds();
                    this.showCreateModal = false;
                    this.showEditModal = false;

                    this.$dispatch( 'notification', {
                        type: 'success',
                        message: this.editingInbound.id
                            ? 'Inbound updated successfully'
                            : 'Inbound created successfully'
                    } );
                } else
                {
                    throw new Error( 'Server error' );
                }
            } catch ( error )
            {
                this.$dispatch( 'notification', {
                    type: 'error',
                    message: 'Failed to save inbound'
                } );
            }
        },

        async deleteInbound ( inbound )
        {
            if ( !confirm( `Are you sure you want to delete inbound "${ inbound.remark }"?` ) )
            {
                return;
            }

            try
            {
                const response = await fetch( `/api/xui/inbounds/${ inbound.id }`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).content
                    }
                } );

                if ( response.ok )
                {
                    await this.loadInbounds();
                    this.$dispatch( 'notification', {
                        type: 'success',
                        message: 'Inbound deleted successfully'
                    } );
                } else
                {
                    throw new Error( 'Server error' );
                }
            } catch ( error )
            {
                this.$dispatch( 'notification', {
                    type: 'error',
                    message: 'Failed to delete inbound'
                } );
            }
        },

        getProtocolIcon ( protocol )
        {
            const icons = {
                vless: 'fas fa-shield-alt',
                vmess: 'fas fa-eye-slash',
                trojan: 'fas fa-horse',
                shadowsocks: 'fas fa-user-secret'
            };
            return icons[ protocol ] || 'fas fa-network-wired';
        },

        getNetworkIcon ( network )
        {
            const icons = {
                tcp: 'fas fa-ethernet',
                ws: 'fas fa-globe',
                grpc: 'fas fa-server',
                h2: 'fas fa-rocket'
            };
            return icons[ network ] || 'fas fa-network-wired';
        }
    };
};

// ClientConfigurationBuilder Component
window.clientConfigurationBuilder = function ()
{
    return {
        configuration: {
            protocol: 'vless',
            host: '',
            port: '',
            uuid: '',
            encryption: 'none',
            network: 'tcp',
            security: 'none',
            path: '',
            serviceName: '',
            alpn: '',
            fingerprint: 'chrome',
            allowInsecure: false,
            sni: ''
        },
        generatedConfig: '',
        qrCode: '',
        subscriptionUrl: '',
        previewMode: 'json',
        isGenerating: false,

        init ()
        {
            this.generateUUID();
            this.watchConfiguration();
        },

        watchConfiguration ()
        {
            this.$watch( 'configuration', () =>
            {
                this.debounceGenerate();
            }, { deep: true } );
        },

        debounceGenerate: debounce( function ()
        {
            this.generateConfiguration();
        }, 300 ),

        generateUUID ()
        {
            this.configuration.uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace( /[xy]/g, function ( c )
            {
                const r = Math.random() * 16 | 0;
                const v = c == 'x' ? r : ( r & 0x3 | 0x8 );
                return v.toString( 16 );
            } );
        },

        async generateConfiguration ()
        {
            if ( !this.configuration.host || !this.configuration.port )
            {
                return;
            }

            this.isGenerating = true;
            try
            {
                const response = await fetch( '/api/xui/generate-config', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).content
                    },
                    body: JSON.stringify( this.configuration )
                } );

                const result = await response.json();

                this.generatedConfig = result.config;
                this.qrCode = result.qrCode;
                this.subscriptionUrl = result.subscriptionUrl;

                this.$dispatch( 'config-generated', {
                    config: this.generatedConfig,
                    qrCode: this.qrCode
                } );

            } catch ( error )
            {
                console.error( 'Failed to generate configuration:', error );
                this.$dispatch( 'notification', {
                    type: 'error',
                    message: 'Failed to generate configuration'
                } );
            } finally
            {
                this.isGenerating = false;
            }
        },

        copyConfiguration ()
        {
            navigator.clipboard.writeText( this.generatedConfig ).then( () =>
            {
                this.$dispatch( 'notification', {
                    type: 'success',
                    message: 'Configuration copied to clipboard'
                } );
            } );
        },

        downloadConfiguration ()
        {
            const blob = new Blob( [ this.generatedConfig ], { type: 'text/plain' } );
            const url = window.URL.createObjectURL( blob );
            const a = document.createElement( 'a' );
            a.href = url;
            a.download = `${ this.configuration.protocol }_config.json`;
            a.click();
            window.URL.revokeObjectURL( url );
        },

        getConfigPreview ()
        {
            if ( !this.generatedConfig ) return '';

            switch ( this.previewMode )
            {
                case 'json':
                    return this.formatJSON( this.generatedConfig );
                case 'url':
                    return this.generatedConfig;
                case 'qr':
                    return this.qrCode;
                default:
                    return this.generatedConfig;
            }
        },

        formatJSON ( jsonString )
        {
            try
            {
                const parsed = JSON.parse( jsonString );
                return JSON.stringify( parsed, null, 2 );
            } catch
            {
                return jsonString;
            }
        },

        resetConfiguration ()
        {
            this.configuration = {
                protocol: 'vless',
                host: '',
                port: '',
                uuid: '',
                encryption: 'none',
                network: 'tcp',
                security: 'none',
                path: '',
                serviceName: '',
                alpn: '',
                fingerprint: 'chrome',
                allowInsecure: false,
                sni: ''
            };
            this.generateUUID();
            this.generatedConfig = '';
            this.qrCode = '';
        }
    };
};

// Utility function for debouncing
function debounce ( func, wait )
{
    let timeout;
    return function executedFunction ( ...args )
    {
        const later = () =>
        {
            clearTimeout( timeout );
            func.apply( this, args );
        };
        clearTimeout( timeout );
        timeout = setTimeout( later, wait );
    };
}

// Register components globally
window.addEventListener( 'alpine:init', () =>
{
    Alpine.data( 'liveXUIServerBrowser', window.liveXUIServerBrowser );
    Alpine.data( 'xuiInboundManager', window.xuiInboundManager );
    Alpine.data( 'clientConfigurationBuilder', window.clientConfigurationBuilder );
} );
