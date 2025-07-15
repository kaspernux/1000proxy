// Revenue Analytics Component with Animated Counters
export default function revenueAnalytics ()
{
    return {
        // Revenue data
        revenue: {
            today: {
                total: 0,
                orders: 0,
                average: 0
            },
            thisMonth: {
                total: 0,
                orders: 0,
                average: 0
            },
            lastMonth: {
                total: 0,
                orders: 0,
                average: 0
            },
            yearly: {
                total: 0,
                orders: 0,
                average: 0
            }
        },

        // Metrics
        metrics: {
            growthRate: 0,
            conversionRate: 0,
            churnRate: 0,
            arpu: 0, // Average Revenue Per User
            ltv: 0   // Lifetime Value
        },

        // Animation state
        animatedValues: {
            todayTotal: 0,
            monthlyTotal: 0,
            yearlyTotal: 0,
            orders: 0
        },

        // Configuration
        animationDuration: 2000,
        refreshInterval: 300000, // 5 minutes
        intervalId: null,

        // UI state
        loading: true,
        selectedPeriod: 'today', // today, week, month, year
        showComparison: true,
        autoRefresh: true,

        // Chart data
        chartData: {
            labels: [],
            datasets: []
        },
        revenueChart: null,

        // Initialize component
        init ()
        {
            this.$nextTick( () =>
            {
                this.loadRevenueData();
                this.initializeChart();
                if ( this.autoRefresh )
                {
                    this.startAutoRefresh();
                }
            } );
        },

        // Load revenue data from API
        async loadRevenueData ()
        {
            try
            {
                this.loading = true;

                const response = await fetch( '/api/analytics/revenue' );
                const data = await response.json();

                this.revenue = data.revenue || this.revenue;
                this.metrics = data.metrics || this.metrics;

                // Animate counters
                this.animateCounters();

                // Update chart
                this.updateChart( data.chartData );

                this.loading = false;
            } catch ( error )
            {
                console.error( 'Failed to load revenue data:', error );
                this.loading = false;

                // Load sample data for demonstration
                this.loadSampleData();
            }
        },

        // Load sample data for demonstration
        loadSampleData ()
        {
            this.revenue = {
                today: {
                    total: 1245.50,
                    orders: 23,
                    average: 54.15
                },
                thisMonth: {
                    total: 28750.25,
                    orders: 456,
                    average: 63.05
                },
                lastMonth: {
                    total: 25630.80,
                    orders: 398,
                    average: 64.40
                },
                yearly: {
                    total: 245780.90,
                    orders: 3847,
                    average: 63.89
                }
            };

            this.metrics = {
                growthRate: 12.2,
                conversionRate: 3.8,
                churnRate: 5.2,
                arpu: 63.89,
                ltv: 892.50
            };

            // Generate sample chart data
            const chartData = {
                labels: this.generateDateLabels(),
                datasets: [ {
                    label: 'Daily Revenue',
                    data: this.generateRevenueData(),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                } ]
            };

            this.animateCounters();
            this.updateChart( chartData );
            this.loading = false;
        },

        // Animate counter values
        animateCounters ()
        {
            const targets = {
                todayTotal: this.revenue.today.total,
                monthlyTotal: this.revenue.thisMonth.total,
                yearlyTotal: this.revenue.yearly.total,
                orders: this.revenue.today.orders
            };

            // Reset animated values
            Object.keys( this.animatedValues ).forEach( key =>
            {
                this.animatedValues[ key ] = 0;
            } );

            // Animate each value
            Object.keys( targets ).forEach( key =>
            {
                this.animateValue( key, targets[ key ] );
            } );
        },

        // Animate individual counter value
        animateValue ( key, target )
        {
            const startTime = performance.now();
            const startValue = this.animatedValues[ key ];

            const animate = ( currentTime ) =>
            {
                const elapsed = currentTime - startTime;
                const progress = Math.min( elapsed / this.animationDuration, 1 );

                // Easing function (ease-out cubic)
                const easeProgress = 1 - Math.pow( 1 - progress, 3 );

                this.animatedValues[ key ] = startValue + ( target - startValue ) * easeProgress;

                if ( progress < 1 )
                {
                    requestAnimationFrame( animate );
                } else
                {
                    this.animatedValues[ key ] = target;
                }
            };

            requestAnimationFrame( animate );
        },

        // Initialize revenue chart
        initializeChart ()
        {
            const canvas = this.$refs.revenueChart;
            if ( !canvas ) return;

            const ctx = canvas.getContext( '2d' );

            this.revenueChart = new Chart( ctx, {
                type: 'line',
                data: this.chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: '#3b82f6',
                            borderWidth: 1,
                            cornerRadius: 8,
                            displayColors: false,
                            callbacks: {
                                label: ( context ) => `Revenue: $${ context.parsed.y.toLocaleString() }`
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#6b7280',
                                font: { size: 12 }
                            }
                        },
                        y: {
                            grid: {
                                color: '#e5e7eb',
                                drawBorder: false
                            },
                            ticks: {
                                color: '#6b7280',
                                font: { size: 12 },
                                callback: ( value ) => '$' + value.toLocaleString()
                            }
                        }
                    },
                    elements: {
                        point: {
                            radius: 4,
                            hoverRadius: 6,
                            borderWidth: 2,
                            backgroundColor: '#ffffff'
                        }
                    },
                    animation: {
                        duration: 1500,
                        easing: 'easeInOutCubic'
                    }
                }
            } );
        },

        // Update chart data
        updateChart ( data )
        {
            if ( !this.revenueChart || !data ) return;

            this.chartData = data;
            this.revenueChart.data = data;
            this.revenueChart.update();
        },

        // Generate sample date labels
        generateDateLabels ()
        {
            const labels = [];
            const today = new Date();

            for ( let i = 29; i >= 0; i-- )
            {
                const date = new Date( today );
                date.setDate( date.getDate() - i );
                labels.push( date.toLocaleDateString( 'en-US', {
                    month: 'short',
                    day: 'numeric'
                } ) );
            }

            return labels;
        },

        // Generate sample revenue data
        generateRevenueData ()
        {
            const data = [];
            let baseValue = 800;

            for ( let i = 0; i < 30; i++ )
            {
                // Add some trend and randomness
                const trend = i * 5; // Slight upward trend
                const randomVariation = ( Math.random() - 0.5 ) * 200;
                const weekendEffect = ( i % 7 === 0 || i % 7 === 6 ) ? -100 : 0; // Lower on weekends

                const value = Math.max( 0, baseValue + trend + randomVariation + weekendEffect );
                data.push( Math.round( value ) );
            }

            return data;
        },

        // Format currency value
        formatCurrency ( value, showCents = true )
        {
            const formatter = new Intl.NumberFormat( 'en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: showCents ? 2 : 0,
                maximumFractionDigits: showCents ? 2 : 0
            } );

            return formatter.format( value );
        },

        // Format percentage
        formatPercentage ( value )
        {
            return ( value >= 0 ? '+' : '' ) + value.toFixed( 1 ) + '%';
        },

        // Get growth comparison
        getGrowthComparison ()
        {
            const current = this.revenue.thisMonth.total;
            const previous = this.revenue.lastMonth.total;

            if ( previous === 0 ) return { percentage: 0, trend: 'neutral' };

            const percentage = ( ( current - previous ) / previous ) * 100;
            const trend = percentage > 0 ? 'up' : percentage < 0 ? 'down' : 'neutral';

            return { percentage, trend };
        },

        // Get metric color based on trend
        getMetricColor ( value, isGood = true )
        {
            const isPositive = value > 0;
            if ( isGood )
            {
                return isPositive ? 'text-green-600' : 'text-red-600';
            } else
            {
                return isPositive ? 'text-red-600' : 'text-green-600';
            }
        },

        // Get metric icon
        getMetricIcon ( value, isGood = true )
        {
            const isPositive = value > 0;
            const shouldShowUp = ( isGood && isPositive ) || ( !isGood && !isPositive );

            return shouldShowUp ? '↗️' : '↘️';
        },

        // Change selected period
        changePeriod ( period )
        {
            this.selectedPeriod = period;
            this.loadPeriodData( period );
        },

        // Load data for specific period
        async loadPeriodData ( period )
        {
            try
            {
                const response = await fetch( `/api/analytics/revenue?period=${ period }` );
                const data = await response.json();

                if ( data.chartData )
                {
                    this.updateChart( data.chartData );
                }
            } catch ( error )
            {
                console.error( 'Failed to load period data:', error );
            }
        },

        // Export revenue data
        async exportData ()
        {
            try
            {
                const response = await fetch( '/api/analytics/revenue/export' );
                const blob = await response.blob();

                const url = URL.createObjectURL( blob );
                const link = document.createElement( 'a' );
                link.href = url;
                link.download = `revenue-report-${ new Date().toISOString().split( 'T' )[ 0 ] }.xlsx`;
                link.click();
                URL.revokeObjectURL( url );
            } catch ( error )
            {
                console.error( 'Failed to export data:', error );
                this.showNotification( 'Failed to export data', 'error' );
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
                this.loadRevenueData();
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
            if ( window.showNotification )
            {
                window.showNotification( type, message );
            }
        },

        // Cleanup
        destroy ()
        {
            this.stopAutoRefresh();
            if ( this.revenueChart )
            {
                this.revenueChart.destroy();
                this.revenueChart = null;
            }
        }
    };
}
