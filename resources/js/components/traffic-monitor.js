// Live Traffic Monitoring Widget Component
export default function trafficMonitor ()
{
    return {
        // Traffic data
        traffic: {
            current: {
                download: 0,
                upload: 0,
                total: 0
            },
            peak: {
                download: 0,
                upload: 0,
                timestamp: null
            },
            average: {
                download: 0,
                upload: 0
            },
            history: []
        },

        // Configuration
        maxHistoryPoints: 60, // Store 60 data points
        refreshInterval: 2000, // Update every 2 seconds
        intervalId: null,

        // UI state
        isMonitoring: false,
        showDetails: false,
        selectedTimeRange: '1h', // 1h, 6h, 12h, 24h
        units: 'auto', // auto, bytes, kb, mb, gb

        // Chart state for mini chart
        miniChart: null,
        chartCanvas: null,

        // Initialize component
        init ()
        {
            this.$nextTick( () =>
            {
                this.initializeMiniChart();
                this.startMonitoring();
                this.loadHistoricalData();
            } );
        },

        // Initialize mini chart for traffic visualization
        initializeMiniChart ()
        {
            this.chartCanvas = this.$refs.miniChart;
            if ( !this.chartCanvas ) return;

            const ctx = this.chartCanvas.getContext( '2d' );

            this.miniChart = new Chart( ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Download',
                            data: [],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Upload',
                            data: [],
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: false
                        }
                    },
                    scales: {
                        x: {
                            display: false
                        },
                        y: {
                            display: false,
                            beginAtZero: true
                        }
                    },
                    elements: {
                        point: {
                            radius: 0
                        }
                    },
                    animation: {
                        duration: 0
                    }
                }
            } );
        },

        // Start traffic monitoring
        startMonitoring ()
        {
            if ( this.intervalId )
            {
                clearInterval( this.intervalId );
            }

            this.isMonitoring = true;
            this.intervalId = setInterval( () =>
            {
                this.updateTrafficData();
            }, this.refreshInterval );
        },

        // Stop traffic monitoring
        stopMonitoring ()
        {
            if ( this.intervalId )
            {
                clearInterval( this.intervalId );
                this.intervalId = null;
            }
            this.isMonitoring = false;
        },

        // Toggle monitoring
        toggleMonitoring ()
        {
            if ( this.isMonitoring )
            {
                this.stopMonitoring();
            } else
            {
                this.startMonitoring();
            }
        },

        // Update traffic data
        async updateTrafficData ()
        {
            try
            {
                // Fetch real-time traffic data from API
                const response = await fetch( '/api/traffic/realtime' );
                const data = await response.json();

                this.processTrafficData( data );
            } catch ( error )
            {
                console.error( 'Failed to fetch traffic data:', error );
                // Generate sample data for demonstration
                this.generateSampleTrafficData();
            }
        },

        // Generate sample traffic data for demonstration
        generateSampleTrafficData ()
        {
            const baseDownload = 50 + Math.random() * 100; // 50-150 MB/s
            const baseUpload = 20 + Math.random() * 40;    // 20-60 MB/s

            // Add some variation
            const downloadVariation = ( Math.random() - 0.5 ) * 20;
            const uploadVariation = ( Math.random() - 0.5 ) * 10;

            const currentData = {
                download: Math.max( 0, baseDownload + downloadVariation ) * 1024 * 1024, // Convert to bytes
                upload: Math.max( 0, baseUpload + uploadVariation ) * 1024 * 1024,
                timestamp: new Date()
            };

            this.processTrafficData( currentData );
        },

        // Process incoming traffic data
        processTrafficData ( data )
        {
            const now = new Date();

            // Update current traffic
            this.traffic.current = {
                download: data.download || 0,
                upload: data.upload || 0,
                total: ( data.download || 0 ) + ( data.upload || 0 )
            };

            // Update peak traffic
            if ( this.traffic.current.download > this.traffic.peak.download )
            {
                this.traffic.peak.download = this.traffic.current.download;
                this.traffic.peak.timestamp = now;
            }
            if ( this.traffic.current.upload > this.traffic.peak.upload )
            {
                this.traffic.peak.upload = this.traffic.current.upload;
                this.traffic.peak.timestamp = now;
            }

            // Add to history
            this.traffic.history.push( {
                timestamp: now,
                download: this.traffic.current.download,
                upload: this.traffic.current.upload,
                total: this.traffic.current.total
            } );

            // Maintain history limit
            if ( this.traffic.history.length > this.maxHistoryPoints )
            {
                this.traffic.history.shift();
            }

            // Update averages
            this.updateAverages();

            // Update mini chart
            this.updateMiniChart();
        },

        // Update average calculations
        updateAverages ()
        {
            if ( this.traffic.history.length === 0 ) return;

            const totalDownload = this.traffic.history.reduce( ( sum, item ) => sum + item.download, 0 );
            const totalUpload = this.traffic.history.reduce( ( sum, item ) => sum + item.upload, 0 );

            this.traffic.average = {
                download: totalDownload / this.traffic.history.length,
                upload: totalUpload / this.traffic.history.length
            };
        },

        // Update mini chart
        updateMiniChart ()
        {
            if ( !this.miniChart || this.traffic.history.length === 0 ) return;

            const labels = this.traffic.history.map( item => this.formatTime( item.timestamp ) );
            const downloadData = this.traffic.history.map( item => item.download );
            const uploadData = this.traffic.history.map( item => item.upload );

            this.miniChart.data.labels = labels;
            this.miniChart.data.datasets[ 0 ].data = downloadData;
            this.miniChart.data.datasets[ 1 ].data = uploadData;

            this.miniChart.update( 'none' );
        },

        // Load historical data
        async loadHistoricalData ()
        {
            try
            {
                const response = await fetch( `/api/traffic/history?range=${ this.selectedTimeRange }` );
                const data = await response.json();

                if ( data.history )
                {
                    this.traffic.history = data.history.slice( -this.maxHistoryPoints );
                    this.updateAverages();
                    this.updateMiniChart();
                }
            } catch ( error )
            {
                console.error( 'Failed to load historical data:', error );
            }
        },

        // Format traffic value with appropriate units
        formatTrafficValue ( bytes, showUnits = true )
        {
            if ( this.units === 'bytes' )
            {
                return showUnits ? `${ bytes.toLocaleString() } B` : bytes.toLocaleString();
            }

            const units = [ 'B', 'KB', 'MB', 'GB', 'TB' ];
            let size = bytes;
            let unitIndex = 0;

            if ( this.units === 'auto' )
            {
                while ( size >= 1024 && unitIndex < units.length - 1 )
                {
                    size /= 1024;
                    unitIndex++;
                }
            } else
            {
                const targetUnit = this.units.toUpperCase();
                const targetIndex = units.indexOf( targetUnit );
                if ( targetIndex !== -1 )
                {
                    unitIndex = targetIndex;
                    size = bytes / Math.pow( 1024, unitIndex );
                }
            }

            const formatted = size < 10 ? size.toFixed( 2 ) : size.toFixed( 1 );
            return showUnits ? `${ formatted } ${ units[ unitIndex ] }` : formatted;
        },

        // Format speed (bytes per second)
        formatSpeed ( bytesPerSecond )
        {
            return this.formatTrafficValue( bytesPerSecond ) + '/s';
        },

        // Format time
        formatTime ( date )
        {
            return date.toLocaleTimeString( 'en-US', {
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            } );
        },

        // Get traffic status color
        getTrafficStatusColor ()
        {
            const totalTraffic = this.traffic.current.total;
            const peakTraffic = Math.max( this.traffic.peak.download, this.traffic.peak.upload );

            if ( totalTraffic === 0 ) return 'text-gray-400';
            if ( totalTraffic > peakTraffic * 0.8 ) return 'text-red-500';
            if ( totalTraffic > peakTraffic * 0.6 ) return 'text-yellow-500';
            return 'text-green-500';
        },

        // Get progress bar percentage
        getProgressPercentage ( current, peak )
        {
            if ( peak === 0 ) return 0;
            return Math.min( ( current / peak ) * 100, 100 );
        },

        // Export traffic data
        exportTrafficData ()
        {
            const csvContent = [
                [ 'Timestamp', 'Download (bytes)', 'Upload (bytes)', 'Total (bytes)' ],
                ...this.traffic.history.map( item => [
                    item.timestamp.toISOString(),
                    item.download,
                    item.upload,
                    item.total
                ] )
            ].map( row => row.join( ',' ) ).join( '\n' );

            const blob = new Blob( [ csvContent ], { type: 'text/csv' } );
            const url = URL.createObjectURL( blob );
            const link = document.createElement( 'a' );
            link.href = url;
            link.download = `traffic-data-${ new Date().toISOString().split( 'T' )[ 0 ] }.csv`;
            link.click();
            URL.revokeObjectURL( url );
        },

        // Reset monitoring data
        resetData ()
        {
            this.traffic.history = [];
            this.traffic.peak = {
                download: 0,
                upload: 0,
                timestamp: null
            };
            this.traffic.average = {
                download: 0,
                upload: 0
            };

            if ( this.miniChart )
            {
                this.miniChart.data.labels = [];
                this.miniChart.data.datasets[ 0 ].data = [];
                this.miniChart.data.datasets[ 1 ].data = [];
                this.miniChart.update();
            }
        },

        // Change time range
        changeTimeRange ( range )
        {
            this.selectedTimeRange = range;
            this.loadHistoricalData();
        },

        // Change units
        changeUnits ( units )
        {
            this.units = units;
        },

        // Cleanup
        destroy ()
        {
            this.stopMonitoring();
            if ( this.miniChart )
            {
                this.miniChart.destroy();
                this.miniChart = null;
            }
        }
    };
}
