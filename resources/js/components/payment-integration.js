// Payment Gateway Integration Components
import Alpine from 'alpinejs';

// Payment API Service
class PaymentAPIService
{
    constructor ()
    {
        this.baseURL = '/api/payments';
        this.websocket = null;
        this.listeners = new Map();
    }

    async makeRequest ( endpoint, options = {} )
    {
        const url = `${ this.baseURL }${ endpoint }`;
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).content
            }
        };

        const response = await fetch( url, { ...defaultOptions, ...options } );

        if ( !response.ok )
        {
            throw new Error( `Payment API error: ${ response.statusText }` );
        }

        return await response.json();
    }

    // Gateway Management
    async getGateways ()
    {
        return await this.makeRequest( '/gateways' );
    }

    async updateGatewayStatus ( gatewayId, status )
    {
        return await this.makeRequest( `/gateways/${ gatewayId }/status`, {
            method: 'PATCH',
            body: JSON.stringify( { status } )
        } );
    }

    // Payment Processing
    async processPayment ( paymentData )
    {
        return await this.makeRequest( '/process', {
            method: 'POST',
            body: JSON.stringify( paymentData )
        } );
    }

    async getPaymentStatus ( paymentId )
    {
        return await this.makeRequest( `/status/${ paymentId }` );
    }

    // Crypto Monitoring
    async getCryptoRates ()
    {
        return await this.makeRequest( '/crypto/rates' );
    }

    async getCryptoPayments ( filters = {} )
    {
        const params = new URLSearchParams( filters );
        return await this.makeRequest( `/crypto/payments?${ params }` );
    }

    // Payment History
    async getPaymentHistory ( filters = {} )
    {
        const params = new URLSearchParams( filters );
        return await this.makeRequest( `/history?${ params }` );
    }

    // Refunds
    async processRefund ( paymentId, amount, reason )
    {
        return await this.makeRequest( '/refunds', {
            method: 'POST',
            body: JSON.stringify( { payment_id: paymentId, amount, reason } )
        } );
    }

    async getRefunds ( filters = {} )
    {
        const params = new URLSearchParams( filters );
        return await this.makeRequest( `/refunds?${ params }` );
    }

    // Wallet Management
    async getWalletBalance ( userId )
    {
        return await this.makeRequest( `/wallet/${ userId }/balance` );
    }

    async addFunds ( userId, amount, method )
    {
        return await this.makeRequest( `/wallet/${ userId }/add-funds`, {
            method: 'POST',
            body: JSON.stringify( { amount, method } )
        } );
    }

    // Transaction Analysis
    async getTransactionAnalytics ( period = '30d' )
    {
        return await this.makeRequest( `/analytics?period=${ period }` );
    }

    async getFraudDetection ()
    {
        return await this.makeRequest( '/fraud-detection' );
    }

    // Real-time WebSocket connection
    connectWebSocket ()
    {
        if ( this.websocket ) return;

        const wsUrl = `${ window.location.protocol === 'https:' ? 'wss:' : 'ws:' }//${ window.location.host }/ws/payments`;
        this.websocket = new WebSocket( wsUrl );

        this.websocket.onmessage = ( event ) =>
        {
            const data = JSON.parse( event.data );
            this.notifyListeners( data.type, data.payload );
        };

        this.websocket.onclose = () =>
        {
            this.websocket = null;
            // Reconnect after 5 seconds
            setTimeout( () => this.connectWebSocket(), 5000 );
        };
    }

    // Event subscription
    on ( event, callback )
    {
        if ( !this.listeners.has( event ) )
        {
            this.listeners.set( event, [] );
        }
        this.listeners.get( event ).push( callback );
    }

    notifyListeners ( event, data )
    {
        if ( this.listeners.has( event ) )
        {
            this.listeners.get( event ).forEach( callback => callback( data ) );
        }
    }

    disconnect ()
    {
        if ( this.websocket )
        {
            this.websocket.close();
            this.websocket = null;
        }
    }
}

// Multi Payment Processor Component
function multiPaymentProcessor ()
{
    return {
        // State
        availableGateways: [],
        selectedGateway: null,
        isProcessing: false,
        error: null,
        paymentResult: null,

        // Payment data
        paymentData: {
            amount: '',
            currency: 'USD',
            description: '',
            customerEmail: '',
            returnUrl: '',
            webhookUrl: ''
        },

        // Gateway status
        gatewayStatuses: new Map(),

        // Real-time updates
        realTimeUpdates: true,

        // API service
        paymentAPI: new PaymentAPIService(),

        // Initialize
        async init ()
        {
            await this.loadGateways();
            if ( this.realTimeUpdates )
            {
                this.setupRealTimeUpdates();
            }
        },

        // Load available gateways
        async loadGateways ()
        {
            try
            {
                const response = await this.paymentAPI.getGateways();
                this.availableGateways = response.gateways || [];

                // Initialize gateway statuses
                this.availableGateways.forEach( gateway =>
                {
                    this.gatewayStatuses.set( gateway.id, gateway.status );
                } );

                // Auto-select first available gateway
                if ( this.availableGateways.length > 0 && !this.selectedGateway )
                {
                    this.selectedGateway = this.availableGateways.find( g => g.status === 'active' ) || this.availableGateways[ 0 ];
                }
            } catch ( error )
            {
                this.error = 'Failed to load payment gateways';
                console.error( error );
            }
        },

        // Select gateway
        selectGateway ( gateway )
        {
            this.selectedGateway = gateway;
            this.error = null;
            this.paymentResult = null;
        },

        // Toggle gateway status
        async toggleGatewayStatus ( gatewayId )
        {
            const currentStatus = this.gatewayStatuses.get( gatewayId );
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';

            try
            {
                await this.paymentAPI.updateGatewayStatus( gatewayId, newStatus );
                this.gatewayStatuses.set( gatewayId, newStatus );

                // Update gateway in array
                const gateway = this.availableGateways.find( g => g.id === gatewayId );
                if ( gateway )
                {
                    gateway.status = newStatus;
                }

                // Switch to active gateway if current one was deactivated
                if ( this.selectedGateway?.id === gatewayId && newStatus === 'inactive' )
                {
                    const activeGateway = this.availableGateways.find( g => g.status === 'active' );
                    if ( activeGateway )
                    {
                        this.selectGateway( activeGateway );
                    }
                }
            } catch ( error )
            {
                this.error = 'Failed to update gateway status';
                console.error( error );
            }
        },

        // Process payment
        async processPayment ()
        {
            if ( !this.selectedGateway )
            {
                this.error = 'Please select a payment gateway';
                return;
            }

            if ( !this.paymentData.amount || !this.paymentData.customerEmail )
            {
                this.error = 'Amount and customer email are required';
                return;
            }

            this.isProcessing = true;
            this.error = null;
            this.paymentResult = null;

            try
            {
                const result = await this.paymentAPI.processPayment( {
                    gateway_id: this.selectedGateway.id,
                    ...this.paymentData
                } );

                this.paymentResult = result;

                if ( result.status === 'success' )
                {
                    this.resetForm();
                } else if ( result.status === 'redirect' )
                {
                    window.location.href = result.redirect_url;
                }
            } catch ( error )
            {
                this.error = error.message;
            } finally
            {
                this.isProcessing = false;
            }
        },

        // Reset form
        resetForm ()
        {
            this.paymentData = {
                amount: '',
                currency: 'USD',
                description: '',
                customerEmail: '',
                returnUrl: '',
                webhookUrl: ''
            };
        },

        // Setup real-time updates
        setupRealTimeUpdates ()
        {
            this.paymentAPI.connectWebSocket();

            this.paymentAPI.on( 'gateway_status_changed', ( data ) =>
            {
                this.gatewayStatuses.set( data.gateway_id, data.status );
                const gateway = this.availableGateways.find( g => g.id === data.gateway_id );
                if ( gateway )
                {
                    gateway.status = data.status;
                }
            } );

            this.paymentAPI.on( 'payment_processed', ( data ) =>
            {
                if ( data.payment_id === this.paymentResult?.payment_id )
                {
                    this.paymentResult.status = data.status;
                }
            } );
        },

        // Get gateway status color
        getGatewayStatusColor ( status )
        {
            const colors = {
                active: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                inactive: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                maintenance: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
            };
            return colors[ status ] || 'bg-gray-100 text-gray-800';
        },

        // Get gateway icon
        getGatewayIcon ( gateway )
        {
            const icons = {
                stripe: '💳',
                paypal: '🅿️',
                bitcoin: '₿',
                ethereum: 'Ξ',
                nowpayments: '💎',
                coinbase: '🔸'
            };
            return icons[ gateway.type ] || '💳';
        },

        // Format currency
        formatCurrency ( amount, currency = 'USD' )
        {
            return new Intl.NumberFormat( 'en-US', {
                style: 'currency',
                currency: currency
            } ).format( amount );
        },

        // Cleanup
        destroy ()
        {
            this.paymentAPI.disconnect();
        }
    };
}

// Crypto Payment Monitor Component
function cryptoPaymentMonitor ()
{
    return {
        // State
        cryptoRates: {},
        cryptoPayments: [],
        isLoading: false,
        error: null,
        autoRefresh: true,
        refreshInterval: null,

        // Filters
        filters: {
            currency: 'all',
            status: 'all',
            timeRange: '24h',
            minAmount: '',
            maxAmount: ''
        },

        // Supported currencies
        supportedCurrencies: [ 'BTC', 'ETH', 'LTC', 'BCH', 'XMR', 'USDT' ],

        // Statistics
        stats: {
            totalValue: 0,
            pendingCount: 0,
            completedToday: 0,
            averageConfirmationTime: 0
        },

        // API service
        paymentAPI: new PaymentAPIService(),

        // Initialize
        async init ()
        {
            await this.loadCryptoRates();
            await this.loadCryptoPayments();
            this.calculateStats();

            if ( this.autoRefresh )
            {
                this.startAutoRefresh();
            }

            this.setupRealTimeUpdates();
        },

        // Load crypto rates
        async loadCryptoRates ()
        {
            try
            {
                const response = await this.paymentAPI.getCryptoRates();
                this.cryptoRates = response.rates || {};
            } catch ( error )
            {
                console.error( 'Failed to load crypto rates:', error );
            }
        },

        // Load crypto payments
        async loadCryptoPayments ()
        {
            this.isLoading = true;
            this.error = null;

            try
            {
                const response = await this.paymentAPI.getCryptoPayments( this.filters );
                this.cryptoPayments = response.payments || [];
                this.calculateStats();
            } catch ( error )
            {
                this.error = 'Failed to load crypto payments';
            } finally
            {
                this.isLoading = false;
            }
        },

        // Calculate statistics
        calculateStats ()
        {
            const payments = this.cryptoPayments;
            const today = new Date().toDateString();

            this.stats = {
                totalValue: payments.reduce( ( sum, p ) => sum + ( p.usd_value || 0 ), 0 ),
                pendingCount: payments.filter( p => p.status === 'pending' ).length,
                completedToday: payments.filter( p =>
                    p.status === 'completed' &&
                    new Date( p.completed_at ).toDateString() === today
                ).length,
                averageConfirmationTime: this.calculateAverageConfirmationTime( payments )
            };
        },

        // Calculate average confirmation time
        calculateAverageConfirmationTime ( payments )
        {
            const completed = payments.filter( p =>
                p.status === 'completed' &&
                p.created_at &&
                p.completed_at
            );

            if ( completed.length === 0 ) return 0;

            const totalTime = completed.reduce( ( sum, p ) =>
            {
                const start = new Date( p.created_at );
                const end = new Date( p.completed_at );
                return sum + ( end - start );
            }, 0 );

            return Math.round( totalTime / completed.length / 60000 ); // minutes
        },

        // Start auto refresh
        startAutoRefresh ()
        {
            this.refreshInterval = setInterval( async () =>
            {
                await this.loadCryptoRates();
                await this.loadCryptoPayments();
            }, 30000 ); // 30 seconds
        },

        // Stop auto refresh
        stopAutoRefresh ()
        {
            if ( this.refreshInterval )
            {
                clearInterval( this.refreshInterval );
                this.refreshInterval = null;
            }
        },

        // Toggle auto refresh
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

        // Setup real-time updates
        setupRealTimeUpdates ()
        {
            this.paymentAPI.connectWebSocket();

            this.paymentAPI.on( 'crypto_rate_updated', ( data ) =>
            {
                this.cryptoRates[ data.currency ] = data.rate;
            } );

            this.paymentAPI.on( 'crypto_payment_updated', ( data ) =>
            {
                const index = this.cryptoPayments.findIndex( p => p.id === data.payment.id );
                if ( index !== -1 )
                {
                    this.cryptoPayments[ index ] = data.payment;
                } else
                {
                    this.cryptoPayments.unshift( data.payment );
                }
                this.calculateStats();
            } );
        },

        // Get filtered payments
        get filteredPayments ()
        {
            let filtered = this.cryptoPayments;

            // Currency filter
            if ( this.filters.currency !== 'all' )
            {
                filtered = filtered.filter( p => p.currency === this.filters.currency );
            }

            // Status filter
            if ( this.filters.status !== 'all' )
            {
                filtered = filtered.filter( p => p.status === this.filters.status );
            }

            // Amount filters
            if ( this.filters.minAmount )
            {
                filtered = filtered.filter( p => p.usd_value >= parseFloat( this.filters.minAmount ) );
            }
            if ( this.filters.maxAmount )
            {
                filtered = filtered.filter( p => p.usd_value <= parseFloat( this.filters.maxAmount ) );
            }

            // Time range filter
            if ( this.filters.timeRange !== 'all' )
            {
                const hours = parseInt( this.filters.timeRange.replace( 'h', '' ) );
                const cutoff = new Date( Date.now() - hours * 60 * 60 * 1000 );
                filtered = filtered.filter( p => new Date( p.created_at ) >= cutoff );
            }

            return filtered.sort( ( a, b ) => new Date( b.created_at ) - new Date( a.created_at ) );
        },

        // Get status color
        getStatusColor ( status )
        {
            const colors = {
                pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                confirmed: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                completed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                failed: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                expired: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
            };
            return colors[ status ] || 'bg-gray-100 text-gray-800';
        },

        // Get currency icon
        getCurrencyIcon ( currency )
        {
            const icons = {
                BTC: '₿',
                ETH: 'Ξ',
                LTC: 'Ł',
                BCH: '₿',
                XMR: 'ɱ',
                USDT: '₮'
            };
            return icons[ currency ] || '¤';
        },

        // Format crypto amount
        formatCryptoAmount ( amount, currency )
        {
            const decimals = currency === 'BTC' ? 8 : 6;
            return parseFloat( amount ).toFixed( decimals );
        },

        // Format USD value
        formatUSDValue ( amount )
        {
            return new Intl.NumberFormat( 'en-US', {
                style: 'currency',
                currency: 'USD'
            } ).format( amount );
        },

        // Format date
        formatDate ( dateString )
        {
            return new Date( dateString ).toLocaleString();
        },

        // Get time since
        getTimeSince ( dateString )
        {
            const now = new Date();
            const date = new Date( dateString );
            const diffMs = now - date;
            const diffMins = Math.floor( diffMs / 60000 );

            if ( diffMins < 1 ) return 'Just now';
            if ( diffMins < 60 ) return `${ diffMins }m ago`;
            if ( diffMins < 1440 ) return `${ Math.floor( diffMins / 60 ) }h ago`;

            return `${ Math.floor( diffMins / 1440 ) }d ago`;
        },

        // Cleanup
        destroy ()
        {
            this.stopAutoRefresh();
            this.paymentAPI.disconnect();
        }
    };
}

// Payment History Table Component
function paymentHistoryTable ()
{
    return {
        // State
        payments: [],
        isLoading: false,
        error: null,

        // Pagination
        currentPage: 1,
        perPage: 20,
        totalPages: 1,
        totalRecords: 0,

        // Filters
        filters: {
            status: 'all',
            gateway: 'all',
            dateFrom: '',
            dateTo: '',
            amount_min: '',
            amount_max: '',
            customer_email: '',
            transaction_id: ''
        },

        // Sorting
        sortBy: 'created_at',
        sortOrder: 'desc',

        // Selection
        selectedPayments: new Set(),
        selectAll: false,

        // Export
        isExporting: false,

        // API service
        paymentAPI: new PaymentAPIService(),

        // Initialize
        async init ()
        {
            await this.loadPayments();
            this.setupRealTimeUpdates();
        },

        // Load payments
        async loadPayments ()
        {
            this.isLoading = true;
            this.error = null;

            try
            {
                const queryParams = {
                    page: this.currentPage,
                    per_page: this.perPage,
                    sort_by: this.sortBy,
                    sort_order: this.sortOrder,
                    ...this.filters
                };

                const response = await this.paymentAPI.getPaymentHistory( queryParams );

                this.payments = response.payments || [];
                this.currentPage = response.current_page || 1;
                this.totalPages = response.total_pages || 1;
                this.totalRecords = response.total_records || 0;
                this.perPage = response.per_page || 20;
            } catch ( error )
            {
                this.error = 'Failed to load payment history';
            } finally
            {
                this.isLoading = false;
            }
        },

        // Change page
        async changePage ( page )
        {
            if ( page >= 1 && page <= this.totalPages && page !== this.currentPage )
            {
                this.currentPage = page;
                await this.loadPayments();
            }
        },

        // Change sort
        async changeSort ( column )
        {
            if ( this.sortBy === column )
            {
                this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
            } else
            {
                this.sortBy = column;
                this.sortOrder = 'desc';
            }

            this.currentPage = 1;
            await this.loadPayments();
        },

        // Apply filters
        async applyFilters ()
        {
            this.currentPage = 1;
            await this.loadPayments();
        },

        // Clear filters
        async clearFilters ()
        {
            this.filters = {
                status: 'all',
                gateway: 'all',
                dateFrom: '',
                dateTo: '',
                amount_min: '',
                amount_max: '',
                customer_email: '',
                transaction_id: ''
            };
            await this.applyFilters();
        },

        // Toggle payment selection
        togglePaymentSelection ( paymentId )
        {
            if ( this.selectedPayments.has( paymentId ) )
            {
                this.selectedPayments.delete( paymentId );
            } else
            {
                this.selectedPayments.add( paymentId );
            }

            this.updateSelectAllState();
        },

        // Toggle select all
        toggleSelectAll ()
        {
            this.selectAll = !this.selectAll;

            if ( this.selectAll )
            {
                this.payments.forEach( payment =>
                {
                    this.selectedPayments.add( payment.id );
                } );
            } else
            {
                this.selectedPayments.clear();
            }
        },

        // Update select all state
        updateSelectAllState ()
        {
            const allSelected = this.payments.every( payment =>
                this.selectedPayments.has( payment.id )
            );
            const noneSelected = this.payments.every( payment =>
                !this.selectedPayments.has( payment.id )
            );

            this.selectAll = allSelected && !noneSelected;
        },

        // Export selected or all
        async exportPayments ( type = 'selected' )
        {
            this.isExporting = true;

            try
            {
                const exportData = {
                    type: type,
                    filters: this.filters,
                    selected_ids: type === 'selected' ? Array.from( this.selectedPayments ) : []
                };

                const response = await fetch( '/api/payments/export', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).content
                    },
                    body: JSON.stringify( exportData )
                } );

                if ( response.ok )
                {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL( blob );
                    const a = document.createElement( 'a' );
                    a.style.display = 'none';
                    a.href = url;
                    a.download = `payments_${ new Date().toISOString().split( 'T' )[ 0 ] }.xlsx`;
                    document.body.appendChild( a );
                    a.click();
                    window.URL.revokeObjectURL( url );
                } else
                {
                    throw new Error( 'Export failed' );
                }
            } catch ( error )
            {
                this.error = 'Failed to export payments';
            } finally
            {
                this.isExporting = false;
            }
        },

        // Setup real-time updates
        setupRealTimeUpdates ()
        {
            this.paymentAPI.connectWebSocket();

            this.paymentAPI.on( 'payment_updated', ( data ) =>
            {
                const index = this.payments.findIndex( p => p.id === data.payment.id );
                if ( index !== -1 )
                {
                    this.payments[ index ] = data.payment;
                }
            } );

            this.paymentAPI.on( 'payment_created', ( data ) =>
            {
                // Add to beginning if on first page
                if ( this.currentPage === 1 )
                {
                    this.payments.unshift( data.payment );
                    this.totalRecords++;
                }
            } );
        },

        // Get status color
        getStatusColor ( status )
        {
            const colors = {
                pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                processing: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                completed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                failed: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                cancelled: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                refunded: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'
            };
            return colors[ status ] || 'bg-gray-100 text-gray-800';
        },

        // Get gateway icon
        getGatewayIcon ( gateway )
        {
            const icons = {
                stripe: '💳',
                paypal: '🅿️',
                bitcoin: '₿',
                ethereum: 'Ξ',
                nowpayments: '💎',
                coinbase: '🔸'
            };
            return icons[ gateway ] || '💳';
        },

        // Format currency
        formatCurrency ( amount, currency = 'USD' )
        {
            return new Intl.NumberFormat( 'en-US', {
                style: 'currency',
                currency: currency
            } ).format( amount );
        },

        // Format date
        formatDate ( dateString )
        {
            return new Date( dateString ).toLocaleString();
        },

        // Get sort icon
        getSortIcon ( column )
        {
            if ( this.sortBy !== column ) return '↕️';
            return this.sortOrder === 'asc' ? '↑' : '↓';
        },

        // Get page numbers for pagination
        get pageNumbers ()
        {
            const pages = [];
            const start = Math.max( 1, this.currentPage - 2 );
            const end = Math.min( this.totalPages, this.currentPage + 2 );

            for ( let i = start; i <= end; i++ )
            {
                pages.push( i );
            }

            return pages;
        }
    };
}

// Export to window for Alpine registration
window.multiPaymentProcessor = multiPaymentProcessor;
window.cryptoPaymentMonitor = cryptoPaymentMonitor;
window.paymentHistoryTable = paymentHistoryTable;
