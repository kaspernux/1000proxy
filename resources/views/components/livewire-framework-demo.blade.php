{{-- Livewire Framework Demo Component --}}
<div x-data="livewireFrameworkDemo" x-init="init()" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Livewire Framework Demo</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Advanced component architecture with lifecycle management</p>
        </div>
        <div class="flex items-center space-x-3 mt-4 sm:mt-0">
            <button
                @click="runTests()"
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                🧪 Run Tests
            </button>
            <button
                @click="demonstrateFeatures()"
                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                🚀 Demo Features
            </button>
        </div>
    </div>

    {{-- Framework Status --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
            <div class="flex items-center">
                <div class="p-2 bg-blue-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Components</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="stats.activeComponents"></p>
                </div>
            </div>
        </div>

        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
            <div class="flex items-center">
                <div class="p-2 bg-green-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Tests Passed</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="stats.testsPassed"></p>
                </div>
            </div>
        </div>

        <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-4">
            <div class="flex items-center">
                <div class="p-2 bg-purple-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h4a1 1 0 011 1v2h4a1 1 0 110 2h-1v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6H3a1 1 0 110-2h4z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Events Fired</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="stats.eventsFired"></p>
                </div>
            </div>
        </div>

        <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-700 rounded-lg p-4">
            <div class="flex items-center">
                <div class="p-2 bg-orange-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Mixins Applied</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="stats.mixinsApplied"></p>
                </div>
            </div>
        </div>
    </div>

    {{-- Component Lifecycle Demo --}}
    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Component Lifecycle</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Lifecycle Stages --}}
            <div>
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Lifecycle Stages</h4>
                <div class="space-y-2">
                    <template x-for="stage in lifecycleStages" :key="stage.name">
                        <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-3 h-3 rounded-full"
                                    :class="stage.completed ? 'bg-green-500' : stage.active ? 'bg-blue-500' : 'bg-gray-300'"
                                ></div>
                                <span class="text-sm text-gray-900 dark:text-white" x-text="stage.name"></span>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400" x-text="stage.timestamp"></span>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Component State --}}
            <div>
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Component State</h4>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded p-4">
                    <pre class="text-xs text-gray-900 dark:text-white overflow-auto max-h-48" x-text="JSON.stringify(componentState, null, 2)"></pre>
                </div>
            </div>
        </div>

        <div class="flex space-x-2 mt-4">
            <button
                @click="createTestComponent()"
                class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm font-medium transition-colors duration-200"
            >
                Create Component
            </button>
            <button
                @click="updateTestComponent()"
                :disabled="!testComponent"
                class="bg-green-500 hover:bg-green-600 disabled:bg-green-300 text-white px-3 py-2 rounded text-sm font-medium transition-colors duration-200"
            >
                Update State
            </button>
            <button
                @click="destroyTestComponent()"
                :disabled="!testComponent"
                class="bg-red-500 hover:bg-red-600 disabled:bg-red-300 text-white px-3 py-2 rounded text-sm font-medium transition-colors duration-200"
            >
                Destroy Component
            </button>
        </div>
    </div>

    {{-- Mixin Demonstration --}}
    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Mixin System</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Loading Mixin --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded p-4">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Loading Mixin</h4>
                <div class="space-y-2">
                    <button
                        @click="demonstrateLoading()"
                        class="w-full bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm font-medium transition-colors duration-200"
                    >
                        Show Loading
                    </button>
                    <div x-show="demoState.isLoading" class="text-center py-2">
                        <div class="inline-flex items-center text-blue-500">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-xs" x-text="demoState.loadingMessage"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Validation Mixin --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded p-4">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Validation Mixin</h4>
                <div class="space-y-2">
                    <input
                        x-model="demoState.testEmail"
                        type="email"
                        placeholder="test@example.com"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-700 dark:text-white"
                    >
                    <button
                        @click="demonstrateValidation()"
                        class="w-full bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded text-sm font-medium transition-colors duration-200"
                    >
                        Validate Email
                    </button>
                    <div x-show="demoState.validationResult" class="text-xs p-2 rounded" :class="demoState.validationResult?.isValid ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'">
                        <span x-text="demoState.validationResult?.message"></span>
                    </div>
                </div>
            </div>

            {{-- API Mixin --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded p-4">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">API Mixin</h4>
                <div class="space-y-2">
                    <button
                        @click="demonstrateApi()"
                        class="w-full bg-purple-500 hover:bg-purple-600 text-white px-3 py-2 rounded text-sm font-medium transition-colors duration-200"
                    >
                        Make API Call
                    </button>
                    <div x-show="demoState.apiResult" class="text-xs p-2 bg-gray-100 dark:bg-gray-700 rounded">
                        <span x-text="demoState.apiResult"></span>
                    </div>
                </div>
            </div>

            {{-- Pagination Mixin --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded p-4">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Pagination Mixin</h4>
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-600 dark:text-gray-400">Page:</span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="demoState.pagination.currentPage"></span>
                    </div>
                    <div class="flex space-x-1">
                        <button
                            @click="demonstratePagination('prev')"
                            :disabled="demoState.pagination.currentPage === 1"
                            class="flex-1 bg-gray-500 hover:bg-gray-600 disabled:bg-gray-300 text-white px-2 py-1 rounded text-xs font-medium transition-colors duration-200"
                        >
                            ←
                        </button>
                        <button
                            @click="demonstratePagination('next')"
                            :disabled="demoState.pagination.currentPage === demoState.pagination.totalPages"
                            class="flex-1 bg-gray-500 hover:bg-gray-600 disabled:bg-gray-300 text-white px-2 py-1 rounded text-xs font-medium transition-colors duration-200"
                        >
                            →
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Event System Demo --}}
    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Event System</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Event Emitter --}}
            <div>
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Event Emitter</h4>
                <div class="space-y-3">
                    <div class="flex space-x-2">
                        <input
                            x-model="eventDemo.eventName"
                            type="text"
                            placeholder="Event name"
                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-800 dark:text-white"
                        >
                        <button
                            @click="fireEvent()"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm font-medium transition-colors duration-200"
                        >
                            Fire Event
                        </button>
                    </div>
                    <input
                        x-model="eventDemo.eventData"
                        type="text"
                        placeholder="Event data (JSON)"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-800 dark:text-white"
                    >
                </div>
            </div>

            {{-- Event Log --}}
            <div>
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Event Log</h4>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded p-3 max-h-32 overflow-y-auto">
                    <template x-for="event in eventLog" :key="event.id">
                        <div class="text-xs py-1 border-b border-gray-100 dark:border-gray-700 last:border-b-0">
                            <span class="font-medium text-blue-600 dark:text-blue-400" x-text="event.name"></span>
                            <span class="text-gray-500 dark:text-gray-400 ml-2" x-text="event.timestamp"></span>
                            <div x-show="event.data" class="text-gray-600 dark:text-gray-300 mt-1" x-text="event.data"></div>
                        </div>
                    </template>
                </div>
                <button
                    @click="clearEventLog()"
                    class="w-full mt-2 bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-xs font-medium transition-colors duration-200"
                >
                    Clear Log
                </button>
            </div>
        </div>
    </div>

    {{-- Test Results --}}
    <div x-show="testResults" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Test Results</h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded p-3 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">Total Tests</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white" x-text="testResults?.total || 0"></p>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded p-3 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">Passed</p>
                <p class="text-xl font-bold text-green-600 dark:text-green-400" x-text="testResults?.passed || 0"></p>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded p-3 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">Failed</p>
                <p class="text-xl font-bold text-red-600 dark:text-red-400" x-text="testResults?.failed || 0"></p>
            </div>
        </div>

        <div class="space-y-2">
            <template x-for="result in testResults?.results || []" :key="result.name">
                <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded">
                    <div class="flex items-center space-x-3">
                        <div
                            class="w-3 h-3 rounded-full"
                            :class="result.status === 'passed' ? 'bg-green-500' : 'bg-red-500'"
                        ></div>
                        <span class="text-sm text-gray-900 dark:text-white" x-text="result.name"></span>
                    </div>
                    <span
                        class="text-xs px-2 py-1 rounded"
                        :class="result.status === 'passed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'"
                        x-text="result.status"
                    ></span>
                </div>
            </template>
        </div>
    </div>

    {{-- Loading State --}}
    <div x-show="isLoading" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-xl">
            <div class="flex items-center space-x-3">
                <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-gray-900 dark:text-white font-medium">Running framework operations...</span>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('livewireFrameworkDemo', () => ({
        isLoading: false,
        testComponent: null,
        testResults: null,
        stats: {
            activeComponents: 0,
            testsPassed: 0,
            eventsFired: 0,
            mixinsApplied: 4
        },
        lifecycleStages: [
            { name: 'beforeInit', completed: false, active: false, timestamp: null },
            { name: 'init', completed: false, active: false, timestamp: null },
            { name: 'afterInit', completed: false, active: false, timestamp: null },
            { name: 'beforeUpdate', completed: false, active: false, timestamp: null },
            { name: 'afterUpdate', completed: false, active: false, timestamp: null }
        ],
        componentState: {},
        demoState: {
            isLoading: false,
            loadingMessage: '',
            testEmail: '',
            validationResult: null,
            apiResult: null,
            pagination: {
                currentPage: 1,
                totalPages: 5
            }
        },
        eventDemo: {
            eventName: 'testEvent',
            eventData: '{"message": "Hello World"}'
        },
        eventLog: [],

        init() {
            this.updateStats();
            this.setupEventListeners();
        },

        updateStats() {
            this.stats.activeComponents = window.LivewireFramework?.ComponentRegistry?.components?.size || 0;
        },

        setupEventListeners() {
            // Listen for framework events
            if (window.LivewireFramework?.ComponentRegistry?.eventBus) {
                window.LivewireFramework.ComponentRegistry.eventBus.on('*', (data, source) => {
                    this.logEvent('Framework Event', JSON.stringify(data));
                });
            }
        },

        createTestComponent() {
            if (window.LivewireExamples?.createUserManagementComponent) {
                this.testComponent = window.LivewireExamples.createUserManagementComponent();
                this.testComponent.init();
                
                this.updateLifecycleStage('beforeInit', true);
                this.updateLifecycleStage('init', true);
                this.updateLifecycleStage('afterInit', true);
                
                this.componentState = this.testComponent.getState();
                this.stats.activeComponents++;
                
                this.logEvent('componentCreated', 'Test component created successfully');
            }
        },

        updateTestComponent() {
            if (this.testComponent) {
                this.updateLifecycleStage('beforeUpdate', true);
                
                this.testComponent.setState({
                    testData: {
                        timestamp: Date.now(),
                        action: 'update'
                    }
                });
                
                this.updateLifecycleStage('afterUpdate', true);
                this.componentState = this.testComponent.getState();
                
                this.logEvent('componentUpdated', 'Test component state updated');
            }
        },

        destroyTestComponent() {
            if (this.testComponent) {
                this.testComponent.destroy();
                this.testComponent = null;
                this.componentState = {};
                this.stats.activeComponents--;
                
                this.logEvent('componentDestroyed', 'Test component destroyed');
                this.resetLifecycleStages();
            }
        },

        updateLifecycleStage(stageName, completed) {
            const stage = this.lifecycleStages.find(s => s.name === stageName);
            if (stage) {
                stage.completed = completed;
                stage.active = false;
                stage.timestamp = new Date().toLocaleTimeString();
            }
        },

        resetLifecycleStages() {
            this.lifecycleStages.forEach(stage => {
                stage.completed = false;
                stage.active = false;
                stage.timestamp = null;
            });
        },

        demonstrateLoading() {
            this.demoState.isLoading = true;
            this.demoState.loadingMessage = 'Simulating async operation...';
            
            setTimeout(() => {
                this.demoState.isLoading = false;
                this.demoState.loadingMessage = '';
                this.logEvent('loadingDemo', 'Loading demonstration completed');
            }, 2000);
        },

        demonstrateValidation() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const isValid = emailRegex.test(this.demoState.testEmail);
            
            this.demoState.validationResult = {
                isValid,
                message: isValid ? 'Valid email address' : 'Invalid email address'
            };
            
            setTimeout(() => {
                this.demoState.validationResult = null;
            }, 3000);
            
            this.logEvent('validationDemo', `Email validation: ${isValid ? 'passed' : 'failed'}`);
        },

        demonstrateApi() {
            this.demoState.apiResult = 'Making API call...';
            
            // Simulate API call
            setTimeout(() => {
                this.demoState.apiResult = `API Response: ${JSON.stringify({
                    status: 'success',
                    timestamp: Date.now(),
                    data: { message: 'Hello from API' }
                })}`;
                
                setTimeout(() => {
                    this.demoState.apiResult = null;
                }, 5000);
                
                this.logEvent('apiDemo', 'API call demonstration completed');
            }, 1000);
        },

        demonstratePagination(action) {
            if (action === 'prev' && this.demoState.pagination.currentPage > 1) {
                this.demoState.pagination.currentPage--;
            } else if (action === 'next' && this.demoState.pagination.currentPage < this.demoState.pagination.totalPages) {
                this.demoState.pagination.currentPage++;
            }
            
            this.logEvent('paginationDemo', `Page changed to ${this.demoState.pagination.currentPage}`);
        },

        fireEvent() {
            try {
                const eventData = this.eventDemo.eventData ? JSON.parse(this.eventDemo.eventData) : null;
                this.logEvent(this.eventDemo.eventName, JSON.stringify(eventData));
                this.stats.eventsFired++;
            } catch (error) {
                this.logEvent('error', `Invalid JSON: ${error.message}`);
            }
        },

        logEvent(name, data = null) {
            this.eventLog.unshift({
                id: Date.now(),
                name,
                data,
                timestamp: new Date().toLocaleTimeString()
            });
            
            if (this.eventLog.length > 20) {
                this.eventLog.pop();
            }
        },

        clearEventLog() {
            this.eventLog = [];
        },

        async runTests() {
            this.isLoading = true;
            
            try {
                if (window.LivewireExamples?.runComponentTests) {
                    const results = window.LivewireExamples.runComponentTests();
                    this.testResults = results;
                    this.stats.testsPassed = results.passed;
                    this.logEvent('testsCompleted', `${results.passed}/${results.total} tests passed`);
                } else {
                    this.logEvent('testError', 'Test framework not available');
                }
            } catch (error) {
                this.logEvent('testError', error.message);
            } finally {
                this.isLoading = false;
            }
        },

        async demonstrateFeatures() {
            this.isLoading = true;
            
            // Demonstrate all features in sequence
            this.createTestComponent();
            await new Promise(resolve => setTimeout(resolve, 500));
            
            this.updateTestComponent();
            await new Promise(resolve => setTimeout(resolve, 500));
            
            this.demonstrateLoading();
            await new Promise(resolve => setTimeout(resolve, 2500));
            
            this.demonstrateValidation();
            await new Promise(resolve => setTimeout(resolve, 500));
            
            this.demonstrateApi();
            await new Promise(resolve => setTimeout(resolve, 1500));
            
            this.demonstratePagination('next');
            await new Promise(resolve => setTimeout(resolve, 500));
            
            this.fireEvent();
            
            this.isLoading = false;
            this.logEvent('demoCompleted', 'All features demonstrated successfully');
        }
    }));
});
</script>
