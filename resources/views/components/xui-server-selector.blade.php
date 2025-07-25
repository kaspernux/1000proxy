{{-- XUI Server Selector with Auto-recommendations --}}
<div x-data="xuiServerSelector()" class="server-selector-container">
    <div class="server-selector-header bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center space-x-2">
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h6a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h6a2 2 0 002-2v-4a2 2 0 00-2-2m8-8a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V4z"></path>
                </svg>
                <span>Server Selector</span>
            </h3>

            <div class="flex items-center space-x-3">
                <button
                    @click="analyzeRecommendations()"
                    :disabled="isAnalyzing"
                    class="btn-primary btn-sm flex items-center space-x-2"
                >
                    <svg x-show="isAnalyzing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="isAnalyzing ? 'Analyzing...' : 'Refresh Recommendations'"></span>
                </button>
            </div>
        </div>

        {{-- User Preferences --}}
        <div class="preferences-section bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
            <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">Preferences</h4>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Protocol</label>
                    <select
                        x-model="userPreferences.protocol"
                        @change="updatePreferences()"
                        class="select-input"
                    >
                        <option value="any">Any Protocol</option>
                        <option value="vless">VLESS</option>
                        <option value="vmess">VMESS</option>
                        <option value="trojan">Trojan</option>
                        <option value="shadowsocks">Shadowsocks</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Region</label>
                    <select
                        x-model="userPreferences.region"
                        @change="updatePreferences()"
                        class="select-input"
                    >
                        <option value="any">Any Region</option>
                        <option value="us">United States</option>
                        <option value="eu">Europe</option>
                        <option value="asia">Asia</option>
                        <option value="oceania">Oceania</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Max Latency (ms)</label>
                    <input
                        type="number"
                        x-model="userPreferences.latencyThreshold"
                        @change="updatePreferences()"
                        min="50"
                        max="1000"
                        step="10"
                        class="input-field"
                    >
                </div>

                <div class="space-y-2">
                    <label class="flex items-center space-x-2">
                        <input
                            type="checkbox"
                            x-model="userPreferences.prioritizeSpeed"
                            @change="updatePreferences()"
                            class="checkbox"
                        >
                        <span class="text-sm text-gray-700 dark:text-gray-300">Prioritize Speed</span>
                    </label>
                    <label class="flex items-center space-x-2">
                        <input
                            type="checkbox"
                            x-model="userPreferences.prioritizeStability"
                            @change="updatePreferences()"
                            class="checkbox"
                        >
                        <span class="text-sm text-gray-700 dark:text-gray-300">Prioritize Stability</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Recommendations Section --}}
        <div x-show="recommendations.length > 0" class="recommendations-section mb-6">
            <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center space-x-2">
                <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                </svg>
                <span>Recommended Servers</span>
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="rec in recommendations" :key="rec.server.id">
                    <div class="recommendation-card bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-700 dark:to-gray-600 rounded-lg p-4 border-2 border-blue-200 dark:border-blue-600">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center space-x-2">
                                <div class="flex items-center justify-center w-6 h-6 bg-blue-500 text-white rounded-full text-xs font-bold" x-text="rec.rank"></div>
                                <h5 class="font-medium text-gray-900 dark:text-gray-100" x-text="rec.server.name"></h5>
                            </div>
                            <div
                                class="status-indicator w-3 h-3 rounded-full"
                                :class="getServerHealth(rec.server.id)?.status === 'online' ? 'bg-green-500' : 'bg-red-500'"
                            ></div>
                        </div>

                        <div class="space-y-2 text-sm mb-4">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Region:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100" x-text="rec.server.region"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Protocols:</span>
                                <span class="text-xs text-gray-500" x-text="rec.server.protocols.join(', ')"></span>
                            </div>
                            <div x-show="getServerHealth(rec.server.id)?.latency" class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Latency:</span>
                                <span class="font-medium text-green-600 dark:text-green-400" x-text="`${getServerHealth(rec.server.id)?.latency}ms`"></span>
                            </div>
                            <div class="text-xs text-blue-600 dark:text-blue-300 mt-2" x-text="rec.reason"></div>
                        </div>

                        <button
                            @click="selectServer(rec.server)"
                            :class="selectedServer?.id === rec.server.id ? 'btn-primary' : 'btn-outline'"
                            class="w-full btn-sm"
                        >
                            <span x-show="selectedServer?.id !== rec.server.id">Select Server</span>
                            <span x-show="selectedServer?.id === rec.server.id" class="flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Selected</span>
                            </span>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        {{-- All Servers Section --}}
        <div class="all-servers-section">
            <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">All Available Servers</h4>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <template x-for="server in servers" :key="server.id">
                    <div
                        class="server-card bg-white dark:bg-gray-700 rounded-lg p-4 border-2 transition-all duration-200"
                        :class="selectedServer?.id === server.id ? 'border-blue-500 shadow-md' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="font-medium text-gray-900 dark:text-gray-100" x-text="server.name"></h5>
                            <div
                                class="status-indicator w-3 h-3 rounded-full"
                                :class="getServerHealth(server.id)?.status === 'online' ? 'bg-green-500' : 'bg-red-500'"
                            ></div>
                        </div>

                        <div class="space-y-2 text-sm mb-4">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Region:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100" x-text="server.region"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Load:</span>
                                <span class="font-medium" :class="server.load > 80 ? 'text-red-500' : server.load > 60 ? 'text-yellow-500' : 'text-green-500'" x-text="`${server.load}%`"></span>
                            </div>
                            <div x-show="getServerHealth(server.id)?.latency" class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Latency:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100" x-text="`${getServerHealth(server.id)?.latency}ms`"></span>
                            </div>
                            <div class="text-xs text-gray-500">
                                <span x-text="server.protocols.join(', ')"></span>
                            </div>
                        </div>

                        <button
                            @click="selectServer(server)"
                            :class="selectedServer?.id === server.id ? 'btn-primary' : 'btn-outline'"
                            class="w-full btn-sm"
                        >
                            <span x-show="selectedServer?.id !== server.id">Select</span>
                            <span x-show="selectedServer?.id === server.id" class="flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Selected</span>
                            </span>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        {{-- Selected Server Info --}}
        <div x-show="selectedServer" class="selected-server-info mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-6">
            <h4 class="text-md font-medium text-blue-900 dark:text-blue-100 mb-4 flex items-center space-x-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span>Selected Server: </span>
                <span x-text="selectedServer?.name"></span>
            </h4>

            <div x-show="selectedServer" class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-blue-700 dark:text-blue-300 font-medium">Region:</span>
                    <span class="ml-2 text-blue-900 dark:text-blue-100" x-text="selectedServer?.region"></span>
                </div>
                <div>
                    <span class="text-blue-700 dark:text-blue-300 font-medium">Load:</span>
                    <span class="ml-2 text-blue-900 dark:text-blue-100" x-text="`${selectedServer?.load}%`"></span>
                </div>
                <div>
                    <span class="text-blue-700 dark:text-blue-300 font-medium">Protocols:</span>
                    <span class="ml-2 text-blue-900 dark:text-blue-100" x-text="selectedServer?.protocols.join(', ')"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.recommendation-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.recommendation-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.server-card {
    transition: all 0.2s ease;
    cursor: pointer;
}

.server-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.status-indicator {
    transition: all 0.3s ease;
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.8);
}

.preferences-section {
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.dark .preferences-section {
    border-color: rgba(255, 255, 255, 0.1);
}

.selected-server-info {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
