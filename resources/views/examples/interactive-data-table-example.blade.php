{{-- Example Usage: Interactive Data Table for Server Management --}}
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Server Management</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Manage your proxy servers with advanced filtering, sorting, and bulk operations.</p>
    </div>

    {{-- Interactive Data Table --}}
    <x-interactive-data-table
        id="servers-table"
        title="Proxy Servers"
        description="Complete list of proxy servers with real-time status monitoring"
        :data-url="route('api.servers.index')"
        :export-url="route('api.servers.export')"
        :save-row-url="route('api.servers.update', ':id')"
        search-placeholder="Search servers by name, location, or IP..."
        no-data-message="No servers found"
        :config="[
            'striped' => true,
            'bordered' => true,
            'hover' => true,
            'autoRefresh' => true,
            'refreshInterval' => 30000
        ]"
        :columns="[
            [
                'key' => 'id',
                'title' => 'ID',
                'sortable' => true,
                'visible' => false,
                'align' => 'center'
            ],
            [
                'key' => 'name',
                'title' => 'Server Name',
                'sortable' => true,
                'filterable' => true,
                'editable' => true,
                'required' => true,
                'searchable' => true
            ],
            [
                'key' => 'location.country',
                'title' => 'Location',
                'sortable' => true,
                'filterable' => true,
                'component' => 'locationCell',
                'formatter' => 'formatLocation'
            ],
            [
                'key' => 'host',
                'title' => 'Host',
                'sortable' => true,
                'filterable' => true,
                'editable' => true,
                'searchable' => true,
                'validate' => 'validateHost'
            ],
            [
                'key' => 'port',
                'title' => 'Port',
                'sortable' => true,
                'editable' => true,
                'align' => 'center',
                'validate' => 'validatePort'
            ],
            [
                'key' => 'status',
                'title' => 'Status',
                'sortable' => true,
                'filterable' => true,
                'component' => 'statusBadge',
                'cellClass' => 'getStatusClass'
            ],
            [
                'key' => 'load_percentage',
                'title' => 'Load',
                'sortable' => true,
                'component' => 'progressBar',
                'align' => 'center'
            ],
            [
                'key' => 'protocols',
                'title' => 'Protocols',
                'filterable' => true,
                'component' => 'protocolTags'
            ],
            [
                'key' => 'last_ping',
                'title' => 'Last Ping',
                'sortable' => true,
                'formatter' => 'formatDateTime'
            ],
            [
                'key' => 'created_at',
                'title' => 'Created',
                'sortable' => true,
                'formatter' => 'formatDate',
                'visible' => false
            ]
        ]"
        :bulk-actions="[
            [
                'id' => 'activate',
                'label' => 'Activate Selected',
                'class' => 'btn-primary',
                'url' => route('api.servers.bulk-activate'),
                'confirm' => 'Are you sure you want to activate the selected servers?'
            ],
            [
                'id' => 'deactivate',
                'label' => 'Deactivate Selected',
                'class' => 'btn-secondary',
                'url' => route('api.servers.bulk-deactivate'),
                'confirm' => 'Are you sure you want to deactivate the selected servers?'
            ],
            [
                'id' => 'delete',
                'label' => 'Delete Selected',
                'class' => 'btn-danger',
                'url' => route('api.servers.bulk-delete'),
                'confirm' => 'Are you sure you want to delete the selected servers? This action cannot be undone!'
            ]
        ]"
        :export-formats="['csv', 'excel', 'pdf']"
        class="shadow-lg"
    />
</div>

{{-- Custom JavaScript for Table Enhancements --}}
@push('scripts')
<script>
// Custom cell components
window.tableComponents = {
    // Location cell with flag and country name
    locationCell: (row, value) => {
        const country = row.location?.country || 'Unknown';
        const countryCode = row.location?.country_code || 'xx';
        const city = row.location?.city || '';

        return `
            <div class="flex items-center space-x-2">
                <img src="https://flagcdn.com/w20/${countryCode.toLowerCase()}.png"
                     alt="${country}"
                     class="w-5 h-3 rounded shadow-sm"
                     onerror="this.style.display='none'">
                <div>
                    <div class="font-medium text-gray-900 dark:text-gray-100">${country}</div>
                    ${city ? `<div class="text-xs text-gray-500">${city}</div>` : ''}
                </div>
            </div>
        `;
    },

    // Status badge with color coding
    statusBadge: (row, value) => {
        const statusConfig = {
            'online': { class: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200', text: 'Online' },
            'offline': { class: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200', text: 'Offline' },
            'maintenance': { class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200', text: 'Maintenance' },
            'error': { class: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200', text: 'Error' }
        };

        const config = statusConfig[value] || statusConfig['offline'];

        return `
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${config.class}">
                <span class="w-2 h-2 bg-current rounded-full mr-1"></span>
                ${config.text}
            </span>
        `;
    },

    // Progress bar for server load
    progressBar: (row, value) => {
        const percentage = Math.min(Math.max(value || 0, 0), 100);
        const colorClass = percentage > 80 ? 'bg-red-500' : percentage > 60 ? 'bg-yellow-500' : 'bg-green-500';

        return `
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="${colorClass} h-2 rounded-full transition-all duration-300" style="width: ${percentage}%"></div>
            </div>
            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1 text-center">${percentage}%</div>
        `;
    },

    // Protocol tags
    protocolTags: (row, value) => {
        if (!Array.isArray(value)) return '';

        return value.map(protocol => `
            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 mr-1 mb-1">
                ${protocol.toUpperCase()}
            </span>
        `).join('');
    }
};

// Custom formatters
window.tableFormatters = {
    formatLocation: (value) => {
        if (typeof value === 'object' && value) {
            return `${value.country}${value.city ? `, ${value.city}` : ''}`;
        }
        return value || 'Unknown';
    },

    formatDateTime: (value) => {
        if (!value) return 'Never';
        return new Date(value).toLocaleString();
    },

    formatDate: (value) => {
        if (!value) return '';
        return new Date(value).toLocaleDateString();
    }
};

// Custom validators
window.tableValidators = {
    validateHost: (value) => {
        if (!value) return 'Host is required';
        const hostRegex = /^[a-zA-Z0-9.-]+$/;
        if (!hostRegex.test(value)) return 'Invalid host format';
        return true;
    },

    validatePort: (value) => {
        const port = parseInt(value);
        if (isNaN(port) || port < 1 || port > 65535) {
            return 'Port must be between 1 and 65535';
        }
        return true;
    }
};

// Custom cell class functions
window.tableCellClasses = {
    getStatusClass: (row, value) => {
        const statusClasses = {
            'online': 'text-green-600 dark:text-green-400',
            'offline': 'text-red-600 dark:text-red-400',
            'maintenance': 'text-yellow-600 dark:text-yellow-400',
            'error': 'text-red-600 dark:text-red-400'
        };
        return statusClasses[value] || '';
    }
};

// Enhanced Alpine.js data function for server table
window.enhancedServerTable = function() {
    return {
        ...window.interactiveDataTable(),

        // Override init to add custom functionality
        init() {
            // Call parent init
            this.initializeTable();
            this.setupEventListeners();
            this.startAutoRefresh();

            // Add custom functionality
            this.setupCustomFormatters();
            this.setupCustomValidators();
            this.setupCustomComponents();
        },

        setupCustomFormatters() {
            this.columns.forEach(column => {
                if (column.formatter && window.tableFormatters[column.formatter]) {
                    column.formatter = window.tableFormatters[column.formatter];
                }
            });
        },

        setupCustomValidators() {
            this.columns.forEach(column => {
                if (column.validate && window.tableValidators[column.validate]) {
                    column.validate = window.tableValidators[column.validate];
                }
            });
        },

        setupCustomComponents() {
            this.columns.forEach(column => {
                if (column.component && window.tableComponents[column.component]) {
                    column.component = window.tableComponents[column.component];
                }
                if (column.cellClass && window.tableCellClasses[column.cellClass]) {
                    column.cellClass = window.tableCellClasses[column.cellClass];
                }
            });
        },

        // Custom refresh with server health check
        async refreshData() {
            this.loading = true;
            try {
                // Load main data
                await this.loadData();

                // Update server health status
                await this.updateServerHealth();
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        async updateServerHealth() {
            try {
                const response = await fetch('/api/servers/health-check', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    }
                });

                if (response.ok) {
                    const healthData = await response.json();

                    // Update server statuses
                    this.data.forEach(server => {
                        const health = healthData.find(h => h.id === server.id);
                        if (health) {
                            server.status = health.status;
                            server.load_percentage = health.load_percentage;
                            server.last_ping = health.last_ping;
                        }
                    });
                }
            } catch (error) {
                console.warn('Health check failed:', error);
            }
        },

        // Custom bulk action with confirmation
        async executeBulkAction(action) {
            if (this.selectedRows.size === 0) return;

            // Show custom confirmation for critical actions
            if (action.id === 'delete') {
                const confirmed = await this.showCustomConfirmation(
                    'Delete Servers',
                    `Are you sure you want to delete ${this.selectedRows.size} server(s)? This action cannot be undone and will permanently remove all associated data.`,
                    'Delete',
                    'Cancel',
                    'danger'
                );

                if (!confirmed) return;
            }

            // Execute the bulk action
            this.bulkActionLoading = true;
            try {
                await this.sendBulkRequest(action.url, Array.from(this.selectedRows));
                this.clearSelection();
                await this.refreshData();

                // Show success notification
                this.showNotification(`${action.label} completed successfully`, 'success');
            } catch (error) {
                this.showNotification(`${action.label} failed: ${error.message}`, 'error');
            } finally {
                this.bulkActionLoading = false;
            }
        },

        async showCustomConfirmation(title, message, confirmText, cancelText, type) {
            return new Promise((resolve) => {
                // Create and show custom modal
                const modal = this.createConfirmationModal(title, message, confirmText, cancelText, type);
                document.body.appendChild(modal);

                // Handle button clicks
                modal.querySelector('[data-action="confirm"]').onclick = () => {
                    document.body.removeChild(modal);
                    resolve(true);
                };

                modal.querySelector('[data-action="cancel"]').onclick = () => {
                    document.body.removeChild(modal);
                    resolve(false);
                };
            });
        },

        createConfirmationModal(title, message, confirmText, cancelText, type) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50';

            const typeClasses = {
                danger: 'border-red-500 text-red-600',
                warning: 'border-yellow-500 text-yellow-600',
                info: 'border-blue-500 text-blue-600'
            };

            modal.innerHTML = `
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 border-t-4 ${typeClasses[type] || typeClasses.info}">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">${title}</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">${message}</p>
                        <div class="flex justify-end space-x-3">
                            <button data-action="cancel" class="btn-outline">${cancelText}</button>
                            <button data-action="confirm" class="btn-${type === 'danger' ? 'danger' : 'primary'}">${confirmText}</button>
                        </div>
                    </div>
                </div>
            `;

            return modal;
        },

        showNotification(message, type) {
            // Create and show notification
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg ${
                type === 'success' ? 'bg-green-500 text-white' :
                type === 'error' ? 'bg-red-500 text-white' :
                'bg-blue-500 text-white'
            }`;
            notification.textContent = message;

            document.body.appendChild(notification);

            // Auto remove after 5 seconds
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 5000);
        }
    };
};

// Register enhanced table component
document.addEventListener('alpine:init', () => {
    Alpine.data('enhancedServerTable', window.enhancedServerTable);
});
</script>
@endpush

{{-- Custom Styles --}}
@push('styles')
<style>
/* Custom table enhancements */
.data-table-container {
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Loading skeleton effect */
.skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* Custom scrollbar for table */
.data-table-container .overflow-x-auto::-webkit-scrollbar {
    height: 6px;
}

.data-table-container .overflow-x-auto::-webkit-scrollbar-track {
    @apply bg-gray-100 dark:bg-gray-700 rounded;
}

.data-table-container .overflow-x-auto::-webkit-scrollbar-thumb {
    @apply bg-gray-400 dark:bg-gray-500 rounded hover:bg-gray-500 dark:hover:bg-gray-400;
}

/* Enhanced button styles */
.btn-danger {
    @apply bg-red-600 text-white hover:bg-red-700 focus:ring-red-500;
}

/* Protocol tag animations */
.protocol-tag {
    transition: all 0.2s ease;
}

.protocol-tag:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>
@endpush
@endsection
