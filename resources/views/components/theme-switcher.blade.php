{{-- Enhanced Theme Switcher Component --}}
@props([
    'position' => 'relative', // 'relative', 'fixed', 'absolute'
    'variant' => 'dropdown', // 'dropdown', 'toggle', 'tabs'
    'size' => 'md', // 'sm', 'md', 'lg'
    'showLabel' => true,
    'showIcons' => true,
    'showTooltips' => true,
    'compact' => false,
    'class' => ''
])

@php
$sizeClasses = [
    'sm' => 'text-sm',
    'md' => 'text-base',
    'lg' => 'text-lg'
];

$buttonSizeClasses = [
    'sm' => 'p-1.5',
    'md' => 'p-2',
    'lg' => 'p-3'
];

$iconSizeClasses = [
    'sm' => 'w-4 h-4',
    'md' => 'w-5 h-5',
    'lg' => 'w-6 h-6'
];
@endphp

<div
    x-data="themeSwitcher()"
    x-init="init()"
    class="{{ $position }} {{ $sizeClasses[$size] }} {{ $class }}"
    x-cloak
>
    @if($variant === 'dropdown')
        {{-- Dropdown Variant --}}
        <div class="relative">
            {{-- Theme Toggle Button --}}
            <button
                @click="showOptions = !showOptions"
                @keydown.escape="showOptions = false"
                :class="{
                    'ring-2 ring-blue-500 ring-offset-2 dark:ring-offset-gray-800': showOptions,
                    'opacity-50': isTransitioning
                }"
                class="{{ $buttonSizeClasses[$size] }} bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                :disabled="isTransitioning"
                aria-label="Toggle theme"
                :title="showTooltips ? `Current theme: ${themes[currentTheme].name}` : null"
            >
                <div class="flex items-center space-x-2">
                    @if($showIcons)
                        <span
                            class="text-lg transition-transform duration-200"
                            :class="{ 'rotate-12': isTransitioning }"
                            x-text="themes[currentTheme].icon"
                        ></span>
                    @endif

                    @if($showLabel && !$compact)
                        <span x-text="themes[currentTheme].name" class="font-medium"></span>
                    @endif

                    {{-- Dropdown Arrow --}}
                    <svg
                        class="{{ $iconSizeClasses[$size] }} transition-transform duration-200"
                        :class="{ 'rotate-180': showOptions }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </button>

            {{-- Dropdown Menu --}}
            <div
                x-show="showOptions"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.away="showOptions = false"
                class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50"
                style="display: none;"
            >
                {{-- Menu Header --}}
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Theme Settings</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Choose how the interface looks</p>
                </div>

                {{-- Theme Options --}}
                <div class="py-2">
                    <template x-for="(theme, key) in themes" :key="key">
                        <button
                            @click="setTheme(key); showOptions = false"
                            :class="{
                                'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300': currentTheme === key,
                                'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700': currentTheme !== key
                            }"
                            class="w-full px-4 py-3 text-left transition-colors duration-150 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700"
                        >
                            <div class="flex items-center space-x-3">
                                @if($showIcons)
                                    <span class="text-lg" x-text="theme.icon"></span>
                                @endif

                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium" x-text="theme.name"></span>

                                        {{-- Current Theme Indicator --}}
                                        <svg
                                            x-show="currentTheme === key"
                                            class="w-4 h-4 text-blue-600 dark:text-blue-400"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>

                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-text="theme.description"></p>
                                </div>
                            </div>
                        </button>
                    </template>
                </div>

                {{-- System Theme Info --}}
                <div x-show="currentTheme === 'system'" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-xs text-gray-600 dark:text-gray-300">
                            Currently using: <span class="font-medium" x-text="actualTheme"></span> theme
                        </span>
                    </div>
                </div>
            </div>
        </div>

    @elseif($variant === 'toggle')
        {{-- Toggle Switch Variant --}}
        <div class="flex items-center space-x-3">
            @if($showLabel)
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Theme</span>
            @endif

            <div class="relative">
                <button
                    @click="toggleTheme()"
                    :class="{ 'opacity-50': isTransitioning }"
                    class="{{ $buttonSizeClasses[$size] }} bg-gray-200 dark:bg-gray-600 rounded-full transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                    :disabled="isTransitioning"
                    :title="showTooltips ? `Switch to ${actualTheme === 'light' ? 'dark' : 'light'} theme` : null"
                >
                    <div class="flex items-center justify-center">
                        <span
                            class="text-lg transition-transform duration-200"
                            :class="{ 'rotate-12': isTransitioning }"
                            x-text="themes[actualTheme].icon"
                        ></span>
                    </div>
                </button>
            </div>
        </div>

    @elseif($variant === 'tabs')
        {{-- Tab Variant --}}
        <div class="inline-flex bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
            <template x-for="(theme, key) in themes" :key="key">
                <button
                    @click="setTheme(key)"
                    :class="{
                        'bg-white dark:bg-gray-600 shadow-sm text-gray-900 dark:text-gray-100': currentTheme === key,
                        'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200': currentTheme !== key
                    }"
                    class="px-3 py-2 text-sm font-medium rounded-md transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                    :title="showTooltips ? theme.description : null"
                >
                    <div class="flex items-center space-x-2">
                        @if($showIcons)
                            <span x-text="theme.icon"></span>
                        @endif
                        @if($showLabel && !$compact)
                            <span x-text="theme.name"></span>
                        @endif
                    </div>
                </button>
            </template>
        </div>
    @endif

    {{-- Theme Transition Overlay --}}
    <div
        x-show="isTransitioning"
        x-transition:enter="transition-opacity ease-in duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-out duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
        style="display: none;"
    >
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-xl">
            <div class="flex items-center space-x-3">
                <svg class="animate-spin w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-gray-700 dark:text-gray-200">Switching theme...</span>
            </div>
        </div>
    </div>
</div>

{{-- Enhanced CSS for smooth theme transitions --}}
<style>
/* Theme transition effects */
.theme-transition {
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}

/* Smooth transitions for all themed elements */
* {
    transition-property: color, background-color, border-color;
    transition-duration: 0.3s;
    transition-timing-function: ease;
}

/* Prevent transition on initial load */
.no-transition * {
    transition: none !important;
}

/* Theme-aware scrollbar */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    @apply bg-gray-100 dark:bg-gray-800;
}

::-webkit-scrollbar-thumb {
    @apply bg-gray-300 dark:bg-gray-600 rounded;
}

::-webkit-scrollbar-thumb:hover {
    @apply bg-gray-400 dark:bg-gray-500;
}

/* Theme-specific animations */
@media (prefers-reduced-motion: no-preference) {
    .theme-switch-animation {
        animation: themeSwitch 0.6s ease-in-out;
    }

    @keyframes themeSwitch {
        0% { opacity: 1; }
        50% { opacity: 0.7; transform: scale(0.98); }
        100% { opacity: 1; transform: scale(1); }
    }
}

/* System preference indicators */
.system-theme-indicator {
    position: relative;
}

.system-theme-indicator::after {
    content: '';
    position: absolute;
    top: -2px;
    right: -2px;
    width: 8px;
    height: 8px;
    background: linear-gradient(45deg, #3b82f6, #1d4ed8);
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Focus indicators for accessibility */
.theme-switcher-focus {
    @apply focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800;
}

/* Dark mode specific enhancements */
@media (prefers-color-scheme: dark) {
    .auto-dark-enhancement {
        @apply text-gray-100 bg-gray-800;
    }
}
</style>

{{-- JavaScript enhancements for better UX --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add keyboard shortcuts for theme switching
    document.addEventListener('keydown', function(event) {
        // Ctrl/Cmd + Shift + T to toggle theme
        if ((event.ctrlKey || event.metaKey) && event.shiftKey && event.key === 'T') {
            event.preventDefault();
            // Trigger theme toggle if component is available
            if (window.Alpine && window.Alpine.store('theme')) {
                window.Alpine.store('theme').toggle();
            }
        }
    });

    // Add system theme change listener
    if (window.matchMedia) {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addEventListener('change', function(e) {
            // Notify all theme components about system change
            window.dispatchEvent(new CustomEvent('system-theme-changed', {
                detail: { isDark: e.matches }
            }));
        });
    }

    // Add smooth transition class to body
    document.body.classList.add('theme-transition');

    // Prevent flash of wrong theme
    const savedTheme = localStorage.getItem('theme-preference');
    if (savedTheme) {
        document.documentElement.classList.add(savedTheme === 'dark' ? 'dark' : 'light');
    }
});

// Global theme utilities
window.themeUtils = {
    // Get current theme
    getCurrentTheme() {
        return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    },

    // Apply theme with animation
    applyThemeWithAnimation(theme) {
        document.body.classList.add('theme-switch-animation');

        setTimeout(() => {
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
            } else {
                document.documentElement.classList.add('light');
                document.documentElement.classList.remove('dark');
            }
        }, 150);

        setTimeout(() => {
            document.body.classList.remove('theme-switch-animation');
        }, 600);
    },

    // Check if user prefers reduced motion
    prefersReducedMotion() {
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }
};
</script>
