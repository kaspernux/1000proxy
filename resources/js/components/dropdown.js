/**
 * Advanced Dropdown Component with Search Functionality
 * Supports single/multi-select, search, keyboard navigation
 */
export default () => ( {
    // Component State
    isOpen: false,
    searchQuery: '',
    selectedItems: [],
    highlightedIndex: -1,
    multiSelect: false,
    placeholder: 'Select an option...',
    searchPlaceholder: 'Search options...',
    options: [],
    filteredOptions: [],

    // Component Configuration
    maxHeight: '300px',
    enableSearch: true,
    enableClearAll: true,
    showSelectedCount: true,

    // Lifecycle
    init ()
    {
        this.filteredOptions = [ ...this.options ];
        this.setupKeyboardListeners();
        this.$watch( 'searchQuery', () => this.filterOptions() );
        this.$watch( 'selectedItems', () => this.updateDisplay() );
    },

    // Event Handlers
    toggle ()
    {
        this.isOpen = !this.isOpen;
        if ( this.isOpen )
        {
            this.$nextTick( () =>
            {
                if ( this.enableSearch )
                {
                    this.$refs.searchInput?.focus();
                }
            } );
        } else
        {
            this.searchQuery = '';
            this.highlightedIndex = -1;
        }
    },

    selectOption ( option, index )
    {
        if ( this.multiSelect )
        {
            const existingIndex = this.selectedItems.findIndex( item => item.value === option.value );
            if ( existingIndex > -1 )
            {
                this.selectedItems.splice( existingIndex, 1 );
            } else
            {
                this.selectedItems.push( option );
            }
        } else
        {
            this.selectedItems = [ option ];
            this.close();
        }

        this.$dispatch( 'selection-changed', {
            selected: this.multiSelect ? this.selectedItems : this.selectedItems[ 0 ],
            option: option
        } );
    },

    removeSelected ( index )
    {
        this.selectedItems.splice( index, 1 );
        this.$dispatch( 'selection-changed', {
            selected: this.multiSelect ? this.selectedItems : this.selectedItems[ 0 ]
        } );
    },

    clearAll ()
    {
        this.selectedItems = [];
        this.$dispatch( 'selection-changed', { selected: null } );
    },

    close ()
    {
        this.isOpen = false;
        this.searchQuery = '';
        this.highlightedIndex = -1;
    },

    // Search and Filtering
    filterOptions ()
    {
        if ( !this.searchQuery.trim() )
        {
            this.filteredOptions = [ ...this.options ];
            return;
        }

        const query = this.searchQuery.toLowerCase();
        this.filteredOptions = this.options.filter( option =>
            option.label.toLowerCase().includes( query ) ||
            ( option.description && option.description.toLowerCase().includes( query ) ) ||
            ( option.tags && option.tags.some( tag => tag.toLowerCase().includes( query ) ) )
        );

        this.highlightedIndex = this.filteredOptions.length > 0 ? 0 : -1;
    },

    // Keyboard Navigation
    setupKeyboardListeners ()
    {
        this.$el.addEventListener( 'keydown', ( e ) =>
        {
            if ( !this.isOpen ) return;

            switch ( e.key )
            {
                case 'ArrowDown':
                    e.preventDefault();
                    this.highlightedIndex = Math.min(
                        this.highlightedIndex + 1,
                        this.filteredOptions.length - 1
                    );
                    this.scrollToHighlighted();
                    break;

                case 'ArrowUp':
                    e.preventDefault();
                    this.highlightedIndex = Math.max( this.highlightedIndex - 1, 0 );
                    this.scrollToHighlighted();
                    break;

                case 'Enter':
                    e.preventDefault();
                    if ( this.highlightedIndex >= 0 && this.filteredOptions[ this.highlightedIndex ] )
                    {
                        this.selectOption( this.filteredOptions[ this.highlightedIndex ], this.highlightedIndex );
                    }
                    break;

                case 'Escape':
                    e.preventDefault();
                    this.close();
                    break;

                case 'Tab':
                    this.close();
                    break;
            }
        } );
    },

    scrollToHighlighted ()
    {
        this.$nextTick( () =>
        {
            const highlightedElement = this.$refs.optionsList?.children[ this.highlightedIndex ];
            if ( highlightedElement )
            {
                highlightedElement.scrollIntoView( {
                    block: 'nearest',
                    behavior: 'smooth'
                } );
            }
        } );
    },

    // Utility Methods
    isSelected ( option )
    {
        return this.selectedItems.some( item => item.value === option.value );
    },

    getDisplayText ()
    {
        if ( this.selectedItems.length === 0 )
        {
            return this.placeholder;
        }

        if ( this.multiSelect )
        {
            if ( this.showSelectedCount && this.selectedItems.length > 2 )
            {
                return `${ this.selectedItems.length } items selected`;
            }
            return this.selectedItems.map( item => item.label ).join( ', ' );
        }

        return this.selectedItems[ 0 ].label;
    },

    updateDisplay ()
    {
        // Trigger any display updates needed
        this.$nextTick( () =>
        {
            // Update any external displays or dependent components
        } );
    },

    // Mega Dropdown Support
    getMegaColumns ()
    {
        const columnSize = Math.ceil( this.filteredOptions.length / 3 );
        const columns = [];

        for ( let i = 0; i < this.filteredOptions.length; i += columnSize )
        {
            columns.push( this.filteredOptions.slice( i, i + columnSize ) );
        }

        return columns;
    },

    // Accessibility
    getAriaLabel ()
    {
        const count = this.selectedItems.length;
        if ( count === 0 ) return 'No items selected';
        if ( count === 1 ) return `${ this.selectedItems[ 0 ].label } selected`;
        return `${ count } items selected`;
    }
} );
