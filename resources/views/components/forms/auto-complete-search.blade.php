<!-- Auto-complete Component with Search and Filtering -->
<div x-data="autoCompleteSearch()" 
     x-init="init()"
     class="autocomplete-container"
     data-placeholder="Search options..."
     data-multiple="false"
     data-searchable="true"
     data-clearable="true"
     data-required="false"
     data-data-source="/api/search"
     data-search-param="query"
     data-value-field="id"
     data-label-field="name"
     data-description-field="description"
     data-image-field="avatar"
     data-debounce-delay="300"
     data-min-search-length="0"
     data-max-results="10"
     data-cache-results="true"
     data-no-results-text="No results found"
     data-loading-text="Searching...">
    
    <!-- Hidden Input for Form Submission -->
    <input type="hidden" 
           name="autocomplete_value" 
           :value="multiple ? JSON.stringify(getSelectedValues()) : (getSelectedValues()[0] || '')">
    
    <!-- Main Input Container -->
    <div class="autocomplete-input-container" 
         :class="{ 
             'focused': isFocused, 
             'disabled': disabled,
             'has-selection': selectedItems.length > 0,
             'multiple': multiple 
         }">
        
        <!-- Selected Items (Multiple Mode) -->
        <div x-show="multiple && selectedItems.length > 0" 
             class="selected-items">
            <template x-for="item in selectedItems" :key="getItemValue(item)">
                <div class="selected-item">
                    <span class="selected-item-image" x-show="getItemImage(item)">
                        <img :src="getItemImage(item)" 
                             :alt="getItemLabel(item)"
                             class="w-5 h-5 rounded-full object-cover">
                    </span>
                    <span class="selected-item-label" x-text="getItemLabel(item)"></span>
                    <button type="button" 
                            @click="removeItem(item)"
                            class="selected-item-remove">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </template>
        </div>
        
        <!-- Search Input -->
        <div class="search-input-wrapper">
            <input type="text"
                   x-model="searchQuery"
                   @input="handleSearch($event.target.value)"
                   @focus="open(); isFocused = true"
                   @blur="isFocused = false"
                   @keydown.enter.prevent="selectHighlighted()"
                   @keydown.escape="close()"
                   @keydown.arrow-down.prevent="highlightNext()"
                   @keydown.arrow-up.prevent="highlightPrevious()"
                   :placeholder="selectedItems.length > 0 && !multiple ? getItemLabel(selectedItems[0]) : placeholder"
                   :disabled="disabled"
                   :readonly="!searchable"
                   class="search-input"
                   autocomplete="off"
                   role="combobox"
                   :aria-expanded="isOpen"
                   :aria-haspopup="true"
                   aria-autocomplete="list">
            
            <!-- Input Icons -->
            <div class="input-icons">
                <!-- Loading Icon -->
                <div x-show="isLoading" class="input-icon loading">
                    <svg class="animate-spin w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                
                <!-- Clear Button -->
                <button x-show="clearable && selectedItems.length > 0 && !isLoading"
                        @click="clearAll()"
                        type="button"
                        class="input-icon clear-button">
                    <svg class="w-4 h-4 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                
                <!-- Dropdown Toggle -->
                <button @click="toggle()"
                        type="button"
                        class="input-icon dropdown-toggle"
                        :class="{ 'open': isOpen }">
                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         :class="{ 'rotate-180': isOpen }">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Dropdown Menu -->
    <div x-show="isOpen" 
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="autocomplete-dropdown"
         role="listbox">
        
        <!-- Loading State -->
        <div x-show="isLoading" class="dropdown-loading">
            <div class="flex items-center justify-center py-4">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-gray-500 dark:text-gray-400" x-text="loadingText"></span>
            </div>
        </div>
        
        <!-- Options List -->
        <div x-show="!isLoading" class="dropdown-options">
            <!-- No Results -->
            <div x-show="filteredOptions.length === 0 && searchQuery.length >= minSearchLength" 
                 class="dropdown-no-results">
                <div class="flex items-center justify-center py-4">
                    <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <span class="text-gray-500 dark:text-gray-400" x-text="noResultsText"></span>
                </div>
            </div>
            
            <!-- Options -->
            <template x-for="(option, index) in filteredOptions" :key="getItemValue(option)">
                <div @click="selectOption(option)"
                     @mouseenter="highlightedIndex = index"
                     class="dropdown-option"
                     :class="{ 
                         'option-highlighted': index === highlightedIndex,
                         'option-selected': isSelected(option)
                     }"
                     role="option"
                     :aria-selected="isSelected(option)">
                    
                    <!-- Option Content -->
                    <div class="option-content">
                        <!-- Option Image -->
                        <div x-show="getItemImage(option)" class="option-image-container">
                            <img :src="getItemImage(option)" 
                                 :alt="getItemLabel(option)"
                                 class="option-image">
                        </div>
                        
                        <!-- Option Text -->
                        <div class="option-text">
                            <div class="option-label" x-text="getItemLabel(option)"></div>
                            <div x-show="getItemDescription(option)" 
                                 class="option-description" 
                                 x-text="getItemDescription(option)"></div>
                        </div>
                        
                        <!-- Selected Indicator -->
                        <div x-show="isSelected(option)" class="option-selected-indicator">
                            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        
        <!-- Dropdown Footer -->
        <div x-show="filteredOptions.length > 0 && searchQuery" class="dropdown-footer">
            <div class="text-xs text-gray-500 dark:text-gray-400 px-3 py-2">
                <span x-text="filteredOptions.length"></span> 
                result<span x-show="filteredOptions.length !== 1">s</span>
                <span x-show="searchQuery"> for "<span x-text="searchQuery"></span>"</span>
            </div>
        </div>
    </div>
    
    <!-- Static Data Script (Example) -->
    <script type="application/json">
    [
        {
            "id": 1,
            "name": "John Doe",
            "description": "Software Engineer",
            "avatar": "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=32&h=32&fit=crop&crop=face"
        },
        {
            "id": 2,
            "name": "Jane Smith",
            "description": "Product Manager",
            "avatar": "https://images.unsplash.com/photo-1494790108755-2616b612b642?w=32&h=32&fit=crop&crop=face"
        },
        {
            "id": 3,
            "name": "Mike Johnson",
            "description": "UI/UX Designer",
            "avatar": "https://images.unsplash.com/photo-1599566150163-29194dcaad36?w=32&h=32&fit=crop&crop=face"
        },
        {
            "id": 4,
            "name": "Sarah Wilson",
            "description": "Data Scientist",
            "avatar": "https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=32&h=32&fit=crop&crop=face"
        },
        {
            "id": 5,
            "name": "David Brown",
            "description": "DevOps Engineer",
            "avatar": "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=32&h=32&fit=crop&crop=face"
        }
    ]
    </script>
</div>

<style>
/* Auto-complete Container */
.autocomplete-container {
    @apply relative w-full;
}

/* Input Container */
.autocomplete-input-container {
    @apply relative border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 transition-all duration-200;
}

.autocomplete-input-container.focused {
    @apply ring-2 ring-blue-500 border-blue-500;
}

.autocomplete-input-container.disabled {
    @apply bg-gray-50 dark:bg-gray-700 cursor-not-allowed opacity-60;
}

.autocomplete-input-container.multiple {
    @apply min-h-[2.5rem];
}

/* Selected Items */
.selected-items {
    @apply flex flex-wrap gap-1 p-2 pb-0;
}

.selected-item {
    @apply inline-flex items-center gap-1.5 px-2 py-1 bg-blue-100 text-blue-800 text-sm rounded-md dark:bg-blue-900/50 dark:text-blue-300;
}

.selected-item-image {
    @apply flex-shrink-0;
}

.selected-item-label {
    @apply truncate max-w-[120px];
}

.selected-item-remove {
    @apply flex-shrink-0 ml-1 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 transition-colors duration-150;
}

/* Search Input */
.search-input-wrapper {
    @apply relative flex items-center;
}

.search-input {
    @apply w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-transparent border-0 focus:outline-none focus:ring-0 placeholder-gray-500 dark:placeholder-gray-400;
}

.search-input:disabled {
    @apply cursor-not-allowed;
}

/* Input Icons */
.input-icons {
    @apply absolute right-2 flex items-center space-x-1;
}

.input-icon {
    @apply flex items-center justify-center p-1 transition-colors duration-150;
}

.input-icon.loading {
    @apply pointer-events-none;
}

.clear-button {
    @apply hover:bg-gray-100 dark:hover:bg-gray-700 rounded;
}

.dropdown-toggle {
    @apply hover:bg-gray-100 dark:hover:bg-gray-700 rounded;
}

.dropdown-toggle.open {
    @apply bg-gray-100 dark:bg-gray-700;
}

/* Dropdown */
.autocomplete-dropdown {
    @apply absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-hidden;
}

/* Dropdown Content */
.dropdown-loading,
.dropdown-no-results {
    @apply text-center;
}

.dropdown-options {
    @apply max-h-48 overflow-y-auto;
}

/* Option Styles */
.dropdown-option {
    @apply px-3 py-2 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-b-0 transition-colors duration-150;
}

.dropdown-option:hover,
.dropdown-option.option-highlighted {
    @apply bg-gray-50 dark:bg-gray-700;
}

.dropdown-option.option-selected {
    @apply bg-blue-50 dark:bg-blue-900/30;
}

/* Option Content */
.option-content {
    @apply flex items-center space-x-3;
}

.option-image-container {
    @apply flex-shrink-0;
}

.option-image {
    @apply w-8 h-8 rounded-full object-cover border border-gray-200 dark:border-gray-600;
}

.option-text {
    @apply flex-1 min-w-0;
}

.option-label {
    @apply text-sm font-medium text-gray-900 dark:text-white truncate;
}

.option-description {
    @apply text-xs text-gray-500 dark:text-gray-400 truncate;
}

.option-selected-indicator {
    @apply flex-shrink-0;
}

/* Dropdown Footer */
.dropdown-footer {
    @apply border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50;
}

/* Search highlighting */
.search-highlight {
    @apply bg-yellow-200 dark:bg-yellow-800 px-0.5 rounded;
}

/* Accessibility */
.autocomplete-container:focus-within .autocomplete-input-container {
    @apply ring-2 ring-blue-500 border-blue-500;
}

/* Error State */
.autocomplete-container.invalid .autocomplete-input-container {
    @apply border-red-500 ring-2 ring-red-500;
}

/* Loading States */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.dropdown-loading,
.dropdown-no-results {
    animation: fadeIn 0.2s ease-in-out;
}

/* Responsive Design */
@media (max-width: 640px) {
    .autocomplete-dropdown {
        @apply max-h-48;
    }
    
    .selected-items {
        @apply gap-1;
    }
    
    .selected-item {
        @apply text-xs px-1.5 py-0.5;
    }
    
    .selected-item-label {
        @apply max-w-[80px];
    }
    
    .option-content {
        @apply space-x-2;
    }
    
    .option-image {
        @apply w-6 h-6;
    }
}

/* Dark Mode Enhancements */
@media (prefers-color-scheme: dark) {
    .autocomplete-dropdown {
        @apply shadow-2xl;
    }
    
    .dropdown-option:hover,
    .dropdown-option.option-highlighted {
        @apply bg-gray-700/70;
    }
}

/* Animation for smooth transitions */
.dropdown-option {
    transition: background-color 0.15s ease-in-out, transform 0.1s ease-in-out;
}

.dropdown-option:active {
    @apply transform scale-[0.98];
}

/* Custom scrollbar for dropdown */
.dropdown-options {
    scrollbar-width: thin;
    scrollbar-color: theme('colors.gray.400') theme('colors.gray.100');
}

.dropdown-options::-webkit-scrollbar {
    width: 6px;
}

.dropdown-options::-webkit-scrollbar-track {
    @apply bg-gray-100 dark:bg-gray-700;
}

.dropdown-options::-webkit-scrollbar-thumb {
    @apply bg-gray-400 dark:bg-gray-500 rounded-full;
}

.dropdown-options::-webkit-scrollbar-thumb:hover {
    @apply bg-gray-500 dark:bg-gray-400;
}
</style>

<script>
// Import the auto-complete component
import autoCompleteSearch from '../js/components/auto-complete-search.js';

// Register the component globally
window.autoCompleteSearch = autoCompleteSearch;
</script>
