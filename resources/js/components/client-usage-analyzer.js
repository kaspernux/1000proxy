/**
 * Client Usage Analyzer Component
 */

// Client Usage Analyzer with Advanced Analytics
window.clientUsageAnalyzer = function ()
{
    return {
        clients: [],
        usageData: {},
        analytics: {
            totalClients: 0,
            activeClients: 0,
            totalTraffic: 0,
            averageUsage: 0,
            topUsers: [],
            usageDistribution: {},
            timeBasedUsage: {}
        },
        filters: {
            timeRange: '7d',
            clientStatus: 'all',
            sortBy: 'traffic',
            sortOrder: 'desc',
            searchTerm: ''
        },
        selectedClient: null,
        isLoading: false,
        refreshInterval: null,

        init ()
        {
            this.loadClientsData();
            this.startAutoRefresh();
        },

        async loadClientsData ()
        {
            this.isLoading = true;
            try
            {
                const response = await fetch( `/api/xui/clients-usage?${ new URLSearchParams( this.filters ) }` );
                const data = await response.json();

                this.clients = data.clients || [];
                this.usageData = data.usage || {};
                this.analytics = data.analytics || this.analytics;

                this.processAnalytics();

            } catch ( error )
            {
                console.error( 'Failed to load clients data:', error );
                this.$dispatch( 'notification', {
                    type: 'error',
                    message: 'Failed to load clients usage data'
                } );
            } finally
            {
                this.isLoading = false;
            }
        },

        processAnalytics ()
        {
            // Calculate derived analytics
            this.analytics.totalClients = this.clients.length;
            this.analytics.activeClients = this.clients.filter( client => client.status === 'active' ).length;

            // Calculate total traffic
            this.analytics.totalTraffic = this.clients.reduce( ( sum, client ) =>
            {
                return sum + ( client.upload || 0 ) + ( client.download || 0 );
            }, 0 );

            // Calculate average usage
            this.analytics.averageUsage = this.analytics.totalClients > 0
                ? this.analytics.totalTraffic / this.analytics.totalClients
                : 0;

            // Get top users
            this.analytics.topUsers = [ ...this.clients ]
                .sort( ( a, b ) => ( ( b.upload || 0 ) + ( b.download || 0 ) ) - ( ( a.upload || 0 ) + ( a.download || 0 ) ) )
                .slice( 0, 10 );

            // Calculate usage distribution
            this.calculateUsageDistribution();

            // Calculate time-based usage patterns
            this.calculateTimeBasedUsage();
        },

        calculateUsageDistribution ()
        {
            const ranges = [
                { label: '0-100MB', min: 0, max: 100 * 1024 * 1024 },
                { label: '100MB-1GB', min: 100 * 1024 * 1024, max: 1024 * 1024 * 1024 },
                { label: '1GB-10GB', min: 1024 * 1024 * 1024, max: 10 * 1024 * 1024 * 1024 },
                { label: '10GB-50GB', min: 10 * 1024 * 1024 * 1024, max: 50 * 1024 * 1024 * 1024 },
                { label: '50GB+', min: 50 * 1024 * 1024 * 1024, max: Infinity }
            ];

            this.analytics.usageDistribution = ranges.reduce( ( dist, range ) =>
            {
                dist[ range.label ] = this.clients.filter( client =>
                {
                    const totalUsage = ( client.upload || 0 ) + ( client.download || 0 );
                    return totalUsage >= range.min && totalUsage < range.max;
                } ).length;
                return dist;
            }, {} );
        },

        calculateTimeBasedUsage ()
        {
            // This would typically come from the backend with time-series data
            // For now, we'll simulate some basic patterns
            const hours = Array.from( { length: 24 }, ( _, i ) => i );
            this.analytics.timeBasedUsage = {
                hourlyDistribution: hours.reduce( ( dist, hour ) =>
                {
                    // Simulate usage patterns (higher during evening hours)
                    const baseUsage = Math.random() * 100;
                    const eveningBoost = hour >= 18 && hour <= 23 ? 50 : 0;
                    dist[ hour ] = Math.floor( baseUsage + eveningBoost );
                    return dist;
                }, {} )
            };
        },

        getFilteredClients ()
        {
            let filtered = [ ...this.clients ];

            // Apply search filter
            if ( this.filters.searchTerm )
            {
                const term = this.filters.searchTerm.toLowerCase();
                filtered = filtered.filter( client =>
                    client.email?.toLowerCase().includes( term ) ||
                    client.uuid?.toLowerCase().includes( term ) ||
                    client.subId?.toLowerCase().includes( term )
                );
            }

            // Apply status filter
            if ( this.filters.clientStatus !== 'all' )
            {
                filtered = filtered.filter( client => client.status === this.filters.clientStatus );
            }

            // Apply sorting
            filtered.sort( ( a, b ) =>
            {
                let valueA, valueB;

                switch ( this.filters.sortBy )
                {
                    case 'traffic':
                        valueA = ( a.upload || 0 ) + ( a.download || 0 );
                        valueB = ( b.upload || 0 ) + ( b.download || 0 );
                        break;
                    case 'upload':
                        valueA = a.upload || 0;
                        valueB = b.upload || 0;
                        break;
                    case 'download':
                        valueA = a.download || 0;
                        valueB = b.download || 0;
                        break;
                    case 'email':
                        valueA = a.email || '';
                        valueB = b.email || '';
                        break;
                    case 'lastConnection':
                        valueA = new Date( a.lastConnection || 0 );
                        valueB = new Date( b.lastConnection || 0 );
                        break;
                    default:
                        valueA = a.email || '';
                        valueB = b.email || '';
                }

                if ( this.filters.sortOrder === 'asc' )
                {
                    return valueA > valueB ? 1 : valueA < valueB ? -1 : 0;
                } else
                {
                    return valueA < valueB ? 1 : valueA > valueB ? -1 : 0;
                }
            } );

            return filtered;
        },

        async updateFilters ()
        {
            await this.loadClientsData();
        },

        selectClient ( client )
        {
            this.selectedClient = client;
            this.$dispatch( 'client-selected', { client } );
        },

        async resetClientTraffic ( clientId )
        {
            try
            {
                const response = await fetch( `/api/xui/clients/${ clientId }/reset-traffic`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).content
                    }
                } );

                if ( response.ok )
                {
                    this.$dispatch( 'notification', {
                        type: 'success',
                        message: 'Client traffic reset successfully'
                    } );
                    await this.loadClientsData();
                } else
                {
                    throw new Error( 'Failed to reset traffic' );
                }
            } catch ( error )
            {
                this.$dispatch( 'notification', {
                    type: 'error',
                    message: 'Failed to reset client traffic'
                } );
            }
        },

        async exportClientData ()
        {
            const data = {
                timestamp: new Date().toISOString(),
                clients: this.getFilteredClients(),
                analytics: this.analytics,
                filters: this.filters
            };

            const blob = new Blob( [ JSON.stringify( data, null, 2 ) ], { type: 'application/json' } );
            const url = URL.createObjectURL( blob );
            const a = document.createElement( 'a' );
            a.href = url;
            a.download = `client_usage_analysis_${ new Date().toISOString().split( 'T' )[ 0 ] }.json`;
            a.click();
            URL.revokeObjectURL( url );
        },

        getUsagePercentage ( client )
        {
            if ( !client.totalGB || client.totalGB === 0 ) return 0;
            const used = ( client.upload || 0 ) + ( client.download || 0 );
            const total = client.totalGB * 1024 * 1024 * 1024; // Convert GB to bytes
            return Math.min( 100, ( used / total ) * 100 );
        },

        getUsageStatusClass ( percentage )
        {
            if ( percentage >= 90 ) return 'usage-critical';
            if ( percentage >= 75 ) return 'usage-warning';
            if ( percentage >= 50 ) return 'usage-moderate';
            return 'usage-normal';
        },

        getDaysUntilExpiry ( client )
        {
            if ( !client.expiryTime ) return null;
            const now = Date.now();
            const expiry = new Date( client.expiryTime );
            const diffTime = expiry - now;
            const diffDays = Math.ceil( diffTime / ( 1000 * 60 * 60 * 24 ) );
            return diffDays;
        },

        startAutoRefresh ()
        {
            this.refreshInterval = setInterval( () =>
            {
                this.loadClientsData();
            }, 60000 ); // Refresh every minute
        },

        stopAutoRefresh ()
        {
            if ( this.refreshInterval )
            {
                clearInterval( this.refreshInterval );
                this.refreshInterval = null;
            }
        },

        destroy ()
        {
            this.stopAutoRefresh();
        }
    };
};

// Register component
window.addEventListener( 'alpine:init', () =>
{
    Alpine.data( 'clientUsageAnalyzer', window.clientUsageAnalyzer );
} );
