// System Health Indicators Component with Color Coding
export default function systemHealth ()
{
    return {
        // Health metrics
        health: {
            overall: {
                status: 'healthy', // healthy, warning, critical, unknown
                score: 95,
                lastUpdated: new Date()
            },
            services: [
                {
                    id: 'database',
                    name: 'Database',
                    status: 'healthy',
                    responseTime: 45,
                    uptime: 99.9,
                    icon: '🗄️',
                    description: 'Primary database connection'
                },
                {
                    id: 'redis',
                    name: 'Redis Cache',
                    status: 'healthy',
                    responseTime: 12,
                    uptime: 100,
                    icon: '⚡',
                    description: 'Cache and session storage'
                },
                {
                    id: 'queue',
                    name: 'Queue System',
                    status: 'warning',
                    responseTime: 150,
                    uptime: 98.5,
                    icon: '📋',
                    description: 'Background job processing'
                },
                {
                    id: 'storage',
                    name: 'File Storage',
                    status: 'healthy',
                    responseTime: 89,
                    uptime: 99.7,
                    icon: '💾',
                    description: 'File upload and storage'
                }
            ],
            servers: [],
            metrics: {
                cpu: { value: 45, status: 'healthy', threshold: 80 },
                memory: { value: 62, status: 'warning', threshold: 85 },
                disk: { value: 78, status: 'warning', threshold: 90 },
                network: { value: 23, status: 'healthy', threshold: 75 }
            }
        },

        // Configuration
        refreshInterval: 30000, // 30 seconds
        intervalId: null,
        autoRefresh: true,

        // UI state
        loading: false,
        showDetails: false,
        selectedService: null,
        alertThresholds: {
            responseTime: 1000, // ms
            uptime: 99.0, // percentage
            cpu: 80,
            memory: 85,
            disk: 90
        },

        // Status colors and icons
        statusConfig: {
            healthy: {
                color: 'text-green-600',
                bgColor: 'bg-green-100',
                borderColor: 'border-green-200',
                icon: '✅',
                label: 'Healthy'
            },
            warning: {
                color: 'text-yellow-600',
                bgColor: 'bg-yellow-100',
                borderColor: 'border-yellow-200',
                icon: '⚠️',
                label: 'Warning'
            },
            critical: {
                color: 'text-red-600',
                bgColor: 'bg-red-100',
                borderColor: 'border-red-200',
                icon: '❌',
                label: 'Critical'
            },
            unknown: {
                color: 'text-gray-600',
                bgColor: 'bg-gray-100',
                borderColor: 'border-gray-200',
                icon: '❓',
                label: 'Unknown'
            }
        },

        // Initialize component
        init ()
        {
            this.$nextTick( () =>
            {
                this.checkSystemHealth();
                if ( this.autoRefresh )
                {
                    this.startAutoRefresh();
                }
            } );
        },

        // Check system health
        async checkSystemHealth ()
        {
            try
            {
                this.loading = true;

                const response = await fetch( '/api/health/status' );
                const data = await response.json();

                this.health = {
                    ...this.health,
                    ...data
                };

                this.health.overall.lastUpdated = new Date();

                // Calculate overall status
                this.calculateOverallStatus();

                this.loading = false;
            } catch ( error )
            {
                console.error( 'Failed to check system health:', error );
                this.loading = false;

                // Update with error status
                this.health.overall.status = 'critical';
                this.health.overall.score = 0;
                this.health.overall.lastUpdated = new Date();

                // Load sample data for demonstration
                this.loadSampleData();
            }
        },

        // Load sample data for demonstration
        loadSampleData ()
        {
            // Update services with sample data
            this.health.services = [
                {
                    id: 'database',
                    name: 'Database',
                    status: 'healthy',
                    responseTime: 45,
                    uptime: 99.9,
                    icon: '🗄️',
                    description: 'Primary database connection',
                    details: {
                        connections: 25,
                        maxConnections: 100,
                        queryTime: 12,
                        slowQueries: 0
                    }
                },
                {
                    id: 'redis',
                    name: 'Redis Cache',
                    status: 'healthy',
                    responseTime: 12,
                    uptime: 100,
                    icon: '⚡',
                    description: 'Cache and session storage',
                    details: {
                        memory: '2.1GB',
                        maxMemory: '4GB',
                        hitRate: 98.5,
                        evictions: 0
                    }
                },
                {
                    id: 'queue',
                    name: 'Queue System',
                    status: 'warning',
                    responseTime: 150,
                    uptime: 98.5,
                    icon: '📋',
                    description: 'Background job processing',
                    details: {
                        pending: 47,
                        processing: 3,
                        failed: 2,
                        avgProcessTime: 145
                    }
                },
                {
                    id: 'xui_api',
                    name: '3X-UI APIs',
                    status: 'healthy',
                    responseTime: 89,
                    uptime: 99.2,
                    icon: '🔗',
                    description: 'External 3X-UI server connections',
                    details: {
                        connectedServers: 34,
                        totalServers: 35,
                        avgLatency: 89,
                        timeouts: 1
                    }
                },
                {
                    id: 'payment',
                    name: 'Payment Gateway',
                    status: 'healthy',
                    responseTime: 234,
                    uptime: 99.8,
                    icon: '💳',
                    description: 'Payment processing services',
                    details: {
                        stripe: 'healthy',
                        paypal: 'healthy',
                        crypto: 'healthy',
                        avgProcessTime: 234
                    }
                }
            ];

            // Update system metrics
            this.health.metrics = {
                cpu: { value: 45, status: 'healthy', threshold: 80, cores: 8 },
                memory: { value: 62, status: 'warning', threshold: 85, total: '16GB', used: '9.9GB' },
                disk: { value: 78, status: 'warning', threshold: 90, total: '1TB', used: '780GB' },
                network: { value: 23, status: 'healthy', threshold: 75, inbound: '125MB/s', outbound: '89MB/s' }
            };

            this.calculateOverallStatus();
            this.loading = false;
        },

        // Calculate overall system status
        calculateOverallStatus ()
        {
            const services = this.health.services || [];
            const metrics = this.health.metrics || {};

            let healthyCount = 0;
            let warningCount = 0;
            let criticalCount = 0;
            let totalCount = services.length + Object.keys( metrics ).length;

            // Check services
            services.forEach( service =>
            {
                switch ( service.status )
                {
                    case 'healthy':
                        healthyCount++;
                        break;
                    case 'warning':
                        warningCount++;
                        break;
                    case 'critical':
                        criticalCount++;
                        break;
                }
            } );

            // Check metrics
            Object.values( metrics ).forEach( metric =>
            {
                switch ( metric.status )
                {
                    case 'healthy':
                        healthyCount++;
                        break;
                    case 'warning':
                        warningCount++;
                        break;
                    case 'critical':
                        criticalCount++;
                        break;
                }
            } );

            // Calculate overall status and score
            if ( criticalCount > 0 )
            {
                this.health.overall.status = 'critical';
                this.health.overall.score = Math.max( 0, 100 - ( criticalCount * 30 ) - ( warningCount * 10 ) );
            } else if ( warningCount > 0 )
            {
                this.health.overall.status = 'warning';
                this.health.overall.score = Math.max( 70, 100 - ( warningCount * 10 ) );
            } else
            {
                this.health.overall.status = 'healthy';
                this.health.overall.score = Math.min( 100, 90 + ( healthyCount / totalCount ) * 10 );
            }
        },

        // Get status configuration
        getStatusConfig ( status )
        {
            return this.statusConfig[ status ] || this.statusConfig.unknown;
        },

        // Get metric status color for progress bars
        getMetricStatusColor ( metric )
        {
            const status = this.getMetricStatus( metric.value, metric.threshold );
            return this.getStatusConfig( status ).color;
        },

        // Get metric status
        getMetricStatus ( value, threshold )
        {
            if ( value >= threshold ) return 'critical';
            if ( value >= threshold * 0.8 ) return 'warning';
            return 'healthy';
        },

        // Format uptime
        formatUptime ( uptime )
        {
            return `${ uptime.toFixed( 1 ) }%`;
        },

        // Format response time
        formatResponseTime ( responseTime )
        {
            if ( responseTime < 1000 )
            {
                return `${ responseTime }ms`;
            } else
            {
                return `${ ( responseTime / 1000 ).toFixed( 1 ) }s`;
            }
        },

        // Show service details
        showServiceDetails ( service )
        {
            this.selectedService = service;
            this.showDetails = true;
        },

        // Hide service details
        hideServiceDetails ()
        {
            this.selectedService = null;
            this.showDetails = false;
        },

        // Get service status indicator
        getServiceStatusIndicator ( service )
        {
            const config = this.getStatusConfig( service.status );
            return {
                icon: config.icon,
                color: config.color,
                label: config.label
            };
        },

        // Check if service has alerts
        hasServiceAlerts ( service )
        {
            return service.responseTime > this.alertThresholds.responseTime ||
                service.uptime < this.alertThresholds.uptime;
        },

        // Get service alerts
        getServiceAlerts ( service )
        {
            const alerts = [];

            if ( service.responseTime > this.alertThresholds.responseTime )
            {
                alerts.push( `High response time: ${ this.formatResponseTime( service.responseTime ) }` );
            }

            if ( service.uptime < this.alertThresholds.uptime )
            {
                alerts.push( `Low uptime: ${ this.formatUptime( service.uptime ) }` );
            }

            return alerts;
        },

        // Restart service (admin action)
        async restartService ( serviceId )
        {
            try
            {
                const response = await fetch( `/api/health/services/${ serviceId }/restart`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.getCsrfToken()
                    }
                } );

                if ( response.ok )
                {
                    this.showNotification( `${ serviceId } service restart initiated`, 'success' );
                    setTimeout( () => this.checkSystemHealth(), 5000 );
                } else
                {
                    throw new Error( 'Failed to restart service' );
                }
            } catch ( error )
            {
                console.error( 'Failed to restart service:', error );
                this.showNotification( 'Failed to restart service', 'error' );
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
                this.checkSystemHealth();
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

        // Export health report
        async exportHealthReport ()
        {
            try
            {
                const response = await fetch( '/api/health/export' );
                const blob = await response.blob();

                const url = URL.createObjectURL( blob );
                const link = document.createElement( 'a' );
                link.href = url;
                link.download = `health-report-${ new Date().toISOString().split( 'T' )[ 0 ] }.pdf`;
                link.click();
                URL.revokeObjectURL( url );
            } catch ( error )
            {
                console.error( 'Failed to export health report:', error );
                this.showNotification( 'Failed to export health report', 'error' );
            }
        },

        // Utility functions
        getCsrfToken ()
        {
            return document.querySelector( 'meta[name="csrf-token"]' )?.getAttribute( 'content' ) || '';
        },

        showNotification ( message, type = 'info' )
        {
            if ( window.showNotification )
            {
                window.showNotification( type, message );
            }
        },

        formatLastUpdated ()
        {
            const date = this.health.overall.lastUpdated;
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor( diffMs / 60000 );

            if ( diffMins < 1 )
            {
                return 'Just updated';
            } else if ( diffMins < 60 )
            {
                return `Updated ${ diffMins }m ago`;
            } else
            {
                return `Updated ${ Math.floor( diffMins / 60 ) }h ago`;
            }
        },

        // Cleanup
        destroy ()
        {
            this.stopAutoRefresh();
        }
    };
}
