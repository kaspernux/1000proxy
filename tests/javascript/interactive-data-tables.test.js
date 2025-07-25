/**
 * Interactive Data Tables Test Suite
 * Comprehensive tests for data table functionality
 */

describe( 'Interactive Data Tables', () =>
{
    let container;
    let dataTable;

    const mockData = [
        { id: 1, name: 'John Doe', email: 'john@example.com', status: 'active', created_at: '2023-01-01' },
        { id: 2, name: 'Jane Smith', email: 'jane@example.com', status: 'inactive', created_at: '2023-01-02' },
        { id: 3, name: 'Bob Johnson', email: 'bob@example.com', status: 'active', created_at: '2023-01-03' }
    ];

    const mockColumns = [
        { key: 'id', label: 'ID', type: 'number', sortable: true, filterable: true },
        { key: 'name', label: 'Name', type: 'text', sortable: true, filterable: true },
        { key: 'email', label: 'Email', type: 'email', sortable: true, filterable: true },
        { key: 'status', label: 'Status', type: 'text', sortable: true, filterable: true },
        { key: 'created_at', label: 'Created', type: 'date', sortable: true, filterable: true }
    ];

    beforeEach( () =>
    {
        container = document.createElement( 'div' );
        container.innerHTML = `
            <div x-data="dataTable({
                data: ${ JSON.stringify( mockData ) },
                columns: ${ JSON.stringify( mockColumns ) },
                perPage: 10
            })">
                <table class="data-table">
                    <thead>
                        <tr>
                            <template x-for="column in orderedVisibleColumns">
                                <th x-text="column.label" @click="sort(column.key)"></th>
                            </template>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in paginatedData">
                            <tr>
                                <template x-for="column in orderedVisibleColumns">
                                    <td x-text="row[column.key]"></td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div class="pagination">
                    <span x-text="pageInfo"></span>
                    <button @click="previousPage()">Previous</button>
                    <button @click="nextPage()">Next</button>
                </div>
            </div>
        `;
        document.body.appendChild( container );

        // Initialize Alpine.js
        Alpine.start();
        dataTable = container.querySelector( '[x-data]' ).__x.$data;
    } );

    afterEach( () =>
    {
        document.body.removeChild( container );
        dataTable = null;
    } );

    describe( 'Initialization', () =>
    {
        test( 'should initialize with correct data', () =>
        {
            expect( dataTable.data ).toEqual( mockData );
            expect( dataTable.originalData ).toEqual( mockData );
            expect( dataTable.columns ).toEqual( mockColumns );
        } );

        test( 'should set up pagination correctly', () =>
        {
            expect( dataTable.currentPage ).toBe( 1 );
            expect( dataTable.perPage ).toBe( 10 );
            expect( dataTable.totalItems ).toBe( 3 );
            expect( dataTable.totalPages ).toBe( 1 );
        } );

        test( 'should initialize visible columns', () =>
        {
            expect( dataTable.visibleColumns.size ).toBe( 5 );
            expect( dataTable.visibleColumns.has( 'id' ) ).toBe( true );
            expect( dataTable.visibleColumns.has( 'name' ) ).toBe( true );
        } );
    } );

    describe( 'Sorting', () =>
    {
        test( 'should sort by column ascending', () =>
        {
            dataTable.sort( 'name' );

            expect( dataTable.sortColumn ).toBe( 'name' );
            expect( dataTable.sortDirection ).toBe( 'asc' );
            expect( dataTable.data[ 0 ].name ).toBe( 'Bob Johnson' );
            expect( dataTable.data[ 1 ].name ).toBe( 'Jane Smith' );
            expect( dataTable.data[ 2 ].name ).toBe( 'John Doe' );
        } );

        test( 'should sort by column descending on second click', () =>
        {
            dataTable.sort( 'name' );
            dataTable.sort( 'name' );

            expect( dataTable.sortDirection ).toBe( 'desc' );
            expect( dataTable.data[ 0 ].name ).toBe( 'John Doe' );
            expect( dataTable.data[ 1 ].name ).toBe( 'Jane Smith' );
            expect( dataTable.data[ 2 ].name ).toBe( 'Bob Johnson' );
        } );

        test( 'should sort numbers correctly', () =>
        {
            dataTable.sort( 'id' );

            expect( dataTable.data[ 0 ].id ).toBe( 1 );
            expect( dataTable.data[ 1 ].id ).toBe( 2 );
            expect( dataTable.data[ 2 ].id ).toBe( 3 );
        } );

        test( 'should sort dates correctly', () =>
        {
            dataTable.sort( 'created_at' );

            expect( dataTable.data[ 0 ].created_at ).toBe( '2023-01-01' );
            expect( dataTable.data[ 1 ].created_at ).toBe( '2023-01-02' );
            expect( dataTable.data[ 2 ].created_at ).toBe( '2023-01-03' );
        } );
    } );

    describe( 'Filtering', () =>
    {
        test( 'should filter globally', () =>
        {
            dataTable.globalFilter = 'john';
            dataTable.applyFilters();

            expect( dataTable.data.length ).toBe( 2 ); // John Doe and Bob Johnson
        } );

        test( 'should filter by column', () =>
        {
            dataTable.columnFilters.status = 'active';
            dataTable.applyFilters();

            expect( dataTable.data.length ).toBe( 2 );
            expect( dataTable.data.every( row => row.status === 'active' ) ).toBe( true );
        } );

        test( 'should support filter operators', () =>
        {
            dataTable.columnFilters.id = '>=2';
            dataTable.applyFilters();

            expect( dataTable.data.length ).toBe( 2 );
            expect( dataTable.data.every( row => row.id >= 2 ) ).toBe( true );
        } );

        test( 'should clear filters', () =>
        {
            dataTable.globalFilter = 'john';
            dataTable.columnFilters.status = 'active';
            dataTable.applyFilters();

            expect( dataTable.data.length ).toBe( 1 ); // Only John Doe

            dataTable.clearAllFilters();

            expect( dataTable.data.length ).toBe( 3 );
            expect( dataTable.globalFilter ).toBe( '' );
            expect( dataTable.columnFilters.status ).toBe( '' );
        } );
    } );

    describe( 'Pagination', () =>
    {
        beforeEach( () =>
        {
            // Add more data to test pagination
            const moreData = [];
            for ( let i = 4; i <= 30; i++ )
            {
                moreData.push( {
                    id: i,
                    name: `User ${ i }`,
                    email: `user${ i }@example.com`,
                    status: i % 2 === 0 ? 'active' : 'inactive',
                    created_at: `2023-01-${ String( i ).padStart( 2, '0' ) }`
                } );
            }
            dataTable.originalData = [ ...mockData, ...moreData ];
            dataTable.data = [ ...dataTable.originalData ];
            dataTable.perPage = 10;
            dataTable.updatePagination();
        } );

        test( 'should calculate pagination correctly', () =>
        {
            expect( dataTable.totalItems ).toBe( 30 );
            expect( dataTable.totalPages ).toBe( 3 );
            expect( dataTable.currentPage ).toBe( 1 );
        } );

        test( 'should navigate to next page', () =>
        {
            dataTable.nextPage();

            expect( dataTable.currentPage ).toBe( 2 );
            expect( dataTable.paginatedData.length ).toBe( 10 );
            expect( dataTable.paginatedData[ 0 ].id ).toBe( 11 );
        } );

        test( 'should navigate to previous page', () =>
        {
            dataTable.currentPage = 2;
            dataTable.previousPage();

            expect( dataTable.currentPage ).toBe( 1 );
            expect( dataTable.paginatedData[ 0 ].id ).toBe( 1 );
        } );

        test( 'should go to specific page', () =>
        {
            dataTable.goToPage( 3 );

            expect( dataTable.currentPage ).toBe( 3 );
            expect( dataTable.paginatedData.length ).toBe( 10 );
            expect( dataTable.paginatedData[ 0 ].id ).toBe( 21 );
        } );

        test( 'should not go beyond available pages', () =>
        {
            dataTable.goToPage( 5 ); // Beyond available pages

            expect( dataTable.currentPage ).toBe( 1 ); // Should remain unchanged
        } );

        test( 'should change items per page', () =>
        {
            dataTable.changePerPage( 5 );

            expect( dataTable.perPage ).toBe( 5 );
            expect( dataTable.totalPages ).toBe( 6 );
            expect( dataTable.currentPage ).toBe( 1 );
            expect( dataTable.paginatedData.length ).toBe( 5 );
        } );
    } );

    describe( 'Row Selection', () =>
    {
        test( 'should select individual row', () =>
        {
            dataTable.toggleRowSelection( 0 );

            expect( dataTable.selectedRows.has( 0 ) ).toBe( true );
            expect( dataTable.selectedRows.size ).toBe( 1 );
        } );

        test( 'should deselect row when clicked again', () =>
        {
            dataTable.toggleRowSelection( 0 );
            dataTable.toggleRowSelection( 0 );

            expect( dataTable.selectedRows.has( 0 ) ).toBe( false );
            expect( dataTable.selectedRows.size ).toBe( 0 );
        } );

        test( 'should select all visible rows', () =>
        {
            dataTable.toggleSelectAll();

            expect( dataTable.selectedRows.size ).toBe( 3 );
            expect( dataTable.selectAll ).toBe( true );
        } );

        test( 'should deselect all when select all is clicked again', () =>
        {
            dataTable.toggleSelectAll();
            dataTable.toggleSelectAll();

            expect( dataTable.selectedRows.size ).toBe( 0 );
            expect( dataTable.selectAll ).toBe( false );
        } );

        test( 'should get selected data', () =>
        {
            dataTable.toggleRowSelection( 0 );
            dataTable.toggleRowSelection( 2 );

            const selectedData = dataTable.getSelectedData();

            expect( selectedData.length ).toBe( 2 );
            expect( selectedData[ 0 ] ).toEqual( mockData[ 0 ] );
            expect( selectedData[ 1 ] ).toEqual( mockData[ 2 ] );
        } );
    } );

    describe( 'Column Management', () =>
    {
        test( 'should toggle column visibility', () =>
        {
            dataTable.toggleColumnVisibility( 'email' );

            expect( dataTable.visibleColumns.has( 'email' ) ).toBe( false );
            expect( dataTable.orderedVisibleColumns.length ).toBe( 4 );
        } );

        test( 'should reorder columns', () =>
        {
            const newOrder = [ 'name', 'id', 'email', 'status', 'created_at' ];
            dataTable.reorderColumns( newOrder );

            expect( dataTable.columnOrder ).toEqual( newOrder );
            expect( dataTable.orderedVisibleColumns[ 0 ].key ).toBe( 'name' );
            expect( dataTable.orderedVisibleColumns[ 1 ].key ).toBe( 'id' );
        } );
    } );

    describe( 'Export Functionality', () =>
    {
        test( 'should generate CSV content', () =>
        {
            const csvContent = dataTable.generateCSVContent( mockData );

            expect( csvContent ).toContain( 'ID,Name,Email,Status,Created' );
            expect( csvContent ).toContain( '1,John Doe,john@example.com,active,2023-01-01' );
        } );

        test( 'should export selected data only', () =>
        {
            dataTable.toggleRowSelection( 0 );
            dataTable.toggleRowSelection( 1 );

            // Mock the export function to capture the data
            const originalExportCSV = dataTable.exportCSV;
            let exportedData;
            dataTable.exportCSV = ( data, filename ) =>
            {
                exportedData = data;
            };

            dataTable.exportData( 'csv' );

            expect( exportedData.length ).toBe( 2 );
            expect( exportedData[ 0 ] ).toEqual( mockData[ 0 ] );
            expect( exportedData[ 1 ] ).toEqual( mockData[ 1 ] );

            // Restore original function
            dataTable.exportCSV = originalExportCSV;
        } );
    } );

    describe( 'Cell Formatting', () =>
    {
        test( 'should format numbers correctly', () =>
        {
            const column = { type: 'number' };
            const result = dataTable.formatCellValue( 1234.56, column );

            expect( result ).toBe( '1,234.56' );
        } );

        test( 'should format dates correctly', () =>
        {
            const column = { type: 'date' };
            const result = dataTable.formatCellValue( '2023-01-01', column );

            expect( result ).toBe( '1/1/2023' );
        } );

        test( 'should format booleans correctly', () =>
        {
            const column = { type: 'boolean' };

            expect( dataTable.formatCellValue( true, column ) ).toBe( '✓' );
            expect( dataTable.formatCellValue( false, column ) ).toBe( '✗' );
        } );

        test( 'should format emails correctly', () =>
        {
            const column = { type: 'email' };
            const result = dataTable.formatCellValue( 'test@example.com', column );

            expect( result ).toContain( 'mailto:test@example.com' );
            expect( result ).toContain( 'test@example.com' );
        } );

        test( 'should handle null values', () =>
        {
            const column = { type: 'text' };
            const result = dataTable.formatCellValue( null, column );

            expect( result ).toBe( '-' );
        } );
    } );

    describe( 'Real-time Updates', () =>
    {
        test( 'should update existing item', () =>
        {
            const updatedItem = { id: 1, name: 'John Updated', email: 'john.updated@example.com', status: 'active', created_at: '2023-01-01' };

            dataTable.updateDataItem( updatedItem );

            expect( dataTable.originalData[ 0 ].name ).toBe( 'John Updated' );
            expect( dataTable.originalData[ 0 ].email ).toBe( 'john.updated@example.com' );
        } );

        test( 'should insert new item', () =>
        {
            const newItem = { id: 4, name: 'New User', email: 'new@example.com', status: 'active', created_at: '2023-01-04' };

            dataTable.insertDataItem( newItem );

            expect( dataTable.originalData.length ).toBe( 4 );
            expect( dataTable.originalData[ 3 ] ).toEqual( newItem );
        } );

        test( 'should delete item', () =>
        {
            dataTable.deleteDataItem( 2 );

            expect( dataTable.originalData.length ).toBe( 2 );
            expect( dataTable.originalData.find( item => item.id === 2 ) ).toBeUndefined();
        } );
    } );
} );

// Editable Data Table Tests
describe( 'Editable Data Tables', () =>
{
    let container;
    let editableTable;

    beforeEach( () =>
    {
        container = document.createElement( 'div' );
        document.body.appendChild( container );

        editableTable = window.editableDataTable( {
            data: [
                { id: 1, name: 'John Doe', email: 'john@example.com' }
            ],
            columns: [
                { key: 'id', label: 'ID', type: 'number', editable: false },
                { key: 'name', label: 'Name', type: 'text', editable: true },
                { key: 'email', label: 'Email', type: 'email', editable: true }
            ]
        } );
    } );

    afterEach( () =>
    {
        document.body.removeChild( container );
    } );

    test( 'should start editing mode', () =>
    {
        editableTable.startEditing( 0, 'name' );

        expect( editableTable.editingCell ).toBe( '0-name' );
        expect( editableTable.editingValue ).toBe( 'John Doe' );
        expect( editableTable.editingOriginalValue ).toBe( 'John Doe' );
    } );

    test( 'should cancel editing', () =>
    {
        editableTable.startEditing( 0, 'name' );
        editableTable.editingValue = 'Modified Name';
        editableTable.cancelEditing();

        expect( editableTable.editingCell ).toBe( null );
        expect( editableTable.editingValue ).toBe( '' );
        expect( editableTable.data[ 0 ].name ).toBe( 'John Doe' ); // Should remain unchanged
    } );

    test( 'should validate cell values', () =>
    {
        const emailColumn = { type: 'email' };

        expect( editableTable.validateCellValue( 'valid@example.com', emailColumn ) ).toBe( true );
        expect( editableTable.validateCellValue( 'invalid-email', emailColumn ) ).toBe( false );

        const numberColumn = { type: 'number' };
        expect( editableTable.validateCellValue( '123', numberColumn ) ).toBe( true );
        expect( editableTable.validateCellValue( 'abc', numberColumn ) ).toBe( false );
    } );

    test( 'should check if cell is being edited', () =>
    {
        editableTable.startEditing( 0, 'name' );

        expect( editableTable.isEditing( 0, 'name' ) ).toBe( true );
        expect( editableTable.isEditing( 0, 'email' ) ).toBe( false );
    } );
} );

// Data Tables Service Tests
describe( 'Data Tables Service', () =>
{
    let service;

    beforeEach( () =>
    {
        service = new DataTablesService();
    } );

    afterEach( () =>
    {
        service.cleanupAll();
    } );

    test( 'should register table', () =>
    {
        const config = { dataUrl: '/api/test' };
        service.registerTable( 'test-table', config );

        expect( service.tables.has( 'test-table' ) ).toBe( true );
        expect( service.apiEndpoints.has( 'test-table' ) ).toBe( true );
    } );

    test( 'should build filter query', () =>
    {
        const filters = {
            name: 'john',
            age: '>=18',
            status: 'active|pending',
            date: '2023-01-01..2023-12-31'
        };

        const query = service.buildFilterQuery( filters );

        expect( query[ 'name[like]' ] ).toBe( 'john' );
        expect( query[ 'age[gte]' ] ).toBe( '18' );
        expect( query[ 'status[in]' ] ).toEqual( [ 'active', 'pending' ] );
        expect( query[ 'date[range]' ] ).toBe( '2023-01-01,2023-12-31' );
    } );

    test( 'should calculate pagination', () =>
    {
        const data = new Array( 100 ).fill().map( ( _, i ) => ( { id: i + 1 } ) );
        const params = { per_page: 10, page: 3 };

        const pagination = service.calculatePagination( data, params );

        expect( pagination.total ).toBe( 100 );
        expect( pagination.per_page ).toBe( 10 );
        expect( pagination.current_page ).toBe( 3 );
        expect( pagination.last_page ).toBe( 10 );
        expect( pagination.from ).toBe( 21 );
        expect( pagination.to ).toBe( 30 );
    } );

    test( 'should cleanup table', () =>
    {
        service.registerTable( 'test-table', { dataUrl: '/api/test' } );
        service.cleanup( 'test-table' );

        expect( service.tables.has( 'test-table' ) ).toBe( false );
        expect( service.apiEndpoints.has( 'test-table' ) ).toBe( false );
    } );
} );

console.log( '✅ Interactive Data Tables tests loaded' );
