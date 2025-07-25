{{-- Interactive Data Table Component --}}
@props([
    'id' => 'data-table-' . uniqid(),
    'dataUrl' => null,
    'data' => [],
    'columns' => [],
    'title' => null,
    'description' => null,
    'searchPlaceholder' => 'Search...',
    'noDataMessage' => 'No data available',
    'loadingMessage' => 'Loading data...',
    'config' => [],
    'bulkActions' => [],
    'exportUrl' => null,
    'saveRowUrl' => null,
    'class' => ''
])

<div
    {{ $attributes->merge(['class' => 'data-table-container ' . $class]) }}
    x-data="interactiveDataTable()"
    x-init="
        dataUrl = '{{ $dataUrl }}';
        data = {{ json_encode($data) }};
        originalData = [...data];
        columns = {{ json_encode($columns) }};
        bulkActions = {{ json_encode($bulkActions) }};
        exportUrl = '{{ $exportUrl }}';
        saveRowUrl = '{{ $saveRowUrl }}';
        config = { ...config, ...{{ json_encode($config) }} };
        init();
    "
    x-ref="dataTable"
>
    {{-- Header Section --}}
    <div class="data-table-header bg-white dark:bg-gray-800 rounded-t-lg border-b border-gray-200 dark:border-gray-700 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
            {{-- Title and Description --}}
            <div class="flex-1 min-w-0">
                @if($title)
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h3>
                @endif
                @if($description)
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $description }}</p>
                @endif
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center space-x-3">
                {{-- Refresh Button --}}
                <button
                    @click="refreshData()"
                    :disabled="loading"
                    class="btn-outline btn-sm flex items-center space-x-2"
                    title="Refresh Data"
                >
                    <svg
                        class="w-4 h-4"
                        :class="{ 'animate-spin': loading }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span class="hidden sm:inline">Refresh</span>
                </button>

                {{-- Column Settings --}}
                <div class="relative" x-data="{ open: false }">
                    <button
                        @click="open = !open"
                        class="btn-outline btn-sm flex items-center space-x-2"
                        title="Column Settings"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                        </svg>
                        <span class="hidden sm:inline">Columns</span>
                    </button>

                    <div
                        x-show="open"
                        @click.away="open = false"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-10"
                    >
                        <div class="p-4">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Visible Columns</h4>
                            <div class="space-y-2 max-h-60 overflow-y-auto">
                                <template x-for="column in columns" :key="column.key">
                                    <label class="flex items-center space-x-2 text-sm">
                                        <input
                                            type="checkbox"
                                            :checked="isColumnVisible(column.key)"
                                            @change="toggleColumnVisibility(column.key)"
                                            class="checkbox checkbox-sm"
                                        >
                                        <span class="text-gray-700 dark:text-gray-300" x-text="column.title || column.key"></span>
                                    </label>
                                </template>
                            </div>
                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                                <button
                                    @click="resetColumns(); open = false"
                                    class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                >
                                    Reset to Default
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Export Button --}}
                <div class="relative" x-data="{ open: false }" x-show="exportFormats.length > 0">
                    <button
                        @click="open = !open"
                        :disabled="exportLoading"
                        class="btn-outline btn-sm flex items-center space-x-2"
                        title="Export Data"
                    >
                        <svg
                            class="w-4 h-4"
                            :class="{ 'animate-spin': exportLoading }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="hidden sm:inline">Export</span>
                    </button>

                    <div
                        x-show="open"
                        @click.away="open = false"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-10"
                    >
                        <div class="p-2">
                            <template x-for="format in exportFormats" :key="format">
                                <button
                                    @click="exportData(format); open = false"
                                    class="block w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded capitalize"
                                    x-text="format.toUpperCase()"
                                ></button>
                            </template>
                            <hr class="my-2 border-gray-200 dark:border-gray-600" x-show="selectedRows.size > 0">
                            <template x-for="format in exportFormats" :key="'selected-' + format" x-show="selectedRows.size > 0">
                                <button
                                    @click="exportData(format, true); open = false"
                                    class="block w-full text-left px-3 py-2 text-sm text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded"
                                    x-text="'Selected as ' + format.toUpperCase()"
                                ></button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Search and Filters Row --}}
        <div class="mt-4 flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-3 lg:space-y-0 lg:space-x-4">
            {{-- Global Search --}}
            <div class="flex-1 max-w-md">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input
                        type="text"
                        x-model="globalSearch"
                        @input.debounce.300ms="updateGlobalSearch()"
                        x-ref="globalSearch"
                        placeholder="{{ $searchPlaceholder }}"
                        class="input-field pl-10"
                    >
                    <button
                        x-show="globalSearch"
                        @click="globalSearch = ''; updateGlobalSearch()"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center"
                    >
                        <svg class="h-4 w-4 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Active Filters --}}
            <div class="flex items-center space-x-2" x-show="Object.keys(activeFilters).length > 0 || globalSearch">
                <span class="text-sm text-gray-600 dark:text-gray-400">Filters:</span>
                <div class="flex flex-wrap gap-2">
                    <template x-for="[key, value] in Object.entries(activeFilters)" :key="key">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            <span x-text="key + ': ' + value"></span>
                            <button @click="updateFilter(key, null)" class="ml-1 text-blue-600 hover:text-blue-800">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </span>
                    </template>
                    <span x-show="globalSearch" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        <span x-text="'Search: ' + globalSearch"></span>
                        <button @click="globalSearch = ''; updateGlobalSearch()" class="ml-1 text-green-600 hover:text-green-800">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </span>
                </div>
                <button
                    @click="clearFilters()"
                    class="text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                >
                    Clear All
                </button>
            </div>
        </div>
    </div>

    {{-- Bulk Actions Bar --}}
    <div
        x-show="showBulkActions"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200 dark:border-blue-700 px-6 py-3"
    >
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <span class="text-sm font-medium text-blue-900 dark:text-blue-100">
                    <span x-text="selectedRows.size"></span> items selected
                </span>
                <button
                    @click="clearSelection()"
                    class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                >
                    Clear selection
                </button>
            </div>
            <div class="flex items-center space-x-2">
                <template x-for="action in bulkActions" :key="action.id">
                    <button
                        @click="executeBulkAction(action)"
                        :disabled="bulkActionLoading"
                        class="btn-sm"
                        :class="action.class || 'btn-outline'"
                        x-text="action.label"
                    ></button>
                </template>
            </div>
        </div>
    </div>

    {{-- Table Container --}}
    <div class="relative">
        {{-- Loading Overlay --}}
        <div
            x-show="loading"
            class="absolute inset-0 bg-white/75 dark:bg-gray-800/75 flex items-center justify-center z-10"
        >
            <div class="flex items-center space-x-3">
                <svg class="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-gray-600 dark:text-gray-400">{{ $loadingMessage }}</span>
            </div>
        </div>

        {{-- Error Message --}}
        <div x-show="error" class="p-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700">
            <div class="flex items-center space-x-3">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Error loading data</h3>
                    <p class="text-sm text-red-700 dark:text-red-300" x-text="error"></p>
                </div>
                <button
                    @click="refreshData()"
                    class="ml-auto btn-sm btn-outline border-red-300 text-red-700 hover:bg-red-50"
                >
                    Retry
                </button>
            </div>
        </div>

        {{-- Table --}}
        <div
            x-show="!error"
            class="overflow-x-auto"
            x-ref="tableContainer"
        >
            <table
                x-ref="table"
                class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
                :class="{
                    'table-striped': config.striped,
                    'table-bordered': config.bordered,
                    'table-hover': config.hover,
                    'table-compact': config.compact
                }"
            >
                {{-- Table Header --}}
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        {{-- Select All Checkbox --}}
                        <th class="w-12 px-4 py-3">
                            <input
                                type="checkbox"
                                :checked="selectAll"
                                :indeterminate="selectAllIndeterminate"
                                @change="toggleSelectAll()"
                                class="checkbox"
                            >
                        </th>

                        {{-- Column Headers --}}
                        <template x-for="column in columns.filter(col => isColumnVisible(col.key))" :key="column.key">
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                :class="column.headerClass || ''"
                            >
                                <div class="flex items-center space-x-1">
                                    <span x-text="column.title || column.key"></span>
                                    <button
                                        x-show="column.sortable !== false"
                                        @click="sortBy(column.key)"
                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                :d="sortColumn === column.key ?
                                                    (sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7') :
                                                    'M8 9l4-4 4 4m0 6l-4 4-4-4'"
                                                :class="sortColumn === column.key ? 'text-blue-600 dark:text-blue-400' : ''"
                                            ></path>
                                        </svg>
                                    </button>
                                </div>

                                {{-- Column Filter --}}
                                <div x-show="column.filterable" class="mt-2">
                                    <input
                                        type="text"
                                        :placeholder="'Filter ' + (column.title || column.key)"
                                        :value="activeFilters[column.key] || ''"
                                        @input.debounce.300ms="updateFilter(column.key, $event.target.value)"
                                        class="input-field input-sm w-full"
                                    >
                                </div>
                            </th>
                        </template>

                        {{-- Actions Column --}}
                        <th class="w-20 px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>

                {{-- Table Body --}}
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    {{-- No Data Message --}}
                    <tr x-show="paginatedData.length === 0 && !loading">
                        <td :colspan="columns.filter(col => isColumnVisible(col.key)).length + 2" class="px-4 py-12 text-center">
                            <div class="text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-lg font-medium">{{ $noDataMessage }}</p>
                                <p class="text-sm mt-1">Try adjusting your search or filter criteria</p>
                            </div>
                        </td>
                    </tr>

                    {{-- Data Rows --}}
                    <template x-for="(row, index) in paginatedData" :key="getRowId(row)">
                        <tr
                            class="transition-colors duration-150"
                            :class="{
                                'bg-blue-50 dark:bg-blue-900/20': isRowSelected(row),
                                'hover:bg-gray-50 dark:hover:bg-gray-700': config.hover
                            }"
                        >
                            {{-- Row Selection --}}
                            <td class="px-4 py-3">
                                <input
                                    type="checkbox"
                                    :checked="isRowSelected(row)"
                                    @change="toggleRowSelection(row)"
                                    class="checkbox"
                                >
                            </td>

                            {{-- Data Cells --}}
                            <template x-for="column in columns.filter(col => isColumnVisible(col.key))" :key="column.key">
                                <td :class="getCellClass(row, column)" class="px-4 py-3">
                                    {{-- Inline Edit Mode --}}
                                    <div x-show="isEditing(row) && column.editable !== false">
                                        <input
                                            type="text"
                                            :value="getEditingData(row)[column.key]"
                                            @input="editingData[getRowId(row)][column.key] = $event.target.value"
                                            class="input-field input-sm w-full"
                                            :class="{ 'border-red-300': getValidationError(row, column.key) }"
                                        >
                                        <p
                                            x-show="getValidationError(row, column.key)"
                                            class="text-xs text-red-600 mt-1"
                                            x-text="getValidationError(row, column.key)"
                                        ></p>
                                    </div>

                                    {{-- Display Mode --}}
                                    <div x-show="!isEditing(row)" class="text-sm">
                                        {{-- Custom Cell Content --}}
                                        <template x-if="column.component">
                                            <div x-html="column.component(row, getNestedValue(row, column.key))"></div>
                                        </template>

                                        {{-- Default Cell Content --}}
                                        <template x-if="!column.component">
                                            <span x-text="formatValue(getNestedValue(row, column.key), column)"></span>
                                        </template>
                                    </div>
                                </td>
                            </template>

                            {{-- Row Actions --}}
                            <td class="px-4 py-3 text-right text-sm">
                                <div class="flex items-center justify-end space-x-1">
                                    {{-- Edit Toggle --}}
                                    <template x-if="!isEditing(row)">
                                        <button
                                            @click="startEditing(row)"
                                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                            title="Edit"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                    </template>

                                    {{-- Save/Cancel Edit --}}
                                    <template x-if="isEditing(row)">
                                        <div class="flex items-center space-x-1">
                                            <button
                                                @click="saveEditing(row)"
                                                class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300"
                                                title="Save"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                            <button
                                                @click="cancelEditing(row)"
                                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                                title="Cancel"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div
        x-show="!infiniteScroll && totalPages > 1"
        class="bg-white dark:bg-gray-800 px-6 py-3 border-t border-gray-200 dark:border-gray-700 rounded-b-lg"
    >
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
            {{-- Page Info --}}
            <div class="text-sm text-gray-700 dark:text-gray-300">
                Showing
                <span class="font-medium" x-text="pageInfo.start"></span>
                to
                <span class="font-medium" x-text="pageInfo.end"></span>
                of
                <span class="font-medium" x-text="pageInfo.total"></span>
                results
            </div>

            {{-- Pagination Controls --}}
            <div class="flex items-center space-x-2">
                {{-- Per Page Selector --}}
                <div class="flex items-center space-x-2">
                    <label class="text-sm text-gray-700 dark:text-gray-300">Show:</label>
                    <select
                        x-model="perPage"
                        @change="changePerPage()"
                        class="select-input select-sm"
                    >
                        <template x-for="option in perPageOptions" :key="option">
                            <option :value="option" x-text="option"></option>
                        </template>
                    </select>
                </div>

                {{-- Page Navigation --}}
                <nav class="flex items-center space-x-1">
                    <button
                        @click="goToPage(1)"
                        :disabled="currentPage <= 1"
                        class="pagination-btn"
                        title="First Page"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                        </svg>
                    </button>

                    <button
                        @click="previousPage()"
                        :disabled="currentPage <= 1"
                        class="pagination-btn"
                        title="Previous Page"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>

                    {{-- Page Numbers --}}
                    <template x-for="page in Array.from({length: Math.min(5, totalPages)}, (_, i) => {
                        const start = Math.max(1, Math.min(currentPage - 2, totalPages - 4));
                        return start + i;
                    }).filter(p => p <= totalPages)" :key="page">
                        <button
                            @click="goToPage(page)"
                            class="pagination-btn"
                            :class="{ 'pagination-btn-active': page === currentPage }"
                            x-text="page"
                        ></button>
                    </template>

                    <button
                        @click="nextPage()"
                        :disabled="currentPage >= totalPages"
                        class="pagination-btn"
                        title="Next Page"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>

                    <button
                        @click="goToPage(totalPages)"
                        :disabled="currentPage >= totalPages"
                        class="pagination-btn"
                        title="Last Page"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </nav>
            </div>
        </div>
    </div>
</div>

{{-- Styles --}}
<style>
.data-table-container {
    @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700;
}

.table-striped tbody tr:nth-child(even) {
    @apply bg-gray-50 dark:bg-gray-750;
}

.table-bordered {
    @apply border border-gray-200 dark:border-gray-700;
}

.table-bordered th,
.table-bordered td {
    @apply border-r border-gray-200 dark:border-gray-700;
}

.table-bordered th:last-child,
.table-bordered td:last-child {
    @apply border-r-0;
}

.table-hover tbody tr:hover {
    @apply bg-gray-50 dark:bg-gray-700;
}

.table-compact th,
.table-compact td {
    @apply py-2;
}

.pagination-btn {
    @apply px-3 py-1 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150;
}

.pagination-btn-active {
    @apply bg-blue-600 text-white border-blue-600 hover:bg-blue-700;
}

.table-cell {
    @apply text-sm text-gray-900 dark:text-gray-100;
}

.table-cell.text-left {
    @apply text-left;
}

.table-cell.text-center {
    @apply text-center;
}

.table-cell.text-right {
    @apply text-right;
}
</style>
