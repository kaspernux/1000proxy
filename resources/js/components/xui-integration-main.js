/**
 * XUI Integration Interface - Main Integration Module
 * Combines all XUI components and provides unified initialization
 */

// Import all XUI components
import './xui-integration-interface.js';
import './xui-monitoring-tools.js';
import './client-usage-analyzer.js';

// XUI Integration Manager
window.xuiIntegrationManager = function ()
{
    return {
        components: {
            serverBrowser: null,
            inboundManager: null,
            configBuilder: null,
            connectionTester: null,
            trafficMonitor: null,
            serverSelector: null,
            usageAnalyzer: null
        },
        isInitialized: false,
        currentView: 'overview',

        init ()
        {
            this.initializeComponents();
            this.setupEventListeners();
            this.isInitialized = true;
            console.log( 'XUI Integration Interface initialized successfully' );
        },

        initializeComponents ()
        {
            // Initialize all XUI components
            this.components.serverBrowser = Alpine.store( 'xuiServerBrowser', {} );
            this.components.inboundManager = Alpine.store( 'xuiInboundManager', {} );
            this.components.configBuilder = Alpine.store( 'xuiConfigBuilder', {} );
            this.components.connectionTester = Alpine.store( 'xuiConnectionTester', {} );
            this.components.trafficMonitor = Alpine.store( 'xuiTrafficMonitor', {} );
            this.components.serverSelector = Alpine.store( 'xuiServerSelector', {} );
            this.components.usageAnalyzer = Alpine.store( 'xuiUsageAnalyzer', {} );
        },

        setupEventListeners ()
        {
            // Listen for component events
            window.addEventListener( 'server-selected', ( event ) =>
            {
                this.handleServerSelection( event.detail.server );
            } );

            window.addEventListener( 'client-selected', ( event ) =>
            {
                this.handleClientSelection( event.detail.client );
            } );

            window.addEventListener( 'inbound-created', ( event ) =>
            {
                this.handleInboundCreation( event.detail.inbound );
            } );

            window.addEventListener( 'configuration-generated', ( event ) =>
            {
                this.handleConfigurationGeneration( event.detail.config );
            } );

            // Setup real-time updates
            this.setupRealTimeUpdates();
        },

        setupRealTimeUpdates ()
        {
            // WebSocket connection for real-time updates
            if ( window.Echo )
            {
                window.Echo.channel( 'xui-updates' )
                    .listen( 'ServerStatusUpdated', ( event ) =>
                    {
                        this.handleServerStatusUpdate( event );
                    } )
                    .listen( 'InboundTrafficUpdated', ( event ) =>
                    {
                        this.handleTrafficUpdate( event );
                    } )
                    .listen( 'ClientStatusChanged', ( event ) =>
                    {
                        this.handleClientStatusChange( event );
                    } );
            }

            // Fallback polling for systems without WebSocket
            if ( !window.Echo )
            {
                this.startPolling();
            }
        },

        startPolling ()
        {
            setInterval( () =>
            {
                this.refreshAllComponents();
            }, 30000 ); // Poll every 30 seconds
        },

        async refreshAllComponents ()
        {
            const promises = [
                this.refreshServerBrowser(),
                this.refreshInboundManager(),
                this.refreshTrafficMonitor(),
                this.refreshUsageAnalyzer()
            ];

            try
            {
                await Promise.all( promises );
            } catch ( error )
            {
                console.error( 'Error refreshing components:', error );
            }
        },

        async refreshServerBrowser ()
        {
            if ( this.components.serverBrowser?.loadServers )
            {
                await this.components.serverBrowser.loadServers();
            }
        },

        async refreshInboundManager ()
        {
            if ( this.components.inboundManager?.loadInbounds )
            {
                await this.components.inboundManager.loadInbounds();
            }
        },

        async refreshTrafficMonitor ()
        {
            if ( this.components.trafficMonitor?.loadTrafficData )
            {
                await this.components.trafficMonitor.loadTrafficData();
            }
        },

        async refreshUsageAnalyzer ()
        {
            if ( this.components.usageAnalyzer?.loadClientsData )
            {
                await this.components.usageAnalyzer.loadClientsData();
            }
        },

        handleServerSelection ( server )
        {
            console.log( 'Server selected:', server );

            // Update relevant components with selected server
            if ( this.components.inboundManager?.setSelectedServer )
            {
                this.components.inboundManager.setSelectedServer( server );
            }

            if ( this.components.configBuilder?.setSelectedServer )
            {
                this.components.configBuilder.setSelectedServer( server );
            }

            // Dispatch global event
            this.$dispatch( 'xui-server-changed', { server } );
        },

        handleClientSelection ( client )
        {
            console.log( 'Client selected:', client );

            // Update configuration builder with client details
            if ( this.components.configBuilder?.setSelectedClient )
            {
                this.components.configBuilder.setSelectedClient( client );
            }

            // Dispatch global event
            this.$dispatch( 'xui-client-changed', { client } );
        },

        handleInboundCreation ( inbound )
        {
            console.log( 'Inbound created:', inbound );

            // Refresh components that display inbound data
            this.refreshInboundManager();
            this.refreshTrafficMonitor();

            // Show success notification
            this.$dispatch( 'notification', {
                type: 'success',
                message: `Inbound ${ inbound.tag } created successfully`
            } );
        },

        handleConfigurationGeneration ( config )
        {
            console.log( 'Configuration generated:', config );

            // You can add logic here to handle configuration generation
            // For example, auto-copy to clipboard or show sharing options
        },

        handleServerStatusUpdate ( event )
        {
            console.log( 'Server status updated:', event );

            // Update server browser with new status
            if ( this.components.serverBrowser?.updateServerStatus )
            {
                this.components.serverBrowser.updateServerStatus( event.serverId, event.status );
            }
        },

        handleTrafficUpdate ( event )
        {
            console.log( 'Traffic updated:', event );

            // Update traffic monitor with new data
            if ( this.components.trafficMonitor?.updateTrafficData )
            {
                this.components.trafficMonitor.updateTrafficData( event.inboundId, event.data );
            }
        },

        handleClientStatusChange ( event )
        {
            console.log( 'Client status changed:', event );

            // Update usage analyzer with client changes
            if ( this.components.usageAnalyzer?.updateClientStatus )
            {
                this.components.usageAnalyzer.updateClientStatus( event.clientId, event.status );
            }
        },

        setView ( view )
        {
            this.currentView = view;

            // Optionally refresh the active view's data
            switch ( view )
            {
                case 'servers':
                    this.refreshServerBrowser();
                    break;
                case 'inbounds':
                    this.refreshInboundManager();
                    break;
                case 'traffic':
                    this.refreshTrafficMonitor();
                    break;
                case 'clients':
                    this.refreshUsageAnalyzer();
                    break;
            }
        },

        async exportAllData ()
        {
            const data = {
                timestamp: new Date().toISOString(),
                servers: await this.getServerData(),
                inbounds: await this.getInboundData(),
                traffic: await this.getTrafficData(),
                clients: await this.getClientData()
            };

            const blob = new Blob( [ JSON.stringify( data, null, 2 ) ], { type: 'application/json' } );
            const url = URL.createObjectURL( blob );
            const a = document.createElement( 'a' );
            a.href = url;
            a.download = `xui_export_${ new Date().toISOString().split( 'T' )[ 0 ] }.json`;
            a.click();
            URL.revokeObjectURL( url );
        },

        async getServerData ()
        {
            try
            {
                const response = await fetch( '/api/xui/servers' );
                return await response.json();
            } catch ( error )
            {
                console.error( 'Failed to get server data:', error );
                return [];
            }
        },

        async getInboundData ()
        {
            try
            {
                const response = await fetch( '/api/xui/inbounds' );
                return await response.json();
            } catch ( error )
            {
                console.error( 'Failed to get inbound data:', error );
                return [];
            }
        },

        async getTrafficData ()
        {
            try
            {
                const response = await fetch( '/api/xui/traffic-data' );
                return await response.json();
            } catch ( error )
            {
                console.error( 'Failed to get traffic data:', error );
                return {};
            }
        },

        async getClientData ()
        {
            try
            {
                const response = await fetch( '/api/xui/clients-usage' );
                return await response.json();
            } catch ( error )
            {
                console.error( 'Failed to get client data:', error );
                return [];
            }
        },

        destroy ()
        {
            // Clean up intervals and WebSocket connections
            Object.values( this.components ).forEach( component =>
            {
                if ( component?.destroy )
                {
                    component.destroy();
                }
            } );

            if ( window.Echo )
            {
                window.Echo.leaveChannel( 'xui-updates' );
            }

            console.log( 'XUI Integration Interface destroyed' );
        }
    };
};

// Global utility functions for XUI integration
window.xuiUtils = {
    formatBytes: function ( bytes, decimals = 2 )
    {
        if ( bytes === 0 ) return '0 Bytes';

        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = [ 'Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' ];

        const i = Math.floor( Math.log( bytes ) / Math.log( k ) );

        return parseFloat( ( bytes / Math.pow( k, i ) ).toFixed( dm ) ) + ' ' + sizes[ i ];
    },

    formatLatency: function ( latency )
    {
        if ( latency < 50 ) return `${ latency }ms (Excellent)`;
        if ( latency < 100 ) return `${ latency }ms (Good)`;
        if ( latency < 200 ) return `${ latency }ms (Fair)`;
        return `${ latency }ms (Poor)`;
    },

    generateUUID: function ()
    {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace( /[xy]/g, function ( c )
        {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : ( r & 0x3 | 0x8 );
            return v.toString( 16 );
        } );
    },

    validateJSON: function ( str )
    {
        try
        {
            JSON.parse( str );
            return true;
        } catch ( e )
        {
            return false;
        }
    },

    copyToClipboard: async function ( text )
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
            const result = document.execCommand( 'copy' );
            document.body.removeChild( textArea );
            return result;
        }
    },

    downloadAsFile: function ( content, filename, contentType = 'text/plain' )
    {
        const blob = new Blob( [ content ], { type: contentType } );
        const url = URL.createObjectURL( blob );
        const a = document.createElement( 'a' );
        a.href = url;
        a.download = filename;
        a.click();
        URL.revokeObjectURL( url );
    },

    generateQRCode: function ( text, elementId )
    {
        // This requires QR code library (QRCode.js)
        if ( window.QRCode )
        {
            const element = document.getElementById( elementId );
            if ( element )
            {
                element.innerHTML = '';
                new QRCode( element, {
                    text: text,
                    width: 256,
                    height: 256,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                } );
            }
        }
    }
};

// Register the main integration manager with Alpine.js
window.addEventListener( 'alpine:init', () =>
{
    Alpine.data( 'xuiIntegrationManager', window.xuiIntegrationManager );
} );

// Auto-initialize when DOM is ready
document.addEventListener( 'DOMContentLoaded', function ()
{
    // Auto-initialize XUI integration if element exists
    const xuiContainer = document.querySelector( '[x-data*="xuiIntegrationManager"]' );
    if ( xuiContainer && !xuiContainer._x_dataStack )
    {
        // Will be initialized by Alpine.js
        console.log( 'XUI Integration Interface ready for initialization' );
    }
} );

console.log( 'XUI Integration Interface modules loaded successfully' );
