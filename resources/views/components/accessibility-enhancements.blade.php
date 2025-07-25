{{-- Accessibility Enhancement Component --}}
@props([
    'enableAnnouncements' => true,
    'enableKeyboard' => true,
    'enableHighContrast' => 'auto',
    'enableReducedMotion' => 'auto',
    'showAccessibilityPanel' => true,
    'touchMinSize' => 44,
    'class' => ''
])

{{-- Initialize accessibility manager --}}
<div
    x-data="accessibilityManager({
        announceChanges: {{ $enableAnnouncements ? 'true' : 'false' }},
        keyboardNavigation: {{ $enableKeyboard ? 'true' : 'false' }},
        colorContrastMode: '{{ $enableHighContrast }}',
        touchTarget: {{ $touchMinSize }}
    })"
    x-init="init(); window.accessibilityManagerInstance = $data"
    class="accessibility-enhancements {{ $class }}"
    x-cloak
>
    {{-- Skip Navigation Links --}}
    <nav class="skip-links" aria-label="Skip navigation" x-cloak>
        <a href="#main-content" class="skip-link">Skip to main content</a>
        <a href="#main-navigation" class="skip-link">Skip to navigation</a>
        <a href="#search" class="skip-link">Skip to search</a>
        @if($showAccessibilityPanel)
            <button type="button" @click="toggleAccessibilityPanel()" class="skip-link">
                Accessibility settings
            </button>
        @endif
    </nav>

    {{-- ARIA Live Regions (created dynamically by JS) --}}

    @if($showAccessibilityPanel)
        {{-- Accessibility Control Panel --}}
        <div
            x-show="false"
            x-ref="accessibilityPanel"
            id="accessibility-panel"
            class="accessibility-panel fixed top-4 right-4 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-xl p-6 w-80 z-50"
            role="dialog"
            aria-labelledby="accessibility-panel-title"
            aria-modal="true"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @keydown.escape="toggleAccessibilityPanel()"
            style="display: none;"
        >
            {{-- Panel Header --}}
            <div class="flex items-center justify-between mb-6">
                <h2 id="accessibility-panel-title" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                    Accessibility Settings
                </h2>
                <button
                    type="button"
                    @click="toggleAccessibilityPanel()"
                    aria-label="Close accessibility panel"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 p-1 rounded"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>

            {{-- Accessibility Controls --}}
            <div class="space-y-6">
                {{-- Visual Settings --}}
                <div>
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Visual Settings</h3>
                    <div class="space-y-3">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input
                                type="checkbox"
                                x-model="colorContrastMode"
                                @change="toggleHighContrast($event.target.checked)"
                                class="form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            >
                            <span class="text-sm text-gray-700 dark:text-gray-300">High Contrast Mode</span>
                        </label>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input
                                type="checkbox"
                                x-model="reducedMotion"
                                @change="toggleReducedMotion($event.target.checked)"
                                class="form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            >
                            <span class="text-sm text-gray-700 dark:text-gray-300">Reduce Motion</span>
                        </label>
                    </div>
                </div>

                {{-- Navigation Settings --}}
                <div>
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Navigation Settings</h3>
                    <div class="space-y-3">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input
                                type="checkbox"
                                x-model="keyboardNavigation"
                                class="form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            >
                            <span class="text-sm text-gray-700 dark:text-gray-300">Enhanced Keyboard Navigation</span>
                        </label>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input
                                type="checkbox"
                                x-model="announceChanges"
                                class="form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            >
                            <span class="text-sm text-gray-700 dark:text-gray-300">Screen Reader Announcements</span>
                        </label>
                    </div>
                </div>

                {{-- Status Information --}}
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Current Status</h3>
                    <div class="space-y-1 text-xs text-gray-600 dark:text-gray-400">
                        <div class="flex justify-between">
                            <span>Screen Reader:</span>
                            <span x-text="screenReaderActive ? 'Detected' : 'Not detected'" :class="screenReaderActive ? 'text-green-600' : 'text-gray-500'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Touch Device:</span>
                            <span x-text="window.matchMedia && window.matchMedia('(pointer: coarse)').matches ? 'Yes' : 'No'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Focus Trap:</span>
                            <span x-text="trapFocus ? 'Active' : 'Inactive'" :class="trapFocus ? 'text-yellow-600' : 'text-gray-500'"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Keyboard Shortcuts Reference --}}
            <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-600">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Keyboard Shortcuts</h3>
                <div class="grid grid-cols-1 gap-2 text-xs text-gray-600 dark:text-gray-400">
                    <div class="flex justify-between">
                        <span class="font-mono">Alt + S</span>
                        <span>Skip to main content</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-mono">Alt + N</span>
                        <span>Skip to navigation</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-mono">Alt + A</span>
                        <span>Toggle this panel</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-mono">Alt + H</span>
                        <span>Announce location</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-mono">Tab</span>
                        <span>Navigate forward</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-mono">Shift + Tab</span>
                        <span>Navigate backward</span>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-600">
                <div class="flex space-x-2">
                    <button
                        @click="skipToMain()"
                        class="flex-1 px-3 py-2 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        Skip to Main
                    </button>
                    <button
                        @click="announceCurrentLocation()"
                        class="flex-1 px-3 py-2 text-xs bg-gray-600 text-white rounded hover:bg-gray-700 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                    >
                        Where Am I?
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Accessibility Floating Action Button --}}
    @if($showAccessibilityPanel)
        <button
            @click="toggleAccessibilityPanel()"
            class="accessibility-fab fixed bottom-4 right-4 bg-blue-600 hover:bg-blue-700 text-white rounded-full p-3 shadow-lg z-40 transition-all duration-200 hover:shadow-xl focus:ring-4 focus:ring-blue-500 focus:ring-offset-2"
            aria-label="Open accessibility settings"
            title="Accessibility Settings (Alt + A)"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
            </svg>
        </button>
    @endif
</div>

{{-- Enhanced Accessibility Styles --}}
<style>
    /* Skip Links */
    .skip-links {
        position: fixed;
        top: 0;
        left: 0;
        z-index: 9999;
        pointer-events: none;
    }

    .skip-link {
        position: absolute;
        top: -40px;
        left: 6px;
        background: #000;
        color: #fff;
        padding: 8px 16px;
        text-decoration: none;
        font-weight: bold;
        border-radius: 0 0 4px 4px;
        transition: top 0.3s ease;
        pointer-events: auto;
    }

    .skip-link:focus {
        top: 0;
    }

    /* Enhanced focus indicators for keyboard users */
    body.using-keyboard *:focus:not(.no-focus-ring) {
        outline: 3px solid #4f46e5 !important;
        outline-offset: 2px !important;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.3) !important;
    }

    /* High contrast mode */
    .high-contrast {
        filter: contrast(150%) brightness(1.2);
    }

    .high-contrast * {
        border-color: #000 !important;
        text-shadow: none !important;
        box-shadow: none !important;
    }

    .high-contrast a {
        color: #0000ff !important;
        text-decoration: underline !important;
    }

    .high-contrast button, .high-contrast input, .high-contrast select, .high-contrast textarea {
        border: 2px solid #000 !important;
        background: #fff !important;
        color: #000 !important;
    }

    /* Reduced motion */
    .reduce-motion *,
    .reduce-motion *::before,
    .reduce-motion *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }

    /* Screen reader only content */
    .sr-only {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
        white-space: nowrap !important;
        border: 0 !important;
    }

    /* Screen reader enhanced content */
    .screen-reader-active .sr-enhanced {
        position: static !important;
        width: auto !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0.25rem !important;
        overflow: visible !important;
        clip: auto !important;
        white-space: normal !important;
        background: #f3f4f6 !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.25rem !important;
    }

    /* Touch accessibility */
    @media (pointer: coarse) {
        button, a, input, select, textarea, [tabindex]:not([tabindex="-1"]), [role="button"] {
            min-height: 44px !important;
            min-width: 44px !important;
        }

        .accessibility-fab {
            width: 56px !important;
            height: 56px !important;
        }
    }

    /* Touch feedback */
    .touch-active {
        background-color: rgba(0, 0, 0, 0.1) !important;
        transform: scale(0.95) !important;
    }

    /* Accessibility panel animations */
    .accessibility-panel {
        backdrop-filter: blur(8px);
        border: 2px solid rgba(59, 130, 246, 0.2);
    }

    .accessibility-fab {
        backdrop-filter: blur(8px);
    }

    .accessibility-fab:hover {
        transform: scale(1.05);
    }

    /* Print accessibility */
    @media print {
        .skip-links,
        .accessibility-panel,
        .accessibility-fab,
        .no-print {
            display: none !important;
        }

        * {
            background: white !important;
            color: black !important;
            box-shadow: none !important;
        }

        a {
            color: black !important;
            text-decoration: underline !important;
        }
    }

    /* Forced colors mode (Windows High Contrast) */
    @media (forced-colors: active) {
        .accessibility-panel {
            border: 1px solid ButtonText;
            background: ButtonFace;
            color: ButtonText;
        }

        .accessibility-fab {
            border: 1px solid ButtonText;
            background: ButtonFace;
            color: ButtonText;
        }

        .skip-link {
            border: 1px solid ButtonText;
            background: ButtonFace;
            color: ButtonText;
        }
    }

    /* Prefers reduced transparency */
    @media (prefers-reduced-transparency: reduce) {
        .accessibility-panel,
        .accessibility-fab {
            backdrop-filter: none;
        }
    }
</style>

{{-- Accessibility Enhancement JavaScript --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add enhanced keyboard navigation class
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            document.body.classList.add('using-keyboard');
        }
    });

    document.addEventListener('mousedown', function() {
        document.body.classList.remove('using-keyboard');
    });

    // Add role attributes to common elements
    const nav = document.querySelector('nav:not([role])');
    if (nav) nav.setAttribute('role', 'navigation');

    const main = document.querySelector('main:not([role])');
    if (main) main.setAttribute('role', 'main');

    const aside = document.querySelector('aside:not([role])');
    if (aside) aside.setAttribute('role', 'complementary');

    // Add landmark roles
    document.querySelectorAll('header:not([role])').forEach(el => {
        el.setAttribute('role', 'banner');
    });

    document.querySelectorAll('footer:not([role])').forEach(el => {
        el.setAttribute('role', 'contentinfo');
    });

    // Enhance form accessibility
    document.querySelectorAll('input, select, textarea').forEach(input => {
        const label = document.querySelector(`label[for="${input.id}"]`);
        if (!label && !input.getAttribute('aria-label') && !input.getAttribute('aria-labelledby')) {
            console.warn('Form element without proper label:', input);
        }
    });

    // Add alt text warnings for images
    document.querySelectorAll('img:not([alt])').forEach(img => {
        console.warn('Image without alt text:', img);
        img.setAttribute('alt', 'Image description needed');
    });

    console.log('✅ Accessibility enhancements applied');
});
</script>
