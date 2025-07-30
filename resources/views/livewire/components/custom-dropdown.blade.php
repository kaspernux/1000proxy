<div x-data="{ open: false, selected: @entangle('selectedOption').defer }" class="relative w-full max-w-xs mx-auto">
    <label for="customDropdown" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Select an option</label>
    <button
        @click="open = !open"
        type="button"
        id="customDropdown"
        aria-haspopup="listbox"
        :aria-expanded="open"
        class="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg shadow-sm pl-4 pr-10 py-2 text-left cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
    >
        <span x-text="selected ? options.find(o => o.value === selected)?.label : 'Choose...'" class="block truncate"></span>
        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </span>
    </button>
    <ul
        x-show="open"
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-10 mt-2 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-60 overflow-auto focus:outline-none"
        role="listbox"
        aria-labelledby="customDropdown"
    >
        <template x-for="option in options" :key="option.value">
            <li
                @click="selected = option.value; open = false; $wire.set('selectedOption', option.value)"
                :class="{'bg-blue-100 dark:bg-blue-900/30 text-blue-900 dark:text-blue-200': selected === option.value, 'text-gray-900 dark:text-gray-100': selected !== option.value}"
                class="cursor-pointer select-none relative py-2 pl-4 pr-10 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition"
                role="option"
                :aria-selected="selected === option.value"
            >
                <span x-text="option.label" class="block truncate"></span>
                <span x-show="selected === option.value" class="absolute inset-y-0 right-0 flex items-center pr-3 text-blue-600 dark:text-blue-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
            </li>
        </template>
        <li x-show="options.length === 0" class="text-gray-500 dark:text-gray-400 px-4 py-2">No options available</li>
    </ul>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('customDropdown', () => ({
                open: false,
                selected: null,
                options: @json($options ?? []),
            }));
        });
    </script>
</div>
