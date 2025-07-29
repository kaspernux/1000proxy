@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Marketing Automation</h1>
                    <p class="text-sm text-gray-600 mt-1">Manage email campaigns, lead nurturing, and automated workflows</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button wire:click="initializeAutomation"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200"
                            wire:loading.attr="disabled">
                        <div wire:loading.remove wire:target="initializeAutomation">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Initialize System
                        </div>
                        <div wire:loading wire:target="initializeAutomation" class="flex items-center">
                            <svg class="animate-spin h-4 w-4 mr-2" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Initializing...
                        </div>
                    </button>
                    <button wire:click="loadInitialData"
                            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div wire:loading.flex wire:target="loading" class="fixed inset-0 bg-black bg-opacity-50 z-50 items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-sm mx-4">
            <div class="flex items-center space-x-3">
                <svg class="animate-spin h-8 w-8 text-blue-600" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <div>
                    <div class="text-lg font-medium text-gray-900">Processing...</div>
                    @if($processingAction)
                        <div class="text-sm text-gray-600">{{ $processingAction }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if($successMessage)
        <div class="bg-green-50 border-l-4 border-green-400 p-4 mx-6 mt-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <p class="text-green-700">{{ $successMessage }}</p>
                <button wire:click="clearMessages" class="ml-auto text-green-400 hover:text-green-600">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    @if($errorMessage)
        <div class="bg-red-50 border-l-4 border-red-400 p-4 mx-6 mt-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <p class="text-red-700">{{ $errorMessage }}</p>
                <button wire:click="clearMessages" class="ml-auto text-red-400 hover:text-red-600">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    <!-- Navigation Tabs -->
    <div class="bg-white border-b border-gray-200 px-6">
        <nav class="flex space-x-8">
            <button wire:click="setActiveTab('overview')"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 {{ $activeTab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Overview
            </button>
            <button wire:click="setActiveTab('campaigns')"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 {{ $activeTab === 'campaigns' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Campaigns
            </button>
            <button wire:click="setActiveTab('workflows')"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 {{ $activeTab === 'workflows' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Workflows
            </button>
            <button wire:click="setActiveTab('segments')"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 {{ $activeTab === 'segments' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Segments
            </button>
            <button wire:click="setActiveTab('analytics')"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 {{ $activeTab === 'analytics' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Analytics
            </button>
        </nav>
    </div>

    <!-- Tab Content -->
    <div class="p-6">
        @if($activeTab === 'overview')
            <!-- Overview Tab -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                <!-- Campaign Performance -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 1.26a2 2 0 001.11-.502l4.29-3.429A2 2 0 0118 6.07V19a2 2 0 01-2 2H4a2 2 0 01-2-2V10a2 2 0 012-2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Total Campaigns</h3>
                            <p class="text-2xl font-semibold text-gray-900">{{ $this->getCampaignMetrics()['total_campaigns'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- Emails Sent -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Emails Sent</h3>
                            <p class="text-2xl font-semibold text-gray-900">{{ number_format($this->getCampaignMetrics()['emails_sent'] ?? 0) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Open Rate -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-yellow-100 rounded-lg">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Open Rate</h3>
                            <p class="text-2xl font-semibold text-gray-900">{{ number_format($this->getCampaignMetrics()['open_rate'] ?? 0, 1) }}%</p>
                        </div>
                    </div>
                </div>

                <!-- Click Rate -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Click Rate</h3>
                            <p class="text-2xl font-semibold text-gray-900">{{ number_format($this->getCampaignMetrics()['click_rate'] ?? 0, 1) }}%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <button wire:click="openCampaignModal"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Create Campaign
                    </button>
                    <button wire:click="processAbandonedCarts"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6 0v6a2 2 0 11-4 0v-6m6 0a2 2 0 11-4 0v-6a2 2 0 114 0v6Z"></path>
                        </svg>
                        Process Abandoned Carts
                    </button>
                    <button wire:click="openTestModal"
                            class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-3 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Test Email
                    </button>
                    <button wire:click="generateAnalytics"
                            class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-3 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Generate Analytics
                    </button>
                </div>
            </div>

            <!-- Email Providers Status -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Email Providers Status</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($emailProviders as $provider => $data)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full mr-3 {{ $data['status'] === 'active' ? 'bg-green-400' : 'bg-red-400' }}"></div>
                                    <span class="font-medium text-gray-900 capitalize">{{ $provider }}</span>
                                </div>
                                <span class="text-xs px-2 py-1 rounded-full {{ $data['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ ucfirst($data['status']) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($activeTab === 'campaigns')
            <!-- Campaigns Tab -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Email Campaigns</h3>
                        <button wire:click="openCampaignModal"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                            Create Campaign
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Campaign Types -->
                        <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow duration-200">
                            <h4 class="font-medium text-gray-900 mb-3">Welcome Series</h4>
                            <p class="text-sm text-gray-600 mb-4">Automated welcome emails for new users</p>
                            <button wire:click="executeCampaign('welcome_series')"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors duration-200">
                                Execute Campaign
                            </button>
                        </div>

                        <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow duration-200">
                            <h4 class="font-medium text-gray-900 mb-3">Win Back Campaign</h4>
                            <p class="text-sm text-gray-600 mb-4">Re-engage inactive customers</p>
                            <button wire:click="executeCampaign('win_back')"
                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors duration-200">
                                Execute Campaign
                            </button>
                        </div>

                        <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow duration-200">
                            <h4 class="font-medium text-gray-900 mb-3">Birthday Campaign</h4>
                            <p class="text-sm text-gray-600 mb-4">Special birthday offers for customers</p>
                            <button wire:click="executeCampaign('birthday')"
                                    class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors duration-200">
                                Execute Campaign
                            </button>
                        </div>

                        <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow duration-200">
                            <h4 class="font-medium text-gray-900 mb-3">Referral Program</h4>
                            <p class="text-sm text-gray-600 mb-4">Encourage customer referrals</p>
                            <button wire:click="executeCampaign('referral')"
                                    class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors duration-200">
                                Execute Campaign
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($activeTab === 'workflows')
            <!-- Workflows Tab -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Automated Workflows</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @foreach($workflows as $workflow)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 rounded-full mr-3 {{ $workflow['enabled'] ? 'bg-green-400' : 'bg-red-400' }}"></div>
                                        <div>
                                            <h4 class="font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $workflow['name']) }}</h4>
                                            <p class="text-sm text-gray-600">Trigger: {{ $workflow['trigger'] }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <span class="text-xs px-2 py-1 rounded-full {{ $workflow['enabled'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ ucfirst($workflow['status']) }}
                                        </span>
                                        <button wire:click="toggleWorkflow('{{ $workflow['name'] }}')"
                                                class="text-sm {{ $workflow['enabled'] ? 'text-red-600 hover:text-red-700' : 'text-green-600 hover:text-green-700' }} font-medium">
                                            {{ $workflow['enabled'] ? 'Disable' : 'Enable' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if($activeTab === 'segments')
            <!-- Segments Tab -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Customer Segments</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @if(isset($segments) && is_array($segments))
                            @foreach($segments as $segment => $data)
                                <div class="border border-gray-200 rounded-lg p-6">
                                    <h4 class="font-medium text-gray-900 mb-3 capitalize">{{ str_replace('_', ' ', $segment) }}</h4>
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600">Size:</span>
                                            <span class="text-sm font-medium">{{ $data['size'] ?? 'N/A' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600">Growth:</span>
                                            <span class="text-sm font-medium text-green-600">{{ $data['growth'] ?? 'N/A' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600">Engagement:</span>
                                            <span class="text-sm font-medium">{{ $data['engagement'] ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="col-span-3 text-center py-8">
                                <p class="text-gray-500">No segments data available</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if($activeTab === 'analytics')
            <!-- Analytics Tab -->
            <div class="space-y-6">
                <!-- Analytics Controls -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Marketing Analytics</h3>
                        <div class="flex items-center space-x-3">
                            <button wire:click="generateAnalytics(7)"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                                Last 7 Days
                            </button>
                            <button wire:click="generateAnalytics(30)"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                                Last 30 Days
                            </button>
                            <button wire:click="exportCampaignData('csv')"
                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                                Export CSV
                            </button>
                        </div>
                    </div>

                    <!-- Analytics Summary -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-900">{{ $this->getEmailMetrics()['delivered'] ?? 0 }}</div>
                            <div class="text-sm text-gray-600">Emails Delivered</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-900">{{ number_format(($this->getConversionRates()['email_to_website'] ?? 0), 1) }}%</div>
                            <div class="text-sm text-gray-600">Email to Website</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-900">{{ number_format(($this->getConversionRates()['cart_to_purchase'] ?? 0), 1) }}%</div>
                            <div class="text-sm text-gray-600">Cart to Purchase</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Create Campaign Modal -->
    @if($showCampaignModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg max-w-2xl w-full max-h-screen overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Create Email Campaign</h3>
                        <button wire:click="$set('showCampaignModal', false)" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <form wire:submit.prevent="createCampaign">
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Campaign Name</label>
                                <input type="text" wire:model="newCampaign.name"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                @error('newCampaign.name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Subject</label>
                                <input type="text" wire:model="newCampaign.subject"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                @error('newCampaign.subject') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Target Segment</label>
                                <select wire:model="newCampaign.target_segment"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select segment</option>
                                    <option value="all_customers">All Customers</option>
                                    <option value="new_customers">New Customers</option>
                                    <option value="high_value_customers">High Value Customers</option>
                                    <option value="at_risk_customers">At Risk Customers</option>
                                </select>
                                @error('newCampaign.target_segment') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Content</label>
                                <textarea wire:model="newCampaign.content" rows="6"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
                                @error('newCampaign.content') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Schedule (Optional)</label>
                                <input type="datetime-local" wire:model="newCampaign.schedule_at"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <div class="flex items-center justify-end space-x-3 mt-6">
                            <button type="button" wire:click="$set('showCampaignModal', false)"
                                    class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                                Create Campaign
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Test Email Modal -->
    @if($showTestModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Test Email Delivery</h3>
                        <button wire:click="$set('showTestModal', false)" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <form wire:submit.prevent="testEmailDelivery">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <input type="email" wire:model="testEmail"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                @error('testEmail') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Template</label>
                                <select wire:model="testTemplate"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select template</option>
                                    <option value="welcome">Welcome Email</option>
                                    <option value="abandoned_cart">Abandoned Cart</option>
                                    <option value="newsletter">Newsletter</option>
                                </select>
                                @error('testTemplate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="flex items-center justify-end space-x-3 mt-6">
                            <button type="button" wire:click="$set('showTestModal', false)"
                                    class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                                Send Test Email
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
