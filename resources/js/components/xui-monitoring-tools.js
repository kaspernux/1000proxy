/**
 * XUI Connection Tester and Traffic Monitor Components
 */

// XUI Connection Tester Component
window.xuiConnectionTester = function ()
{
    return {
        testResults: {},
        isTestingAll: false,
        testHistory: [],
        selectedTest: null,

        init ()
        {
            this.loadTestHistory();
        },

        async testConnection ( server )
        {
            const testId = `test_${ server.id }_${ Date.now() }`;

            this.testResults[ server.id ] = {
                status: 'testing',
                startTime: Date.now(),
                testId: testId
            };

            try
            {
                const response = await fetch( `/api/xui/test-connection/${ server.id }`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).content
                    }
                } );

                const result = await response.json();
                const endTime = Date.now();

                this.testResults[ server.id ] = {
                    status: result.success ? 'success' : 'failed',
                    latency: result.latency,
                    message: result.message,
                    duration: endTime - this.testResults[ server.id ].startTime,
                    timestamp: new Date(),
                    testId: testId,
                    details: result.details || {}
                };

                // Add to history
                this.testHistory.unshift( {
                    serverId: server.id,
                    serverName: server.name,
                    ...this.testResults[ server.id ]
                } );

                // Keep only last 50 tests
                if ( this.testHistory.length > 50 )
                {
                    this.testHistory = this.testHistory.slice( 0, 50 );
                }

                this.saveTestHistory();

            } catch ( error )
            {
                this.testResults[ server.id ] = {
                    status: 'error',
                    message: error.message,
                    timestamp: new Date(),
                    testId: testId
                };
            }
        },

        async testAllConnections ( servers )
        {
            this.isTestingAll = true;

            const promises = servers.map( server => this.testConnection( server ) );

            try
            {
                await Promise.all( promises );
            } catch ( error )
            {
                console.error( 'Error testing connections:', error );
            } finally
            {
                this.isTestingAll = false;
            }
        },

        getTestStatusClass ( serverId )
        {
            const result = this.testResults[ serverId ];
            if ( !result ) return 'status-unknown';

            switch ( result.status )
            {
                case 'testing': return 'status-testing';
                case 'success': return 'status-online';
                case 'failed': return 'status-offline';
                case 'error': return 'status-unknown';
                default: return 'status-unknown';
            }
        },

        getLatencyColor ( latency )
        {
            if ( latency < 50 ) return 'performance-excellent';
            if ( latency < 100 ) return 'performance-good';
            if ( latency < 200 ) return 'performance-fair';
            return 'performance-poor';
        },

        loadTestHistory ()
        {
            const saved = localStorage.getItem( 'xui_test_history' );
            if ( saved )
            {
                try
                {
                    this.testHistory = JSON.parse( saved );
                } catch ( error )
                {
                    console.error( 'Failed to load test history:', error );
                    this.testHistory = [];
                }
            }
        },

        saveTestHistory ()
        {
            localStorage.setItem( 'xui_test_history', JSON.stringify( this.testHistory ) );
        },

        clearTestHistory ()
        {
            this.testHistory = [];
            localStorage.removeItem( 'xui_test_history' );
        },

        exportTestResults ()
        {
            const data = {
                timestamp: new Date().toISOString(),
                results: this.testResults,
                history: this.testHistory
            };

            const blob = new Blob( [ JSON.stringify( data, null, 2 ) ], { type: 'application/json' } );
            const url = URL.createObjectURL( blob );
            const a = document.createElement( 'a' );
            a.href = url;
            a.download = `xui_test_results_${ new Date().toISOString().split( 'T' )[ 0 ] }.json`;
            a.click();
            URL.revokeObjectURL( url );
        }
    };
};

// Traffic Monitor Component
window.inboundTrafficMonitor = function ()
{
    return {
        trafficData: {},
        chartInstances: {},
        updateInterval: null,
        timeRange: '1h',
        selectedMetric: 'bandwidth',
        isLoading: false,
        autoUpdate: true,

        init ()
        {
            this.loadTrafficData();
            if ( this.autoUpdate )
            {
                this.startAutoUpdate();
            }
        },

        async loadTrafficData ()
        {
            this.isLoading = true;
            try
            {
                const response = await fetch( `/api/xui/traffic-data?range=${ this.timeRange }` );
                const data = await response.json();

                this.trafficData = data;
                this.updateCharts();

            } catch ( error )
            {
                console.error( 'Failed to load traffic data:', error );
                this.$dispatch( 'notification', {
                    type: 'error',
                    message: 'Failed to load traffic data'
                } );
            } finally
            {
                this.isLoading = false;
            }
        },

        updateCharts ()
        {
            Object.keys( this.trafficData ).forEach( inboundId =>
            {
                this.updateChart( inboundId );
            } );
        },

        updateChart ( inboundId )
        {
            const chartId = `traffic-chart-${ inboundId }`;
            const ctx = document.getElementById( chartId );

            if ( !ctx ) return;

            const data = this.trafficData[ inboundId ];
            if ( !data ) return;

            // Destroy existing chart
            if ( this.chartInstances[ inboundId ] )
            {
                this.chartInstances[ inboundId ].destroy();
            }

            // Create new chart
            this.chartInstances[ inboundId ] = new Chart( ctx, {
                type: 'line',
                data: {
                    labels: data.timestamps,
                    datasets: [
                        {
                            label: 'Upload',
                            data: data.upload,
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Download',
                            data: data.download,
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: this.getTimeUnit()
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function ( value )
                                {
                                    return formatBytes( value ) + '/s';
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function ( context )
                                {
                                    return context.dataset.label + ': ' + formatBytes( context.parsed.y ) + '/s';
                                }
                            }
                        }
                    }
                }
            } );
        },

        getTimeUnit ()
        {
            switch ( this.timeRange )
            {
                case '1h': return 'minute';
                case '24h': return 'hour';
                case '7d': return 'day';
                case '30d': return 'day';
                default: return 'minute';
            }
        },

        async setTimeRange ( range )
        {
            this.timeRange = range;
            await this.loadTrafficData();
        },

        startAutoUpdate ()
        {
            this.updateInterval = setInterval( () =>
            {
                this.loadTrafficData();
            }, 30000 ); // Update every 30 seconds
        },

        stopAutoUpdate ()
        {
            if ( this.updateInterval )
            {
                clearInterval( this.updateInterval );
                this.updateInterval = null;
            }
        },

        toggleAutoUpdate ()
        {
            this.autoUpdate = !this.autoUpdate;
            if ( this.autoUpdate )
            {
                this.startAutoUpdate();
            } else
            {
                this.stopAutoUpdate();
            }
        },

        getTotalTraffic ( inboundId )
        {
            const data = this.trafficData[ inboundId ];
            if ( !data ) return { upload: 0, download: 0 };

            const totalUpload = data.upload.reduce( ( sum, val ) => sum + val, 0 );
            const totalDownload = data.download.reduce( ( sum, val ) => sum + val, 0 );

            return {
                upload: totalUpload,
                download: totalDownload,
                total: totalUpload + totalDownload
            };
        },

        getAverageSpeed ( inboundId )
        {
            const data = this.trafficData[ inboundId ];
            if ( !data || data.upload.length === 0 ) return { upload: 0, download: 0 };

            const avgUpload = data.upload.reduce( ( sum, val ) => sum + val, 0 ) / data.upload.length;
            const avgDownload = data.download.reduce( ( sum, val ) => sum + val, 0 ) / data.download.length;

            return {
                upload: avgUpload,
                download: avgDownload
            };
        },

        destroy ()
        {
            this.stopAutoUpdate();
            Object.values( this.chartInstances ).forEach( chart =>
            {
                if ( chart ) chart.destroy();
            } );
        }
    };
};

// Server Selector with Auto-recommendation
window.xuiServerSelector = function ()
{
    return {
        servers: [],
        recommendations: [],
        selectedServer: null,
        userPreferences: {
            protocol: 'any',
            region: 'any',
            latencyThreshold: 200,
            prioritizeSpeed: true,
            prioritizeStability: false
        },
        isAnalyzing: false,

        init ()
        {
            this.loadServers();
            this.loadUserPreferences();
        },

        async loadServers ()
        {
            try
            {
                const response = await fetch( '/api/xui/servers' );
                this.servers = await response.json();
                this.analyzeRecommendations();
            } catch ( error )
            {
                console.error( 'Failed to load servers:', error );
            }
        },

        async analyzeRecommendations ()
        {
            this.isAnalyzing = true;

            try
            {
                const response = await fetch( '/api/xui/server-recommendations', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).content
                    },
                    body: JSON.stringify( {
                        preferences: this.userPreferences,
                        servers: this.servers
                    } )
                } );

                this.recommendations = await response.json();

            } catch ( error )
            {
                console.error( 'Failed to analyze recommendations:', error );
                // Fallback to client-side analysis
                this.clientSideRecommendations();
            } finally
            {
                this.isAnalyzing = false;
            }
        },

        clientSideRecommendations ()
        {
            // Simple client-side recommendation algorithm
            const scored = this.servers.map( server =>
            {
                let score = 0;
                const health = this.getServerHealth( server.id );

                // Health score (40% weight)
                if ( health?.status === 'online' ) score += 40;
                else if ( health?.status === 'maintenance' ) score += 20;

                // Latency score (30% weight)
                if ( health?.latency )
                {
                    const latencyScore = Math.max( 0, 30 - ( health.latency / this.userPreferences.latencyThreshold ) * 30 );
                    score += latencyScore;
                }

                // Protocol preference (20% weight)
                if ( this.userPreferences.protocol !== 'any' && server.protocols.includes( this.userPreferences.protocol ) )
                {
                    score += 20;
                }

                // Region preference (10% weight)
                if ( this.userPreferences.region !== 'any' && server.region === this.userPreferences.region )
                {
                    score += 10;
                }

                return { ...server, score };
            } );

            this.recommendations = scored
                .sort( ( a, b ) => b.score - a.score )
                .slice( 0, 5 )
                .map( ( server, index ) => ( {
                    server,
                    rank: index + 1,
                    reason: this.getRecommendationReason( server )
                } ) );
        },

        getRecommendationReason ( server )
        {
            const health = this.getServerHealth( server.id );
            const reasons = [];

            if ( health?.status === 'online' ) reasons.push( 'Currently online' );
            if ( health?.latency && health.latency < 100 ) reasons.push( 'Low latency' );
            if ( server.protocols.includes( this.userPreferences.protocol ) ) reasons.push( 'Supports preferred protocol' );
            if ( server.region === this.userPreferences.region ) reasons.push( 'In preferred region' );

            return reasons.join( ', ' ) || 'Available server';
        },

        selectServer ( server )
        {
            this.selectedServer = server;
            this.$dispatch( 'server-selected', { server } );
        },

        updatePreferences ()
        {
            this.saveUserPreferences();
            this.analyzeRecommendations();
        },

        loadUserPreferences ()
        {
            const saved = localStorage.getItem( 'xui_user_preferences' );
            if ( saved )
            {
                try
                {
                    this.userPreferences = { ...this.userPreferences, ...JSON.parse( saved ) };
                } catch ( error )
                {
                    console.error( 'Failed to load user preferences:', error );
                }
            }
        },

        saveUserPreferences ()
        {
            localStorage.setItem( 'xui_user_preferences', JSON.stringify( this.userPreferences ) );
        },

        getServerHealth ( serverId )
        {
            // This should integrate with the health monitoring system
            return window.serverHealthStatus?.[ serverId ] || null;
        }
    };
};

// Register components
window.addEventListener( 'alpine:init', () =>
{
    Alpine.data( 'xuiConnectionTester', window.xuiConnectionTester );
    Alpine.data( 'inboundTrafficMonitor', window.inboundTrafficMonitor );
    Alpine.data( 'xuiServerSelector', window.xuiServerSelector );
} );
