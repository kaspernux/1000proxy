// User Activity Timeline Component with Infinite Scroll
export default function activityTimeline ()
{
    return {
        // Activity data
        activities: [],
        filteredActivities: [],

        // Pagination
        currentPage: 1,
        perPage: 20,
        totalActivities: 0,
        hasMoreActivities: true,
        loading: false,
        loadingMore: false,

        // Filters
        filters: {
            type: 'all', // all, user, admin, system, payment, server
            severity: 'all', // all, info, warning, error, success
            dateRange: '24h', // 1h, 24h, 7d, 30d, all
            search: ''
        },

        // Activity types configuration
        activityTypes: {
            user: {
                icon: '👤',
                color: 'bg-blue-100 text-blue-800',
                label: 'User Activity'
            },
            admin: {
                icon: '⚙️',
                color: 'bg-purple-100 text-purple-800',
                label: 'Admin Action'
            },
            system: {
                icon: '🔧',
                color: 'bg-gray-100 text-gray-800',
                label: 'System Event'
            },
            payment: {
                icon: '💳',
                color: 'bg-green-100 text-green-800',
                label: 'Payment'
            },
            server: {
                icon: '🖥️',
                color: 'bg-indigo-100 text-indigo-800',
                label: 'Server'
            },
            security: {
                icon: '🔒',
                color: 'bg-red-100 text-red-800',
                label: 'Security'
            }
        },

        // Severity levels
        severityLevels: {
            info: {
                icon: 'ℹ️',
                color: 'text-blue-600',
                bgColor: 'bg-blue-50 border-blue-200'
            },
            success: {
                icon: '✅',
                color: 'text-green-600',
                bgColor: 'bg-green-50 border-green-200'
            },
            warning: {
                icon: '⚠️',
                color: 'text-yellow-600',
                bgColor: 'bg-yellow-50 border-yellow-200'
            },
            error: {
                icon: '❌',
                color: 'text-red-600',
                bgColor: 'bg-red-50 border-red-200'
            }
        },

        // Auto refresh
        autoRefresh: true,
        refreshInterval: 30000, // 30 seconds
        intervalId: null,

        // UI state
        showFilters: false,
        groupByDate: true,

        // Initialize component
        init ()
        {
            this.$nextTick( () =>
            {
                this.loadActivities();
                this.setupInfiniteScroll();
                if ( this.autoRefresh )
                {
                    this.startAutoRefresh();
                }
            } );
        },

        // Load activities from API
        async loadActivities ( reset = true )
        {
            if ( this.loading && reset ) return;

            try
            {
                this.loading = reset;
                this.loadingMore = !reset;

                const params = new URLSearchParams( {
                    page: reset ? 1 : this.currentPage + 1,
                    per_page: this.perPage,
                    type: this.filters.type,
                    severity: this.filters.severity,
                    date_range: this.filters.dateRange,
                    search: this.filters.search
                } );

                const response = await fetch( `/api/activities?${ params }` );
                const data = await response.json();

                if ( reset )
                {
                    this.activities = data.activities || [];
                    this.currentPage = 1;
                } else
                {
                    this.activities.push( ...( data.activities || [] ) );
                    this.currentPage++;
                }

                this.totalActivities = data.total || 0;
                this.hasMoreActivities = data.has_more || false;

                this.applyFilters();

                this.loading = false;
                this.loadingMore = false;
            } catch ( error )
            {
                console.error( 'Failed to load activities:', error );
                this.loading = false;
                this.loadingMore = false;

                // Load sample data for demonstration
                if ( reset )
                {
                    this.loadSampleData();
                }
            }
        },

        // Load sample activity data
        loadSampleData ()
        {
            const sampleActivities = [
                {
                    id: 1,
                    type: 'user',
                    severity: 'info',
                    title: 'User Registration',
                    description: 'New user john.doe@example.com registered successfully',
                    user: { name: 'John Doe', email: 'john.doe@example.com' },
                    timestamp: new Date( Date.now() - 5 * 60 * 1000 ), // 5 minutes ago
                    metadata: { ip: '192.168.1.1', user_agent: 'Chrome 91.0' }
                },
                {
                    id: 2,
                    type: 'payment',
                    severity: 'success',
                    title: 'Payment Received',
                    description: 'Payment of $29.99 received for Premium Plan',
                    user: { name: 'Jane Smith', email: 'jane.smith@example.com' },
                    timestamp: new Date( Date.now() - 15 * 60 * 1000 ), // 15 minutes ago
                    metadata: { amount: 29.99, payment_method: 'stripe', transaction_id: 'txn_123456' }
                },
                {
                    id: 3,
                    type: 'server',
                    severity: 'warning',
                    title: 'High CPU Usage',
                    description: 'Server US-East-01 CPU usage reached 85%',
                    timestamp: new Date( Date.now() - 30 * 60 * 1000 ), // 30 minutes ago
                    metadata: { server: 'US-East-01', cpu_usage: 85, threshold: 80 }
                },
                {
                    id: 4,
                    type: 'admin',
                    severity: 'info',
                    title: 'Server Configuration Updated',
                    description: 'Admin updated inbound configuration for server EU-West-01',
                    user: { name: 'Admin User', email: 'admin@1000proxy.com' },
                    timestamp: new Date( Date.now() - 45 * 60 * 1000 ), // 45 minutes ago
                    metadata: { server: 'EU-West-01', changes: [ 'max_clients', 'protocol' ] }
                },
                {
                    id: 5,
                    type: 'security',
                    severity: 'error',
                    title: 'Failed Login Attempt',
                    description: 'Multiple failed login attempts from IP 192.168.1.100',
                    timestamp: new Date( Date.now() - 60 * 60 * 1000 ), // 1 hour ago
                    metadata: { ip: '192.168.1.100', attempts: 5, blocked: true }
                }
            ];

            this.activities = sampleActivities;
            this.totalActivities = sampleActivities.length;
            this.hasMoreActivities = false;
            this.applyFilters();
            this.loading = false;
        },

        // Apply filters to activities
        applyFilters ()
        {
            let filtered = [ ...this.activities ];

            // Filter by type
            if ( this.filters.type !== 'all' )
            {
                filtered = filtered.filter( activity => activity.type === this.filters.type );
            }

            // Filter by severity
            if ( this.filters.severity !== 'all' )
            {
                filtered = filtered.filter( activity => activity.severity === this.filters.severity );
            }

            // Filter by search
            if ( this.filters.search )
            {
                const search = this.filters.search.toLowerCase();
                filtered = filtered.filter( activity =>
                    activity.title.toLowerCase().includes( search ) ||
                    activity.description.toLowerCase().includes( search ) ||
                    ( activity.user && activity.user.name.toLowerCase().includes( search ) )
                );
            }

            this.filteredActivities = filtered;
        },

        // Setup infinite scroll
        setupInfiniteScroll ()
        {
            const container = this.$refs.timelineContainer;
            if ( !container ) return;

            const observer = new IntersectionObserver( ( entries ) =>
            {
                entries.forEach( entry =>
                {
                    if ( entry.isIntersecting && this.hasMoreActivities && !this.loadingMore )
                    {
                        this.loadMoreActivities();
                    }
                } );
            }, {
                root: container,
                rootMargin: '100px',
                threshold: 0.1
            } );

            // Observe the load more trigger
            const loadMoreTrigger = this.$refs.loadMoreTrigger;
            if ( loadMoreTrigger )
            {
                observer.observe( loadMoreTrigger );
            }
        },

        // Load more activities
        loadMoreActivities ()
        {
            if ( !this.hasMoreActivities || this.loadingMore ) return;
            this.loadActivities( false );
        },

        // Group activities by date
        getGroupedActivities ()
        {
            if ( !this.groupByDate )
            {
                return [ { date: null, activities: this.filteredActivities } ];
            }

            const groups = {};

            this.filteredActivities.forEach( activity =>
            {
                const date = this.getDateKey( activity.timestamp );
                if ( !groups[ date ] )
                {
                    groups[ date ] = [];
                }
                groups[ date ].push( activity );
            } );

            return Object.keys( groups ).map( date => ( {
                date: date,
                activities: groups[ date ]
            } ) ).sort( ( a, b ) => new Date( b.date ) - new Date( a.date ) );
        },

        // Get date key for grouping
        getDateKey ( timestamp )
        {
            const date = new Date( timestamp );
            const today = new Date();
            const yesterday = new Date( today );
            yesterday.setDate( yesterday.getDate() - 1 );

            if ( this.isSameDay( date, today ) )
            {
                return 'Today';
            } else if ( this.isSameDay( date, yesterday ) )
            {
                return 'Yesterday';
            } else
            {
                return date.toLocaleDateString( 'en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                } );
            }
        },

        // Check if two dates are the same day
        isSameDay ( date1, date2 )
        {
            return date1.getFullYear() === date2.getFullYear() &&
                date1.getMonth() === date2.getMonth() &&
                date1.getDate() === date2.getDate();
        },

        // Format timestamp
        formatTimestamp ( timestamp )
        {
            const date = new Date( timestamp );
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor( diffMs / 60000 );
            const diffHours = Math.floor( diffMs / 3600000 );
            const diffDays = Math.floor( diffMs / 86400000 );

            if ( diffMins < 1 )
            {
                return 'Just now';
            } else if ( diffMins < 60 )
            {
                return `${ diffMins }m ago`;
            } else if ( diffHours < 24 )
            {
                return `${ diffHours }h ago`;
            } else if ( diffDays < 7 )
            {
                return `${ diffDays }d ago`;
            } else
            {
                return date.toLocaleDateString( 'en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
                } );
            }
        },

        // Get activity type configuration
        getActivityTypeConfig ( type )
        {
            return this.activityTypes[ type ] || this.activityTypes.system;
        },

        // Get severity configuration
        getSeverityConfig ( severity )
        {
            return this.severityLevels[ severity ] || this.severityLevels.info;
        },

        // Update filter
        updateFilter ( filterType, value )
        {
            this.filters[ filterType ] = value;
            this.currentPage = 1;
            this.loadActivities( true );
        },

        // Clear all filters
        clearFilters ()
        {
            this.filters = {
                type: 'all',
                severity: 'all',
                dateRange: '24h',
                search: ''
            };
            this.loadActivities( true );
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
                this.refreshActivities();
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

        // Refresh activities (only new ones)
        async refreshActivities ()
        {
            if ( this.activities.length === 0 ) return;

            try
            {
                const lastActivity = this.activities[ 0 ];
                const params = new URLSearchParams( {
                    since: lastActivity.timestamp.toISOString(),
                    type: this.filters.type,
                    severity: this.filters.severity
                } );

                const response = await fetch( `/api/activities/new?${ params }` );
                const data = await response.json();

                if ( data.activities && data.activities.length > 0 )
                {
                    this.activities.unshift( ...data.activities );
                    this.applyFilters();
                }
            } catch ( error )
            {
                console.error( 'Failed to refresh activities:', error );
            }
        },

        // Export activities
        async exportActivities ()
        {
            try
            {
                const params = new URLSearchParams( {
                    type: this.filters.type,
                    severity: this.filters.severity,
                    date_range: this.filters.dateRange,
                    format: 'csv'
                } );

                const response = await fetch( `/api/activities/export?${ params }` );
                const blob = await response.blob();

                const url = URL.createObjectURL( blob );
                const link = document.createElement( 'a' );
                link.href = url;
                link.download = `activities-${ new Date().toISOString().split( 'T' )[ 0 ] }.csv`;
                link.click();
                URL.revokeObjectURL( url );
            } catch ( error )
            {
                console.error( 'Failed to export activities:', error );
                this.showNotification( 'Failed to export activities', 'error' );
            }
        },

        // Show activity details
        showActivityDetails ( activity )
        {
            // Emit event or open modal with activity details
            this.$dispatch( 'show-activity-details', { activity } );
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
        }
    };
}
