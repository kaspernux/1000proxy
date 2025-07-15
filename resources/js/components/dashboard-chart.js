// Real-time Dashboard Chart Component with Chart.js Integration
export default function dashboardChart ()
{
    return {
        // Component state
        chart: null,
        chartCanvas: null,
        data: {
            labels: [],
            datasets: []
        },
        config: {
            type: 'line',
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 750,
                easing: 'easeInOutCubic'
            }
        },

        // Chart types available
        chartTypes: {
            line: 'Line Chart',
            bar: 'Bar Chart',
            doughnut: 'Doughnut Chart',
            radar: 'Radar Chart',
            area: 'Area Chart'
        },

        // Chart configuration
        currentType: 'line',
        theme: 'light',
        autoRefresh: true,
        refreshInterval: 30000, // 30 seconds
        intervalId: null,

        // Data management
        maxDataPoints: 24,
        realTimeData: true,

        // Initialize chart
        init ()
        {
            this.$nextTick( () =>
            {
                this.chartCanvas = this.$refs.chartCanvas;
                if ( this.chartCanvas )
                {
                    this.initializeChart();
                    if ( this.autoRefresh )
                    {
                        this.startAutoRefresh();
                    }
                }
            } );
        },

        // Initialize Chart.js instance
        initializeChart ()
        {
            const ctx = this.chartCanvas.getContext( '2d' );

            // Chart.js configuration
            const chartConfig = {
                type: this.currentType,
                data: this.data,
                options: this.getChartOptions()
            };

            // Create chart instance
            this.chart = new Chart( ctx, chartConfig );

            // Load initial data
            this.loadInitialData();
        },

        // Get chart options based on type and theme
        getChartOptions ()
        {
            const isDark = this.theme === 'dark';
            const textColor = isDark ? '#e5e7eb' : '#374151';
            const gridColor = isDark ? '#374151' : '#e5e7eb';

            const baseOptions = {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: textColor,
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: isDark ? '#1f2937' : '#ffffff',
                        titleColor: textColor,
                        bodyColor: textColor,
                        borderColor: gridColor,
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            label: ( context ) => this.formatTooltipLabel( context )
                        }
                    }
                },
                scales: this.getScalesConfig(),
                animation: {
                    duration: 750,
                    easing: 'easeInOutCubic'
                }
            };

            // Type-specific options
            if ( this.currentType === 'line' || this.currentType === 'area' )
            {
                baseOptions.elements = {
                    line: {
                        tension: 0.4,
                        borderWidth: 3
                    },
                    point: {
                        radius: 4,
                        hoverRadius: 6,
                        borderWidth: 2
                    }
                };
            }

            return baseOptions;
        },

        // Get scales configuration
        getScalesConfig ()
        {
            const isDark = this.theme === 'dark';
            const textColor = isDark ? '#e5e7eb' : '#374151';
            const gridColor = isDark ? '#374151' : '#e5e7eb';

            if ( this.currentType === 'doughnut' || this.currentType === 'radar' )
            {
                return {};
            }

            return {
                x: {
                    grid: {
                        color: gridColor,
                        drawBorder: false
                    },
                    ticks: {
                        color: textColor,
                        font: { size: 12 }
                    }
                },
                y: {
                    grid: {
                        color: gridColor,
                        drawBorder: false
                    },
                    ticks: {
                        color: textColor,
                        font: { size: 12 },
                        callback: ( value ) => this.formatYAxisLabel( value )
                    }
                }
            };
        },

        // Load initial chart data
        loadInitialData ()
        {
            // Generate sample data for demonstration
            const now = new Date();
            const labels = [];
            const data = [];

            for ( let i = this.maxDataPoints - 1; i >= 0; i-- )
            {
                const time = new Date( now.getTime() - ( i * 60 * 60 * 1000 ) ); // Hour intervals
                labels.push( this.formatTimeLabel( time ) );
                data.push( Math.floor( Math.random() * 100 ) + 20 );
            }

            this.updateChartData( {
                labels: labels,
                datasets: [ {
                    label: 'Server Traffic',
                    data: data,
                    borderColor: '#3b82f6',
                    backgroundColor: this.currentType === 'area' ? 'rgba(59, 130, 246, 0.1)' : 'rgba(59, 130, 246, 0.8)',
                    fill: this.currentType === 'area'
                } ]
            } );
        },

        // Update chart data
        updateChartData ( newData )
        {
            if ( !this.chart ) return;

            this.chart.data = newData;
            this.chart.update( 'none' ); // Smooth update without animation
        },

        // Add real-time data point
        addDataPoint ( label, value, datasetIndex = 0 )
        {
            if ( !this.chart ) return;

            const data = this.chart.data;

            // Add new data point
            data.labels.push( label );
            data.datasets[ datasetIndex ].data.push( value );

            // Remove old data points to maintain max limit
            if ( data.labels.length > this.maxDataPoints )
            {
                data.labels.shift();
                data.datasets[ datasetIndex ].data.shift();
            }

            this.chart.update( 'active' );
        },

        // Change chart type
        changeChartType ( newType )
        {
            if ( !this.chart || this.currentType === newType ) return;

            this.currentType = newType;
            this.chart.config.type = newType;
            this.chart.config.options = this.getChartOptions();

            // Update dataset properties based on type
            if ( newType === 'area' )
            {
                this.chart.data.datasets.forEach( dataset =>
                {
                    dataset.fill = true;
                    dataset.backgroundColor = dataset.borderColor.replace( '1)', '0.1)' );
                } );
            } else if ( newType === 'bar' )
            {
                this.chart.data.datasets.forEach( dataset =>
                {
                    dataset.fill = false;
                    dataset.backgroundColor = dataset.borderColor;
                } );
            }

            this.chart.update();
        },

        // Toggle theme
        toggleTheme ()
        {
            this.theme = this.theme === 'light' ? 'dark' : 'light';
            if ( this.chart )
            {
                this.chart.config.options = this.getChartOptions();
                this.chart.update();
            }
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
                this.refreshData();
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

        // Refresh chart data
        refreshData ()
        {
            if ( !this.realTimeData ) return;

            const now = new Date();
            const newValue = Math.floor( Math.random() * 100 ) + 20;
            this.addDataPoint( this.formatTimeLabel( now ), newValue );
        },

        // Export chart as image
        exportChart ( format = 'png' )
        {
            if ( !this.chart ) return;

            const url = this.chart.toBase64Image( `image/${ format }`, 1.0 );
            const link = document.createElement( 'a' );
            link.download = `chart-${ Date.now() }.${ format }`;
            link.href = url;
            link.click();
        },

        // Utility functions
        formatTimeLabel ( date )
        {
            return date.toLocaleTimeString( 'en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            } );
        },

        formatTooltipLabel ( context )
        {
            const label = context.dataset.label || '';
            const value = context.parsed.y || context.parsed;
            return `${ label }: ${ this.formatValue( value ) }`;
        },

        formatYAxisLabel ( value )
        {
            return this.formatValue( value );
        },

        formatValue ( value )
        {
            if ( value >= 1000000 )
            {
                return ( value / 1000000 ).toFixed( 1 ) + 'M';
            } else if ( value >= 1000 )
            {
                return ( value / 1000 ).toFixed( 1 ) + 'K';
            }
            return value.toString();
        },

        // Cleanup
        destroy ()
        {
            this.stopAutoRefresh();
            if ( this.chart )
            {
                this.chart.destroy();
                this.chart = null;
            }
        }
    };
}
