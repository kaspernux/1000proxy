{{-- Color Theme Settings Component --}}
<div x-data="colorThemeManager()" class="color-theme-settings">
    {{-- Theme Selection --}}
    <div class="theme-controls space-y-6">
        <div class="theme-selector">
            <h3 class="text-lg font-semibold text-primary mb-4">
                <i class="fas fa-palette mr-2"></i>
                Theme Selection
            </h3>

            <div class="theme-options grid grid-cols-2 md:grid-cols-4 gap-4">
                <template x-for="theme in availableThemes" :key="theme">
                    <button
                        @click="switchTheme(theme)"
                        :class="{
                            'ring-2 ring-primary-500': isCurrentTheme(theme),
                            'ring-1 ring-border-primary': !isCurrentTheme(theme)
                        }"
                        class="theme-option p-4 rounded-lg border transition-all duration-200 hover:shadow-md"
                    >
                        <div class="theme-preview mb-2">
                            {{-- Theme preview circles --}}
                            <div class="flex space-x-1 justify-center">
                                <template x-if="theme === 'light'">
                                    <div class="flex space-x-1">
                                        <div class="w-4 h-4 rounded-full bg-white border border-gray-300"></div>
                                        <div class="w-4 h-4 rounded-full bg-blue-500"></div>
                                        <div class="w-4 h-4 rounded-full bg-gray-100"></div>
                                    </div>
                                </template>

                                <template x-if="theme === 'dark'">
                                    <div class="flex space-x-1">
                                        <div class="w-4 h-4 rounded-full bg-gray-900 border border-gray-600"></div>
                                        <div class="w-4 h-4 rounded-full bg-blue-400"></div>
                                        <div class="w-4 h-4 rounded-full bg-gray-800"></div>
                                    </div>
                                </template>

                                <template x-if="theme === 'colorblind'">
                                    <div class="flex space-x-1">
                                        <div class="w-4 h-4 rounded-full bg-teal-600"></div>
                                        <div class="w-4 h-4 rounded-full bg-orange-500"></div>
                                        <div class="w-4 h-4 rounded-full bg-red-700"></div>
                                    </div>
                                </template>

                                <template x-if="theme === 'high-contrast'">
                                    <div class="flex space-x-1">
                                        <div class="w-4 h-4 rounded-full bg-black border-2 border-white"></div>
                                        <div class="w-4 h-4 rounded-full bg-yellow-400"></div>
                                        <div class="w-4 h-4 rounded-full bg-white border-2 border-black"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="theme-name text-sm font-medium capitalize" x-text="theme"></div>

                        <template x-if="theme === 'colorblind'">
                            <div class="text-xs text-muted mt-1">Accessibility</div>
                        </template>

                        <template x-if="theme === 'high-contrast'">
                            <div class="text-xs text-muted mt-1">High Contrast</div>
                        </template>
                    </button>
                </template>
            </div>
        </div>

        {{-- Country Themes --}}
        <div class="country-themes">
            <h3 class="text-lg font-semibold text-primary mb-4">
                <i class="fas fa-globe mr-2"></i>
                Country Themes
            </h3>

            <div class="country-options flex flex-wrap gap-3">
                <template x-for="country in countryThemes" :key="country">
                    <button
                        @click="applyCountryTheme(country)"
                        class="country-option flex items-center space-x-2 px-4 py-2 rounded-lg border border-border-primary hover:border-border-accent transition-colors"
                    >
                        <span class="flag-icon" x-text="`🇺🇸`" x-show="country === 'us'"></span>
                        <span class="flag-icon" x-text="`🇬🇧`" x-show="country === 'uk'"></span>
                        <span class="flag-icon" x-text="`🇩🇪`" x-show="country === 'de'"></span>
                        <span class="flag-icon" x-text="`🇯🇵`" x-show="country === 'jp'"></span>
                        <span class="flag-icon" x-text="`🇸🇬`" x-show="country === 'sg'"></span>
                        <span class="country-name text-sm font-medium uppercase" x-text="country"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- Brand Themes --}}
        <div class="brand-themes">
            <h3 class="text-lg font-semibold text-primary mb-4">
                <i class="fas fa-tags mr-2"></i>
                Brand Themes
            </h3>

            <div class="brand-options grid grid-cols-2 md:grid-cols-4 gap-4">
                <template x-for="brand in brandThemes" :key="brand">
                    <button
                        @click="applyBrandTheme(brand)"
                        class="brand-option p-4 rounded-lg border border-border-primary hover:border-border-accent transition-colors text-center"
                    >
                        <div class="brand-icon mb-2">
                            <template x-if="brand === 'premium'">
                                <i class="fas fa-crown text-2xl text-purple-600"></i>
                            </template>
                            <template x-if="brand === 'gaming'">
                                <i class="fas fa-gamepad text-2xl text-green-600"></i>
                            </template>
                            <template x-if="brand === 'streaming'">
                                <i class="fas fa-play text-2xl text-red-600"></i>
                            </template>
                            <template x-if="brand === 'business'">
                                <i class="fas fa-briefcase text-2xl text-gray-600"></i>
                            </template>
                        </div>
                        <div class="brand-name text-sm font-medium capitalize" x-text="brand"></div>
                    </button>
                </template>
            </div>
        </div>

        {{-- Status Colors Preview --}}
        <div class="status-colors-preview">
            <h3 class="text-lg font-semibold text-primary mb-4">
                <i class="fas fa-circle mr-2"></i>
                Status Colors
            </h3>

            <div class="status-examples grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="status-example">
                    <div class="status-indicator w-6 h-6 rounded-full bg-status-online mb-2"></div>
                    <div class="status-label text-sm status-online">Online</div>
                </div>

                <div class="status-example">
                    <div class="status-indicator w-6 h-6 rounded-full bg-status-offline mb-2"></div>
                    <div class="status-label text-sm status-offline">Offline</div>
                </div>

                <div class="status-example">
                    <div class="status-indicator w-6 h-6 rounded-full bg-status-maintenance mb-2"></div>
                    <div class="status-label text-sm status-maintenance">Maintenance</div>
                </div>

                <div class="status-example">
                    <div class="status-indicator w-6 h-6 rounded-full bg-status-partial mb-2"></div>
                    <div class="status-label text-sm status-partial">Partial</div>
                </div>

                <div class="status-example">
                    <div class="status-indicator w-6 h-6 rounded-full bg-status-unknown mb-2"></div>
                    <div class="status-label text-sm status-unknown">Unknown</div>
                </div>
            </div>
        </div>

        {{-- Performance Colors Preview --}}
        <div class="performance-colors-preview">
            <h3 class="text-lg font-semibold text-primary mb-4">
                <i class="fas fa-tachometer-alt mr-2"></i>
                Performance Colors
            </h3>

            <div class="performance-examples grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="performance-example">
                    <div class="performance-bar h-4 rounded-full bg-performance-excellent mb-2"></div>
                    <div class="performance-label text-sm performance-excellent">Excellent</div>
                </div>

                <div class="performance-example">
                    <div class="performance-bar h-4 rounded-full bg-performance-good mb-2"></div>
                    <div class="performance-label text-sm performance-good">Good</div>
                </div>

                <div class="performance-example">
                    <div class="performance-bar h-4 rounded-full bg-performance-fair mb-2"></div>
                    <div class="performance-label text-sm performance-fair">Fair</div>
                </div>

                <div class="performance-example">
                    <div class="performance-bar h-4 rounded-full bg-performance-poor mb-2"></div>
                    <div class="performance-label text-sm performance-poor">Poor</div>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="quick-actions">
            <h3 class="text-lg font-semibold text-primary mb-4">
                <i class="fas fa-tools mr-2"></i>
                Quick Actions
            </h3>

            <div class="action-buttons flex flex-wrap gap-3">
                <button
                    @click="toggleTheme()"
                    class="action-button interactive-primary px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                >
                    <i class="fas fa-adjust mr-2"></i>
                    Toggle Theme
                </button>

                <button
                    @click="exportSettings()"
                    class="action-button interactive-secondary px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                >
                    <i class="fas fa-download mr-2"></i>
                    Export Settings
                </button>

                <label class="action-button interactive-secondary px-4 py-2 rounded-lg text-sm font-medium transition-colors cursor-pointer">
                    <i class="fas fa-upload mr-2"></i>
                    Import Settings
                    <input
                        type="file"
                        accept=".json"
                        @change="importSettings"
                        class="hidden"
                    >
                </label>

                <button
                    @click="resetToDefaults()"
                    class="action-button border border-error-500 text-error-500 hover:bg-error-50 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                >
                    <i class="fas fa-undo mr-2"></i>
                    Reset to Defaults
                </button>
            </div>
        </div>

        {{-- Accessibility Features --}}
        <div class="accessibility-features">
            <h3 class="text-lg font-semibold text-primary mb-4">
                <i class="fas fa-universal-access mr-2"></i>
                Accessibility Features
            </h3>

            <div class="accessibility-options space-y-3">
                <label class="accessibility-option flex items-center space-x-3">
                    <input
                        type="checkbox"
                        data-high-contrast-toggle
                        class="w-4 h-4 text-primary-600 bg-bg-surface border-border-primary rounded focus:ring-primary-500 focus:ring-2"
                    >
                    <span class="text-sm">High Contrast Mode</span>
                </label>

                <label class="accessibility-option flex items-center space-x-3">
                    <input
                        type="checkbox"
                        data-colorblind-toggle
                        class="w-4 h-4 text-primary-600 bg-bg-surface border-border-primary rounded focus:ring-primary-500 focus:ring-2"
                    >
                    <span class="text-sm">Color-blind Friendly Mode</span>
                </label>

                <label class="accessibility-option flex items-center space-x-3">
                    <input
                        type="checkbox"
                        data-reduce-motion-toggle
                        class="w-4 h-4 text-primary-600 bg-bg-surface border-border-primary rounded focus:ring-primary-500 focus:ring-2"
                    >
                    <span class="text-sm">Reduce Motion</span>
                </label>
            </div>
        </div>
    </div>
</div>

{{-- Color Demonstration Cards --}}
<div class="color-demo-cards mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    {{-- Success Demo --}}
    <div class="demo-card bg-surface border border-success-200 rounded-lg p-6">
        <div class="demo-header flex items-center mb-4">
            <div class="status-dot w-3 h-3 rounded-full bg-success-500 mr-3"></div>
            <h4 class="font-semibold text-success-700">Server Online</h4>
        </div>
        <div class="demo-content text-sm text-success-600">
            All systems operational. Connection established successfully.
        </div>
        <div class="demo-action mt-4">
            <button class="btn btn-success text-sm px-3 py-1 rounded">
                Connect
            </button>
        </div>
    </div>

    {{-- Warning Demo --}}
    <div class="demo-card bg-surface border border-warning-200 rounded-lg p-6">
        <div class="demo-header flex items-center mb-4">
            <div class="status-dot w-3 h-3 rounded-full bg-warning-500 mr-3"></div>
            <h4 class="font-semibold text-warning-700">Limited Bandwidth</h4>
        </div>
        <div class="demo-content text-sm text-warning-600">
            Server experiencing high load. Performance may be affected.
        </div>
        <div class="demo-action mt-4">
            <button class="btn btn-warning text-sm px-3 py-1 rounded">
                Monitor
            </button>
        </div>
    </div>

    {{-- Error Demo --}}
    <div class="demo-card bg-surface border border-error-200 rounded-lg p-6">
        <div class="demo-header flex items-center mb-4">
            <div class="status-dot w-3 h-3 rounded-full bg-error-500 mr-3"></div>
            <h4 class="font-semibold text-error-700">Connection Failed</h4>
        </div>
        <div class="demo-content text-sm text-error-600">
            Unable to establish connection. Please check your configuration.
        </div>
        <div class="demo-action mt-4">
            <button class="btn btn-error text-sm px-3 py-1 rounded">
                Retry
            </button>
        </div>
    </div>
</div>

<style>
/* Component-specific styles */
.color-theme-settings {
    @apply bg-surface rounded-lg shadow-sm border border-border-primary p-6;
}

.theme-option:hover {
    @apply transform scale-105;
}

.btn {
    @apply font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2;
}

.btn-success {
    @apply bg-success-500 text-white hover:bg-success-600 focus:ring-success-500;
}

.btn-warning {
    @apply bg-warning-500 text-white hover:bg-warning-600 focus:ring-warning-500;
}

.btn-error {
    @apply bg-error-500 text-white hover:bg-error-600 focus:ring-error-500;
}

.demo-card {
    @apply transition-all duration-200 hover:shadow-md;
}

.keyboard-navigation .focus-visible {
    @apply ring-2 ring-primary-500 ring-offset-2;
}
</style>
