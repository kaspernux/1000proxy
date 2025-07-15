/**
 * Accessibility Enhanced Data Tables
 * Comprehensive accessibility implementation with ARIA support, keyboard navigation,
 * and screen reader compatibility for all data table components
 */

window.accessibleDataTable = function ( config = {} )
{
    return {
        // Configuration
        dataUrl: config.dataUrl || '/api/data',
        columns: config.columns || [],
        pageSize: config.pageSize || 25,
        sortable: config.sortable !== false,
        filterable: config.filterable !== false,
        selectable: config.selectable !== false,

        // Accessibility specific config
        announceChanges: config.announceChanges !== false,
        keyboardNavigation: config.keyboardNavigation !== false,
        screenReaderOptimized: config.screenReaderOptimized !== false,

        // State
        data: [],
        filteredData: [],
        currentPage: 1,
        totalPages: 1,
        sortColumn: null,
        sortDirection: 'asc',
        loading: false,
        selectedRows: new Set(),

        // Accessibility state
        currentFocusRow: 0,
        currentFocusCell: 0,
        announcer: null,
        tableRole: 'table',

        // Screen reader announcements
        announcements: {
            loading: 'Loading table data',
            loaded: 'Table data loaded',
            sorted: ( column, direction ) => `Table sorted by ${ column } ${ direction === 'asc' ? 'ascending' : 'descending' }`,
            filtered: ( count ) => `Table filtered, showing ${ count } results`,
            selected: ( count ) => `${ count } rows selected`,
            navigated: ( row, cell ) => `Navigated to row ${ row + 1 }, column ${ cell + 1 }`
        },

        init ()
        {
            this.setupAccessibilityFeatures();
            this.loadData();
            this.setupKeyboardHandlers();
            this.setupScreenReaderSupport();
            this.setupAriaLabels();
            this.setupFocusManagement();
        },

        setupAccessibilityFeatures ()
        {
            // Create live region for announcements
            this.announcer = document.createElement( 'div' );
            this.announcer.setAttribute( 'aria-live', 'polite' );
            this.announcer.setAttribute( 'aria-atomic', 'true' );
            this.announcer.style.position = 'absolute';
            this.announcer.style.left = '-10000px';
            this.announcer.style.width = '1px';
            this.announcer.style.height = '1px';
            this.announcer.style.overflow = 'hidden';
            document.body.appendChild( this.announcer );
        },

        setupAriaLabels ()
        {
            // Set up table ARIA attributes
            this.$nextTick( () =>
            {
                const table = this.$el.querySelector( 'table' );
                if ( table )
                {
                    table.setAttribute( 'role', 'table' );
                    table.setAttribute( 'aria-label', config.ariaLabel || 'Data table' );
                    table.setAttribute( 'aria-rowcount', this.filteredData.length + 1 ); // +1 for header

                    // Setup header accessibility
                    const headers = table.querySelectorAll( 'th' );
                    headers.forEach( ( header, index ) =>
                    {
                        header.setAttribute( 'role', 'columnheader' );
                        header.setAttribute( 'scope', 'col' );
                        header.setAttribute( 'tabindex', '0' );

                        if ( this.columns[ index ]?.sortable )
                        {
                            header.setAttribute( 'aria-sort', this.getSortAriaValue( this.columns[ index ].key ) );
                            header.setAttribute( 'aria-label',
                                `${ this.columns[ index ].label }, column header, sortable. ${ this.getSortInstruction( this.columns[ index ].key ) }` );
                        } else
                        {
                            header.setAttribute( 'aria-label', `${ this.columns[ index ]?.label || '' }, column header` );
                        }
                    } );

                    // Setup row accessibility
                    const rows = table.querySelectorAll( 'tbody tr' );
                    rows.forEach( ( row, rowIndex ) =>
                    {
                        row.setAttribute( 'role', 'row' );
                        row.setAttribute( 'aria-rowindex', rowIndex + 2 ); // +2 for 1-based and header
                        row.setAttribute( 'tabindex', rowIndex === 0 ? '0' : '-1' );

                        if ( this.selectable )
                        {
                            row.setAttribute( 'aria-selected', this.selectedRows.has( rowIndex ) ? 'true' : 'false' );
                        }

                        // Setup cell accessibility
                        const cells = row.querySelectorAll( 'td' );
                        cells.forEach( ( cell, cellIndex ) =>
                        {
                            cell.setAttribute( 'role', 'gridcell' );
                            cell.setAttribute( 'aria-describedby', `col-${ cellIndex }-desc` );

                            // Add column description for screen readers
                            if ( !document.getElementById( `col-${ cellIndex }-desc` ) )
                            {
                                const desc = document.createElement( 'div' );
                                desc.id = `col-${ cellIndex }-desc`;
                                desc.className = 'sr-only';
                                desc.textContent = this.columns[ cellIndex ]?.label || `Column ${ cellIndex + 1 }`;
                                document.body.appendChild( desc );
                            }
                        } );
                    } );
                }
            } );
        },

        setupKeyboardHandlers ()
        {
            this.$el.addEventListener( 'keydown', ( e ) =>
            {
                if ( !this.keyboardNavigation ) return;

                const table = this.$el.querySelector( 'table' );
                const currentFocus = document.activeElement;

                switch ( e.key )
                {
                    case 'ArrowUp':
                        e.preventDefault();
                        this.navigateVertical( -1 );
                        break;
                    case 'ArrowDown':
                        e.preventDefault();
                        this.navigateVertical( 1 );
                        break;
                    case 'ArrowLeft':
                        e.preventDefault();
                        this.navigateHorizontal( -1 );
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        this.navigateHorizontal( 1 );
                        break;
                    case 'Home':
                        e.preventDefault();
                        this.navigateToStart();
                        break;
                    case 'End':
                        e.preventDefault();
                        this.navigateToEnd();
                        break;
                    case 'Enter':
                    case ' ':
                        if ( currentFocus.tagName === 'TH' && this.sortable )
                        {
                            e.preventDefault();
                            this.handleSort( currentFocus.dataset.column );
                        } else if ( this.selectable && currentFocus.closest( 'tr' ) )
                        {
                            e.preventDefault();
                            this.toggleRowSelection( currentFocus.closest( 'tr' ) );
                        }
                        break;
                    case 'Escape':
                        this.clearSelection();
                        break;
                }
            } );
        },

        navigateVertical ( direction )
        {
            const rows = this.$el.querySelectorAll( 'tbody tr' );
            const newIndex = Math.max( 0, Math.min( rows.length - 1, this.currentFocusRow + direction ) );

            if ( newIndex !== this.currentFocusRow )
            {
                this.currentFocusRow = newIndex;
                this.focusCell( this.currentFocusRow, this.currentFocusCell );
                this.announceNavigation();
            }
        },

        navigateHorizontal ( direction )
        {
            const newIndex = Math.max( 0, Math.min( this.columns.length - 1, this.currentFocusCell + direction ) );

            if ( newIndex !== this.currentFocusCell )
            {
                this.currentFocusCell = newIndex;
                this.focusCell( this.currentFocusRow, this.currentFocusCell );
                this.announceNavigation();
            }
        },

        navigateToStart ()
        {
            this.currentFocusRow = 0;
            this.currentFocusCell = 0;
            this.focusCell( 0, 0 );
            this.announceNavigation();
        },

        navigateToEnd ()
        {
            const rows = this.$el.querySelectorAll( 'tbody tr' );
            this.currentFocusRow = Math.max( 0, rows.length - 1 );
            this.currentFocusCell = Math.max( 0, this.columns.length - 1 );
            this.focusCell( this.currentFocusRow, this.currentFocusCell );
            this.announceNavigation();
        },

        focusCell ( rowIndex, cellIndex )
        {
            const rows = this.$el.querySelectorAll( 'tbody tr' );
            if ( rows[ rowIndex ] )
            {
                const cells = rows[ rowIndex ].querySelectorAll( 'td' );
                if ( cells[ cellIndex ] )
                {
                    // Remove previous focus
                    rows.forEach( row => row.setAttribute( 'tabindex', '-1' ) );

                    // Set new focus
                    rows[ rowIndex ].setAttribute( 'tabindex', '0' );
                    rows[ rowIndex ].focus();
                }
            }
        },

        setupScreenReaderSupport ()
        {
            // Enhanced descriptions for screen readers
            this.$nextTick( () =>
            {
                const table = this.$el.querySelector( 'table' );
                if ( table )
                {
                    // Add table summary
                    let summary = `Data table with ${ this.filteredData.length } rows and ${ this.columns.length } columns.`;
                    if ( this.sortable ) summary += ' Use arrow keys to navigate and Enter to sort columns.';
                    if ( this.selectable ) summary += ' Use Space to select rows.';

                    table.setAttribute( 'aria-describedby', 'table-summary' );

                    if ( !document.getElementById( 'table-summary' ) )
                    {
                        const summaryDiv = document.createElement( 'div' );
                        summaryDiv.id = 'table-summary';
                        summaryDiv.className = 'sr-only';
                        summaryDiv.textContent = summary;
                        table.parentNode.insertBefore( summaryDiv, table );
                    }
                }
            } );
        },

        setupFocusManagement ()
        {
            // Setup focus trap when in keyboard navigation mode
            this.$el.addEventListener( 'focusin', () =>
            {
                if ( this.keyboardNavigation )
                {
                    this.updateFocusPosition();
                }
            } );
        },

        updateFocusPosition ()
        {
            const focusedRow = document.activeElement.closest( 'tr' );
            if ( focusedRow )
            {
                const rows = this.$el.querySelectorAll( 'tbody tr' );
                this.currentFocusRow = Array.from( rows ).indexOf( focusedRow );
            }
        },

        getSortAriaValue ( columnKey )
        {
            if ( this.sortColumn === columnKey )
            {
                return this.sortDirection === 'asc' ? 'ascending' : 'descending';
            }
            return 'none';
        },

        getSortInstruction ( columnKey )
        {
            if ( this.sortColumn === columnKey )
            {
                return this.sortDirection === 'asc'
                    ? 'Press Enter to sort descending'
                    : 'Press Enter to sort ascending';
            }
            return 'Press Enter to sort';
        },

        announce ( message )
        {
            if ( this.announceChanges && this.announcer )
            {
                this.announcer.textContent = message;
            }
        },

        announceNavigation ()
        {
            const message = this.announcements.navigated( this.currentFocusRow, this.currentFocusCell );
            this.announce( message );
        },

        handleSort ( column )
        {
            const oldColumn = this.sortColumn;
            const oldDirection = this.sortDirection;

            if ( this.sortColumn === column )
            {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else
            {
                this.sortColumn = column;
                this.sortDirection = 'asc';
            }

            this.sortData();
            this.setupAriaLabels(); // Update ARIA labels after sort

            const message = this.announcements.sorted( column, this.sortDirection );
            this.announce( message );
        },

        sortData ()
        {
            this.filteredData.sort( ( a, b ) =>
            {
                const aValue = a[ this.sortColumn ];
                const bValue = b[ this.sortColumn ];

                if ( aValue < bValue ) return this.sortDirection === 'asc' ? -1 : 1;
                if ( aValue > bValue ) return this.sortDirection === 'asc' ? 1 : -1;
                return 0;
            } );
        },

        toggleRowSelection ( row )
        {
            if ( !this.selectable ) return;

            const rowIndex = Array.from( this.$el.querySelectorAll( 'tbody tr' ) ).indexOf( row );

            if ( this.selectedRows.has( rowIndex ) )
            {
                this.selectedRows.delete( rowIndex );
                row.setAttribute( 'aria-selected', 'false' );
            } else
            {
                this.selectedRows.add( rowIndex );
                row.setAttribute( 'aria-selected', 'true' );
            }

            const message = this.announcements.selected( this.selectedRows.size );
            this.announce( message );
        },

        clearSelection ()
        {
            this.selectedRows.clear();
            this.$el.querySelectorAll( 'tbody tr' ).forEach( row =>
            {
                row.setAttribute( 'aria-selected', 'false' );
            } );
            this.announce( 'Selection cleared' );
        },

        async loadData ()
        {
            this.loading = true;
            this.announce( this.announcements.loading );

            try
            {
                const response = await fetch( this.dataUrl );
                this.data = await response.json();
                this.filteredData = [ ...this.data ];
                this.calculatePagination();

                this.$nextTick( () =>
                {
                    this.setupAriaLabels();
                    this.setupScreenReaderSupport();
                } );

                this.announce( this.announcements.loaded );
            } catch ( error )
            {
                console.error( 'Error loading data:', error );
                this.announce( 'Error loading table data' );
            } finally
            {
                this.loading = false;
            }
        },

        calculatePagination ()
        {
            this.totalPages = Math.ceil( this.filteredData.length / this.pageSize );
        },

        // Enhanced filter with accessibility announcements
        filterData ( searchTerm )
        {
            this.filteredData = this.data.filter( item =>
            {
                return Object.values( item ).some( value =>
                    value.toString().toLowerCase().includes( searchTerm.toLowerCase() )
                );
            } );

            this.currentPage = 1;
            this.calculatePagination();

            this.$nextTick( () =>
            {
                this.setupAriaLabels();
            } );

            const message = this.announcements.filtered( this.filteredData.length );
            this.announce( message );
        },

        // Get table data for current page with accessibility context
        get paginatedData ()
        {
            const start = ( this.currentPage - 1 ) * this.pageSize;
            const end = start + this.pageSize;
            return this.filteredData.slice( start, end );
        },

        // Accessibility-aware pagination
        goToPage ( page )
        {
            if ( page >= 1 && page <= this.totalPages )
            {
                this.currentPage = page;
                this.currentFocusRow = 0; // Reset focus to first row
                this.currentFocusCell = 0;

                this.$nextTick( () =>
                {
                    this.setupAriaLabels();
                    this.focusCell( 0, 0 );
                } );

                this.announce( `Navigated to page ${ page } of ${ this.totalPages }` );
            }
        },

        // Cleanup when component is destroyed
        destroy ()
        {
            if ( this.announcer && this.announcer.parentNode )
            {
                this.announcer.parentNode.removeChild( this.announcer );
            }

            // Remove column descriptions
            this.columns.forEach( ( col, index ) =>
            {
                const desc = document.getElementById( `col-${ index }-desc` );
                if ( desc && desc.parentNode )
                {
                    desc.parentNode.removeChild( desc );
                }
            } );
        }
    };
};

// Enhanced table template with accessibility features
window.accessibleTableTemplate = `
<div class="accessible-data-table" x-data="accessibleDataTable(tableConfig)" x-init="init()">
    <!-- Screen reader table description -->
    <div id="table-description" class="sr-only">
        <span x-text="'Data table with ' + filteredData.length + ' rows and ' + columns.length + ' columns'"></span>
        <span x-show="sortable">Use arrow keys to navigate and Enter to sort columns.</span>
        <span x-show="selectable">Use Space to select rows.</span>
    </div>

    <!-- Loading state with accessibility -->
    <div x-show="loading" class="flex items-center justify-center p-4" role="status" aria-live="polite">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <span class="ml-2">Loading table data...</span>
    </div>

    <!-- Table with full accessibility support -->
    <div x-show="!loading" class="overflow-x-auto">
        <table
            class="min-w-full bg-white border border-gray-200 rounded-lg"
            role="table"
            :aria-label="config.ariaLabel || 'Data table'"
            :aria-rowcount="filteredData.length + 1"
            aria-describedby="table-description"
        >
            <!-- Enhanced accessible header -->
            <thead class="bg-gray-50">
                <tr role="row">
                    <template x-for="(column, index) in columns" :key="column.key">
                        <th
                            :class="['px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 focus:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500',
                                    column.sortable ? 'cursor-pointer' : '']"
                            role="columnheader"
                            scope="col"
                            tabindex="0"
                            :data-column="column.key"
                            :aria-sort="getSortAriaValue(column.key)"
                            :aria-label="column.label + ', column header' + (column.sortable ? ', sortable. ' + getSortInstruction(column.key) : '')"
                            @click="column.sortable && handleSort(column.key)"
                            @keydown.enter="column.sortable && handleSort(column.key)"
                            @keydown.space.prevent="column.sortable && handleSort(column.key)"
                        >
                            <div class="flex items-center space-x-1">
                                <span x-text="column.label"></span>
                                <template x-if="column.sortable">
                                    <div class="flex flex-col">
                                        <svg class="w-3 h-3 text-gray-400"
                                             :class="{'text-blue-600': sortColumn === column.key && sortDirection === 'asc'}"
                                             viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                        </svg>
                                        <svg class="w-3 h-3 text-gray-400"
                                             :class="{'text-blue-600': sortColumn === column.key && sortDirection === 'desc'}"
                                             viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </template>
                            </div>
                        </th>
                    </template>
                </tr>
            </thead>

            <!-- Enhanced accessible table body -->
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="(item, rowIndex) in paginatedData" :key="item.id || rowIndex">
                    <tr
                        class="hover:bg-gray-50 focus:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :class="{'bg-blue-50': selectable && selectedRows.has(rowIndex)}"
                        role="row"
                        :aria-rowindex="rowIndex + 2"
                        :tabindex="rowIndex === 0 ? '0' : '-1'"
                        :aria-selected="selectable ? (selectedRows.has(rowIndex) ? 'true' : 'false') : null"
                        @click="selectable && toggleRowSelection($event.currentTarget)"
                        @keydown.space.prevent="selectable && toggleRowSelection($event.currentTarget)"
                    >
                        <template x-for="(column, cellIndex) in columns" :key="column.key">
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                role="gridcell"
                                :aria-describedby="'col-' + cellIndex + '-desc'"
                            >
                                <span x-text="item[column.key]"></span>
                            </td>
                        </template>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Accessible pagination -->
    <div x-show="totalPages > 1" class="mt-4 flex items-center justify-between" role="navigation" aria-label="Table pagination">
        <div class="text-sm text-gray-700" aria-live="polite">
            <span>Showing page </span>
            <span x-text="currentPage"></span>
            <span> of </span>
            <span x-text="totalPages"></span>
        </div>

        <div class="flex space-x-2">
            <button
                @click="goToPage(currentPage - 1)"
                :disabled="currentPage === 1"
                :class="['px-3 py-1 text-sm border rounded',
                        currentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500']"
                :aria-label="'Go to page ' + (currentPage - 1)"
                aria-describedby="prev-page-desc"
            >
                Previous
            </button>

            <button
                @click="goToPage(currentPage + 1)"
                :disabled="currentPage === totalPages"
                :class="['px-3 py-1 text-sm border rounded',
                        currentPage === totalPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500']"
                :aria-label="'Go to page ' + (currentPage + 1)"
                aria-describedby="next-page-desc"
            >
                Next
            </button>
        </div>
    </div>

    <!-- Hidden descriptions for screen readers -->
    <div id="prev-page-desc" class="sr-only">Navigate to previous page of results</div>
    <div id="next-page-desc" class="sr-only">Navigate to next page of results</div>
</div>
`;

// CSS for accessibility features
const accessibilityCSS = `
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.accessible-data-table table:focus-within {
    box-shadow: 0 0 0 2px #3B82F6;
}

.accessible-data-table th:focus,
.accessible-data-table tr:focus {
    outline: 2px solid #3B82F6;
    outline-offset: -2px;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .accessible-data-table {
        --table-border-color: #000;
        --table-focus-color: #0000FF;
    }

    .accessible-data-table table {
        border-color: var(--table-border-color);
    }

    .accessible-data-table th:focus,
    .accessible-data-table tr:focus {
        outline-color: var(--table-focus-color);
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .accessible-data-table * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
`;

// Inject accessibility CSS
if ( !document.getElementById( 'accessibility-table-styles' ) )
{
    const style = document.createElement( 'style' );
    style.id = 'accessibility-table-styles';
    style.textContent = accessibilityCSS;
    document.head.appendChild( style );
}
