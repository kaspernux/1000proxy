{{-- Advanced State Management Demo Component --}}
<div x-data="stateManagerDemo" x-init="init()" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Advanced State Management</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Global state with persistence, validation, and synchronization</p>
        </div>
        <div class="flex items-center space-x-3 mt-4 sm:mt-0">
            <button
                @click="exportState()"
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                📤 Export State
            </button>
            <button
                @click="resetAllStores()"
                class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                🔄 Reset All
            </button>
        </div>
    </div>

    {{-- State Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
            <div class="flex items-center">
                <div class="p-2 bg-blue-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Stores</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="stats.activeStores"></p>
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
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">State Changes</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="stats.stateChanges"></p>
                </div>
            </div>
        </div>

        <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-4">
            <div class="flex items-center">
                <div class="p-2 bg-purple-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Persistence</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-white" x-text="persistenceStatus"></p>
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
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Sync Status</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-white" x-text="syncStatus"></p>
                </div>
            </div>
        </div>
    </div>

    {{-- User Preferences Store Demo --}}
    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">User Preferences Store</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {{-- Theme Settings --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded p-4">
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Theme Settings</h4>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Theme</label>
                        <select 
                            x-model="userPrefs.theme"
                            @change="updateUserPreference('theme', $event.target.value)"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-700 dark:text-white"
                        >
                            <option value="light">Light</option>
                            <option value="dark">Dark</option>
                            <option value="auto">Auto</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Language</label>
                        <select 
                            x-model="userPrefs.language"
                            @change="updateUserPreference('language', $event.target.value)"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-700 dark:text-white"
                        >
                            <option value="en">English</option>
                            <option value="es">Español</option>
                            <option value="fr">Français</option>
                            <option value="de">Deutsch</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Notification Settings --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded p-4">
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Notifications</h4>
                <div class="space-y-3">
                    <label class="flex items-center">
                        <input 
                            type="checkbox"
                            x-model="userPrefs.notifications.email"
                            @change="updateUserPreference('notifications.email', $event.target.checked)"
                            class="mr-2"
                        >
                        <span class="text-sm text-gray-700 dark:text-gray-300">Email Notifications</span>
                    </label>
                    <label class="flex items-center">
                        <input 
                            type="checkbox"
                            x-model="userPrefs.notifications.browser"
                            @change="updateUserPreference('notifications.browser', $event.target.checked)"
                            class="mr-2"
                        >
                        <span class="text-sm text-gray-700 dark:text-gray-300">Browser Notifications</span>
                    </label>
                    <label class="flex items-center">
                        <input 
                            type="checkbox"
                            x-model="userPrefs.notifications.telegram"
                            @change="updateUserPreference('notifications.telegram', $event.target.checked)"
                            class="mr-2"
                        >
                        <span class="text-sm text-gray-700 dark:text-gray-300">Telegram Alerts</span>
                    </label>
                </div>
            </div>

            {{-- Dashboard Settings --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded p-4">
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Dashboard</h4>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Layout</label>
                        <select 
                            x-model="userPrefs.dashboard.layout"
                            @change="updateUserPreference('dashboard.layout', $event.target.value)"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-700 dark:text-white"
                        >
                            <option value="grid">Grid</option>
                            <option value="list">List</option>
                            <option value="cards">Cards</option>
                        </select>
                    </div>
                    <label class="flex items-center">
                        <input 
                            type="checkbox"
                            x-model="userPrefs.dashboard.autoRefresh"
                            @change="updateUserPreference('dashboard.autoRefresh', $event.target.checked)"
                            class="mr-2"
                        >
                        <span class="text-sm text-gray-700 dark:text-gray-300">Auto Refresh</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- Application State Demo --}}
    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Application State</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Current State --}}
            <div>
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Current State</h4>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded p-4 space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Loading:</span>
                        <span class="text-sm font-medium" :class="appState.isLoading ? 'text-yellow-600' : 'text-green-600'">
                            <span x-text="appState.isLoading ? 'Yes' : 'No'"></span>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Online:</span>
                        <span class="text-sm font-medium" :class="appState.isOnline ? 'text-green-600' : 'text-red-600'">
                            <span x-text="appState.isOnline ? 'Yes' : 'No'"></span>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Active Connections:</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="appState.activeConnections.length"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Notifications:</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="appState.notifications.length"></span>
                    </div>
                </div>
            </div>

            {{-- State Actions --}}
            <div>
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">State Actions</h4>
                <div class="space-y-2">
                    <button
                        @click="toggleLoading()"
                        class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm font-medium transition-colors duration-200"
                    >
                        Toggle Loading State
                    </button>
                    <button
                        @click="addNotification()"
                        class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded text-sm font-medium transition-colors duration-200"
                    >
                        Add Notification
                    </button>
                    <button
                        @click="addConnection()"
                        class="w-full bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded text-sm font-medium transition-colors duration-200"
                    >
                        Add Connection
                    </button>
                    <button
                        @click="clearNotifications()"
                        class="w-full bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded text-sm font-medium transition-colors duration-200"
                    >
                        Clear Notifications
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- State History & Time Travel --}}
    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">State History & Time Travel</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- History Log --}}
            <div>
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Recent Changes</h4>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded p-3 max-h-64 overflow-y-auto">
                    <template x-for="change in stateHistory.slice(-10).reverse()" :key="change.timestamp">
                        <div class="text-xs py-2 border-b border-gray-100 dark:border-gray-700 last:border-b-0">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="font-medium text-blue-600 dark:text-blue-400" x-text="change.storeName"></span>
                                    <span class="text-gray-500 dark:text-gray-400 ml-1" x-text="change.action"></span>
                                </div>
                                <span class="text-gray-400 text-xs" x-text="formatTime(change.timestamp)"></span>
                            </div>
                            <div class="text-gray-600 dark:text-gray-300 mt-1" x-text="change.path"></div>
                        </div>
                    </template>
                </div>
                <div class="flex space-x-2 mt-3">
                    <button
                        @click="undoLastChange()"
                        class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-xs font-medium transition-colors duration-200"
                    >
                        ↶ Undo
                    </button>
                    <button
                        @click="clearHistory()"
                        class="flex-1 bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs font-medium transition-colors duration-200"
                    >
                        Clear History
                    </button>
                </div>
            </div>

            {{-- Validation & Middleware --}}
            <div>
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Validation & Middleware</h4>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Test Input (Email)</label>
                        <input 
                            x-model="testInput.email"
                            type="email"
                            placeholder="test@example.com"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-700 dark:text-white"
                        >
                        <button
                            @click="testValidation()"
                            class="w-full mt-2 bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs font-medium transition-colors duration-200"
                        >
                            Test Validation
                        </button>
                    </div>
                    <div x-show="validationResult" class="p-2 rounded text-xs" :class="validationResult?.success ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'">
                        <span x-text="validationResult?.message"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Computed Properties Demo --}}
    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Computed Properties</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded p-4 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">Total State Size</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white" x-text="computedValues.totalStateSize"></p>
            </div>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded p-4 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">Active Features</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white" x-text="computedValues.activeFeatures"></p>
            </div>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded p-4 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">System Health</p>
                <p class="text-sm font-bold" :class="computedValues.systemHealth === 'Healthy' ? 'text-green-600' : 'text-yellow-600'" x-text="computedValues.systemHealth"></p>
            </div>
        </div>
    </div>

    {{-- Export/Import State --}}
    <div x-show="exportData" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-xl max-w-2xl w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Export State Data</h3>
            <textarea 
                x-model="exportData"
                class="w-full h-64 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-700 dark:text-white font-mono"
                readonly
            ></textarea>
            <div class="flex justify-end space-x-3 mt-4">
                <button
                    @click="copyToClipboard()"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm font-medium transition-colors duration-200"
                >
                    📋 Copy
                </button>
                <button
                    @click="exportData = null"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm font-medium transition-colors duration-200"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('stateManagerDemo', () => ({
        stats: {
            activeStores: 0,
            stateChanges: 0
        },
        persistenceStatus: 'Active',
        syncStatus: 'Connected',
        userPrefs: {},
        appState: {},
        stateHistory: [],
        testInput: {
            email: ''
        },
        validationResult: null,
        computedValues: {
            totalStateSize: '0 KB',
            activeFeatures: 0,
            systemHealth: 'Healthy'
        },
        exportData: null,

        init() {
            this.setupStateManager();
            this.loadInitialState();
            this.setupComputedProperties();
            this.setupValidation();
            this.setupEventListeners();
        },

        setupStateManager() {
            // Ensure state manager is initialized
            if (window.StateManager && typeof window.StateManager.init === 'function') {
                window.StateManager.init();
                this.stats.activeStores = window.StateManager.stores.size;
            }
        },

        loadInitialState() {
            // Load user preferences
            const userPrefStore = window.StateManager?.getStore('userPreferences');
            if (userPrefStore) {
                this.userPrefs = { ...userPrefStore.state };
            }

            // Load app state
            const appStateStore = window.StateManager?.getStore('appState');
            if (appStateStore) {
                this.appState = { ...appStateStore.state };
            }
        },

        setupComputedProperties() {
            // Setup computed properties for demo
            const userPrefStore = window.StateManager?.getStore('userPreferences');
            if (userPrefStore) {
                userPrefStore.addComputed('activeFeatures', ['notifications', 'dashboard'], (state) => {
                    let count = 0;
                    if (state.notifications.email) count++;
                    if (state.notifications.browser) count++;
                    if (state.notifications.telegram) count++;
                    if (state.dashboard.autoRefresh) count++;
                    return count;
                });
            }

            // Update computed values
            this.updateComputedValues();
        },

        setupValidation() {
            const userPrefStore = window.StateManager?.getStore('userPreferences');
            if (userPrefStore) {
                userPrefStore.addValidator('testEmail', (value) => {
                    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                }, 'Invalid email format');
            }
        },

        setupEventListeners() {
            // Listen for state changes
            if (window.StateManager?.eventBus) {
                window.StateManager.eventBus.addEventListener('stateChange', (event) => {
                    this.stats.stateChanges++;
                    this.stateHistory.push(event.detail);
                    
                    // Update local state copies
                    if (event.detail.storeName === 'userPreferences') {
                        this.userPrefs = { ...window.StateManager.getStore('userPreferences').state };
                    } else if (event.detail.storeName === 'appState') {
                        this.appState = { ...window.StateManager.getStore('appState').state };
                    }
                    
                    this.updateComputedValues();
                });
            }

            // Listen for online/offline status
            window.addEventListener('online', () => {
                this.updateAppState('isOnline', true);
                this.syncStatus = 'Connected';
            });

            window.addEventListener('offline', () => {
                this.updateAppState('isOnline', false);
                this.syncStatus = 'Offline';
            });
        },

        updateUserPreference(path, value) {
            const store = window.StateManager?.getStore('userPreferences');
            if (store) {
                store.set(path, value);
            }
        },

        updateAppState(path, value) {
            const store = window.StateManager?.getStore('appState');
            if (store) {
                store.set(path, value);
            }
        },

        toggleLoading() {
            this.updateAppState('isLoading', !this.appState.isLoading);
        },

        addNotification() {
            const newNotification = {
                id: Date.now(),
                message: `Notification at ${new Date().toLocaleTimeString()}`,
                type: 'info',
                timestamp: Date.now()
            };
            
            const currentNotifications = [...this.appState.notifications, newNotification];
            this.updateAppState('notifications', currentNotifications);
        },

        addConnection() {
            const newConnection = {
                id: Date.now(),
                server: `Server-${Math.floor(Math.random() * 100)}`,
                status: 'connected',
                timestamp: Date.now()
            };
            
            const currentConnections = [...this.appState.activeConnections, newConnection];
            this.updateAppState('activeConnections', currentConnections);
        },

        clearNotifications() {
            this.updateAppState('notifications', []);
        },

        testValidation() {
            try {
                const store = window.StateManager?.getStore('userPreferences');
                if (store) {
                    store.set('testEmail', this.testInput.email);
                    this.validationResult = {
                        success: true,
                        message: 'Email validation passed'
                    };
                }
            } catch (error) {
                this.validationResult = {
                    success: false,
                    message: error.message
                };
            }
            
            setTimeout(() => {
                this.validationResult = null;
            }, 3000);
        },

        updateComputedValues() {
            // Calculate total state size
            const totalState = {};
            window.StateManager?.stores.forEach((store, name) => {
                totalState[name] = store.state;
            });
            const stateString = JSON.stringify(totalState);
            this.computedValues.totalStateSize = (stateString.length / 1024).toFixed(1) + ' KB';

            // Get active features from computed property
            const userPrefStore = window.StateManager?.getStore('userPreferences');
            if (userPrefStore) {
                this.computedValues.activeFeatures = userPrefStore.getComputed('activeFeatures') || 0;
            }

            // Calculate system health
            this.computedValues.systemHealth = this.appState.isOnline ? 'Healthy' : 'Degraded';
        },

        undoLastChange() {
            // Find the most recent store with changes and undo
            if (this.stateHistory.length > 0) {
                const lastChange = this.stateHistory[this.stateHistory.length - 1];
                const store = window.StateManager?.getStore(lastChange.storeName);
                if (store && typeof store.undo === 'function') {
                    store.undo();
                }
            }
        },

        clearHistory() {
            window.StateManager?.stores.forEach(store => {
                if (typeof store.clearHistory === 'function') {
                    store.clearHistory();
                }
            });
            this.stateHistory = [];
        },

        exportState() {
            const exportData = {};
            window.StateManager?.stores.forEach((store, name) => {
                exportData[name] = store.serialize();
            });
            
            this.exportData = JSON.stringify(exportData, null, 2);
        },

        resetAllStores() {
            window.StateManager?.stores.forEach(store => {
                if (typeof store.reset === 'function') {
                    store.reset();
                }
            });
            
            this.loadInitialState();
            this.stats.stateChanges = 0;
            this.stateHistory = [];
        },

        copyToClipboard() {
            navigator.clipboard.writeText(this.exportData).then(() => {
                // Show success feedback
                console.log('State data copied to clipboard');
            });
        },

        formatTime(timestamp) {
            return new Date(timestamp).toLocaleTimeString();
        }
    }));
});
</script>
