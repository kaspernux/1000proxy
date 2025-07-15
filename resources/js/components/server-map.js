// Interactive Server Map Component with Country Flags
export default function serverMap ()
{
    return {
        // Map state
        map: null,
        mapContainer: null,
        servers: [],
        selectedServer: null,

        // Map configuration
        center: [ 20, 0 ], // Default center coordinates
        zoom: 2,
        maxZoom: 18,
        minZoom: 1,

        // Server data
        serverLocations: {},
        countryStats: {},

        // Filter options
        filters: {
            status: 'all', // all, online, offline, maintenance
            region: 'all', // all, north-america, europe, asia, etc.
            protocol: 'all', // all, vless, vmess, trojan, shadowsocks
            category: 'all' // all, gaming, streaming, business
        },

        // UI state
        loading: true,
        showLegend: true,
        showStats: true,
        autoRefresh: true,
        refreshInterval: 60000, // 1 minute
        intervalId: null,

        // Initialize map
        init ()
        {
            this.$nextTick( () =>
            {
                this.mapContainer = this.$refs.mapContainer;
                if ( this.mapContainer )
                {
                    this.initializeMap();
                    this.loadServerData();
                    if ( this.autoRefresh )
                    {
                        this.startAutoRefresh();
                    }
                }
            } );
        },

        // Initialize Leaflet map
        initializeMap ()
        {
            // Initialize the map
            this.map = L.map( this.mapContainer, {
                center: this.center,
                zoom: this.zoom,
                maxZoom: this.maxZoom,
                minZoom: this.minZoom,
                zoomControl: true,
                scrollWheelZoom: true
            } );

            // Add tile layer
            L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: this.maxZoom
            } ).addTo( this.map );

            // Add custom controls
            this.addCustomControls();

            // Map event listeners
            this.map.on( 'click', () =>
            {
                this.selectedServer = null;
            } );
        },

        // Load server data from API
        async loadServerData ()
        {
            try
            {
                this.loading = true;

                // Fetch server data from API
                const response = await fetch( '/api/servers/map-data' );
                const data = await response.json();

                this.servers = data.servers || [];
                this.countryStats = data.stats || {};

                // Process server locations
                this.processServerLocations();

                // Add markers to map
                this.addServerMarkers();

                this.loading = false;
            } catch ( error )
            {
                console.error( 'Failed to load server data:', error );
                this.loading = false;

                // Load sample data for demonstration
                this.loadSampleData();
            }
        },

        // Load sample data for demonstration
        loadSampleData ()
        {
            this.servers = [
                {
                    id: 1,
                    name: 'US-East-01',
                    country: 'United States',
                    countryCode: 'US',
                    city: 'New York',
                    coordinates: [ 40.7128, -74.0060 ],
                    status: 'online',
                    protocol: 'vless',
                    category: 'gaming',
                    clients: 45,
                    maxClients: 100,
                    uptime: 99.9,
                    flag: '🇺🇸'
                },
                {
                    id: 2,
                    name: 'EU-West-01',
                    country: 'Germany',
                    countryCode: 'DE',
                    city: 'Frankfurt',
                    coordinates: [ 50.1109, 8.6821 ],
                    status: 'online',
                    protocol: 'vmess',
                    category: 'streaming',
                    clients: 32,
                    maxClients: 75,
                    uptime: 98.7,
                    flag: '🇩🇪'
                },
                {
                    id: 3,
                    name: 'AS-East-01',
                    country: 'Japan',
                    countryCode: 'JP',
                    city: 'Tokyo',
                    coordinates: [ 35.6762, 139.6503 ],
                    status: 'maintenance',
                    protocol: 'trojan',
                    category: 'business',
                    clients: 0,
                    maxClients: 50,
                    uptime: 95.2,
                    flag: '🇯🇵'
                },
                {
                    id: 4,
                    name: 'EU-North-01',
                    country: 'United Kingdom',
                    countryCode: 'GB',
                    city: 'London',
                    coordinates: [ 51.5074, -0.1278 ],
                    status: 'online',
                    protocol: 'shadowsocks',
                    category: 'streaming',
                    clients: 67,
                    maxClients: 120,
                    uptime: 99.1,
                    flag: '🇬🇧'
                }
            ];

            this.processServerLocations();
            this.addServerMarkers();
            this.loading = false;
        },

        // Process server locations for clustering
        processServerLocations ()
        {
            this.serverLocations = {};

            this.servers.forEach( server =>
            {
                const key = `${ server.countryCode }_${ server.city }`;
                if ( !this.serverLocations[ key ] )
                {
                    this.serverLocations[ key ] = {
                        country: server.country,
                        countryCode: server.countryCode,
                        city: server.city,
                        coordinates: server.coordinates,
                        flag: server.flag,
                        servers: []
                    };
                }
                this.serverLocations[ key ].servers.push( server );
            } );
        },

        // Add server markers to map
        addServerMarkers ()
        {
            // Clear existing markers
            this.map.eachLayer( ( layer ) =>
            {
                if ( layer instanceof L.Marker )
                {
                    this.map.removeLayer( layer );
                }
            } );

            // Add markers for each location
            Object.values( this.serverLocations ).forEach( location =>
            {
                const filteredServers = this.getFilteredServers( location.servers );
                if ( filteredServers.length === 0 ) return;

                const marker = this.createServerMarker( location, filteredServers );
                marker.addTo( this.map );
            } );
        },

        // Create server marker
        createServerMarker ( location, servers )
        {
            const totalServers = servers.length;
            const onlineServers = servers.filter( s => s.status === 'online' ).length;
            const statusColor = this.getLocationStatusColor( servers );

            // Create custom marker HTML
            const markerHtml = `
                <div class="server-marker ${ statusColor }" data-servers="${ totalServers }">
                    <div class="server-flag">${ location.flag }</div>
                    <div class="server-count">${ totalServers }</div>
                    <div class="server-pulse ${ statusColor }"></div>
                </div>
            `;

            // Create marker
            const marker = L.marker( location.coordinates, {
                icon: L.divIcon( {
                    html: markerHtml,
                    className: 'custom-marker',
                    iconSize: [ 40, 40 ],
                    iconAnchor: [ 20, 20 ]
                } )
            } );

            // Add click event
            marker.on( 'click', ( e ) =>
            {
                e.originalEvent.stopPropagation();
                this.showLocationDetails( location, servers );
            } );

            // Add popup
            const popupContent = this.createPopupContent( location, servers );
            marker.bindPopup( popupContent, {
                maxWidth: 300,
                className: 'server-popup'
            } );

            return marker;
        },

        // Get location status color
        getLocationStatusColor ( servers )
        {
            const onlineCount = servers.filter( s => s.status === 'online' ).length;
            const totalCount = servers.length;
            const ratio = onlineCount / totalCount;

            if ( ratio === 1 ) return 'status-online';
            if ( ratio >= 0.5 ) return 'status-warning';
            return 'status-offline';
        },

        // Create popup content
        createPopupContent ( location, servers )
        {
            const onlineServers = servers.filter( s => s.status === 'online' ).length;
            const totalClients = servers.reduce( ( sum, s ) => sum + s.clients, 0 );
            const maxClients = servers.reduce( ( sum, s ) => sum + s.maxClients, 0 );
            const avgUptime = servers.reduce( ( sum, s ) => sum + s.uptime, 0 ) / servers.length;

            return `
                <div class="server-popup-content">
                    <div class="popup-header">
                        <h3>${ location.flag } ${ location.city }, ${ location.country }</h3>
                    </div>
                    <div class="popup-stats">
                        <div class="stat-item">
                            <span class="stat-label">Servers:</span>
                            <span class="stat-value">${ onlineServers }/${ servers.length } online</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Clients:</span>
                            <span class="stat-value">${ totalClients }/${ maxClients }</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Uptime:</span>
                            <span class="stat-value">${ avgUptime.toFixed( 1 ) }%</span>
                        </div>
                    </div>
                    <div class="popup-actions">
                        <button class="btn-view-details" onclick="showLocationDetails('${ location.countryCode }', '${ location.city }')">
                            View Details
                        </button>
                    </div>
                </div>
            `;
        },

        // Show location details in sidebar
        showLocationDetails ( location, servers )
        {
            this.selectedServer = {
                location: location,
                servers: servers
            };

            // Animate to location
            this.map.flyTo( location.coordinates, 8, {
                duration: 1.5
            } );
        },

        // Filter servers based on current filters
        getFilteredServers ( servers )
        {
            return servers.filter( server =>
            {
                if ( this.filters.status !== 'all' && server.status !== this.filters.status )
                {
                    return false;
                }
                if ( this.filters.protocol !== 'all' && server.protocol !== this.filters.protocol )
                {
                    return false;
                }
                if ( this.filters.category !== 'all' && server.category !== this.filters.category )
                {
                    return false;
                }
                return true;
            } );
        },

        // Update filters
        updateFilter ( filterType, value )
        {
            this.filters[ filterType ] = value;
            this.addServerMarkers();
        },

        // Add custom map controls
        addCustomControls ()
        {
            // Fullscreen control
            const fullscreenControl = L.control( { position: 'topright' } );
            fullscreenControl.onAdd = () =>
            {
                const div = L.DomUtil.create( 'div', 'leaflet-control-fullscreen' );
                div.innerHTML = '<button title="Toggle Fullscreen">⛶</button>';
                L.DomEvent.on( div, 'click', this.toggleFullscreen.bind( this ) );
                return div;
            };
            fullscreenControl.addTo( this.map );

            // Refresh control
            const refreshControl = L.control( { position: 'topright' } );
            refreshControl.onAdd = () =>
            {
                const div = L.DomUtil.create( 'div', 'leaflet-control-refresh' );
                div.innerHTML = '<button title="Refresh Data">⟳</button>';
                L.DomEvent.on( div, 'click', this.refreshServerData.bind( this ) );
                return div;
            };
            refreshControl.addTo( this.map );
        },

        // Toggle fullscreen
        toggleFullscreen ()
        {
            const container = this.mapContainer.parentElement;
            if ( document.fullscreenElement )
            {
                document.exitFullscreen();
            } else
            {
                container.requestFullscreen();
            }
        },

        // Refresh server data
        async refreshServerData ()
        {
            await this.loadServerData();
            this.showNotification( 'Server data refreshed', 'success' );
        },

        // Auto refresh functionality
        startAutoRefresh ()
        {
            if ( this.intervalId )
            {
                clearInterval( this.intervalId );
            }

            this.intervalId = setInterval( () =>
            {
                this.refreshServerData();
            }, this.refreshInterval );
        },

        stopAutoRefresh ()
        {
            if ( this.intervalId )
            {
                clearInterval( this.intervalId );
                this.intervalId = null;
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

        // Utility functions
        showNotification ( message, type = 'info' )
        {
            // Use global notification system
            if ( window.showNotification )
            {
                window.showNotification( type, message );
            }
        },

        formatUptime ( uptime )
        {
            return `${ uptime.toFixed( 1 ) }%`;
        },

        getProtocolColor ( protocol )
        {
            const colors = {
                vless: '#3b82f6',
                vmess: '#10b981',
                trojan: '#f59e0b',
                shadowsocks: '#8b5cf6'
            };
            return colors[ protocol ] || '#6b7280';
        },

        // Cleanup
        destroy ()
        {
            this.stopAutoRefresh();
            if ( this.map )
            {
                this.map.remove();
                this.map = null;
            }
        }
    };
}
