/**
 * Interactive Data Table Component
 * Advanced data table with filtering, sorting, pagination, bulk actions, and more
 */

export default function interactiveDataTable ()
{
    return {
        // Core Data
        data: [],
        originalData: [],
        columns: [],
        loading: false,
        error: null,

        // Pagination
        currentPage: 1,
        perPage: 10,
        totalItems: 0,
        totalPages: 1,
        perPageOptions: [ 5, 10, 25, 50, 100 ],
        infiniteScroll: false,

        // Sorting
        sortColumn: null,
        sortDirection: 'asc',
        sortFunctions: {},

        // Filtering
        filters: {},
        activeFilters: {},
        globalSearch: '',
        searchableColumns: [],

        // Selection
        selectedRows: new Set(),
        selectAll: false,
        selectAllIndeterminate: false,

        // Column Management
        visibleColumns: new Set(),
        columnOrder: [],
        resizableColumns: true,

        // Bulk Actions
        bulkActions: [],
        showBulkActions: false,
        bulkActionLoading: false,

        // Inline Editing
        editingRows: new Set(),
        editingData: {},
        validationErrors: {},

        // Export
        exportFormats: [ 'csv', 'excel', 'pdf' ],
        exportLoading: false,

        // Configuration
        config: {
            striped: true,
            bordered: true,
            hover: true,
            compact: false,
            responsive: true,
            virtualScroll: false,
            autoRefresh: false,
            refreshInterval: 30000
        },

        // Auto Refresh
        refreshTimer: null,

        init ()
        {
            this.initializeTable();
            this.setupEventListeners();
            this.startAutoRefresh();
        },

        initializeTable ()
        {
            // Initialize visible columns
            this.columns.forEach( column =>
            {
                if ( column.visible !== false )
                {
                    this.visibleColumns.add( column.key );
                }
            } );

            // Set column order
            this.columnOrder = this.columns.map( col => col.key );

            // Initialize searchable columns
            this.searchableColumns = this.columns
                .filter( col => col.searchable !== false )
                .map( col => col.key );

            // Calculate initial pagination
            this.calculatePagination();

            // Apply initial sorting if specified
            const defaultSort = this.columns.find( col => col.defaultSort );
            if ( defaultSort )
            {
                this.sortColumn = defaultSort.key;
                this.sortDirection = defaultSort.defaultSort;
                this.applySorting();
            }

            // Apply initial filters
            this.applyFilters();
        },

        setupEventListeners ()
        {
            // Keyboard shortcuts
            document.addEventListener( 'keydown', ( e ) =>
            {
                if ( e.ctrlKey || e.metaKey )
                {
                    switch ( e.key )
                    {
                        case 'f':
                            e.preventDefault();
                            this.$refs.globalSearch?.focus();
                            break;
                        case 'a':
                            if ( this.$refs.table?.contains( document.activeElement ) )
                            {
                                e.preventDefault();
                                this.toggleSelectAll();
                            }
                            break;
                        case 'e':
                            if ( this.selectedRows.size > 0 )
                            {
                                e.preventDefault();
                                this.exportSelected();
                            }
                            break;
                    }
                }
            } );

            // Infinite scroll
            if ( this.infiniteScroll )
            {
                this.$refs.tableContainer?.addEventListener( 'scroll', this.handleScroll.bind( this ) );
            }
        },

        startAutoRefresh ()
        {
            if ( this.config.autoRefresh && this.config.refreshInterval > 0 )
            {
                this.refreshTimer = setInterval( () =>
                {
                    this.refreshData();
                }, this.config.refreshInterval );
            }
        },

        stopAutoRefresh ()
        {
            if ( this.refreshTimer )
            {
                clearInterval( this.refreshTimer );
                this.refreshTimer = null;
            }
        },

        // Data Management
        async loadData ( url = null, params = {} )
        {
            this.loading = true;
            this.error = null;

            try
            {
                const queryParams = {
                    page: this.currentPage,
                    per_page: this.perPage,
                    sort_column: this.sortColumn,
                    sort_direction: this.sortDirection,
                    search: this.globalSearch,
                    filters: JSON.stringify( this.activeFilters ),
                    ...params
                };

                const response = await fetch( url || this.dataUrl, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' )?.getAttribute( 'content' )
                    },
                    body: new URLSearchParams( queryParams )
                } );

                if ( !response.ok )
                {
                    throw new Error( `HTTP ${ response.status }: ${ response.statusText }` );
                }

                const result = await response.json();

                if ( this.infiniteScroll && this.currentPage > 1 )
                {
                    this.data = [ ...this.data, ...result.data ];
                } else
                {
                    this.data = result.data;
                    this.originalData = [ ...result.data ];
                }

                this.totalItems = result.total || result.data.length;
                this.currentPage = result.current_page || this.currentPage;
                this.calculatePagination();

            } catch ( error )
            {
                this.error = error.message;
                console.error( 'Data loading error:', error );
            } finally
            {
                this.loading = false;
            }
        },

        async refreshData ()
        {
            await this.loadData();
        },

        // Sorting
        sortBy ( column )
        {
            if ( this.sortColumn === column )
            {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else
            {
                this.sortColumn = column;
                this.sortDirection = 'asc';
            }

            this.applySorting();
        },

        applySorting ()
        {
            if ( !this.sortColumn ) return;

            const column = this.columns.find( col => col.key === this.sortColumn );
            const sortFunction = this.sortFunctions[ this.sortColumn ] || column?.sortFunction;

            if ( sortFunction )
            {
                this.data.sort( ( a, b ) =>
                {
                    const result = sortFunction( a, b );
                    return this.sortDirection === 'desc' ? -result : result;
                } );
            } else
            {
                this.data.sort( ( a, b ) =>
                {
                    const aVal = this.getNestedValue( a, this.sortColumn );
                    const bVal = this.getNestedValue( b, this.sortColumn );

                    if ( aVal < bVal ) return this.sortDirection === 'asc' ? -1 : 1;
                    if ( aVal > bVal ) return this.sortDirection === 'asc' ? 1 : -1;
                    return 0;
                } );
            }
        },

        // Filtering
        updateFilter ( column, value )
        {
            if ( value === '' || value === null || value === undefined )
            {
                delete this.activeFilters[ column ];
            } else
            {
                this.activeFilters[ column ] = value;
            }
            this.currentPage = 1;
            this.applyFilters();
        },

        updateGlobalSearch ()
        {
            this.currentPage = 1;
            this.applyFilters();
        },

        applyFilters ()
        {
            let filteredData = [ ...this.originalData ];

            // Apply global search
            if ( this.globalSearch )
            {
                const search = this.globalSearch.toLowerCase();
                filteredData = filteredData.filter( row =>
                {
                    return this.searchableColumns.some( column =>
                    {
                        const value = this.getNestedValue( row, column );
                        return String( value ).toLowerCase().includes( search );
                    } );
                } );
            }

            // Apply column filters
            Object.entries( this.activeFilters ).forEach( ( [ column, filter ] ) =>
            {
                const columnConfig = this.columns.find( col => col.key === column );
                const filterFunction = columnConfig?.filterFunction;

                if ( filterFunction )
                {
                    filteredData = filteredData.filter( row => filterFunction( row, filter ) );
                } else
                {
                    filteredData = filteredData.filter( row =>
                    {
                        const value = this.getNestedValue( row, column );
                        return String( value ).toLowerCase().includes( String( filter ).toLowerCase() );
                    } );
                }
            } );

            this.data = filteredData;
            this.totalItems = filteredData.length;
            this.calculatePagination();
        },

        clearFilters ()
        {
            this.activeFilters = {};
            this.globalSearch = '';
            this.data = [ ...this.originalData ];
            this.totalItems = this.originalData.length;
            this.currentPage = 1;
            this.calculatePagination();
        },

        // Pagination
        calculatePagination ()
        {
            this.totalPages = Math.ceil( this.totalItems / this.perPage );
            if ( this.currentPage > this.totalPages )
            {
                this.currentPage = Math.max( 1, this.totalPages );
            }
        },

        goToPage ( page )
        {
            if ( page >= 1 && page <= this.totalPages )
            {
                this.currentPage = page;
                if ( this.dataUrl )
                {
                    this.loadData();
                } else
                {
                    this.calculatePagination();
                }
            }
        },

        nextPage ()
        {
            this.goToPage( this.currentPage + 1 );
        },

        previousPage ()
        {
            this.goToPage( this.currentPage - 1 );
        },

        changePerPage ()
        {
            this.currentPage = 1;
            this.calculatePagination();
            if ( this.dataUrl )
            {
                this.loadData();
            }
        },

        get paginatedData ()
        {
            if ( this.dataUrl || this.infiniteScroll )
            {
                return this.data;
            }

            const start = ( this.currentPage - 1 ) * this.perPage;
            const end = start + this.perPage;
            return this.data.slice( start, end );
        },

        get pageInfo ()
        {
            const start = ( this.currentPage - 1 ) * this.perPage + 1;
            const end = Math.min( start + this.perPage - 1, this.totalItems );
            return {
                start,
                end,
                total: this.totalItems
            };
        },

        handleScroll ( event )
        {
            const { scrollTop, scrollHeight, clientHeight } = event.target;
            if ( scrollTop + clientHeight >= scrollHeight - 5 && !this.loading && this.currentPage < this.totalPages )
            {
                this.currentPage++;
                this.loadData();
            }
        },

        // Selection
        toggleRowSelection ( row )
        {
            const rowId = this.getRowId( row );
            if ( this.selectedRows.has( rowId ) )
            {
                this.selectedRows.delete( rowId );
            } else
            {
                this.selectedRows.add( rowId );
            }
            this.updateSelectAllState();
        },

        toggleSelectAll ()
        {
            if ( this.selectAll )
            {
                this.selectedRows.clear();
            } else
            {
                this.paginatedData.forEach( row =>
                {
                    this.selectedRows.add( this.getRowId( row ) );
                } );
            }
            this.updateSelectAllState();
        },

        updateSelectAllState ()
        {
            const visibleIds = this.paginatedData.map( row => this.getRowId( row ) );
            const selectedVisible = visibleIds.filter( id => this.selectedRows.has( id ) );

            this.selectAll = selectedVisible.length === visibleIds.length && visibleIds.length > 0;
            this.selectAllIndeterminate = selectedVisible.length > 0 && selectedVisible.length < visibleIds.length;
            this.showBulkActions = this.selectedRows.size > 0;
        },

        getRowId ( row )
        {
            return row.id || row.uuid || JSON.stringify( row );
        },

        isRowSelected ( row )
        {
            return this.selectedRows.has( this.getRowId( row ) );
        },

        clearSelection ()
        {
            this.selectedRows.clear();
            this.updateSelectAllState();
        },

        // Bulk Actions
        async executeBulkAction ( action )
        {
            if ( this.selectedRows.size === 0 ) return;

            this.bulkActionLoading = true;
            try
            {
                const selectedData = this.data.filter( row => this.selectedRows.has( this.getRowId( row ) ) );

                if ( action.confirm && !confirm( action.confirm ) )
                {
                    return;
                }

                if ( action.handler )
                {
                    await action.handler( selectedData, Array.from( this.selectedRows ) );
                } else if ( action.url )
                {
                    await this.sendBulkRequest( action.url, Array.from( this.selectedRows ) );
                }

                this.clearSelection();
                if ( action.refresh !== false )
                {
                    await this.refreshData();
                }
            } catch ( error )
            {
                console.error( 'Bulk action error:', error );
                alert( 'Bulk action failed: ' + error.message );
            } finally
            {
                this.bulkActionLoading = false;
            }
        },

        async sendBulkRequest ( url, selectedIds )
        {
            const response = await fetch( url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' )?.getAttribute( 'content' )
                },
                body: JSON.stringify( { ids: selectedIds } )
            } );

            if ( !response.ok )
            {
                throw new Error( `HTTP ${ response.status }: ${ response.statusText }` );
            }

            return await response.json();
        },

        // Inline Editing
        startEditing ( row )
        {
            const rowId = this.getRowId( row );
            this.editingRows.add( rowId );
            this.editingData[ rowId ] = { ...row };
            this.validationErrors[ rowId ] = {};
        },

        cancelEditing ( row )
        {
            const rowId = this.getRowId( row );
            this.editingRows.delete( rowId );
            delete this.editingData[ rowId ];
            delete this.validationErrors[ rowId ];
        },

        async saveEditing ( row )
        {
            const rowId = this.getRowId( row );
            const editedData = this.editingData[ rowId ];

            try
            {
                // Validate data
                const errors = await this.validateRow( editedData );
                if ( Object.keys( errors ).length > 0 )
                {
                    this.validationErrors[ rowId ] = errors;
                    return;
                }

                // Save data
                if ( this.saveRowFunction )
                {
                    await this.saveRowFunction( editedData );
                } else if ( this.saveRowUrl )
                {
                    await this.sendSaveRequest( this.saveRowUrl, editedData );
                }

                // Update local data
                const index = this.data.findIndex( r => this.getRowId( r ) === rowId );
                if ( index !== -1 )
                {
                    this.data[ index ] = { ...editedData };
                }

                this.cancelEditing( row );
            } catch ( error )
            {
                console.error( 'Save error:', error );
                this.validationErrors[ rowId ] = { _general: error.message };
            }
        },

        async validateRow ( data )
        {
            const errors = {};

            // Basic validation
            this.columns.forEach( column =>
            {
                if ( column.required && !data[ column.key ] )
                {
                    errors[ column.key ] = 'This field is required';
                }

                if ( column.validate && data[ column.key ] )
                {
                    const result = column.validate( data[ column.key ], data );
                    if ( result !== true )
                    {
                        errors[ column.key ] = result;
                    }
                }
            } );

            return errors;
        },

        async sendSaveRequest ( url, data )
        {
            const response = await fetch( url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' )?.getAttribute( 'content' )
                },
                body: JSON.stringify( data )
            } );

            if ( !response.ok )
            {
                const errorData = await response.json();
                throw new Error( errorData.message || `HTTP ${ response.status }` );
            }

            return await response.json();
        },

        isEditing ( row )
        {
            return this.editingRows.has( this.getRowId( row ) );
        },

        getEditingData ( row )
        {
            return this.editingData[ this.getRowId( row ) ] || row;
        },

        getValidationError ( row, column )
        {
            const rowId = this.getRowId( row );
            return this.validationErrors[ rowId ]?.[ column ];
        },

        // Column Management
        toggleColumnVisibility ( column )
        {
            if ( this.visibleColumns.has( column ) )
            {
                this.visibleColumns.delete( column );
            } else
            {
                this.visibleColumns.add( column );
            }
        },

        isColumnVisible ( column )
        {
            return this.visibleColumns.has( column );
        },

        moveColumn ( fromIndex, toIndex )
        {
            const column = this.columnOrder.splice( fromIndex, 1 )[ 0 ];
            this.columnOrder.splice( toIndex, 0, column );
        },

        resetColumns ()
        {
            this.visibleColumns.clear();
            this.columns.forEach( column =>
            {
                if ( column.visible !== false )
                {
                    this.visibleColumns.add( column.key );
                }
            } );
            this.columnOrder = this.columns.map( col => col.key );
        },

        // Export
        async exportData ( format = 'csv', selectedOnly = false )
        {
            this.exportLoading = true;
            try
            {
                const dataToExport = selectedOnly
                    ? this.data.filter( row => this.selectedRows.has( this.getRowId( row ) ) )
                    : this.data;

                const visibleColumnKeys = Array.from( this.visibleColumns );
                const visibleColumnsConfig = this.columns.filter( col => visibleColumnKeys.includes( col.key ) );

                if ( format === 'csv' )
                {
                    this.exportToCsv( dataToExport, visibleColumnsConfig );
                } else if ( this.exportUrl )
                {
                    await this.exportViaServer( format, dataToExport, visibleColumnsConfig );
                }
            } catch ( error )
            {
                console.error( 'Export error:', error );
                alert( 'Export failed: ' + error.message );
            } finally
            {
                this.exportLoading = false;
            }
        },

        exportToCsv ( data, columns )
        {
            const headers = columns.map( col => col.title || col.key );
            const csvData = data.map( row =>
                columns.map( col =>
                {
                    const value = this.getNestedValue( row, col.key );
                    return this.escapeCsvValue( value );
                } )
            );

            const csvContent = [ headers, ...csvData ]
                .map( row => row.join( ',' ) )
                .join( '\n' );

            this.downloadFile( csvContent, 'table-export.csv', 'text/csv' );
        },

        async exportViaServer ( format, data, columns )
        {
            const response = await fetch( this.exportUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' )?.getAttribute( 'content' )
                },
                body: JSON.stringify( {
                    format,
                    data,
                    columns: columns.map( col => ( { key: col.key, title: col.title || col.key } ) )
                } )
            } );

            if ( !response.ok )
            {
                throw new Error( `Export failed: HTTP ${ response.status }` );
            }

            const blob = await response.blob();
            const filename = response.headers.get( 'Content-Disposition' )?.match( /filename="(.+)"/ )?.[ 1 ] || `export.${ format }`;
            this.downloadBlob( blob, filename );
        },

        downloadFile ( content, filename, contentType )
        {
            const blob = new Blob( [ content ], { type: contentType } );
            this.downloadBlob( blob, filename );
        },

        downloadBlob ( blob, filename )
        {
            const url = URL.createObjectURL( blob );
            const a = document.createElement( 'a' );
            a.href = url;
            a.download = filename;
            document.body.appendChild( a );
            a.click();
            document.body.removeChild( a );
            URL.revokeObjectURL( url );
        },

        escapeCsvValue ( value )
        {
            if ( value === null || value === undefined ) return '';
            const stringValue = String( value );
            if ( stringValue.includes( ',' ) || stringValue.includes( '"' ) || stringValue.includes( '\n' ) )
            {
                return `"${ stringValue.replace( /"/g, '""' ) }"`;
            }
            return stringValue;
        },

        // Utilities
        getNestedValue ( obj, path )
        {
            return path.split( '.' ).reduce( ( current, key ) => current?.[ key ], obj );
        },

        formatValue ( value, column )
        {
            if ( column.formatter )
            {
                return column.formatter( value );
            }
            return value;
        },

        getCellClass ( row, column )
        {
            const classes = [ 'table-cell' ];
            if ( column.align ) classes.push( `text-${ column.align }` );
            if ( column.cellClass )
            {
                if ( typeof column.cellClass === 'function' )
                {
                    classes.push( column.cellClass( row, this.getNestedValue( row, column.key ) ) );
                } else
                {
                    classes.push( column.cellClass );
                }
            }
            return classes.join( ' ' );
        },

        destroy ()
        {
            this.stopAutoRefresh();
            if ( this.$refs.tableContainer )
            {
                this.$refs.tableContainer.removeEventListener( 'scroll', this.handleScroll );
            }
        }
    };
}

// Export for window usage
if ( typeof window !== 'undefined' )
{
    window.interactiveDataTable = interactiveDataTable;
}
