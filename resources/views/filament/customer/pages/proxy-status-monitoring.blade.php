<x-filament-panels::page>
    <div class="fi-section-content-ctn">
    <div class="space-y-10">
        <!-- QR Modal -->
        <div x-data="{ qr: null }" x-init="$watch(() => $wire.selectedQrImage, v => qr = v)" x-cloak>
            <div x-show="qr" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/60" @click="$wire.closeQr()"></div>
                <div class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl p-4 w-[90vw] max-w-md">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Client QR Code</h3>
                        <button class="fi-btn fi-color-gray fi-size-xs" @click="$wire.closeQr()">
                            <x-heroicon-o-x-mark class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="w-full flex items-center justify-center">
                        <img :src="qr" alt="QR Code" class="rounded-lg max-w-full h-auto" />
                    </div>
                </div>
            </div>
        </div>
        <!-- Modern Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 mb-8">
            <div>
                <p class="text-base text-gray-600 dark:text-gray-400">Live monitoring, health scores, and actionable alerts for all your proxies.</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 xl:grid-cols-4">
            <div class="bg-gradient-to-br from-primary-500 to-blue-600 text-white rounded-xl shadow-lg p-6 flex flex-col items-center">
                <x-heroicon-o-list-bullet class="h-8 w-8 mb-2 text-white" />
                <p class="text-lg font-semibold">Total Proxies</p>
                <p class="text-3xl font-bold">{{ $overallMetrics['total_proxies'] ?? 0 }}</p>
            </div>
            <div class="bg-gradient-to-br from-success-500 to-emerald-600 text-white rounded-xl shadow-lg p-6 flex flex-col items-center">
                <x-heroicon-o-check-circle class="h-8 w-8 mb-2 text-white" />
                <p class="text-lg font-semibold">Online</p>
                <p class="text-3xl font-bold">{{ $overallMetrics['online_proxies'] ?? 0 }}</p>
            </div>
            <div class="bg-gradient-to-br from-red-500 to-pink-600 text-white rounded-xl shadow-lg p-6 flex flex-col items-center">
                <x-heroicon-o-x-circle class="h-8 w-8 mb-2 text-white" />
                <p class="text-lg font-semibold">Offline</p>
                <p class="text-3xl font-bold">{{ $overallMetrics['offline_proxies'] ?? 0 }}</p>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-indigo-600 text-white rounded-xl shadow-lg p-6 flex flex-col items-center">
                <x-heroicon-o-heart class="h-8 w-8 mb-2 text-white" />
                <p class="text-lg font-semibold">Health Score</p>
                <p class="text-3xl font-bold">{{ $overallMetrics['health_score'] ?? 0 }}</p>
            </div>
        </div>

        <!-- Alerts Section -->
        @if(!empty($alertsAndIssues))
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                @foreach($alertsAndIssues as $alert)
                    <div class="rounded-xl shadow-lg border p-6 flex items-start gap-4 {{
                        $alert['type'] === 'error' ? 'bg-gradient-to-r from-red-50 to-red-100 border-red-200 dark:from-red-900/20 dark:to-red-800/20 dark:border-red-800' :
                        ($alert['type'] === 'warning' ? 'bg-gradient-to-r from-yellow-50 to-yellow-100 border-yellow-200 dark:from-yellow-900/20 dark:to-yellow-800/20 dark:border-yellow-800' :
                        'bg-gradient-to-r from-blue-50 to-blue-100 border-blue-200 dark:from-blue-900/20 dark:to-blue-800/20 dark:border-blue-800')
                    }}">
                        <div>
                            @if($alert['type'] === 'error')
                                <x-heroicon-o-x-circle class="h-7 w-7 text-red-400" />
                            @elseif($alert['type'] === 'warning')
                                <x-heroicon-o-exclamation-triangle class="h-7 w-7 text-yellow-400" />
                            @else
                                <x-heroicon-o-information-circle class="h-7 w-7 text-blue-400" />
                            @endif
                        </div>
                        <div>
                            <h3 class="text-base font-semibold mb-1 {{
                                $alert['type'] === 'error' ? 'text-red-800 dark:text-red-200' :
                                ($alert['type'] === 'warning' ? 'text-yellow-800 dark:text-yellow-200' :
                                'text-blue-800 dark:text-blue-200')
                            }}">
                                {{ $alert['title'] }}
                            </h3>
                            <div class="text-sm mb-2 {{
                                $alert['type'] === 'error' ? 'text-red-700 dark:text-red-300' :
                                ($alert['type'] === 'warning' ? 'text-yellow-700 dark:text-yellow-300' :
                                'text-blue-700 dark:text-blue-300')
                            }}">
                                <p>{{ $alert['message'] }}</p>
                            </div>
                            @if(!empty($alert['proxies']))
                                <div class="flex flex-wrap gap-2 mt-2">
                                    @foreach($alert['proxies'] as $proxy)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">{{ $proxy }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Proxy Status Table -->
        <div class="my-16">
            <x-filament::section class="shadow-xl rounded-xl">
                <x-slot name="heading">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-primary-100 dark:bg-primary-900/30 rounded-xl">
                            <x-heroicon-o-list-bullet class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Proxy Statuses</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Comprehensive monitoring with real-time updates</p>
                        </div>
                    </div>
                </x-slot>
                <div class="p-6">
                    <!-- Filter Bar -->
                    <div class="mb-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Search</label>
                            <input type="search" wire:model.debounce.500ms="searchTerm" placeholder="Search by name, plan, email…" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Protocol</label>
                            <select wire:model="protocolFilter" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                @foreach($this->getProtocolOptions() as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center gap-2 py-2">
                            <input id="only-offline" type="checkbox" wire:model="showOfflineOnly" class="rounded border-gray-300 dark:border-gray-700" />
                            <label for="only-offline" class="text-sm text-gray-700 dark:text-gray-300">Show offline only</label>
                        </div>
                        <div class="flex items-center gap-2 py-2">
                            <input id="include-pending" type="checkbox" wire:model="includePending" class="rounded border-gray-300 dark:border-gray-700" />
                            <label for="include-pending" class="text-sm text-gray-700 dark:text-gray-300">Include pending purchases</label>
                        </div>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 rounded-xl overflow-hidden">
                            <thead class="bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-900/10 dark:to-primary-800/10">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-primary-700 dark:text-primary-300 uppercase tracking-wider w-2/5">Proxy / Plan</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-primary-700 dark:text-primary-300 uppercase tracking-wider w-1/6">Location</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-primary-700 dark:text-primary-300 uppercase tracking-wider w-1/6">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-primary-700 dark:text-primary-300 uppercase tracking-wider w-1/6">Latency</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-primary-700 dark:text-primary-300 uppercase tracking-wider w-1/6">Uptime</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-primary-700 dark:text-primary-300 uppercase tracking-wider w-1/5">Usage</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-primary-700 dark:text-primary-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $rows = method_exists($this, 'getFilteredProxies') ? $this->getFilteredProxies() : ($proxyStatuses ?? []);
                                @endphp
                                @if(empty($rows))
                                    <tr>
                                        <td colspan="7" class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">No proxies found.</td>
                                    </tr>
                                @else
                                @foreach($rows as $proxy)
                                    <tr class="hover:bg-primary-100 dark:hover:bg-primary-900/20 transition border-b last:border-b-0">
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white flex flex-col gap-1">
                                            <div class="font-semibold flex items-center gap-2 truncate">
                                                <x-heroicon-o-server class="w-4 h-4 text-primary-500 dark:text-primary-400" />
                                                <span class="truncate">{{ $proxy['name'] }}</span>
                                            </div>
                                            <div class="text-xs text-gray-600 dark:text-gray-400 flex flex-wrap gap-x-4 gap-y-1">
                                                <span class="flex items-center gap-1">
                                                    <x-heroicon-o-tag class="w-3 h-3" />
                                                    {{ $proxy['plan']['name'] ?? '—' }}
                                                    @if(!empty($proxy['plan']['protocol']))
                                                        <span class="text-gray-400">• {{ strtoupper($proxy['plan']['protocol']) }}</span>
                                                    @endif
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <x-heroicon-o-globe-alt class="w-3 h-3" />
                                                    {{ $proxy['connection_details']['host'] ?? $proxy['connection_details']['ip'] ?? '—' }}
                                                    @if(!empty($proxy['connection_details']['port']))
                                                        :{{ $proxy['connection_details']['port'] }}
                                                    @endif
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <x-heroicon-o-squares-2x2 class="w-3 h-3" />
                                                    {{ $proxy['inbound']['protocol'] ?? '—' }}@if(!empty($proxy['inbound']['port'])):{{ $proxy['inbound']['port'] }}@endif
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <x-heroicon-o-user class="w-3 h-3" />
                                                    {{ $proxy['client']['email'] ?? \Illuminate\Support\Str::limit($proxy['client']['uuid'] ?? '—', 10, '…') }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 flex items-center gap-2 truncate">
                                            <x-heroicon-o-map-pin class="w-4 h-4 text-primary-400 dark:text-primary-300" />
                                            <span class="truncate">{{ $proxy['location'] }}</span>
                                        </td>
                                        @php
                                            $statusClass = 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-700';
                                            if (($proxy['status'] ?? null) === 'online') {
                                                $statusClass = 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-700';
                                            } elseif (($proxy['status'] ?? null) === 'offline') {
                                                $statusClass = 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-700';
                                            }
                                        @endphp
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold shadow-sm border {{ $statusClass }}">
                                                @if($proxy['status'] === 'online')
                                                    <x-heroicon-o-check-circle class="w-3 h-3 mr-1 text-green-600 dark:text-green-400" />
                                                @elseif($proxy['status'] === 'offline')
                                                    <x-heroicon-o-x-circle class="w-3 h-3 mr-1 text-red-600 dark:text-red-400" />
                                                @else
                                                    <x-heroicon-o-exclamation-triangle class="w-3 h-3 mr-1 text-yellow-600 dark:text-yellow-400" />
                                                @endif
                                                {{ ucfirst($proxy['status']) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-mono flex items-center gap-1 whitespace-nowrap">
                                            <x-heroicon-o-bolt class="w-3 h-3 text-primary-400 dark:text-primary-300" />
                                            {{ $proxy['latency'] }}<span class="text-xs text-gray-500 dark:text-gray-400">ms</span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-mono flex items-center gap-1 whitespace-nowrap">
                                            <x-heroicon-o-clock class="w-3 h-3 text-primary-400 dark:text-primary-300" />
                                            {{ $proxy['uptime'] }}<span class="text-xs text-gray-500 dark:text-gray-400">%</span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-1 font-mono">
                                                    <x-heroicon-o-chart-bar class="w-3 h-3 text-primary-400 dark:text-primary-300" />
                                                    {{ $proxy['data_usage']['total'] ?? '0 B' }}
                                                </div>
                                                @if(isset($proxy['data_usage']['usage_percent']))
                                                    <div class="text-xs text-gray-600 dark:text-gray-400">
                                                        {{ $proxy['data_usage']['usage_percent'] }}%
                                                    </div>
                                                @endif
                                            </div>
                                            @php
                                                $percent = (float)($proxy['data_usage']['usage_percent'] ?? 0);
                                            @endphp
                                            <div class="mt-1 h-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                                <div class="h-2 bg-primary-500 dark:bg-primary-400" style="width: {{ min(100, max(0, $percent)) }}%"></div>
                                            </div>
                                            @if(isset($proxy['data_usage']['limit_mb']) && $proxy['data_usage']['limit_mb'])
                                                @php
                                                    $usedMb = (float)($proxy['data_usage']['used_mb'] ?? 0);
                                                    $limitMb = (float)$proxy['data_usage']['limit_mb'];
                                                    $fmt = function($mb){ return $mb >= 1024 ? number_format($mb/1024, 1).' GB' : number_format($mb, 0).' MB'; };
                                                @endphp
                                                <div class="mt-1 text-xs text-gray-600 dark:text-gray-400 font-mono">
                                                    {{ $fmt($usedMb) }} / {{ $fmt($limitMb) }}
                                                </div>
                                            @endif
                                        </td>
                                    @php
                                        $isPending = is_string($proxy['id'] ?? null) && str_starts_with($proxy['id'], 'order_item_');
                                        $dis = $isPending ? 'disabled' : '';
                                    @endphp
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex flex-wrap gap-2">
                                            <button class="fi-btn fi-color-gray fi-size-xs" title="Copy link" wire:click="copyLink(@js($proxy['id']))" {{ $dis }}>
                                                <x-heroicon-o-document-duplicate class="w-4 h-4" />
                                            </button>
                                            <button class="fi-btn fi-color-gray fi-size-xs" title="Show QR" wire:click="openQr(@js($proxy['id']))" {{ $dis }}>
                                                <x-heroicon-o-qr-code class="w-4 h-4" />
                                            </button>
                                            <button class="fi-btn fi-color-primary fi-size-xs" title="Test" wire:click="testSingle(@js($proxy['id']))" {{ $dis }}>
                                                <x-heroicon-o-bolt class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                    </tr>
                                @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
                                