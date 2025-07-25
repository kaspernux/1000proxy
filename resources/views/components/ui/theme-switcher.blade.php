{{-- Theme Switcher Component with Professional Design --}}
<div x-data="themeSwitcher()" 
     x-init="init()"
     @keydown.window="handleKeydown($event)"
     @click.away="closeOptions()"
     class="theme-switcher relative">
    
    {{-- Main Theme Toggle Button --}}
    <button @click="toggleOptions()"
            :disabled="isTransitioning"
            class="theme-switcher__button group relative flex items-center justify-center w-10 h-10 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
            :class="{ 'animate-pulse': isTransitioning }"
            type="button"
            aria-label="Toggle theme"
            :aria-expanded="showOptions">
        
        {{-- Theme Icon --}}
        <span class="text-lg transition-transform duration-200 group-hover:scale-110" 
              x-text="getCurrentIcon()"></span>
        
        {{-- Loading Spinner --}}
        <div x-show="isTransitioning" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0 flex items-center justify-center">
            <div class="w-4 h-4 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
        </div>
    </button>

    {{-- Theme Options Dropdown --}}
    <div x-show="showOptions"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95"
         class="theme-switcher__dropdown absolute right-0 top-full mt-2 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-2 z-50"
         style="display: none;">
        
        {{-- Dropdown Header --}}
        <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Choose Theme</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Current: <span x-text="getCurrentName()"></span>
            </p>
        </div>

        {{-- Theme Options --}}
        <div class="py-1">
            <template x-for="(theme, key) in themes" :key="key">
                <button @click="switchTheme(key)"
                        :disabled="isTransitioning"
                        class="theme-switcher__option w-full flex items-center px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
                        :class="{ 
                            'bg-blue-50 dark:bg-blue-900/20 border-r-2 border-blue-500': isThemeActive(key),
                            'text-blue-600 dark:text-blue-400': isThemeActive(key),
                            'text-gray-700 dark:text-gray-200': !isThemeActive(key)
                        }"
                        type="button">
                    
                    {{-- Theme Icon --}}
                    <span class="text-lg mr-3" x-text="theme.icon"></span>
                    
                    {{-- Theme Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium" x-text="theme.name"></span>
                            <div x-show="isThemeActive(key)" class="ml-2">
                                <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" 
                           x-text="getThemeDescription(key)"></p>
                    </div>
                </button>
            </template>
        </div>

        {{-- Additional Options --}}
        <div class="border-t border-gray-200 dark:border-gray-700 pt-2">
            <button @click="autoDetectTheme()"
                    class="w-full flex items-center px-4 py-2 text-left text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150"
                    type="button">
                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                Auto-detect theme
            </button>
        </div>

        {{-- Keyboard Shortcut Hint --}}
        <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-400 dark:text-gray-500">
                <kbd class="px-1 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded">Ctrl</kbd>
                +
                <kbd class="px-1 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded">Shift</kbd>
                +
                <kbd class="px-1 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded">T</kbd>
                to toggle
            </p>
        </div>
    </div>

    {{-- Quick Toggle (Alternative Mobile-Friendly Version) --}}
    <div class="theme-switcher__mobile-toggle hidden">
        <button @click="toggleTheme()"
                :disabled="isTransitioning"
                class="flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg hover:shadow-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                :class="{ 'animate-pulse': isTransitioning }"
                type="button"
                aria-label="Toggle theme quickly">
            <span class="text-xl" x-text="getCurrentIcon()"></span>
        </button>
    </div>
</div>

{{-- Theme Transition Styles --}}
<style>
/* Theme switcher specific styles */
.theme-switcher__button {
    position: relative;
    overflow: hidden;
}

.theme-switcher__button::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
    transform: translateX(-100%);
    transition: transform 0.6s ease;
}

.theme-switcher__button:hover::before {
    transform: translateX(100%);
}

.theme-switcher__dropdown {
    min-width: 16rem;
    max-height: 32rem;
    overflow-y: auto;
}

.theme-switcher__option {
    position: relative;
}

.theme-switcher__option:focus {
    outline: 2px solid #3b82f6;
    outline-offset: -2px;
}

/* Mobile responsiveness */
@media (max-width: 640px) {
    .theme-switcher__dropdown {
        right: 0;
        left: auto;
        width: 20rem;
        max-width: calc(100vw - 2rem);
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .theme-switcher__button {
        border-width: 2px;
    }
    
    .theme-switcher__option {
        border-bottom: 1px solid currentColor;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .theme-switcher__button,
    .theme-switcher__option,
    .theme-switcher__dropdown {
        transition: none !important;
    }
    
    .theme-switcher__button::before {
        display: none;
    }
}

/* Print styles */
@media print {
    .theme-switcher {
        display: none !important;
    }
}
</style>

{{-- Additional Theme Meta Tags (should be in head) --}}
@push('head')
<meta name="theme-color" content="#ffffff" id="theme-color-meta">
<meta name="color-scheme" content="light dark">
@endpush

{{-- Theme Analytics (optional) --}}
@push('scripts')
<script>
// Track theme usage for analytics
document.addEventListener('themeChanged', function(event) {
    if (typeof gtag !== 'undefined') {
        gtag('event', 'theme_change', {
            'theme_selected': event.detail.theme,
            'actual_theme': event.detail.actualTheme
        });
    }
});
</script>
@endpush
