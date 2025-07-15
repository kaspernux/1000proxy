{{-- Advanced Dropdown Component Template --}}
@php
    $id = $id ?? 'dropdown-' . uniqid();
    $size = $size ?? 'md';
    $variant = $variant ?? 'default';
    $placeholder = $placeholder ?? 'Select an option...';
    $searchPlaceholder = $searchPlaceholder ?? 'Search options...';
    $multiple = $multiple ?? false;
    $searchable = $searchable ?? true;
    $clearable = $clearable ?? true;
    $options = $options ?? [];
    $selected = $selected ?? [];
    $disabled = $disabled ?? false;
    $error = $error ?? null;
    $megaDropdown = $megaDropdown ?? false;
@endphp

<div
    x-data="dropdown()"
    x-init="
        options = {{ json_encode($options) }};
        selectedItems = {{ json_encode(is_array($selected) ? $selected : [$selected]) }};
        multiSelect = {{ $multiple ? 'true' : 'false' }};
        placeholder = '{{ $placeholder }}';
        searchPlaceholder = '{{ $searchPlaceholder }}';
        enableSearch = {{ $searchable ? 'true' : 'false' }};
        enableClearAll = {{ $clearable ? 'true' : 'false' }};
    "
    class="relative {{ $attributes->get('class', '') }}"
    {{ $attributes->except(['class']) }}
>
    {{-- Trigger Button --}}
    <button
        type="button"
        @click="toggle()"
        @keydown.space.prevent="toggle()"
        :aria-expanded="isOpen"
        :aria-label="getAriaLabel()"
        :disabled="{{ $disabled ? 'true' : 'false' }}"
        class="
            relative w-full cursor-pointer rounded-lg border border-gray-300 bg-white pl-3 pr-10 text-left shadow-sm
            focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500
            disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500
            {{ $size === 'sm' ? 'py-1.5 text-sm' : ($size === 'lg' ? 'py-3 text-lg' : 'py-2 text-base') }}
        "
    >
        <span class="block truncate" x-text="getDisplayText()"></span>

        {{-- Clear Button --}}
        <button
            v-show="enableClearAll && selectedItems.length > 0"
            @click.stop="clearAll()"
            type="button"
            class="absolute inset-y-0 right-8 flex items-center pr-2 hover:text-red-500"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        {{-- Dropdown Arrow --}}
        <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
            <svg
                class="h-5 w-5 text-gray-400 transition-transform duration-200"
                :class="{ 'rotate-180': isOpen }"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </span>
    </button>

    {{-- Error Message --}}
    @if($error)
        <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
    @endif

    {{-- Dropdown Menu --}}
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        @click.outside="close()"
        class="
            absolute z-50 mt-1 overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5
            focus:outline-none sm:text-sm
            {{ $megaDropdown ? 'max-h-96 w-96' : 'max-h-60 w-full' }}
        "
        style="max-height: 300px"
    >
        {{-- Search Input --}}
        <div x-show="enableSearch" class="sticky top-0 bg-white border-b border-gray-200 p-2">
            <input
                x-ref="searchInput"
                x-model="searchQuery"
                type="text"
                :placeholder="searchPlaceholder"
                class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            />
        </div>

        {{-- Options List --}}
        <div x-ref="optionsList" class="py-1">
            @if($megaDropdown)
                {{-- Mega Dropdown Layout --}}
                <div class="grid grid-cols-3 gap-4 p-4">
                    <template x-for="(column, columnIndex) in getMegaColumns()" :key="columnIndex">
                        <div>
                            <template x-for="(option, index) in column" :key="option.value">
                                <div
                                    @click="selectOption(option, index)"
                                    @mouseenter="hoverDate = option.value"
                                    :class="{
                                        'bg-blue-100 text-blue-900': highlightedIndex === index,
                                        'bg-blue-600 text-white': isSelected(option),
                                        'text-gray-900': !isSelected(option)
                                    }"
                                    class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100"
                                >
                                    <div class="flex items-center">
                                        <span x-text="option.label" class="font-normal block truncate"></span>
                                        <span x-show="option.description" x-text="option.description" class="text-gray-500 ml-2 block text-sm"></span>
                                    </div>

                                    {{-- Selection Indicator --}}
                                    <span x-show="isSelected(option)" class="absolute inset-y-0 right-0 flex items-center pr-4 text-blue-600">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            @else
                {{-- Standard Dropdown Layout --}}
                <template x-for="(option, index) in filteredOptions" :key="option.value">
                    <div
                        @click="selectOption(option, index)"
                        @mouseenter="highlightedIndex = index"
                        :class="{
                            'bg-blue-100 text-blue-900': highlightedIndex === index,
                            'bg-blue-600 text-white': isSelected(option),
                            'text-gray-900': !isSelected(option)
                        }"
                        class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100"
                    >
                        <div class="flex items-center">
                            {{-- Option Icon --}}
                            <span x-show="option.icon" class="mr-3 flex-shrink-0">
                                <img x-show="option.icon" :src="option.icon" :alt="option.label" class="h-5 w-5 rounded-full">
                            </span>

                            <div class="flex-1">
                                <span x-text="option.label" class="font-normal block truncate"></span>
                                <span x-show="option.description" x-text="option.description" class="text-gray-500 block text-sm truncate"></span>
                            </div>
                        </div>

                        {{-- Selection Indicator --}}
                        <span x-show="isSelected(option)" class="absolute inset-y-0 right-0 flex items-center pr-4">
                            <svg class="h-5 w-5" :class="isSelected(option) ? 'text-white' : 'text-blue-600'" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </div>
                </template>

                {{-- No Results --}}
                <div x-show="filteredOptions.length === 0" class="py-2 px-3 text-gray-500">
                    No options found
                </div>
            @endif
        </div>
    </div>

    {{-- Multi-select Display --}}
    <div x-show="multiSelect && selectedItems.length > 0" class="mt-2 flex flex-wrap gap-1">
        <template x-for="(item, index) in selectedItems" :key="item.value">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                <span x-text="item.label"></span>
                <button
                    @click="removeSelected(index)"
                    type="button"
                    class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full hover:bg-blue-200 focus:outline-none focus:bg-blue-200"
                >
                    <svg class="w-2 h-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </span>
        </template>
    </div>
</div>
