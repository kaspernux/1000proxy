/**
 * Advanced Data Tables for Servers Page
 * Specialized implementation for server management
 */

// Servers Data Table Configuration
window.serversDataTable = function() {
    const config = {
        dataUrl: '/api/servers',
        updateUrl: '/api/servers',
        deleteUrl: '/api/servers',
        createUrl: '/api/servers',
        websocketUrl: window.location.protocol === 'https:' 
            ? `wss://${window.location.host}/ws/servers`
            : `ws://${window.location.host}/ws/servers`,
        autoRefresh: true,
        refreshRate: 30000,
        columns: [
            {
                key: 'id',
                label: 'ID',
                type: 'number',
                sortable: true,
                filterable: true,
                visible: true,
                width: '80px'
            },
            {
                key: 'name',
                label: 'Server Name',
                type: 'text',
                sortable: true,
                filterable: true,
                visible: true,
                editable: true
            },
            {
                key: 'host',
                label: 'Host',
                type: 'url',
                sortable: true,
                filterable: true,
                visible: true,
                editable: true
            },
            {
                key: 'port',
                label: 'Port',
                type: 'number',
                sortable: true,
                filterable: true,
                visible: true,
                editable: true
            },
            {
                key: 'status',
                label: 'Status',
                type: 'badge',
                sortable: true,
                filterable: true,
                visible: true,
                render: (value) => {
                    const statusClasses = {
                        'active': 'bg-green-100 text-green-800',
                        'inactive': 'bg-red-100 text-red-800',
                        'maintenance': 'bg-yellow-100 text-yellow-800'
                    };
                    const className = statusClasses[value] || 'bg-gray-100 text-gray-800';
                    return `<span class="px-2 py-1 text-xs font-medium rounded-full ${className}">${value}</span>`;
                }
            },
            {
                key: 'location',
                label: 'Location',
                type: 'text',
                sortable: true,
                filterable: true,
                visible: true,
                editable: true
            },
            {
                key: 'clients_count',
                label: 'Clients',
                type: 'number',
                sortable: true,
                filterable: true,
                visible: true,
                render: (value, row) => {
                    return `<span class="font-mono">${value || 0}</span>`;
                }
            },
            {
                key: 'created_at',
                label: 'Created',
                type: 'date',
                sortable: true,
                filterable: true,
                visible: true,
                render: (value) => {
                    return new Date(value).toLocaleDateString();
                }
            },
            {
                key: 'actions',
                label: 'Actions',
                type: 'actions',
                sortable: false,
                filterable: false,
                visible: true,
                render: (value, row) => {
                    return `
                        <div class="flex space-x-2">
                            <button 
                                onclick="editServer(${row.id})"
                                class="text-blue-600 hover:text-blue-800 text-sm"
                                title="Edit Server"
                            >
                                <i class="fas fa-edit"></i>
                            </button>
                            <button 
                                onclick="testConnection(${row.id})"
                                class="text-green-600 hover:text-green-800 text-sm"
                                title="Test Connection"
                            >
                                <i class="fas fa-plug"></i>
                            </button>
                            <button 
                                onclick="viewClients(${row.id})"
                                class="text-purple-600 hover:text-purple-800 text-sm"
                                title="View Clients"
                            >
                                <i class="fas fa-users"></i>
                            </button>
                            <button 
                                onclick="deleteServer(${row.id})"
                                class="text-red-600 hover:text-red-800 text-sm"
                                title="Delete Server"
                            >
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    };
    
    return window.editableDataTable(config);
};

// Server Clients Data Table Configuration
window.serverClientsDataTable = function(serverId) {
    const config = {
        dataUrl: `/api/servers/${serverId}/clients`,
        updateUrl: `/api/server-clients`,
        deleteUrl: `/api/server-clients`,
        createUrl: `/api/server-clients`,
        websocketUrl: window.location.protocol === 'https:' 
            ? `wss://${window.location.host}/ws/server-clients/${serverId}`
            : `ws://${window.location.host}/ws/server-clients/${serverId}`,
        autoRefresh: true,
        refreshRate: 15000,
        columns: [
            {
                key: 'id',
                label: 'ID',
                type: 'number',
                sortable: true,
                filterable: true,
                visible: true,
                width: '80px'
            },
            {
                key: 'email',
                label: 'Email',
                type: 'email',
                sortable: true,
                filterable: true,
                visible: true,
                editable: true
            },
            {
                key: 'uuid',
                label: 'UUID',
                type: 'text',
                sortable: false,
                filterable: true,
                visible: true,
                render: (value) => {
                    return `<code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">${value.substring(0, 8)}...</code>`;
                }
            },
            {
                key: 'config_link',
                label: 'Config',
                type: 'url',
                sortable: false,
                filterable: false,
                visible: true,
                render: (value, row) => {
                    return `
                        <div class="flex space-x-2">
                            <button 
                                onclick="copyConfig('${value}')"
                                class="text-blue-600 hover:text-blue-800 text-sm"
                                title="Copy Config"
                            >
                                <i class="fas fa-copy"></i>
                            </button>
                            <button 
                                onclick="showQRCode('${value}')"
                                class="text-green-600 hover:text-green-800 text-sm"
                                title="Show QR Code"
                            >
                                <i class="fas fa-qrcode"></i>
                            </button>
                        </div>
                    `;
                }
            },
            {
                key: 'status',
                label: 'Status',
                type: 'badge',
                sortable: true,
                filterable: true,
                visible: true,
                editable: true,
                render: (value) => {
                    const statusClasses = {
                        'active': 'bg-green-100 text-green-800',
                        'inactive': 'bg-red-100 text-red-800',
                        'suspended': 'bg-yellow-100 text-yellow-800'
                    };
                    const className = statusClasses[value] || 'bg-gray-100 text-gray-800';
                    return `<span class="px-2 py-1 text-xs font-medium rounded-full ${className}">${value}</span>`;
                }
            },
            {
                key: 'data_usage',
                label: 'Data Usage',
                type: 'number',
                sortable: true,
                filterable: true,
                visible: true,
                render: (value) => {
                    return this.formatBytes(value || 0);
                }
            },
            {
                key: 'expires_at',
                label: 'Expires',
                type: 'date',
                sortable: true,
                filterable: true,
                visible: true,
                editable: true,
                render: (value) => {
                    if (!value) return '-';
                    const date = new Date(value);
                    const now = new Date();
                    const isExpired = date < now;
                    const className = isExpired ? 'text-red-600' : 'text-gray-900 dark:text-white';
                    return `<span class="${className}">${date.toLocaleDateString()}</span>`;
                }
            },
            {
                key: 'created_at',
                label: 'Created',
                type: 'date',
                sortable: true,
                filterable: true,
                visible: false,
                render: (value) => {
                    return new Date(value).toLocaleDateString();
                }
            },
            {
                key: 'actions',
                label: 'Actions',
                type: 'actions',
                sortable: false,
                filterable: false,
                visible: true,
                render: (value, row) => {
                    return `
                        <div class="flex space-x-2">
                            <button 
                                onclick="editClient(${row.id})"
                                class="text-blue-600 hover:text-blue-800 text-sm"
                                title="Edit Client"
                            >
                                <i class="fas fa-edit"></i>
                            </button>
                            <button 
                                onclick="resetClientData(${row.id})"
                                class="text-yellow-600 hover:text-yellow-800 text-sm"
                                title="Reset Data Usage"
                            >
                                <i class="fas fa-redo"></i>
                            </button>
                            <button 
                                onclick="deleteClient(${row.id})"
                                class="text-red-600 hover:text-red-800 text-sm"
                                title="Delete Client"
                            >
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    };
    
    return {
        ...window.editableDataTable(config),
        
        // Custom methods for server clients
        formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    };
};

// Orders Data Table Configuration
window.ordersDataTable = function() {
    const config = {
        dataUrl: '/api/orders',
        updateUrl: '/api/orders',
        websocketUrl: window.location.protocol === 'https:' 
            ? `wss://${window.location.host}/ws/orders`
            : `ws://${window.location.host}/ws/orders`,
        autoRefresh: true,
        refreshRate: 10000,
        columns: [
            {
                key: 'id',
                label: 'Order ID',
                type: 'number',
                sortable: true,
                filterable: true,
                visible: true,
                width: '100px'
            },
            {
                key: 'user_email',
                label: 'User',
                type: 'email',
                sortable: true,
                filterable: true,
                visible: true
            },
            {
                key: 'server_name',
                label: 'Server',
                type: 'text',
                sortable: true,
                filterable: true,
                visible: true
            },
            {
                key: 'amount',
                label: 'Amount',
                type: 'currency',
                sortable: true,
                filterable: true,
                visible: true,
                render: (value) => {
                    return `$${parseFloat(value).toFixed(2)}`;
                }
            },
            {
                key: 'status',
                label: 'Status',
                type: 'badge',
                sortable: true,
                filterable: true,
                visible: true,
                render: (value) => {
                    const statusClasses = {
                        'pending': 'bg-yellow-100 text-yellow-800',
                        'processing': 'bg-blue-100 text-blue-800',
                        'completed': 'bg-green-100 text-green-800',
                        'failed': 'bg-red-100 text-red-800',
                        'cancelled': 'bg-gray-100 text-gray-800'
                    };
                    const className = statusClasses[value] || 'bg-gray-100 text-gray-800';
                    return `<span class="px-2 py-1 text-xs font-medium rounded-full ${className}">${value}</span>`;
                }
            },
            {
                key: 'payment_method',
                label: 'Payment',
                type: 'text',
                sortable: true,
                filterable: true,
                visible: true,
                render: (value) => {
                    const icons = {
                        'stripe': 'fab fa-stripe',
                        'paypal': 'fab fa-paypal',
                        'crypto': 'fab fa-bitcoin'
                    };
                    const icon = icons[value] || 'fas fa-credit-card';
                    return `<i class="${icon} mr-1"></i>${value}`;
                }
            },
            {
                key: 'created_at',
                label: 'Created',
                type: 'date',
                sortable: true,
                filterable: true,
                visible: true,
                render: (value) => {
                    return new Date(value).toLocaleString();
                }
            },
            {
                key: 'actions',
                label: 'Actions',
                type: 'actions',
                sortable: false,
                filterable: false,
                visible: true,
                render: (value, row) => {
                    return `
                        <div class="flex space-x-2">
                            <button 
                                onclick="viewOrder(${row.id})"
                                class="text-blue-600 hover:text-blue-800 text-sm"
                                title="View Order"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                            ${row.status === 'pending' ? `
                                <button 
                                    onclick="processOrder(${row.id})"
                                    class="text-green-600 hover:text-green-800 text-sm"
                                    title="Process Order"
                                >
                                    <i class="fas fa-play"></i>
                                </button>
                                <button 
                                    onclick="cancelOrder(${row.id})"
                                    class="text-red-600 hover:text-red-800 text-sm"
                                    title="Cancel Order"
                                >
                                    <i class="fas fa-times"></i>
                                </button>
                            ` : ''}
                        </div>
                    `;
                }
            }
        ]
    };
    
    return window.dataTable(config);
};

// Users Data Table Configuration
window.usersDataTable = function() {
    const config = {
        dataUrl: '/api/admin/users',
        updateUrl: '/api/admin/users',
        deleteUrl: '/api/admin/users',
        websocketUrl: window.location.protocol === 'https:' 
            ? `wss://${window.location.host}/ws/users`
            : `ws://${window.location.host}/ws/users`,
        autoRefresh: true,
        refreshRate: 60000,
        columns: [
            {
                key: 'id',
                label: 'ID',
                type: 'number',
                sortable: true,
                filterable: true,
                visible: true,
                width: '80px'
            },
            {
                key: 'name',
                label: 'Name',
                type: 'text',
                sortable: true,
                filterable: true,
                visible: true,
                editable: true
            },
            {
                key: 'email',
                label: 'Email',
                type: 'email',
                sortable: true,
                filterable: true,
                visible: true
            },
            {
                key: 'role',
                label: 'Role',
                type: 'badge',
                sortable: true,
                filterable: true,
                visible: true,
                editable: true,
                render: (value) => {
                    const roleClasses = {
                        'admin': 'bg-purple-100 text-purple-800',
                        'user': 'bg-blue-100 text-blue-800',
                        'moderator': 'bg-green-100 text-green-800'
                    };
                    const className = roleClasses[value] || 'bg-gray-100 text-gray-800';
                    return `<span class="px-2 py-1 text-xs font-medium rounded-full ${className}">${value}</span>`;
                }
            },
            {
                key: 'wallet_balance',
                label: 'Wallet',
                type: 'currency',
                sortable: true,
                filterable: true,
                visible: true,
                render: (value) => {
                    return `$${parseFloat(value || 0).toFixed(2)}`;
                }
            },
            {
                key: 'orders_count',
                label: 'Orders',
                type: 'number',
                sortable: true,
                filterable: true,
                visible: true
            },
            {
                key: 'last_login',
                label: 'Last Login',
                type: 'date',
                sortable: true,
                filterable: true,
                visible: true,
                render: (value) => {
                    return value ? new Date(value).toLocaleString() : 'Never';
                }
            },
            {
                key: 'created_at',
                label: 'Joined',
                type: 'date',
                sortable: true,
                filterable: true,
                visible: true,
                render: (value) => {
                    return new Date(value).toLocaleDateString();
                }
            },
            {
                key: 'actions',
                label: 'Actions',
                type: 'actions',
                sortable: false,
                filterable: false,
                visible: true,
                render: (value, row) => {
                    return `
                        <div class="flex space-x-2">
                            <button 
                                onclick="viewUser(${row.id})"
                                class="text-blue-600 hover:text-blue-800 text-sm"
                                title="View User"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                            <button 
                                onclick="editUser(${row.id})"
                                class="text-green-600 hover:text-green-800 text-sm"
                                title="Edit User"
                            >
                                <i class="fas fa-edit"></i>
                            </button>
                            <button 
                                onclick="impersonateUser(${row.id})"
                                class="text-purple-600 hover:text-purple-800 text-sm"
                                title="Impersonate User"
                            >
                                <i class="fas fa-user-secret"></i>
                            </button>
                            ${row.role !== 'admin' ? `
                                <button 
                                    onclick="deleteUser(${row.id})"
                                    class="text-red-600 hover:text-red-800 text-sm"
                                    title="Delete User"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            ` : ''}
                        </div>
                    `;
                }
            }
        ]
    };
    
    return window.editableDataTable(config);
};

// Global table action handlers
window.tableActions = {
    // Server actions
    editServer(id) {
        window.dispatchEvent(new CustomEvent('edit-server', { detail: { id } }));
    },
    
    testConnection(id) {
        window.dispatchEvent(new CustomEvent('test-server-connection', { detail: { id } }));
    },
    
    viewClients(id) {
        window.dispatchEvent(new CustomEvent('view-server-clients', { detail: { id } }));
    },
    
    deleteServer(id) {
        window.dispatchEvent(new CustomEvent('delete-server', { detail: { id } }));
    },
    
    // Client actions
    editClient(id) {
        window.dispatchEvent(new CustomEvent('edit-client', { detail: { id } }));
    },
    
    copyConfig(configLink) {
        navigator.clipboard.writeText(configLink).then(() => {
            window.dispatchEvent(new CustomEvent('show-notification', { 
                detail: { type: 'success', message: 'Configuration copied to clipboard' }
            }));
        });
    },
    
    showQRCode(configLink) {
        window.dispatchEvent(new CustomEvent('show-qr-code', { detail: { configLink } }));
    },
    
    resetClientData(id) {
        window.dispatchEvent(new CustomEvent('reset-client-data', { detail: { id } }));
    },
    
    deleteClient(id) {
        window.dispatchEvent(new CustomEvent('delete-client', { detail: { id } }));
    },
    
    // Order actions
    viewOrder(id) {
        window.dispatchEvent(new CustomEvent('view-order', { detail: { id } }));
    },
    
    processOrder(id) {
        window.dispatchEvent(new CustomEvent('process-order', { detail: { id } }));
    },
    
    cancelOrder(id) {
        window.dispatchEvent(new CustomEvent('cancel-order', { detail: { id } }));
    },
    
    // User actions
    viewUser(id) {
        window.dispatchEvent(new CustomEvent('view-user', { detail: { id } }));
    },
    
    editUser(id) {
        window.dispatchEvent(new CustomEvent('edit-user', { detail: { id } }));
    },
    
    impersonateUser(id) {
        window.dispatchEvent(new CustomEvent('impersonate-user', { detail: { id } }));
    },
    
    deleteUser(id) {
        window.dispatchEvent(new CustomEvent('delete-user', { detail: { id } }));
    }
};

// Register global action handlers
Object.entries(window.tableActions).forEach(([key, handler]) => {
    window[key] = handler;
});

console.log('✅ Advanced Data Tables configurations loaded');
