<div>
    <style>[x-cloak]{display:none !important;}</style>
    <div
        class="fixed z-50 bottom-4 right-4 md:bottom-6 md:right-6"
        x-data="{ open: false }"
        x-effect="if (open) { setTimeout(() => window.dispatchEvent(new CustomEvent('chat-opened')), 150) }"
        x-on:keydown.escape.window="open = false"
        @open-chat.window="open = true"
    >
    <!-- Icon-only launcher (hidden while open) -->
    <button
        type="button"
        x-show="!open"
        x-transition.scale
        @click="open = true"
        :aria-expanded="open.toString()"
        aria-controls="chat-widget-panel"
        aria-label="Open support chat"
        x-cloak
        class="group relative inline-flex items-center justify-center h-12 w-12 rounded-full text-white shadow-lg shadow-primary-900/20 ring-1 ring-black/5 bg-gradient-to-r from-primary-600 to-primary-500 hover:from-primary-500 hover:to-primary-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-300"
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-7">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.556 0 8.25-3.694 8.25-8.25S16.556 3.75 12 3.75 3.75 7.444 3.75 12c0 1.56.431 3.02 1.187 4.27.23.386.327.843.266 1.292l-.41 3.074 3.074-.41c.45-.06.906.036 1.292.266A8.23 8.23 0 0012 20.25zM8.25 12h.008v.008H8.25V12zm3 0h.008v.008H11.25V12zm3 0h.008v.008H14.25V12z" />
        </svg>
        <!-- Unread badge placeholder (optional) -->
        <span class="absolute -top-1 -right-1">
            <span class="sr-only">Unread messages</span>
            <span class="hidden items-center justify-center text-[10px] font-semibold h-5 min-w-5 px-1 rounded-full bg-rose-500 text-white shadow-md" x-ref="badge">0</span>
        </span>
    </button>

    <!-- Backdrop -->
    <div x-show="open" x-transition.opacity class="fixed inset-0 z-40" aria-hidden="true" x-cloak>
        <div class="absolute inset-0 bg-black/40" @click="open = false"></div>
    </div>

    <!-- Panel -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
    id="chat-widget-panel"
    x-cloak
    class="mt-3 w-[420px] max-w-[95vw] h-[560px] max-h-[60vh] bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl overflow-hidden relative z-50"
    >
        <!-- Header -->
        <div class="flex items-center justify-between px-3 py-2 border-b border-gray-200 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/40">
            <div class="flex items-center gap-2">
                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">Live Support</span>
            </div>
            <button @click="open = false" aria-label="Close chat" class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5 text-gray-600 dark:text-gray-300">
                    <path fill-rule="evenodd" d="M6.225 4.811a.75.75 0 011.06 0L12 9.525l4.715-4.714a.75.75 0 111.06 1.06L13.06 10.585l4.715 4.715a.75.75 0 11-1.06 1.06L12 11.646l-4.715 4.714a.75.75 0 01-1.06-1.06l4.714-4.715-4.714-4.714a.75.75 0 010-1.06z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
        <!-- Body: make inner scrollable within fixed panel -->
        <div class="h-[calc(100%-40px)] min-h-0 overflow-hidden flex flex-col">
            <div class="flex-1 min-h-0 overflow-hidden">
                <livewire:chat.chat-panel :minimal="true" />
            </div>
        </div>
    </div>
</div>
