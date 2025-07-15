# 📊 **Interactive Data Table System Documentation**

## **Overview**

The Interactive Data Table system provides a comprehensive, feature-rich table component for Laravel applications with Alpine.js integration. It supports advanced filtering, sorting, pagination, bulk actions, inline editing, and export functionality.

## **Features**

### **Core Functionality**
- ✅ **Advanced Filtering**: Multiple criteria, global search, column-specific filters
- ✅ **Sortable Columns**: Custom sort functions, multi-column sorting
- ✅ **Pagination**: Traditional pagination and infinite scroll options
- ✅ **Bulk Actions**: Select all, batch operations with confirmation
- ✅ **Inline Editing**: Edit cells directly with validation
- ✅ **Export**: CSV, Excel, PDF export with custom formatting
- ✅ **Column Management**: Show/hide columns, reorder, resize
- ✅ **Real-time Updates**: Auto-refresh, WebSocket integration
- ✅ **Responsive Design**: Mobile-first approach with touch support
- ✅ **Accessibility**: WCAG 2.1 compliant with keyboard navigation

### **Advanced Features**
- ✅ **Custom Cell Components**: Rich content rendering
- ✅ **Validation System**: Real-time validation with error display
- ✅ **Loading States**: Skeleton loaders, progress indicators
- ✅ **Error Handling**: Graceful error recovery and retry
- ✅ **Performance**: Virtual scrolling, debounced search
- ✅ **Theming**: Dark/light mode support
- ✅ **Keyboard Shortcuts**: Power user features

## **Installation & Setup**

### **1. File Structure**
```
resources/
├── js/
│   └── components/
│       └── interactive-data-table.js     # Core JavaScript component
├── views/
│   └── components/
│       └── interactive-data-table.blade.php  # Blade component
└── examples/
    └── interactive-data-table-example.blade.php  # Usage example
```

### **2. Include in app.js**
```javascript
// Import the component
import './components/interactive-data-table.js';

// Register with Alpine.js
document.addEventListener('alpine:init', () => {
    Alpine.data('interactiveDataTable', window.interactiveDataTable);
});
```

### **3. Include CSS Framework**
Ensure Tailwind CSS is included in your project as the component relies on utility classes.

## **Basic Usage**

### **Simple Table**
```blade
<x-interactive-data-table
    title="Users"
    :data="$users"
    :columns="[
        ['key' => 'id', 'title' => 'ID'],
        ['key' => 'name', 'title' => 'Name', 'sortable' => true],
        ['key' => 'email', 'title' => 'Email', 'filterable' => true],
        ['key' => 'created_at', 'title' => 'Created', 'formatter' => 'formatDate']
    ]"
/>
```

### **Advanced Table with API**
```blade
<x-interactive-data-table
    id="advanced-table"
    title="Server Management"
    description="Manage proxy servers with real-time monitoring"
    :data-url="route('api.servers.index')"
    :export-url="route('api.servers.export')"
    :save-row-url="route('api.servers.update', ':id')"
    :columns="$columns"
    :bulk-actions="$bulkActions"
    :config="[
        'striped' => true,
        'autoRefresh' => true,
        'refreshInterval' => 30000
    ]"
/>
```

## **Component Properties**

### **Basic Props**
| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `id` | string | auto-generated | Unique table identifier |
| `title` | string | null | Table title |
| `description` | string | null | Table description |
| `data` | array | [] | Static data array |
| `dataUrl` | string | null | API endpoint for dynamic data |
| `columns` | array | [] | Column definitions |
| `class` | string | '' | Additional CSS classes |

### **Functional Props**
| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `searchPlaceholder` | string | 'Search...' | Search input placeholder |
| `noDataMessage` | string | 'No data available' | Empty state message |
| `loadingMessage` | string | 'Loading data...' | Loading state message |
| `exportUrl` | string | null | Export API endpoint |
| `saveRowUrl` | string | null | Row update endpoint |
| `bulkActions` | array | [] | Bulk action definitions |
| `config` | array | [] | Table configuration options |

## **Column Configuration**

### **Column Properties**
```php
[
    'key' => 'field_name',           // Required: Data field name
    'title' => 'Display Name',       // Column header title
    'sortable' => true,              // Enable sorting
    'filterable' => true,            // Enable column filtering
    'searchable' => true,            // Include in global search
    'editable' => true,              // Enable inline editing
    'required' => true,              // Required for editing
    'visible' => true,               // Default visibility
    'align' => 'left',               // left|center|right
    'headerClass' => 'font-bold',    // Header CSS classes
    'cellClass' => 'text-blue-500',  // Cell CSS classes
    'component' => 'statusBadge',    // Custom component name
    'formatter' => 'formatDate',     // Value formatter function
    'validate' => 'validateEmail',   // Validation function
    'sortFunction' => function($a, $b) { ... }, // Custom sort
    'filterFunction' => function($row, $filter) { ... } // Custom filter
]
```

### **Built-in Formatters**
```javascript
// Date formatting
'formatter' => 'formatDate'         // Date only
'formatter' => 'formatDateTime'     // Date and time
'formatter' => 'formatRelative'     // Relative time (e.g., "2 hours ago")

// Number formatting
'formatter' => 'formatNumber'       // Thousands separator
'formatter' => 'formatCurrency'     // Currency format
'formatter' => 'formatBytes'        // File size format
'formatter' => 'formatPercentage'   // Percentage format

// Text formatting
'formatter' => 'formatTruncate'     // Truncate long text
'formatter' => 'formatCapitalize'   // Capitalize first letter
'formatter' => 'formatSlug'         // Convert to slug format
```

### **Custom Components**
```javascript
// Define custom cell components
window.tableComponents = {
    statusBadge: (row, value) => {
        const colors = {
            active: 'bg-green-100 text-green-800',
            inactive: 'bg-red-100 text-red-800'
        };
        return `<span class="px-2 py-1 rounded ${colors[value]}">${value}</span>`;
    },
    
    progressBar: (row, value) => {
        return `
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full" style="width: ${value}%"></div>
            </div>
        `;
    }
};
```

## **Bulk Actions**

### **Configuration**
```php
$bulkActions = [
    [
        'id' => 'activate',
        'label' => 'Activate Selected',
        'class' => 'btn-primary',
        'url' => route('api.servers.bulk-activate'),
        'confirm' => 'Activate selected items?',
        'refresh' => true
    ],
    [
        'id' => 'delete',
        'label' => 'Delete Selected',
        'class' => 'btn-danger',
        'handler' => 'customDeleteHandler',
        'confirm' => 'Delete selected items permanently?'
    ]
];
```

### **Server-side Handler**
```php
// API Controller
public function bulkActivate(Request $request)
{
    $ids = $request->input('ids');
    
    Server::whereIn('id', $ids)->update(['status' => 'active']);
    
    return response()->json([
        'success' => true,
        'message' => count($ids) . ' servers activated successfully'
    ]);
}
```

### **Custom JavaScript Handler**
```javascript
window.customBulkHandlers = {
    customDeleteHandler: async (selectedData, selectedIds) => {
        // Custom logic here
        console.log('Deleting:', selectedData);
        
        // Make API call
        const response = await fetch('/api/custom-delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: selectedIds })
        });
        
        if (!response.ok) throw new Error('Delete failed');
        return await response.json();
    }
};
```

## **Advanced Features**

### **Real-time Updates**
```javascript
// Auto-refresh configuration
:config="[
    'autoRefresh' => true,
    'refreshInterval' => 30000  // 30 seconds
]"

// WebSocket integration
window.Echo.channel('server-updates')
    .listen('ServerStatusChanged', (e) => {
        // Update table data
        this.$refs.dataTable.updateRow(e.serverId, e.data);
    });
```

### **Infinite Scroll**
```blade
<x-interactive-data-table
    :data-url="route('api.large-dataset')"
    :config="['infiniteScroll' => true]"
    :columns="$columns"
/>
```

### **Export Functionality**
```php
// Export controller
public function export(Request $request)
{
    $format = $request->input('format', 'csv');
    $data = $request->input('data');
    $columns = $request->input('columns');
    
    switch ($format) {
        case 'csv':
            return $this->exportCsv($data, $columns);
        case 'excel':
            return $this->exportExcel($data, $columns);
        case 'pdf':
            return $this->exportPdf($data, $columns);
    }
}
```

### **Validation System**
```javascript
// Custom validators
window.tableValidators = {
    validateEmail: (value) => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(value) || 'Invalid email format';
    },
    
    validateRequired: (value) => {
        return value && value.trim() !== '' || 'This field is required';
    },
    
    validateNumeric: (value, min = null, max = null) => {
        const num = parseFloat(value);
        if (isNaN(num)) return 'Must be a number';
        if (min !== null && num < min) return `Must be at least ${min}`;
        if (max !== null && num > max) return `Must be at most ${max}`;
        return true;
    }
};
```

## **API Integration**

### **Data Endpoint**
```php
// Expected response format
{
    "data": [
        {"id": 1, "name": "Server 1", "status": "active"},
        {"id": 2, "name": "Server 2", "status": "inactive"}
    ],
    "total": 150,
    "current_page": 1,
    "per_page": 10,
    "last_page": 15
}
```

### **Query Parameters**
The component automatically sends these parameters:
- `page`: Current page number
- `per_page`: Items per page
- `sort_column`: Column being sorted
- `sort_direction`: asc or desc
- `search`: Global search term
- `filters`: JSON object of column filters

### **Laravel Controller Example**
```php
public function index(Request $request)
{
    $query = Server::with('location');
    
    // Apply search
    if ($search = $request->get('search')) {
        $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('host', 'like', "%{$search}%");
        });
    }
    
    // Apply filters
    if ($filters = $request->get('filters')) {
        $filters = json_decode($filters, true);
        foreach ($filters as $column => $value) {
            $query->where($column, 'like', "%{$value}%");
        }
    }
    
    // Apply sorting
    if ($sortColumn = $request->get('sort_column')) {
        $direction = $request->get('sort_direction', 'asc');
        $query->orderBy($sortColumn, $direction);
    }
    
    // Paginate
    $perPage = $request->get('per_page', 10);
    return $query->paginate($perPage);
}
```

## **Styling & Customization**

### **CSS Classes**
```css
/* Table container */
.data-table-container { /* Custom styles */ }

/* Loading states */
.loading-overlay { /* Overlay styles */ }
.skeleton { /* Skeleton loader */ }

/* Row states */
.table-row-selected { /* Selected row */ }
.table-row-editing { /* Editing row */ }
.table-row-error { /* Error state */ }

/* Pagination */
.pagination-btn { /* Pagination button */ }
.pagination-btn-active { /* Active page */ }
```

### **Dark Mode Support**
```css
/* Automatic dark mode support */
.data-table-container {
    @apply bg-white dark:bg-gray-800;
    @apply text-gray-900 dark:text-gray-100;
    @apply border-gray-200 dark:border-gray-700;
}
```

### **Responsive Design**
```css
/* Mobile optimizations */
@media (max-width: 768px) {
    .data-table-container {
        @apply text-sm;
    }
    
    .table-cell {
        @apply px-2 py-3;
    }
}
```

## **Performance Optimization**

### **Large Datasets**
```javascript
// Enable virtual scrolling for large datasets
:config="[
    'virtualScroll' => true,
    'bufferSize' => 50,
    'itemHeight' => 48
]"
```

### **Debounced Search**
```blade
{{-- Search is automatically debounced 300ms --}}
@input.debounce.300ms="updateGlobalSearch()"
```

### **Lazy Loading**
```php
// Server-side pagination for performance
$query->paginate(50); // Smaller page sizes for better performance
```

## **Accessibility Features**

### **Keyboard Navigation**
- `Ctrl+F`: Focus search input
- `Ctrl+A`: Select all rows (when table has focus)
- `Ctrl+E`: Export selected data
- `Tab/Shift+Tab`: Navigate through interactive elements
- `Enter/Space`: Activate buttons and checkboxes
- `Arrow Keys`: Navigate table cells during editing

### **Screen Reader Support**
- ARIA labels on all interactive elements
- Table headers properly associated with cells
- Loading states announced
- Error messages announced
- Row selection state announced

### **WCAG Compliance**
- ✅ Color contrast meets AA standards
- ✅ Focus indicators visible
- ✅ Alternative text for icons
- ✅ Semantic HTML structure
- ✅ Keyboard-only operation possible

## **Error Handling**

### **Network Errors**
```javascript
// Automatic retry with exponential backoff
async loadData() {
    let retries = 0;
    const maxRetries = 3;
    
    while (retries < maxRetries) {
        try {
            // Load data
            return await this.fetchData();
        } catch (error) {
            retries++;
            if (retries >= maxRetries) throw error;
            
            // Wait before retry (exponential backoff)
            await new Promise(resolve => 
                setTimeout(resolve, Math.pow(2, retries) * 1000)
            );
        }
    }
}
```

### **Validation Errors**
```javascript
// Display validation errors inline
getValidationError(row, column) {
    const rowId = this.getRowId(row);
    return this.validationErrors[rowId]?.[column];
}
```

### **Graceful Degradation**
- Works without JavaScript (basic table)
- Fallback for unsupported browsers
- Progressive enhancement approach

## **Testing**

### **Unit Tests**
```javascript
// Jest/Alpine.js testing
describe('InteractiveDataTable', () => {
    test('sorts data correctly', () => {
        const table = interactiveDataTable();
        table.data = [
            { name: 'Charlie', age: 30 },
            { name: 'Alice', age: 25 },
            { name: 'Bob', age: 35 }
        ];
        
        table.sortBy('name');
        
        expect(table.data[0].name).toBe('Alice');
        expect(table.data[1].name).toBe('Bob');
        expect(table.data[2].name).toBe('Charlie');
    });
});
```

### **Integration Tests**
```php
// Laravel Feature Tests
public function test_table_loads_data()
{
    $response = $this->get('/api/servers');
    
    $response->assertStatus(200)
             ->assertJsonStructure([
                 'data' => [
                     '*' => ['id', 'name', 'status']
                 ],
                 'total',
                 'current_page'
             ]);
}
```

## **Browser Support**

### **Minimum Requirements**
- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

### **Polyfills**
```javascript
// Include for IE11 support (if needed)
import 'core-js/stable';
import 'regenerator-runtime/runtime';
```

## **Migration Guide**

### **From Basic Tables**
1. Replace `<table>` with `<x-interactive-data-table>`
2. Convert column definitions to array format
3. Add API endpoints for dynamic loading
4. Update styling to use Tailwind classes

### **From Other Table Libraries**
1. Map existing column configurations
2. Migrate custom cell renderers to components
3. Update bulk action handlers
4. Test thoroughly with existing data

## **Troubleshooting**

### **Common Issues**

**Data not loading:**
- Check API endpoint returns correct JSON format
- Verify CSRF token is included in requests
- Check browser console for errors

**Sorting not working:**
- Ensure column has `sortable: true`
- Check data types are consistent
- Verify custom sort functions return numbers

**Filtering slow:**
- Implement server-side filtering for large datasets
- Add database indexes on filtered columns
- Use debounced input for better UX

**Styling issues:**
- Ensure Tailwind CSS is loaded
- Check for CSS conflicts
- Verify dark mode classes are available

### **Debug Mode**
```javascript
// Enable debug logging
:config="['debug' => true]"

// Check component state
console.log(this.$refs.dataTable);
```

## **Changelog**

### **Version 1.0.0**
- Initial release with full feature set
- Alpine.js integration
- Tailwind CSS styling
- Comprehensive accessibility support
- Full documentation and examples

---

**📝 Note**: This documentation covers the complete Interactive Data Table system. For specific implementation questions or feature requests, refer to the example files or create GitHub issues.

**🔗 Related Components**:
- [XUI Integration Interface](./XUI_INTEGRATION_INTERFACE.md)
- [Advanced Theme System](./ENHANCED_THEME_SYSTEM.md)
- [Accessibility Features](./ACCESSIBILITY_FEATURES.md)
