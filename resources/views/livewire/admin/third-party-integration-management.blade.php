@extends('layouts.app')

@section('content')

<section class="min-h-screen bg-gradient-to-br from-blue-900 via-gray-900 to-indigo-900 py-8 px-2 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-8">
        <!-- Header -->
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 bg-white/10 dark:bg-gray-900/80 shadow-2xl rounded-2xl px-6 py-8 border border-white/20 mb-6">
            <div>
                <h2 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight">Third-Party Integrations</h2>
                <p class="mt-2 text-lg text-white/80">Manage external service integrations and APIs</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <button
                    wire:click="refreshStatus"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-semibold border border-white/20 shadow transition"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Refresh Status</span>
                    <span wire:loading>Refreshing...</span>
                </button>
                <button
                    wire:click="exportConfiguration"
                    class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg text-sm font-semibold border border-white/20 shadow transition"
                >
                    Export Config
                </button>
            </div>
        </header>

        <!-- ...existing code... -->

    {{-- Navigation Tabs --}}
    <div class="bg-white shadow rounded-lg">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6">
                <button
                    wire:click="setActiveTab('overview')"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors
                        {{ $activeTab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    Overview
                </button>
                <button
                    wire:click="setActiveTab('billing')"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors
                        {{ $activeTab === 'billing' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    Billing Systems
                </button>
                <button
                    wire:click="setActiveTab('crm')"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors
                        {{ $activeTab === 'crm' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    CRM Integration
                </button>
                <button
                    wire:click="setActiveTab('analytics')"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors
                        {{ $activeTab === 'analytics' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    Analytics
                </button>
                <button
                    wire:click="setActiveTab('support')"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors
                        {{ $activeTab === 'support' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    Support Systems
                </button>
                <button
                    wire:click="setActiveTab('webhooks')"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors
                        {{ $activeTab === 'webhooks' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    Webhooks
                </button>
                <button
                    wire:click="setActiveTab('partner_api')"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors
                        {{ $activeTab === 'partner_api' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    Partner API
                </button>
            </nav>
        </div>

        {{-- Tab Content --}}
        <div class="p-6">
            {{-- Overview Tab --}}
            @if($activeTab === 'overview')
                <div class="space-y-6">
                    {{-- Overall Health Status --}}
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-6 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-blue-100 text-sm">Total Integrations</p>
                                    <p class="text-3xl font-bold">{{ $this->totalIntegrations }}</p>
                                </div>
                                <div class="text-blue-200">
                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-green-500 to-green-600 text-white p-6 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-green-100 text-sm">Active Integrations</p>
                                    <p class="text-3xl font-bold">{{ $this->activeIntegrations }}</p>
                                </div>
                                <div class="text-green-200">
                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-red-500 to-red-600 text-white p-6 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-red-100 text-sm">Failed Integrations</p>
                                    <p class="text-3xl font-bold">{{ $this->failedIntegrations }}</p>
                                </div>
                                <div class="text-red-200">
                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-purple-500 to-purple-600 text-white p-6 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-purple-100 text-sm">System Health</p>
                                    <p class="text-xl font-bold capitalize">{{ $this->integrationHealth }}</p>
                                </div>
                                <div class="text-purple-200">
                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Integration Status Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {{-- Billing Systems --}}
                        <div class="border border-gray-200 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Billing Systems</h3>
                                <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">
                                    Partially Configured
                                </span>
                            </div>
                            <div class="space-y-3">
                                @if(!empty($billingConfig['providers']))
                                    @foreach($billingConfig['providers'] as $provider => $config)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600 capitalize">{{ $provider }}</span>
                                            <span class="px-2 py-1 text-xs rounded-full {{ $config['enabled'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                {{ $config['enabled'] ? 'Active' : 'Disabled' }}
                                            </span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <div class="mt-4 flex space-x-2">
                                <button
                                    wire:click="setupBillingIntegration"
                                    class="text-blue-600 hover:text-blue-700 text-sm font-medium"
                                >
                                    Configure
                                </button>
                                <button
                                    wire:click="testIntegration('billing')"
                                    class="text-green-600 hover:text-green-700 text-sm font-medium"
                                >
                                    Test
                                </button>
                            </div>
                        </div>

                        {{-- CRM Integration --}}
                        <div class="border border-gray-200 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">CRM Integration</h3>
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                    Active
                                </span>
                            </div>
                            <div class="space-y-3">
                                @if(!empty($crmConfig['platforms']))
                                    @foreach($crmConfig['platforms'] as $platform => $config)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600 capitalize">{{ $platform }}</span>
                                            <span class="px-2 py-1 text-xs rounded-full {{ $config['enabled'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                {{ $config['enabled'] ? 'Active' : 'Disabled' }}
                                            </span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <div class="mt-4 flex space-x-2">
                                <button
                                    wire:click="setupCRMIntegration"
                                    class="text-blue-600 hover:text-blue-700 text-sm font-medium"
                                >
                                    Configure
                                </button>
                                <button
                                    wire:click="testIntegration('crm')"
                                    class="text-green-600 hover:text-green-700 text-sm font-medium"
                                >
                                    Test
                                </button>
                            </div>
                        </div>

                        {{-- Analytics Integration --}}
                        <div class="border border-gray-200 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Analytics</h3>
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                    Active
                                </span>
                            </div>
                            <div class="space-y-3">
                                @if(!empty($analyticsConfig['platforms']))
                                    @foreach($analyticsConfig['platforms'] as $platform => $config)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600 capitalize">{{ str_replace('_', ' ', $platform) }}</span>
                                            <span class="px-2 py-1 text-xs rounded-full {{ $config['enabled'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                {{ $config['enabled'] ? 'Active' : 'Disabled' }}
                                            </span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <div class="mt-4 flex space-x-2">
                                <button
                                    wire:click="setupAnalyticsIntegration"
                                    class="text-blue-600 hover:text-blue-700 text-sm font-medium"
                                >
                                    Configure
                                </button>
                                <button
                                    wire:click="testIntegration('analytics')"
                                    class="text-green-600 hover:text-green-700 text-sm font-medium"
                                >
                                    Test
                                </button>
                            </div>
                        </div>

                        {{-- Support Systems --}}
                        <div class="border border-gray-200 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Support Systems</h3>
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                    Active
                                </span>
                            </div>
                            <div class="space-y-3">
                                @if(!empty($supportConfig['platforms']))
                                    @foreach($supportConfig['platforms'] as $platform => $config)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600 capitalize">{{ $platform }}</span>
                                            <span class="px-2 py-1 text-xs rounded-full {{ $config['enabled'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                {{ $config['enabled'] ? 'Active' : 'Disabled' }}
                                            </span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <div class="mt-4 flex space-x-2">
                                <button
                                    wire:click="setupSupportIntegration"
                                    class="text-blue-600 hover:text-blue-700 text-sm font-medium"
                                >
                                    Configure
                                </button>
                                <button
                                    wire:click="testIntegration('support')"
                                    class="text-green-600 hover:text-green-700 text-sm font-medium"
                                >
                                    Test
                                </button>
                            </div>
                        </div>

                        {{-- Webhook System --}}
                        <div class="border border-gray-200 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Webhook System</h3>
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                    Active
                                </span>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Endpoints</span>
                                    <span class="text-sm font-medium">{{ $webhookConfig['endpoints_configured'] ?? 0 }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Success Rate</span>
                                    <span class="text-sm font-medium">{{ $webhookConfig['delivery_success_rate'] ?? 0 }}%</span>
                                </div>
                            </div>
                            <div class="mt-4 flex space-x-2">
                                <button
                                    wire:click="setupWebhookSystem"
                                    class="text-blue-600 hover:text-blue-700 text-sm font-medium"
                                >
                                    Configure
                                </button>
                                <button
                                    wire:click="testIntegration('webhooks')"
                                    class="text-green-600 hover:text-green-700 text-sm font-medium"
                                >
                                    Test
                                </button>
                            </div>
                        </div>

                        {{-- Partner API --}}
                        <div class="border border-gray-200 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Partner API</h3>
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                    Active
                                </span>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Active Partners</span>
                                    <span class="text-sm font-medium">{{ $partnerApiConfig['active_partners'] ?? 0 }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Rate Limit</span>
                                    <span class="text-sm font-medium">{{ $partnerApiConfig['rate_limit'] ?? 0 }}/hr</span>
                                </div>
                            </div>
                            <div class="mt-4 flex space-x-2">
                                <button
                                    wire:click="setupPartnerAPI"
                                    class="text-blue-600 hover:text-blue-700 text-sm font-medium"
                                >
                                    Configure
                                </button>
                                <button
                                    wire:click="testIntegration('partner_api')"
                                    class="text-green-600 hover:text-green-700 text-sm font-medium"
                                >
                                    Test
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Recent Alerts --}}
                    @if(!empty($activeAlerts))
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Alerts</h3>
                            <div class="space-y-3">
                                @foreach($activeAlerts as $alert)
                                    <div class="flex items-center justify-between bg-white p-3 rounded border">
                                        <div class="flex items-center space-x-3">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                                {{ $alert['severity'] === 'error' ? 'bg-red-100 text-red-800' :
                                                   ($alert['severity'] === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') }}">
                                                {{ ucfirst($alert['severity']) }}
                                            </span>
                                            <span class="text-sm text-gray-900">{{ $alert['message'] }}</span>
                                        </div>
                                        <span class="text-xs text-gray-500">{{ $alert['created_at']->diffForHumans() }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Quick Actions --}}
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <button
                                wire:click="initializeIntegrations"
                                class="bg-blue-600 text-white px-4 py-3 rounded-lg hover:bg-blue-700 transition-colors text-center"
                                wire:loading.attr="disabled"
                            >
                                <div class="flex items-center justify-center space-x-2">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"/>
                                    </svg>
                                    <span>Initialize All</span>
                                </div>
                            </button>

                            <button
                                wire:click="exportConfiguration"
                                class="bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 transition-colors text-center"
                            >
                                <div class="flex items-center justify-center space-x-2">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>Export Config</span>
                                </div>
                            </button>

                            <button
                                wire:click="refreshStatus"
                                class="bg-purple-600 text-white px-4 py-3 rounded-lg hover:bg-purple-700 transition-colors text-center"
                            >
                                <div class="flex items-center justify-center space-x-2">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>Refresh Status</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Other tab content would be implemented similarly --}}
            @if($activeTab !== 'overview')
                <div class="text-center py-12">
                    <div class="text-gray-400 mb-4">
                        <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.78 0-2.678-2.157-1.414-3.414l5-5A2 2 0 009 9.586V5L8 4z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">{{ ucfirst(str_replace('_', ' ', $activeTab)) }} Configuration</h3>
                    <p class="text-gray-500">Detailed configuration interface coming soon...</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Test Results Modal --}}
    @if($showTestModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Integration Test Results</h3>
                        <button wire:click="closeTestModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    @if(!empty($testResults))
                        <div class="space-y-4">
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-600">Service:</span>
                                <span class="text-sm font-medium capitalize">{{ $testResults['service'] ?? '' }}</span>
                            </div>

                            @if(isset($testResults['test_results']))
                                <div class="space-y-2">
                                    @foreach($testResults['test_results'] as $test => $result)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600 capitalize">{{ str_replace('_', ' ', $test) }}</span>
                                            <span class="px-2 py-1 text-xs rounded-full {{ $result === 'passed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ ucfirst($result) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="flex items-center justify-between border-t pt-4">
                                <span class="text-sm text-gray-600">Response Time</span>
                                <span class="text-sm font-medium">{{ $testResults['response_time'] ?? 0 }}ms</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Loading Overlay --}}
    @if($loading)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <div class="flex items-center space-x-3">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                    <span class="text-gray-900">Processing...</span>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('download-configuration', (data) => {
            const blob = new Blob([data.content], { type: 'application/json' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = data.filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        });
    });
</script>
@endsection
