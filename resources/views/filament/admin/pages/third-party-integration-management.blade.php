<x-filament-panels::page>
<div class="fi-section-content-ctn">
    <section class="space-y-8 max-w-7xl mx-auto px-2 sm:px-4 lg:px-8 py-8">
    {{-- Filament Notifications handle toasts; no session flash UI needed here. --}}

        {{-- Header --}}
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Third-Party Integration Management</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Configure, monitor and test all external integrations</p>
            </div>
            <div class="flex items-center gap-3">
                <button wire:click="initializeIntegrations" class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                    <x-heroicon-o-rocket-launch class="w-4 h-4 mr-2" /> Initialize All
                </button>
                <button wire:click="refreshStatus" class="inline-flex items-center px-4 py-2 rounded-md border border-gray-300 dark:border-gray-700 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                    <x-heroicon-o-arrow-path class="w-4 h-4 mr-2" /> Refresh
                </button>
                <button wire:click="exportConfiguration" class="inline-flex items-center px-4 py-2 rounded-md border border-gray-300 dark:border-gray-700 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                    <x-heroicon-o-document-arrow-down class="w-4 h-4 mr-2" /> Export Config
                </button>
            </div>
        </header>

        {{-- Overview cards --}}
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Integrations</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $integrationStatus['total_integrations'] ?? 0 }}</p>
                    </div>
                    <x-heroicon-o-puzzle-piece class="w-8 h-8 text-indigo-500" />
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Active</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $integrationStatus['active_integrations'] ?? 0 }}</p>
                    </div>
                    <x-heroicon-o-check-badge class="w-8 h-8 text-green-500" />
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Failed</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $integrationStatus['failed_integrations'] ?? 0 }}</p>
                    </div>
                    <x-heroicon-o-x-circle class="w-8 h-8 text-red-500" />
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Uptime</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $integrationStatus['uptime_percentage'] ?? 0 }}%</p>
                    </div>
                    <x-heroicon-o-chart-bar-square class="w-8 h-8 text-purple-500" />
                </div>
            </div>
        </section>

        {{-- Tab nav --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="border-b border-gray-200 dark:border-gray-700 px-4">
                <nav class="-mb-px flex flex-wrap gap-4" aria-label="Tabs">
                    @php($tabs = ['overview' => 'Overview', 'billing' => 'Billing', 'crm' => 'CRM', 'analytics' => 'Analytics', 'support' => 'Support', 'webhooks' => 'Webhooks', 'partner_api' => 'Partner API'])
                    @foreach($tabs as $key => $label)
                        <button wire:click="setActiveTab('{{ $key }}')" class="py-3 px-1 border-b-2 text-sm font-medium {{ $activeTab === $key ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </nav>
            </div>

            <div class="p-6">
                {{-- Overview --}}
                @if($activeTab === 'overview')
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Service Health</h3>
                                <div class="space-y-3">
                                    @foreach(($integrationStatus['services'] ?? []) as $service => $s)
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <span class="h-2 w-2 rounded-full {{ ($s['status'] ?? '') === 'active' ? 'bg-green-500' : (($s['status'] ?? '') === 'failed' ? 'bg-red-500' : 'bg-yellow-500') }}"></span>
                                                <span class="text-sm text-gray-700 dark:text-gray-300 capitalize">{{ str_replace('_', ' ', $service) }}</span>
                                            </div>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $integrationStatus['last_check'] ?? '' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Active Alerts</h3>
                                <div class="space-y-3">
                                    @forelse($activeAlerts as $alert)
                                        <div class="flex items-start gap-3">
                                            <x-heroicon-o-bell-alert class="w-5 h-5 text-amber-500 mt-0.5" />
                                            <div>
                                                <p class="text-sm text-gray-900 dark:text-white">[{{ strtoupper($alert['severity']) }}] {{ ucfirst($alert['service']) }} — {{ $alert['message'] }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($alert['created_at'])->diffForHumans() }}</p>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-500 dark:text-gray-400">No active alerts</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Billing --}}
                @if($activeTab === 'billing')
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Billing Providers</h3>
                            <div class="flex gap-2">
                                <button wire:click="setupBillingIntegration" class="px-3 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">
                                    <x-heroicon-o-cog-6-tooth class="w-4 h-4 inline mr-1" /> Configure
                                </button>
                                <button wire:click="openConfigModal('billing')" class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md text-sm text-gray-700 dark:text-gray-200">
                                    <x-heroicon-o-eye class="w-4 h-4 inline mr-1" /> View Config
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach(($billingConfig['providers'] ?? []) as $provider => $cfg)
                                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-900">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-semibold capitalize text-gray-900 dark:text-white">{{ str_replace('_', ' ', $provider) }}</h4>
                                        <span class="px-2 py-0.5 rounded-full text-xs {{ ($cfg['enabled'] ?? false) ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">{{ ($cfg['enabled'] ?? false) ? 'Enabled' : 'Disabled' }}</span>
                                    </div>
                                    <div class="flex gap-2">
                                        <button wire:click="testIntegration('billing','{{ $provider }}')" class="px-3 py-1.5 text-sm rounded-md border border-gray-300 dark:border-gray-700">
                                            <x-heroicon-o-wrench-screwdriver class="w-4 h-4 inline mr-1" /> Test
                                        </button>
                                        <button wire:click="syncData('billing','{{ $provider }}')" class="px-3 py-1.5 text-sm rounded-md bg-green-600 text-white hover:bg-green-700">
                                            <x-heroicon-o-arrow-path class="w-4 h-4 inline mr-1" /> Sync
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- CRM --}}
                @if($activeTab === 'crm')
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">CRM Platforms</h3>
                            <div class="flex gap-2">
                                <button wire:click="setupCRMIntegration" class="px-3 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">
                                    <x-heroicon-o-cog-6-tooth class="w-4 h-4 inline mr-1" /> Configure
                                </button>
                                <button wire:click="openConfigModal('crm')" class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md text-sm text-gray-700 dark:text-gray-200">
                                    <x-heroicon-o-eye class="w-4 h-4 inline mr-1" /> View Config
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach(($crmConfig['platforms'] ?? []) as $platform => $cfg)
                                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-900">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-semibold capitalize text-gray-900 dark:text-white">{{ str_replace('_', ' ', $platform) }}</h4>
                                        <span class="px-2 py-0.5 rounded-full text-xs {{ ($cfg['status'] ?? '') === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">{{ ucfirst($cfg['status'] ?? 'unknown') }}</span>
                                    </div>
                                    <button wire:click="testIntegration('crm','{{ $platform }}')" class="px-3 py-1.5 text-sm rounded-md border border-gray-300 dark:border-gray-700">
                                        <x-heroicon-o-wrench-screwdriver class="w-4 h-4 inline mr-1" /> Test
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Analytics --}}
                @if($activeTab === 'analytics')
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Analytics Platforms</h3>
                            <div class="flex gap-2">
                                <button wire:click="setupAnalyticsIntegration" class="px-3 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">
                                    <x-heroicon-o-cog-6-tooth class="w-4 h-4 inline mr-1" /> Configure
                                </button>
                                <button wire:click="openConfigModal('analytics')" class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md text-sm text-gray-700 dark:text-gray-200">
                                    <x-heroicon-o-eye class="w-4 h-4 inline mr-1" /> View Config
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach(($analyticsConfig['platforms'] ?? []) as $platform => $cfg)
                                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-900">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-semibold capitalize text-gray-900 dark:text-white">{{ str_replace('_', ' ', $platform) }}</h4>
                                        <span class="px-2 py-0.5 rounded-full text-xs {{ ($cfg['status'] ?? '') === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">{{ ucfirst($cfg['status'] ?? 'unknown') }}</span>
                                    </div>
                                    <button wire:click="testIntegration('analytics','{{ $platform }}')" class="px-3 py-1.5 text-sm rounded-md border border-gray-300 dark:border-gray-700">
                                        <x-heroicon-o-wrench-screwdriver class="w-4 h-4 inline mr-1" /> Test
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Support --}}
                @if($activeTab === 'support')
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Support Platforms</h3>
                            <div class="flex gap-2">
                                <button wire:click="setupSupportIntegration" class="px-3 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">
                                    <x-heroicon-o-cog-6-tooth class="w-4 h-4 inline mr-1" /> Configure
                                </button>
                                <button wire:click="openConfigModal('support')" class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md text-sm text-gray-700 dark:text-gray-200">
                                    <x-heroicon-o-eye class="w-4 h-4 inline mr-1" /> View Config
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach(($supportConfig['platforms'] ?? []) as $platform => $cfg)
                                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-900">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-semibold capitalize text-gray-900 dark:text-white">{{ str_replace('_', ' ', $platform) }}</h4>
                                        <span class="px-2 py-0.5 rounded-full text-xs {{ ($cfg['status'] ?? '') === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">{{ ucfirst($cfg['status'] ?? 'unknown') }}</span>
                                    </div>
                                    <button wire:click="testIntegration('support','{{ $platform }}')" class="px-3 py-1.5 text-sm rounded-md border border-gray-300 dark:border-gray-700">
                                        <x-heroicon-o-wrench-screwdriver class="w-4 h-4 inline mr-1" /> Test
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Webhooks --}}
                @if($activeTab === 'webhooks')
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Webhook System</h3>
                            <div class="flex gap-2">
                                <button wire:click="setupWebhookSystem" class="px-3 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">
                                    <x-heroicon-o-cog-6-tooth class="w-4 h-4 inline mr-1" /> Configure
                                </button>
                                <button wire:click="openConfigModal('webhooks')" class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md text-sm text-gray-700 dark:text-gray-200">
                                    <x-heroicon-o-eye class="w-4 h-4 inline mr-1" /> View Config
                                </button>
                            </div>
                        </div>
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-900">
                            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Endpoints Configured</dt>
                                    <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ $webhookConfig['endpoints_configured'] ?? 0 }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Delivery Success</dt>
                                    <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ $webhookConfig['delivery_success_rate'] ?? 0 }}%</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Signature Verification</dt>
                                    <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ !empty($webhookConfig['signature_verification']) ? 'Enabled' : 'Disabled' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                @endif

                {{-- Partner API --}}
                @if($activeTab === 'partner_api')
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Partner API</h3>
                            <div class="flex gap-2">
                                <button wire:click="setupPartnerAPI" class="px-3 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">
                                    <x-heroicon-o-cog-6-tooth class="w-4 h-4 inline mr-1" /> Configure
                                </button>
                                <button wire:click="openConfigModal('partner_api')" class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md text-sm text-gray-700 dark:text-gray-200">
                                    <x-heroicon-o-eye class="w-4 h-4 inline mr-1" /> View Config
                                </button>
                                <button wire:click="testIntegration('partner_api')" class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md text-sm text-gray-700 dark:text-gray-200">
                                    <x-heroicon-o-wrench-screwdriver class="w-4 h-4 inline mr-1" /> Test
                                </button>
                            </div>
                        </div>
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-900">
                            <dl class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                                <div>
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">API Version</dt>
                                    <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ $partnerApiConfig['api_version'] ?? 'v1' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Active Partners</dt>
                                    <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ $partnerApiConfig['active_partners'] ?? 0 }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Rate Limit</dt>
                                    <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ $partnerApiConfig['rate_limit'] ?? 0 }}/hr</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Docs</dt>
                                    <dd class="text-lg font-semibold text-indigo-600 dark:text-indigo-400">
                                        <a href="{{ $partnerApiConfig['documentation_url'] ?? '#' }}" target="_blank" class="hover:underline">View</a>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Modals --}}
        @if($showConfigModal)
            <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-3xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ ucfirst(str_replace('_',' ', $selectedService)) }} Configuration</h3>
                        <button wire:click="closeConfigModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <x-heroicon-o-x-mark class="w-5 h-5" />
                        </button>
                    </div>
                    <pre class="text-xs bg-gray-50 dark:bg-gray-800 rounded-md p-4 overflow-auto max-h-96">{{ json_encode($configurationData, JSON_PRETTY_PRINT) }}</pre>
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400 space-y-1">
                        <p>Tip: Secrets are not shown. Change them in the provider dashboard and re-sync.</p>
                        <p>Export: Use the "Export Config" button to download a sanitized JSON snapshot.</p>
                        <p>Cache: This view reflects the currently cached configuration.</p>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button wire:click="closeConfigModal" class="px-4 py-2 rounded-md border border-gray-300 dark:border-gray-700 text-sm">Close</button>
                    </div>
                </div>
            </div>
        @endif

        @if($showTestModal)
            <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-lg p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Test Results</h3>
                        <button wire:click="closeTestModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <x-heroicon-o-x-mark class="w-5 h-5" />
                        </button>
                    </div>
                    <div class="space-y-2 text-sm">
                        <p><span class="font-semibold">Service:</span> {{ ucfirst(str_replace('_',' ', $testResults['service'] ?? '')) }}</p>
                        @if(!empty($testResults['provider']))
                            <p><span class="font-semibold">Provider:</span> {{ ucfirst(str_replace('_',' ', $testResults['provider'])) }}</p>
                        @endif
                        <p><span class="font-semibold">Response Time:</span> {{ $testResults['response_time'] ?? 0 }}ms</p>
                        <div class="mt-3 bg-gray-50 dark:bg-gray-800 rounded-md p-3">
                            <p class="font-semibold mb-2">Checks</p>
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach(($testResults['test_results'] ?? []) as $k => $v)
                                    <li class="capitalize">{{ str_replace('_',' ', $k) }}: <span class="text-green-600 dark:text-green-400 font-semibold">{{ $v }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button wire:click="closeTestModal" class="px-4 py-2 rounded-md border border-gray-300 dark:border-gray-700 text-sm">Close</button>
                    </div>
                </div>
            </div>
        @endif
    </section>
</div>
</x-filament-panels::page>

