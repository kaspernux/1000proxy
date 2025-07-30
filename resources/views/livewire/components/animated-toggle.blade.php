<div x-data="{ checked: @entangle('checked').defer }" class="flex flex-col items-center justify-center w-full py-6">
    <label for="animatedToggle" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Enable Feature</label>
    <button
        id="animatedToggle"
        type="button"
        @click="checked = !checked; $wire.set('checked', checked)"
        :aria-pressed="checked"
        class="relative inline-flex h-8 w-16 items-center rounded-full transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 bg-gray-200 dark:bg-gray-700"
        :class="checked ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700'"
        aria-label="Toggle feature"
    >
        <span class="sr-only">Toggle feature</span>
        <span
            class="inline-block h-6 w-6 transform rounded-full bg-white shadow transition-transform duration-300"
            :class="checked ? 'translate-x-8' : 'translate-x-1'"
        ></span>
        <span class="absolute left-2 text-xs font-semibold text-gray-500 dark:text-gray-400 select-none" x-show="!checked">Off</span>
        <span class="absolute right-2 text-xs font-semibold text-blue-100 dark:text-blue-200 select-none" x-show="checked">On</span>
    </button>
    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">This toggle animates smoothly and is fully accessible.</p>
</div>
