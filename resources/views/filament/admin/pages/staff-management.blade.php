
<x-filament-panels::page>
    <x-filament::section class="!p-0">
    <div class="space-y-8 px-6 py-8">
            <p class="text-sm text-gray-600 dark:text-gray-400">Advanced user management with bulk operations and analytics</p>

            <!-- Statistics InfoCards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <x-filament::card class="flex items-center gap-4">
                    <div class="w-10 h-10 flex items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                        <x-heroicon-o-users class="w-6 h-6 text-blue-600 dark:text-blue-300" />
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Total Users</div>
                        <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['total_users'] ?? 0 }}</div>
                    </div>
                </x-filament::card>
                <x-filament::card class="flex items-center gap-4">
                    <div class="w-10 h-10 flex items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                        <x-heroicon-o-check-circle class="w-6 h-6 text-green-600 dark:text-green-300" />
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Active Users</div>
                        <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['active_users'] ?? 0 }}</div>
                    </div>
                </x-filament::card>
                <x-filament::card class="flex items-center gap-4">
                    <div class="w-10 h-10 flex items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900/30">
                        <x-heroicon-o-chat-bubble-left-right class="w-6 h-6 text-purple-600 dark:text-purple-300" />
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Telegram Linked</div>
                        <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['telegram_linked'] ?? 0 }}</div>
                    </div>
                </x-filament::card>
                <x-filament::card class="flex items-center gap-4">
                    <div class="w-10 h-10 flex items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                        <x-heroicon-o-shield-check class="w-6 h-6 text-red-600 dark:text-red-300" />
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Admin Users</div>
                        <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['admin_users'] ?? 0 }}</div>
                    </div>
                </x-filament::card>
            </div>

            <!-- Role Distribution Chart -->
            @if(!empty($stats['role_distribution']))
            <x-filament::card class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Role Distribution</h3>
                <div class="flex flex-wrap gap-4">
                    @foreach($stats['role_distribution'] as $role => $count)
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium
                            @if($role==='admin') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300
                            @elseif($role==='manager') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                            @elseif($role==='support_manager') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300
                            @elseif($role==='sales_support') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                            @else bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300
                            @endif">
                            {{ ucwords(str_replace('_', ' ', $role)) }}: {{ $count }}
                        </span>
                    @endforeach
                </div>
                <!-- Chart.js role chart can be added here if desired -->
            </x-filament::card>
            @endif

            <!-- Filament Table (filters, actions, badges, avatars, etc. are handled in PHP) -->
            <x-filament::card>
                {{ $this->table }}
            </x-filament::card>

            <!-- Charts always render, even if data is empty -->
            <!-- Activity Charts -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Daily Registrations (Last 30 Days)</h3>
                            <div class="h-64 flex items-center justify-center text-gray-500 dark:text-gray-400">
                                <canvas id="registrations-chart" class="w-full h-full" style="min-height: 256px;"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Daily Logins (Last 30 Days)</h3>
                            <div class="h-64 flex items-center justify-center text-gray-500 dark:text-gray-400">
                                <canvas id="logins-chart" class="w-full h-full" style="min-height: 256px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
    </div>
    </x-filament::section>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Always provide at least one label/value so chart always renders
        const regLabels = {!! json_encode(count($stats['daily_registrations'] ?? []) ? array_keys($stats['daily_registrations']) : ['No Data']) !!};
        const regData = {!! json_encode(count($stats['daily_registrations'] ?? []) ? array_values($stats['daily_registrations']) : [0]) !!};
        const logLabels = {!! json_encode(count($stats['daily_logins'] ?? []) ? array_keys($stats['daily_logins']) : ['No Data']) !!};
        const logData = {!! json_encode(count($stats['daily_logins'] ?? []) ? array_values($stats['daily_logins']) : [0]) !!};

        const registrationsCtx = document.getElementById('registrations-chart');
        if (registrationsCtx) {
            new Chart(registrationsCtx, {
                type: 'line',
                data: {
                    labels: regLabels,
                    datasets: [{
                        label: 'Registrations',
                        data: regData,
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
                            display: true,
                            min: 0,
                            max: 1,
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                color: '#6B7280',
                                callback: function(value, index, values) {
                                    // Always show at least two ticks: 0 and 1
                                    if (value === 0 || value === 1) return value;
                                    return '';
                                },
                                count: 2
                            },
                            grid: {
                                color: '#E5E7EB'
                            }
                        },
                        x: {
                            display: true,
                            ticks: {
                                color: '#6B7280',
                                callback: function(value, index, values) {
                                    return regLabels[index] || 'No Data';
                                }
                            },
                            grid: {
                                color: '#E5E7EB'
                            }
                        }
                    }
                }
            });
        }

        const loginsCtx = document.getElementById('logins-chart');
        if (loginsCtx) {
            new Chart(loginsCtx, {
                type: 'line',
                data: {
                    labels: logLabels,
                    datasets: [{
                        label: 'Logins',
                        data: logData,
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
                            display: true,
                            min: 0,
                            max: 1,
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                color: '#6B7280',
                                callback: function(value, index, values) {
                                    if (value === 0 || value === 1) return value;
                                    return '';
                                },
                                count: 2
                            },
                            grid: {
                                color: '#E5E7EB'
                            }
                        },
                        x: {
                            display: true,
                            ticks: {
                                color: '#6B7280',
                                callback: function(value, index, values) {
                                    return logLabels[index] || 'No Data';
                                }
                            },
                            grid: {
                                color: '#E5E7EB'
                            }
                        }
                    }
                }
            });
        }
    });
    </script>
    @endpush
</x-filament-panels::page>
