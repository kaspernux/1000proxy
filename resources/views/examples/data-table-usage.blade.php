<!-- Example Usage: Servers Management Page -->
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Server Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Servers Data Table -->
            <x-data-table
                title="Servers"
                description="Manage your proxy servers and monitor their status"
                :searchable="true"
                :sortable="true"
                :paginated="true"
                :selectable="true"
                :exportable="true"
                :editable="true"
                :refreshable="true"
                :auto-refresh="true"
                :refresh-rate="30000"
                data-url="/api/servers"
                update-url="/api/servers"
                websocket-url="{{ config('app.websocket_url') }}/servers"
                class="mb-8"
                x-data="serversDataTable()"
            />

            <!-- Server Clients Modal -->
            <div 
                x-data="{ 
                    showModal: false, 
                    selectedServerId: null,
                    serverName: ''
                }"
                @view-server-clients.window="
                    selectedServerId = $event.detail.id;
                    serverName = $event.detail.name || 'Server ' + $event.detail.id;
                    showModal = true;
                "
            >
                <!-- Modal Overlay -->
                <div 
                    x-show="showModal"
                    x-transition.opacity
                    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
                    @click.self="showModal = false"
                >
                    <!-- Modal Content -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-6xl w-full mx-4 max-h-[90vh] overflow-hidden">
                        <!-- Modal Header -->
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                    <span x-text="serverName"></span> - Clients
                                </h3>
                                <button 
                                    @click="showModal = false"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                >
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Modal Body -->
                        <div class="overflow-auto max-h-[calc(90vh-120px)]">
                            <div x-show="selectedServerId" x-data="serverClientsDataTable(selectedServerId)">
                                <x-data-table
                                    title=""
                                    :searchable="true"
                                    :sortable="true"
                                    :paginated="true"
                                    :selectable="true"
                                    :exportable="true"
                                    :editable="true"
                                    :refreshable="true"
                                    :auto-refresh="true"
                                    :refresh-rate="15000"
                                    :default-per-page="10"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Modals and Forms -->
            @include('admin.servers.modals')
        </div>
    </div>
</x-app-layout>

<!-- Orders Management Page Example -->
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Order Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                            <i class="fas fa-shopping-cart text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Total Orders</h3>
                            <p class="text-2xl font-bold text-blue-600">{{ $totalOrders }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                            <i class="fas fa-clock text-yellow-600 dark:text-yellow-400"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Pending</h3>
                            <p class="text-2xl font-bold text-yellow-600">{{ $pendingOrders }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                            <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Completed</h3>
                            <p class="text-2xl font-bold text-green-600">{{ $completedOrders }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                            <i class="fas fa-dollar-sign text-purple-600 dark:text-purple-400"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Revenue</h3>
                            <p class="text-2xl font-bold text-purple-600">${{ number_format($totalRevenue, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Data Table -->
            <x-data-table
                title="Orders"
                description="Monitor and manage customer orders in real-time"
                :searchable="true"
                :sortable="true"
                :paginated="true"
                :selectable="true"
                :exportable="true"
                :refreshable="true"
                :auto-refresh="true"
                :refresh-rate="10000"
                data-url="/api/orders"
                websocket-url="{{ config('app.websocket_url') }}/orders"
                class="mb-8"
                x-data="ordersDataTable()"
            />
        </div>
    </div>
</x-app-layout>

<!-- Users Management Page Example -->
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('User Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Users Data Table -->
            <x-data-table
                title="Users"
                description="Manage user accounts, roles, and permissions"
                :searchable="true"
                :sortable="true"
                :paginated="true"
                :selectable="true"
                :exportable="true"
                :editable="true"
                :refreshable="true"
                :auto-refresh="true"
                :refresh-rate="60000"
                data-url="/api/admin/users"
                update-url="/api/admin/users"
                websocket-url="{{ config('app.websocket_url') }}/users"
                :per-page-options="[25, 50, 100, 200]"
                :default-per-page="25"
                class="mb-8"
                x-data="usersDataTable()"
            />

            <!-- Bulk Actions Panel -->
            <div 
                x-data="{ selectedCount: 0 }"
                @cell-updated.window="console.log('Cell updated:', $event.detail)"
                class="bg-white dark:bg-gray-800 rounded-lg shadow p-6"
            >
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Bulk Actions</h3>
                
                <div class="flex flex-wrap gap-4">
                    <button 
                        @click="bulkAction('export')"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                    >
                        <i class="fas fa-download mr-2"></i>
                        Export Selected
                    </button>
                    
                    <button 
                        @click="bulkAction('delete')"
                        class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100"
                    >
                        <i class="fas fa-trash mr-2"></i>
                        Delete Selected
                    </button>
                    
                    <button 
                        @click="bulkAction('role-change')"
                        class="inline-flex items-center px-4 py-2 border border-blue-300 rounded-md shadow-sm text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100"
                    >
                        <i class="fas fa-user-cog mr-2"></i>
                        Change Role
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<!-- Mobile Responsive Example -->
<div class="block lg:hidden">
    <!-- Mobile-optimized data table -->
    <x-data-table
        title="Orders (Mobile)"
        :searchable="true"
        :paginated="true"
        :default-per-page="10"
        :per-page-options="[5, 10, 25]"
        data-url="/api/orders"
        class="text-sm"
        x-data="ordersDataTable()"
    />
</div>

<script>
// Global bulk action handler
window.bulkAction = function(action) {
    const table = document.querySelector('[x-data*="dataTable"]').__x.$data;
    const selectedData = table.getSelectedData();
    
    switch (action) {
        case 'export':
            if (selectedData.length === 0) {
                alert('Please select items to export');
                return;
            }
            table.exportData('csv');
            break;
            
        case 'delete':
            if (selectedData.length === 0) {
                alert('Please select items to delete');
                return;
            }
            if (confirm(`Are you sure you want to delete ${selectedData.length} items?`)) {
                // Handle bulk delete
                window.dataTablesService.bulkDelete(table.tableId, selectedData.map(item => item.id));
            }
            break;
            
        case 'role-change':
            if (selectedData.length === 0) {
                alert('Please select users to update');
                return;
            }
            const newRole = prompt('Enter new role (admin, user, moderator):');
            if (newRole) {
                // Handle bulk role change
                const updates = selectedData.map(user => ({
                    id: user.id,
                    data: { role: newRole }
                }));
                window.dataTablesService.bulkUpdate(table.tableId, updates);
            }
            break;
    }
};

// Custom event handlers for table actions
document.addEventListener('DOMContentLoaded', function() {
    // Server management events
    window.addEventListener('edit-server', function(e) {
        console.log('Edit server:', e.detail.id);
        // Open edit modal or navigate to edit page
    });
    
    window.addEventListener('test-server-connection', function(e) {
        console.log('Test connection for server:', e.detail.id);
        // Test server connection
    });
    
    window.addEventListener('delete-server', function(e) {
        if (confirm('Are you sure you want to delete this server?')) {
            console.log('Delete server:', e.detail.id);
            // Delete server
        }
    });
    
    // Order management events
    window.addEventListener('process-order', function(e) {
        console.log('Process order:', e.detail.id);
        // Process order
    });
    
    window.addEventListener('cancel-order', function(e) {
        if (confirm('Are you sure you want to cancel this order?')) {
            console.log('Cancel order:', e.detail.id);
            // Cancel order
        }
    });
    
    // Notification system
    window.addEventListener('show-notification', function(e) {
        const { type, message } = e.detail;
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            type === 'warning' ? 'bg-yellow-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    });
});
</script>
