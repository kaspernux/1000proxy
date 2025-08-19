<x-filament-panels::page>
    <div class="fi-section-content-ctn">
        <div class="my-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Staff Management</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Advanced user management with bulk operations and analytics</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            @php
                $stats = $this->getStatisticsData();
            @endphp

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                <x-heroicon-o-users class="w-5 h-5 text-white" />
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Users</dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-white">{{ $stats['total_users'] ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                <x-heroicon-o-check-circle class="w-5 h-5 text-white" />
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Active Users</dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-white">{{ $stats['active_users'] ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                                <x-heroicon-o-chat-bubble-left-right class="w-5 h-5 text-white" />
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Telegram Linked</dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-white">{{ $stats['telegram_linked'] ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                                <x-heroicon-o-shield-check class="w-5 h-5 text-white" />
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Admin Users</dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-white">{{ $stats['admin_users'] ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Role Distribution Chart -->
        @if(!empty($stats['role_distribution']))
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Role Distribution</h3>
                <div class="flex flex-wrap gap-4">
                    @foreach($stats['role_distribution'] as $role => $count)
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full mr-2
                                @if($role === 'admin') bg-red-500
                                @elseif($role === 'manager') bg-blue-500
                                @elseif($role === 'support_manager') bg-yellow-500
                                @elseif($role === 'sales_support') bg-green-500
                                @else bg-gray-500
                                @endif">
                            </div>
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                {{ ucwords(str_replace('_', ' ', $role)) }}: {{ $count }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Advanced Filters -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Advanced Filters</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                        <input type="text"
                            wire:model="filters.search"
                            placeholder="Name, email, telegram..."
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
                        <select wire:model="filters.role"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="">All Roles</option>
                            <option value="admin">Admin</option>
                            <option value="manager">Manager</option>
                            <option value="support_manager">Support Manager</option>
                            <option value="sales_support">Sales Support</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select wire:model="filters.is_active"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Telegram</label>
                        <select wire:model="filters.has_telegram"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="">All Users</option>
                            <option value="1">Linked</option>
                            <option value="0">Not Linked</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Last Active</label>
                        <select wire:model="filters.last_active_days"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Any Time</option>
                            <option value="1">Last 24 hours</option>
                            <option value="7">Last 7 days</option>
                            <option value="30">Last 30 days</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button wire:click="$set('filters', [])"
                                class="w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Table -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                {{ $this->table }}
            </div>
        </div>

        <!-- Activity Charts -->
        @if(!empty($stats['daily_registrations']) || !empty($stats['daily_logins']))
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
            @if(!empty($stats['daily_registrations']))
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Daily Registrations (Last 30 Days)</h3>
                    <div class="h-64 flex items-center justify-center text-gray-500 dark:text-gray-400">
                        <canvas id="registrations-chart" class="w-full h-full"></canvas>
                    </div>
                </div>
            </div>
            @endif

            @if(!empty($stats['daily_logins']))
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Daily Logins (Last 30 Days)</h3>
                    <div class="h-64 flex items-center justify-center text-gray-500 dark:text-gray-400">
                        <canvas id="logins-chart" class="w-full h-full"></canvas>
                    </div>
                </div>
            </div>
            @endif
        </div>
        @endif
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Registration Chart
        @if(!empty($stats['daily_registrations']))
        const registrationsCtx = document.getElementById('registrations-chart');
        if (registrationsCtx) {
            new Chart(registrationsCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode(array_keys($stats['daily_registrations'])) !!},
                    datasets: [{
                        label: 'Registrations',
                        data: {!! json_encode(array_values($stats['daily_registrations'])) !!},
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        @endif

        // Logins Chart
        @if(!empty($stats['daily_logins']))
        const loginsCtx = document.getElementById('logins-chart');
        if (loginsCtx) {
            new Chart(loginsCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode(array_keys($stats['daily_logins'])) !!},
                    datasets: [{
                        label: 'Logins',
                        data: {!! json_encode(array_values($stats['daily_logins'])) !!},
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        @endif
    });
    </script>
    @endpush
</x-filament-panels::page>
