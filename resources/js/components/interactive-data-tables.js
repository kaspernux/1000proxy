/**
 * Interactive Data Tables Component
 * Advanced data table with sorting, filtering, pagination, and real-time updates
 */

// Base Data Table Component
window.dataTable = function ( config = {} )
{
    return {
        // Configuration and state
        data: config.data || [],
        originalData: [],
        columns: config.columns || [],
        loading: false,
        error: null,

        // Pagination
        currentPage: 1,
        perPage: config.perPage || 25,
        totalPages: 1,
        totalItems: 0,

        // Sorting
        sortColumn: null,
        sortDirection: 'asc',

        // Filtering
        globalFilter: '',
        columnFilters: {},
        activeFilters: [],

        // Selection
        selectedRows: new Set(),
        selectAll: false,

        // Column management
        visibleColumns: new Set(),
        columnOrder: [],

        // Export functionality
        exportFormats: [ 'csv', 'json', 'excel' ],

        // Real-time updates
        refreshInterval: null,
        autoRefresh: config.autoRefresh || false,
        refreshRate: config.refreshRate || 30000, // 30 seconds

        // WebSocket integration
        websocket: null,
        wsConnected: false,

        init ()
        {
            console.log( '🔧 Initializing Interactive Data Table...' );

            // Initialize data
            this.originalData = [ ...this.data ];
            this.initializeColumns();
            this.initializePagination();

            // Setup real-time updates
            if ( this.autoRefresh )
            {
                this.startAutoRefresh();
            }

            // Initialize WebSocket if configured
            if ( config.websocketUrl )
            {
                this.initializeWebSocket( config.websocketUrl );
            }

            // Keyboard shortcuts
            this.setupKeyboardShortcuts();

            console.log( '✅ Data Table initialized with', this.data.length, 'items' );
        },

        // Column Management
        initializeColumns ()
        {
            if ( this.columns.length === 0 && this.data.length > 0 )
            {
                // Auto-generate columns from first data item
                this.columns = Object.keys( this.data[ 0 ] ).map( key => ( {
                    key,
                    label: this.formatLabel( key ),
                    sortable: true,
                    filterable: true,
                    visible: true,
                    type: this.detectColumnType( this.data[ 0 ][ key ] )
                } ) );
            }

            // Initialize visible columns
            this.visibleColumns = new Set(
                this.columns.filter( col => col.visible !== false ).map( col => col.key )
            );

            // Initialize column order
            this.columnOrder = this.columns.map( col => col.key );

            // Initialize column filters
            this.columns.forEach( column =>
            {
                if ( column.filterable )
                {
                    this.columnFilters[ column.key ] = '';
                }
            } );
        },

        formatLabel ( key )
        {
            return key.replace( /([A-Z])/g, ' $1' )
                .replace( /^./, str => str.toUpperCase() )
                .replace( /_/g, ' ' );
        },

        detectColumnType ( value )
        {
            if ( typeof value === 'number' ) return 'number';
            if ( typeof value === 'boolean' ) return 'boolean';
            if ( value instanceof Date || !isNaN( Date.parse( value ) ) ) return 'date';
            if ( typeof value === 'string' && value.includes( '@' ) ) return 'email';
            if ( typeof value === 'string' && value.startsWith( 'http' ) ) return 'url';
            return 'text';
        },

        // Sorting functionality
        sort ( columnKey )
        {
            if ( this.sortColumn === columnKey )
            {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else
            {
                this.sortColumn = columnKey;
                this.sortDirection = 'asc';
            }

            this.applySort();
            this.updatePagination();
        },

        applySort ()
        {
            if ( !this.sortColumn ) return;

            const column = this.columns.find( col => col.key === this.sortColumn );
            const direction = this.sortDirection === 'asc' ? 1 : -1;

            this.data.sort( ( a, b ) =>
            {
                let aVal = a[ this.sortColumn ];
                let bVal = b[ this.sortColumn ];

                // Handle different data types
                if ( column.type === 'number' )
                {
                    aVal = parseFloat( aVal ) || 0;
                    bVal = parseFloat( bVal ) || 0;
                } else if ( column.type === 'date' )
                {
                    aVal = new Date( aVal );
                    bVal = new Date( bVal );
                } else
                {
                    aVal = String( aVal ).toLowerCase();
                    bVal = String( bVal ).toLowerCase();
                }

                if ( aVal < bVal ) return -1 * direction;
                if ( aVal > bVal ) return 1 * direction;
                return 0;
            } );
        },

        // Filtering functionality
        applyFilters ()
        {
            let filteredData = [ ...this.originalData ];

            const anyColumnFilterActive = Object.values( this.columnFilters || {} ).some( v => ( v || '' ).trim() );

            // Apply global filter
            if ( this.globalFilter.trim() )
            {
                const searchTerm = this.globalFilter.toLowerCase();
                // Escape regex special chars
                const esc = ( s ) => s.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
                if ( anyColumnFilterActive )
                {
                    // When column filters are active, use whole-word matching to reduce false positives like "john" in "Johnson"
                    const re = new RegExp( `\\b${ esc( searchTerm ) }\\b`, 'i' );
                    filteredData = filteredData.filter( row =>
                    {
                        return Object.values( row ).some( value => re.test( String( value ) ) );
                    } );
                } else
                {
                    // Default: substring match across all fields
                    filteredData = filteredData.filter( row =>
                    {
                        return Object.values( row ).some( value =>
                            String( value ).toLowerCase().includes( searchTerm )
                        );
                    } );
                }
            }

            // Apply column filters
            Object.entries( this.columnFilters ).forEach( ( [ columnKey, filter ] ) =>
            {
                if ( ( filter || '' ).trim() )
                {
                    const filterValue = filter.toLowerCase();
                    filteredData = filteredData.filter( row =>
                    {
                        const cellValue = String( row[ columnKey ] ).toLowerCase();
                        const column = this.columns.find( c => c.key === columnKey ) || { type: 'text' };

                        // Support different filter operators
                        if ( filter.startsWith( '>=' ) )
                        {
                            return parseFloat( row[ columnKey ] ) >= parseFloat( filter.slice( 2 ) );
                        } else if ( filter.startsWith( '<=' ) )
                        {
                            return parseFloat( row[ columnKey ] ) <= parseFloat( filter.slice( 2 ) );
                        } else if ( filter.startsWith( '>' ) )
                        {
                            return parseFloat( row[ columnKey ] ) > parseFloat( filter.slice( 1 ) );
                        } else if ( filter.startsWith( '<' ) )
                        {
                            return parseFloat( row[ columnKey ] ) < parseFloat( filter.slice( 1 ) );
                        } else if ( filter.startsWith( '=' ) )
                        {
                            return cellValue === filter.slice( 1 );
                        } else
                        {
                            // Default behavior: for text-like columns, use exact match; otherwise substring
                            if ( [ 'number', 'date', 'boolean' ].includes( column.type ) )
                            {
                                return cellValue.includes( filterValue );
                            }
                            return cellValue === filterValue;
                        }
                    } );
                }
            } );

            this.data = filteredData;
            this.updateActiveFilters();
            this.updatePagination();
            this.currentPage = 1;
        },

        updateActiveFilters ()
        {
            this.activeFilters = [];

            if ( this.globalFilter.trim() )
            {
                this.activeFilters.push( {
                    type: 'global',
                    label: `Global: "${ this.globalFilter }"`,
                    value: this.globalFilter
                } );
            }

            Object.entries( this.columnFilters ).forEach( ( [ column, filter ] ) =>
            {
                if ( filter.trim() )
                {
                    const columnLabel = this.columns.find( col => col.key === column )?.label || column;
                    this.activeFilters.push( {
                        type: 'column',
                        column,
                        label: `${ columnLabel }: "${ filter }"`,
                        value: filter
                    } );
                }
            } );
        },

        clearFilter ( filter )
        {
            if ( filter.type === 'global' )
            {
                this.globalFilter = '';
            } else if ( filter.type === 'column' )
            {
                this.columnFilters[ filter.column ] = '';
            }

            this.applyFilters();
        },

        clearAllFilters ()
        {
            this.globalFilter = '';
            Object.keys( this.columnFilters ).forEach( key =>
            {
                this.columnFilters[ key ] = '';
            } );
            this.applyFilters();
        },

        // Pagination
        initializePagination ()
        {
            this.updatePagination();
        },

        updatePagination ()
        {
            this.totalItems = this.data.length;
            this.totalPages = Math.ceil( this.totalItems / this.perPage );

            // Ensure current page is valid
            if ( this.currentPage > this.totalPages && this.totalPages > 0 )
            {
                this.currentPage = this.totalPages;
            } else if ( this.currentPage < 1 )
            {
                this.currentPage = 1;
            }
        },

        goToPage ( page )
        {
            if ( page >= 1 && page <= this.totalPages )
            {
                this.currentPage = page;
            }
        },

        previousPage ()
        {
            if ( this.currentPage > 1 )
            {
                this.currentPage--;
            }
        },

        nextPage ()
        {
            if ( this.currentPage < this.totalPages )
            {
                this.currentPage++;
            }
        },

        changePerPage ( newPerPage )
        {
            this.perPage = parseInt( newPerPage );
            this.updatePagination();
            this.currentPage = 1;
        },

        // Data access methods
        get paginatedData ()
        {
            const start = ( this.currentPage - 1 ) * this.perPage;
            const end = start + this.perPage;
            return this.data.slice( start, end );
        },

        get pageInfo ()
        {
            const start = ( this.currentPage - 1 ) * this.perPage + 1;
            const end = Math.min( this.currentPage * this.perPage, this.totalItems );
            return `${ start }-${ end } of ${ this.totalItems }`;
        },

        get pageNumbers ()
        {
            const pages = [];
            const maxVisible = 7;
            let start = Math.max( 1, this.currentPage - Math.floor( maxVisible / 2 ) );
            let end = Math.min( this.totalPages, start + maxVisible - 1 );

            // Adjust start if we're near the end
            if ( end - start + 1 < maxVisible )
            {
                start = Math.max( 1, end - maxVisible + 1 );
            }

            for ( let i = start; i <= end; i++ )
            {
                pages.push( i );
            }

            return pages;
        },

        // Row selection
        toggleRowSelection ( rowIndex )
        {
            const globalIndex = ( this.currentPage - 1 ) * this.perPage + rowIndex;

            if ( this.selectedRows.has( globalIndex ) )
            {
                this.selectedRows.delete( globalIndex );
            } else
            {
                this.selectedRows.add( globalIndex );
            }

            this.updateSelectAllState();
        },

        toggleSelectAll ()
        {
            if ( this.selectAll )
            {
                // Deselect all visible rows
                for ( let i = 0; i < this.paginatedData.length; i++ )
                {
                    const globalIndex = ( this.currentPage - 1 ) * this.perPage + i;
                    this.selectedRows.delete( globalIndex );
                }
            } else
            {
                // Select all visible rows
                for ( let i = 0; i < this.paginatedData.length; i++ )
                {
                    const globalIndex = ( this.currentPage - 1 ) * this.perPage + i;
                    this.selectedRows.add( globalIndex );
                }
            }

            this.updateSelectAllState();
        },

        updateSelectAllState ()
        {
            const visibleRowCount = this.paginatedData.length;
            let selectedVisibleCount = 0;

            for ( let i = 0; i < visibleRowCount; i++ )
            {
                const globalIndex = ( this.currentPage - 1 ) * this.perPage + i;
                if ( this.selectedRows.has( globalIndex ) )
                {
                    selectedVisibleCount++;
                }
            }

            this.selectAll = selectedVisibleCount === visibleRowCount && visibleRowCount > 0;
        },

        getSelectedData ()
        {
            return Array.from( this.selectedRows ).map( index => this.data[ index ] );
        },

        // Column management
        toggleColumnVisibility ( columnKey )
        {
            if ( this.visibleColumns.has( columnKey ) )
            {
                this.visibleColumns.delete( columnKey );
            } else
            {
                this.visibleColumns.add( columnKey );
            }
        },

        reorderColumns ( newOrder )
        {
            this.columnOrder = newOrder;
        },

        get orderedVisibleColumns ()
        {
            return this.columnOrder
                .filter( key => this.visibleColumns.has( key ) )
                .map( key => this.columns.find( col => col.key === key ) );
        },

        // Export functionality
        exportData ( format = 'csv' )
        {
            const data = this.getSelectedData().length > 0 ? this.getSelectedData() : this.data;
            const timestamp = new Date().toISOString().slice( 0, 19 ).replace( /:/g, '-' );
            const filename = `data-export-${ timestamp }`;

            switch ( format )
            {
                case 'csv':
                    this.exportCSV( data, filename );
                    break;
                case 'json':
                    this.exportJSON( data, filename );
                    break;
                case 'excel':
                    this.exportExcel( data, filename );
                    break;
                default:
                    console.error( 'Unsupported export format:', format );
            }
        },

        exportCSV ( data, filename )
        {
            const headers = this.orderedVisibleColumns.map( col => col.label );
            const rows = data.map( row =>
                this.orderedVisibleColumns.map( col =>
                {
                    const value = row[ col.key ];
                    return typeof value === 'string' && value.includes( ',' )
                        ? `"${ value.replace( /"/g, '""' ) }"`
                        : value;
                } )
            );

            const csvContent = [ headers, ...rows ]
                .map( row => row.join( ',' ) )
                .join( '\n' );

            this.downloadFile( csvContent, `${ filename }.csv`, 'text/csv' );
        },

        exportJSON ( data, filename )
        {
            const jsonContent = JSON.stringify( data, null, 2 );
            this.downloadFile( jsonContent, `${ filename }.json`, 'application/json' );
        },

        exportExcel ( data, filename )
        {
            // Simplified Excel export (would need a library like xlsx for full functionality)
            const csvContent = this.generateCSVContent( data );
            this.downloadFile( csvContent, `${ filename }.xlsx`, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
        },

        // Utility for tests and simplified exports: generate CSV content string from data
        generateCSVContent ( data )
        {
            const headers = this.orderedVisibleColumns.map( col => col.label );
            const rows = data.map( row =>
                this.orderedVisibleColumns.map( col =>
                {
                    const value = row[ col.key ];
                    if ( value === undefined || value === null ) return '';
                    const str = String( value );
                    return str.includes( ',' ) ? `"${ str.replace( /"/g, '""' ) }"` : str;
                } )
            );
            return [ headers, ...rows ].map( r => r.join( ',' ) ).join( '\n' );
        },

        downloadFile ( content, filename, mimeType )
        {
            const blob = new Blob( [ content ], { type: mimeType } );
            const url = URL.createObjectURL( blob );
            const link = document.createElement( 'a' );

            link.href = url;
            link.download = filename;
            document.body.appendChild( link );
            link.click();
            document.body.removeChild( link );
            URL.revokeObjectURL( url );
        },

        // Real-time updates
        startAutoRefresh ()
        {
            if ( this.refreshInterval )
            {
                clearInterval( this.refreshInterval );
            }

            this.refreshInterval = setInterval( () =>
            {
                this.refreshData();
            }, this.refreshRate );
        },

        stopAutoRefresh ()
        {
            if ( this.refreshInterval )
            {
                clearInterval( this.refreshInterval );
                this.refreshInterval = null;
            }
        },

        async refreshData ()
        {
            if ( !config.dataUrl ) return;

            try
            {
                this.loading = true;
                const response = await fetch( config.dataUrl );
                const newData = await response.json();

                this.originalData = newData;
                this.applyFilters();
                this.applySort();

                this.error = null;
            } catch ( error )
            {
                console.error( 'Failed to refresh data:', error );
                this.error = 'Failed to refresh data';
            } finally
            {
                this.loading = false;
            }
        },

        // WebSocket integration
        initializeWebSocket ( url )
        {
            try
            {
                this.websocket = new WebSocket( url );

                this.websocket.onopen = () =>
                {
                    this.wsConnected = true;
                    console.log( '✅ WebSocket connected for real-time updates' );
                };

                this.websocket.onmessage = ( event ) =>
                {
                    const message = JSON.parse( event.data );
                    this.handleWebSocketMessage( message );
                };

                this.websocket.onclose = () =>
                {
                    this.wsConnected = false;
                    console.log( '❌ WebSocket disconnected' );

                    // Attempt to reconnect after 5 seconds
                    setTimeout( () =>
                    {
                        this.initializeWebSocket( url );
                    }, 5000 );
                };

                this.websocket.onerror = ( error ) =>
                {
                    console.error( 'WebSocket error:', error );
                };
            } catch ( error )
            {
                console.error( 'Failed to initialize WebSocket:', error );
            }
        },

        handleWebSocketMessage ( message )
        {
            switch ( message.type )
            {
                case 'data_update':
                    this.updateDataItem( message.data );
                    break;
                case 'data_insert':
                    this.insertDataItem( message.data );
                    break;
                case 'data_delete':
                    this.deleteDataItem( message.id );
                    break;
                case 'full_refresh':
                    this.refreshData();
                    break;
                default:
                    console.log( 'Unknown WebSocket message type:', message.type );
            }
        },

        updateDataItem ( updatedItem )
        {
            const index = this.originalData.findIndex( item => item.id === updatedItem.id );
            if ( index !== -1 )
            {
                this.originalData[ index ] = updatedItem;
                this.applyFilters();
                this.applySort();
            }
        },

        insertDataItem ( newItem )
        {
            this.originalData.push( newItem );
            this.applyFilters();
            this.applySort();
        },

        deleteDataItem ( itemId )
        {
            this.originalData = this.originalData.filter( item => item.id !== itemId );
            this.applyFilters();
            this.applySort();
        },

        // Keyboard shortcuts
        setupKeyboardShortcuts ()
        {
            document.addEventListener( 'keydown', ( e ) =>
            {
                // Only handle shortcuts when the table container is focused
                if ( !this.$el.contains( document.activeElement ) ) return;

                if ( e.ctrlKey || e.metaKey )
                {
                    switch ( e.key )
                    {
                        case 'f':
                            e.preventDefault();
                            this.$refs.globalFilter?.focus();
                            break;
                        case 'a':
                            e.preventDefault();
                            this.toggleSelectAll();
                            break;
                        case 'e':
                            e.preventDefault();
                            this.exportData( 'csv' );
                            break;
                        case 'r':
                            e.preventDefault();
                            this.refreshData();
                            break;
                    }
                }

                // Navigation shortcuts
                switch ( e.key )
                {
                    case 'ArrowLeft':
                        if ( e.ctrlKey )
                        {
                            e.preventDefault();
                            this.previousPage();
                        }
                        break;
                    case 'ArrowRight':
                        if ( e.ctrlKey )
                        {
                            e.preventDefault();
                            this.nextPage();
                        }
                        break;
                }
            } );
        },

        // Utility methods
        formatCellValue ( value, column )
        {
            if ( value === null || value === undefined ) return '-';

            switch ( column.type )
            {
                case 'number':
                    return typeof value === 'number' ? value.toLocaleString() : value;
                case 'date':
                    return new Date( value ).toLocaleDateString();
                case 'boolean':
                    return value ? '✓' : '✗';
                case 'email':
                    return `<a href="mailto:${ value }" class="text-blue-600 hover:underline">${ value }</a>`;
                case 'url':
                    return `<a href="${ value }" target="_blank" class="text-blue-600 hover:underline">Link</a>`;
                default:
                    return value;
            }
        },

        getCellClass ( value, column )
        {
            const baseClass = 'px-4 py-3 text-sm';

            switch ( column.type )
            {
                case 'number':
                    return `${ baseClass } text-right`;
                case 'boolean':
                    return `${ baseClass } text-center`;
                default:
                    return baseClass;
            }
        },

        // Cleanup
        destroy ()
        {
            this.stopAutoRefresh();

            if ( this.websocket )
            {
                this.websocket.close();
            }
        }
    };
};

// Advanced Data Table with Inline Editing
window.editableDataTable = function ( config = {} )
{
    const baseTable = window.dataTable( config );

    return {
        ...baseTable,

        // Editing state
        editingCell: null,
        editingValue: '',
        editingOriginalValue: '',

        // Inline editing methods
        startEditing ( rowIndex, columnKey )
        {
            const globalIndex = ( this.currentPage - 1 ) * this.perPage + rowIndex;
            const currentValue = this.data[ globalIndex ][ columnKey ];

            this.editingCell = `${ globalIndex }-${ columnKey }`;
            this.editingValue = currentValue;
            this.editingOriginalValue = currentValue;

            // Focus the input after Vue updates
            this.$nextTick( () =>
            {
                const input = this.$refs[ `edit-${ globalIndex }-${ columnKey }` ];
                if ( input )
                {
                    input.focus();
                    input.select();
                }
            } );
        },

        cancelEditing ()
        {
            this.editingCell = null;
            this.editingValue = '';
            this.editingOriginalValue = '';
        },

        async saveEdit ( rowIndex, columnKey )
        {
            const globalIndex = ( this.currentPage - 1 ) * this.perPage + rowIndex;
            const oldValue = this.editingOriginalValue;
            const newValue = this.editingValue;

            if ( oldValue === newValue )
            {
                this.cancelEditing();
                return;
            }

            try
            {
                // Validate the new value
                const column = this.columns.find( col => col.key === columnKey );
                if ( !this.validateCellValue( newValue, column ) )
                {
                    throw new Error( `Invalid value for ${ column.label }` );
                }

                // Call the update API if configured
                if ( config.updateUrl )
                {
                    const item = this.data[ globalIndex ];
                    await this.updateItemOnServer( item.id, columnKey, newValue );
                }

                // Update the local data
                this.data[ globalIndex ][ columnKey ] = newValue;
                this.originalData[ globalIndex ][ columnKey ] = newValue;

                this.cancelEditing();

                // Emit update event
                this.$dispatch( 'cell-updated', {
                    rowIndex: globalIndex,
                    column: columnKey,
                    oldValue,
                    newValue
                } );

            } catch ( error )
            {
                console.error( 'Failed to save edit:', error );
                this.error = error.message;

                // Keep editing mode active so user can correct the value
            }
        },

        validateCellValue ( value, column )
        {
            switch ( column.type )
            {
                case 'number':
                    return !isNaN( parseFloat( value ) );
                case 'email':
                    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( value );
                case 'url':
                    try
                    {
                        new URL( value );
                        return true;
                    } catch
                    {
                        return false;
                    }
                default:
                    return true;
            }
        },

        async updateItemOnServer ( itemId, column, value )
        {
            const response = await fetch( `${ config.updateUrl }/${ itemId }`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' )?.content
                },
                body: JSON.stringify( {
                    [ column ]: value
                } )
            } );

            if ( !response.ok )
            {
                throw new Error( `Failed to update: ${ response.statusText }` );
            }

            return response.json();
        },

        isEditing ( rowIndex, columnKey )
        {
            const globalIndex = ( this.currentPage - 1 ) * this.perPage + rowIndex;
            return this.editingCell === `${ globalIndex }-${ columnKey }`;
        }
    };
};

// Initialize component registration
if ( typeof window.registerAlpineComponent === 'function' )
{
    window.registerAlpineComponent( 'dataTable', window.dataTable );
    window.registerAlpineComponent( 'editableDataTable', window.editableDataTable );
}

console.log( '✅ Interactive Data Tables component loaded' );
