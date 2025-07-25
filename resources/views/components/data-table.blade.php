<!-- Interactive Data Table Blade Component -->
@props([
    'data' => [],
    'columns' => [],
    'title' => 'Data Table',
    'description' => null,
    'searchable' => true,
    'sortable' => true,
    'paginated' => true,
    'selectable' => false,
    'exportable' => false,
    'editable' => false,
    'refreshable' => false,
    'perPageOptions' => [10, 25, 50, 100],
    'defaultPerPage' => 25,
    'autoRefresh' => false,
    'refreshRate' => 30000,
    'websocketUrl' => null,
    'dataUrl' => null,
    'updateUrl' => null,
    'class' => '',
    'id' => null
])

@php
    $tableId = $id ?: 'data-table-' . uniqid();
    $tableConfig = [
        'data' => $data,
        'columns' => $columns,
        'perPage' => $defaultPerPage,
        'autoRefresh' => $autoRefresh,
        'refreshRate' => $refreshRate,
        'websocketUrl' => $websocketUrl,
        'dataUrl' => $dataUrl,
        'updateUrl' => $updateUrl,
    ];
@endphp

<div 
    id="{{ $tableId }}"
    x-data="{{ $editable ? 'editableDataTable' : 'dataTable' }}({{ json_encode($tableConfig) }})"
    x-init="init()"
    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm {{ $class }}"
    tabindex="0"
>
    <!-- Table Header -->
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
            <!-- Title and Description -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ $title }}
                    <span x-show="loading" class="ml-2 text-sm text-gray-500">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </h3>
                @if($description)
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $description }}</p>
                @endif
            </div>
            
            <!-- Actions -->
            <div class="flex items-center space-x-3">
                <!-- Refresh Button -->
                @if($refreshable)
                    <button
                        @click="refreshData()"
                        :disabled="loading"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50"
                        title="Refresh Data (Ctrl+R)"
                    >
                        <i class="fas fa-sync-alt mr-2" :class="{ 'fa-spin': loading }"></i>
                        Refresh
                    </button>
                @endif
                
                <!-- Export Dropdown -->
                @if($exportable)
                    <div x-data="{ open: false }" class="relative">
                        <button
                            @click="open = !open"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                            title="Export Data (Ctrl+E)"
                        >
                            <i class="fas fa-download mr-2"></i>
                            Export
                            <i class="fas fa-chevron-down ml-2"></i>
                        </button>
                        
                        <div
                            x-show="open"
                            @click.away="open = false"
                            x-transition
                            class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-700 rounded-md shadow-lg border border-gray-200 dark:border-gray-600 z-50"
                        >
                            <div class="py-1">
                                <button
                                    @click="exportData('csv'); open = false"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600"
                                >
                                    <i class="fas fa-file-csv mr-2"></i>
                                    Export as CSV
                                </button>
                                <button
                                    @click="exportData('json'); open = false"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600"
                                >
                                    <i class="fas fa-file-code mr-2"></i>
                                    Export as JSON
                                </button>
                                <button
                                    @click="exportData('excel'); open = false"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600"
                                >
                                    <i class="fas fa-file-excel mr-2"></i>
                                    Export as Excel
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
                
                <!-- Column Visibility Toggle -->
                <div x-data="{ open: false }" class="relative">
                    <button
                        @click="open = !open"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                        title="Manage Columns"
                    >
                        <i class="fas fa-columns mr-2"></i>
                        Columns
                    </button>
                    
                    <div
                        x-show="open"
                        @click.away="open = false"
                        x-transition
                        class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-700 rounded-md shadow-lg border border-gray-200 dark:border-gray-600 z-50"
                    >
                        <div class="p-4">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Visible Columns</h4>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                <template x-for="column in columns" :key="column.key">
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            :checked="visibleColumns.has(column.key)"
                                            @change="toggleColumnVisibility(column.key)"
                                            class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                                        >
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300" x-text="column.label"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Search and Filters -->
        @if($searchable)
            <div class="mt-4 flex flex-col sm:flex-row gap-4">
                <!-- Global Search -->
                <div class="flex-1">
                    <div class="relative">
                        <input
                            type="text"
                            x-model="globalFilter"
                            @input.debounce.300ms="applyFilters()"
                            x-ref="globalFilter"
                            placeholder="Search all columns... (Ctrl+F)"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm placeholder-gray-400 dark:placeholder-gray-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                        >
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Per Page Selector -->
                @if($paginated)
                    <div class="flex items-center space-x-2">
                        <label class="text-sm text-gray-700 dark:text-gray-300">Show:</label>
                        <select
                            x-model="perPage"
                            @change="changePerPage($event.target.value)"
                            class="border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                        >
                            @foreach($perPageOptions as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        @endif
        
        <!-- Active Filters -->
        <div x-show="activeFilters.length > 0" class="mt-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm text-gray-600 dark:text-gray-400">Active filters:</span>
                <template x-for="filter in activeFilters" :key="filter.label">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900 text-primary-800 dark:text-primary-200">
                        <span x-text="filter.label"></span>
                        <button
                            @click="clearFilter(filter)"
                            class="ml-1.5 inline-flex items-center justify-center w-4 h-4 rounded-full text-primary-600 dark:text-primary-400 hover:bg-primary-200 dark:hover:bg-primary-800"
                        >
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </span>
                </template>
                <button
                    @click="clearAllFilters()"
                    class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 underline"
                >
                    Clear all
                </button>
            </div>
        </div>
        
        <!-- Connection Status -->
        <div x-show="websocket" class="mt-2 flex items-center text-xs">
            <div class="flex items-center">
                <div 
                    class="w-2 h-2 rounded-full mr-2"
                    :class="wsConnected ? 'bg-green-500' : 'bg-red-500'"
                ></div>
                <span x-text="wsConnected ? 'Real-time updates active' : 'Real-time updates disconnected'" 
                      :class="wsConnected ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                </span>
            </div>
        </div>
    </div>
    
    <!-- Table Container -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <!-- Table Header -->
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <!-- Selection Column -->
                    @if($selectable)
                        <th class="px-4 py-3 text-left">
                            <input
                                type="checkbox"
                                x-model="selectAll"
                                @change="toggleSelectAll()"
                                class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                            >
                        </th>
                    @endif
                    
                    <!-- Data Columns -->
                    <template x-for="column in orderedVisibleColumns" :key="column.key">
                        <th 
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                            :class="column.sortable ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800' : ''"
                            @click="column.sortable && sort(column.key)"
                        >
                            <div class="flex items-center space-x-1">
                                <span x-text="column.label"></span>
                                <div x-show="column.sortable" class="flex flex-col">
                                    <i 
                                        class="fas fa-chevron-up text-xs"
                                        :class="sortColumn === column.key && sortDirection === 'asc' ? 'text-primary-600' : 'text-gray-300 dark:text-gray-600'"
                                    ></i>
                                    <i 
                                        class="fas fa-chevron-down text-xs -mt-1"
                                        :class="sortColumn === column.key && sortDirection === 'desc' ? 'text-primary-600' : 'text-gray-300 dark:text-gray-600'"
                                    ></i>
                                </div>
                            </div>
                            
                            <!-- Column Filter -->
                            <div x-show="column.filterable" class="mt-1" @click.stop>
                                <input
                                    type="text"
                                    x-model="columnFilters[column.key]"
                                    @input.debounce.300ms="applyFilters()"
                                    :placeholder="'Filter ' + column.label.toLowerCase() + '...'"
                                    class="block w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                                >
                            </div>
                        </th>
                    </template>
                </tr>
            </thead>
            
            <!-- Table Body -->
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <!-- Data Rows -->
                <template x-for="(row, rowIndex) in paginatedData" :key="row.id || rowIndex">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <!-- Selection Column -->
                        @if($selectable)
                            <td class="px-4 py-3">
                                <input
                                    type="checkbox"
                                    :checked="selectedRows.has((currentPage - 1) * perPage + rowIndex)"
                                    @change="toggleRowSelection(rowIndex)"
                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                                >
                            </td>
                        @endif
                        
                        <!-- Data Cells -->
                        <template x-for="column in orderedVisibleColumns" :key="column.key">
                            <td :class="getCellClass(row[column.key], column)">
                                @if($editable)
                                    <!-- Editable Cell -->
                                    <div x-show="!isEditing(rowIndex, column.key)">
                                        <span 
                                            x-html="formatCellValue(row[column.key], column)"
                                            @dblclick="startEditing(rowIndex, column.key)"
                                            class="cursor-pointer hover:bg-yellow-100 dark:hover:bg-yellow-900 rounded px-1"
                                            title="Double-click to edit"
                                        ></span>
                                    </div>
                                    
                                    <div x-show="isEditing(rowIndex, column.key)">
                                        <input
                                            :x-ref="'edit-' + ((currentPage - 1) * perPage + rowIndex) + '-' + column.key"
                                            x-model="editingValue"
                                            @keydown.enter="saveEdit(rowIndex, column.key)"
                                            @keydown.escape="cancelEditing()"
                                            @blur="saveEdit(rowIndex, column.key)"
                                            class="w-full px-2 py-1 text-sm border border-primary-300 rounded focus:ring-primary-500 focus:border-primary-500"
                                        >
                                    </div>
                                @else
                                    <!-- Read-only Cell -->
                                    <span x-html="formatCellValue(row[column.key], column)"></span>
                                @endif
                            </td>
                        </template>
                    </tr>
                </template>
                
                <!-- Empty State -->
                <tr x-show="paginatedData.length === 0 && !loading">
                    <td :colspan="orderedVisibleColumns.length + ({{ $selectable ? '1' : '0' }})" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-inbox text-4xl mb-2 text-gray-300 dark:text-gray-600"></i>
                            <p class="text-lg font-medium">No data available</p>
                            <p class="text-sm">Try adjusting your search criteria or filters.</p>
                        </div>
                    </td>
                </tr>
                
                <!-- Loading State -->
                <tr x-show="loading">
                    <td :colspan="orderedVisibleColumns.length + ({{ $selectable ? '1' : '0' }})" class="px-4 py-8 text-center">
                        <div class="flex items-center justify-center">
                            <i class="fas fa-spinner fa-spin text-2xl text-primary-600 mr-2"></i>
                            <span class="text-gray-600 dark:text-gray-400">Loading data...</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Table Footer -->
    @if($paginated)
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
                <!-- Results Info -->
                <div class="flex items-center text-sm text-gray-700 dark:text-gray-300">
                    <span>Showing </span>
                    <span x-text="pageInfo" class="font-medium"></span>
                    <span> results</span>
                    
                    @if($selectable)
                        <span x-show="selectedRows.size > 0" class="ml-4">
                            (<span x-text="selectedRows.size"></span> selected)
                        </span>
                    @endif
                </div>
                
                <!-- Pagination Controls -->
                <div class="flex items-center space-x-2">
                    <!-- Previous Button -->
                    <button
                        @click="previousPage()"
                        :disabled="currentPage <= 1"
                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                        title="Previous Page (Ctrl+←)"
                    >
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    
                    <!-- Page Numbers -->
                    <template x-for="page in pageNumbers" :key="page">
                        <button
                            @click="goToPage(page)"
                            :class="page === currentPage 
                                ? 'bg-primary-600 text-white border-primary-600' 
                                : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600'"
                            class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                            x-text="page"
                        ></button>
                    </template>
                    
                    <!-- Next Button -->
                    <button
                        @click="nextPage()"
                        :disabled="currentPage >= totalPages"
                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                        title="Next Page (Ctrl+→)"
                    >
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    @endif
    
    <!-- Error Message -->
    <div 
        x-show="error"
        x-transition
        class="px-6 py-4 bg-red-50 dark:bg-red-900 border-l-4 border-red-400"
    >
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-red-400 mr-2"></i>
            <span class="text-red-800 dark:text-red-200" x-text="error"></span>
            <button
                @click="error = null"
                class="ml-auto text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200"
            >
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</div>

<!-- Keyboard Shortcuts Help (Hidden by default, can be toggled) -->
<div x-data="{ showHelp: false }">
    <!-- Help Toggle Button -->
    <button
        @click="showHelp = !showHelp"
        class="fixed bottom-4 right-4 bg-primary-600 text-white p-3 rounded-full shadow-lg hover:bg-primary-700 transition-colors z-40"
        title="Keyboard Shortcuts"
    >
        <i class="fas fa-keyboard"></i>
    </button>
    
    <!-- Help Modal -->
    <div
        x-show="showHelp"
        x-transition.opacity
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        @click.self="showHelp = false"
    >
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Keyboard Shortcuts</h3>
                <button @click="showHelp = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Search</span>
                    <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">Ctrl+F</kbd>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Select All</span>
                    <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">Ctrl+A</kbd>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Export</span>
                    <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">Ctrl+E</kbd>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Refresh</span>
                    <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">Ctrl+R</kbd>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Previous Page</span>
                    <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">Ctrl+←</kbd>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Next Page</span>
                    <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">Ctrl+→</kbd>
                </div>
                @if($editable)
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Edit Cell</span>
                        <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">Double Click</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Save Edit</span>
                        <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">Enter</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Cancel Edit</span>
                        <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">Escape</kbd>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
