/**
 * Auto-complete Component with Search and Filtering
 * 
 * Advanced auto-complete component with search, filtering, keyboard navigation, and async data loading
 * Features: multiple selection, custom templates, data caching, accessibility, debouncing
 */

export default () => ( {
    // Component state
    isOpen: false,
    isFocused: false,
    isLoading: false,
    searchQuery: '',
    selectedItems: [],
    filteredOptions: [],
    highlightedIndex: -1,

    // Configuration
    options: [],
    placeholder: 'Search...',
    noResultsText: 'No results found',
    loadingText: 'Loading...',
    multiple: false,
    searchable: true,
    clearable: true,
    disabled: false,

    // Data source
    dataSource: null, // URL for async loading
    staticData: [], // Static data array
    searchParam: 'query',
    valueField: 'value',
    labelField: 'label',
    descriptionField: 'description',
    imageField: 'image',

    // Performance settings
    debounceDelay: 300,
    minSearchLength: 0,
    maxResults: 50,
    cacheResults: true,

    // Cache
    cache: new Map(),
    debounceTimer: null,

    // Templates
    customItemTemplate: null,
    customSelectionTemplate: null,

    // Validation
    required: false,
    validator: null,

    /**
     * Initialize the auto-complete component
     */
    init ()
    {
        this.loadConfiguration();
        this.setupKeyboardNavigation();
        this.loadInitialData();

        // Watch for external value changes
        this.$watch( 'selectedItems', () =>
        {
            this.updateInputValue();
            this.validateSelection();
            this.$dispatch( 'autocomplete-change', {
                selected: this.selectedItems,
                values: this.getSelectedValues()
            } );
        } );

        console.log( 'Auto-complete component initialized:', {
            multiple: this.multiple,
            searchable: this.searchable,
            dataSource: this.dataSource,
            options: this.options.length
        } );
    },

    /**
     * Load configuration from data attributes
     */
    loadConfiguration ()
    {
        const element = this.$el;

        this.placeholder = element.dataset.placeholder || this.placeholder;
        this.multiple = element.dataset.multiple === 'true';
        this.searchable = element.dataset.searchable !== 'false';
        this.clearable = element.dataset.clearable !== 'false';
        this.disabled = element.dataset.disabled === 'true';
        this.required = element.dataset.required === 'true';

        this.dataSource = element.dataset.dataSource;
        this.searchParam = element.dataset.searchParam || this.searchParam;
        this.valueField = element.dataset.valueField || this.valueField;
        this.labelField = element.dataset.labelField || this.labelField;
        this.descriptionField = element.dataset.descriptionField || this.descriptionField;
        this.imageField = element.dataset.imageField || this.imageField;

        this.debounceDelay = parseInt( element.dataset.debounceDelay ) || this.debounceDelay;
        this.minSearchLength = parseInt( element.dataset.minSearchLength ) || this.minSearchLength;
        this.maxResults = parseInt( element.dataset.maxResults ) || this.maxResults;
        this.cacheResults = element.dataset.cacheResults !== 'false';

        this.noResultsText = element.dataset.noResultsText || this.noResultsText;
        this.loadingText = element.dataset.loadingText || this.loadingText;

        // Load static data from script tag
        const dataScript = element.querySelector( 'script[type="application/json"]' );
        if ( dataScript )
        {
            try
            {
                this.staticData = JSON.parse( dataScript.textContent );
            } catch ( error )
            {
                console.warn( 'Failed to parse static data:', error );
            }
        }

        // Load initial selection
        const initialValue = element.dataset.value;
        if ( initialValue )
        {
            try
            {
                const values = JSON.parse( initialValue );
                this.loadInitialSelection( Array.isArray( values ) ? values : [ values ] );
            } catch ( error )
            {
                this.loadInitialSelection( [ initialValue ] );
            }
        }
    },

    /**
     * Setup keyboard navigation
     */
    setupKeyboardNavigation ()
    {
        document.addEventListener( 'keydown', ( e ) =>
        {
            if ( !this.isOpen || !this.isFocused ) return;

            switch ( e.key )
            {
                case 'ArrowDown':
                    e.preventDefault();
                    this.highlightNext();
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    this.highlightPrevious();
                    break;
                case 'Enter':
                    e.preventDefault();
                    this.selectHighlighted();
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

    /**
     * Load initial data
     */
    async loadInitialData ()
    {
        if ( this.dataSource )
        {
            // Load from remote source
            await this.loadFromSource( '' );
        } else
        {
            // Use static data
            this.options = this.staticData;
            this.filteredOptions = this.options.slice( 0, this.maxResults );
        }
    },

    /**
     * Load initial selection
     */
    async loadInitialSelection ( values )
    {
        if ( !values.length ) return;

        for ( const value of values )
        {
            const option = await this.findOptionByValue( value );
            if ( option && !this.isSelected( option ) )
            {
                this.selectedItems.push( option );
            }
        }
    },

    /**
     * Find option by value
     */
    async findOptionByValue ( value )
    {
        // Check current options first
        let option = this.options.find( opt => this.getItemValue( opt ) === value );
        if ( option ) return option;

        // Try to load from source if available
        if ( this.dataSource )
        {
            try
            {
                const response = await fetch( `${ this.dataSource }?${ this.valueField }=${ encodeURIComponent( value ) }` );
                const data = await response.json();

                if ( data && ( Array.isArray( data ) ? data.length > 0 : data ) )
                {
                    option = Array.isArray( data ) ? data[ 0 ] : data;
                    this.options.push( option );
                    return option;
                }
            } catch ( error )
            {
                console.warn( 'Failed to load option by value:', error );
            }
        }

        return null;
    },

    /**
     * Handle search input
     */
    async handleSearch ( query )
    {
        this.searchQuery = query;

        // Clear previous debounce timer
        if ( this.debounceTimer )
        {
            clearTimeout( this.debounceTimer );
        }

        // Debounce search
        this.debounceTimer = setTimeout( async () =>
        {
            if ( query.length < this.minSearchLength )
            {
                this.filteredOptions = this.options.slice( 0, this.maxResults );
                return;
            }

            if ( this.dataSource )
            {
                await this.loadFromSource( query );
            } else
            {
                this.filterStaticData( query );
            }

            this.highlightedIndex = -1;
        }, this.debounceDelay );
    },

    /**
     * Load data from remote source
     */
    async loadFromSource ( query )
    {
        const cacheKey = `search:${ query }`;

        // Check cache first
        if ( this.cacheResults && this.cache.has( cacheKey ) )
        {
            this.filteredOptions = this.cache.get( cacheKey );
            return;
        }

        this.isLoading = true;

        try
        {
            const url = new URL( this.dataSource, window.location.origin );
            if ( query )
            {
                url.searchParams.set( this.searchParam, query );
            }
            url.searchParams.set( 'limit', this.maxResults );

            const response = await fetch( url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' )?.content
                }
            } );

            if ( !response.ok )
            {
                throw new Error( `HTTP ${ response.status }: ${ response.statusText }` );
            }

            const data = await response.json();
            const options = Array.isArray( data ) ? data : ( data.data || [] );

            // Update options
            if ( query )
            {
                this.filteredOptions = options;
            } else
            {
                this.options = options;
                this.filteredOptions = options.slice( 0, this.maxResults );
            }

            // Cache results
            if ( this.cacheResults )
            {
                this.cache.set( cacheKey, this.filteredOptions );
            }

        } catch ( error )
        {
            console.error( 'Failed to load data from source:', error );
            this.filteredOptions = [];

            this.$dispatch( 'autocomplete-error', { error: error.message } );
        } finally
        {
            this.isLoading = false;
        }
    },

    /**
     * Filter static data
     */
    filterStaticData ( query )
    {
        const normalizedQuery = query.toLowerCase().trim();

        this.filteredOptions = this.options
            .filter( option =>
            {
                const label = this.getItemLabel( option ).toLowerCase();
                const value = this.getItemValue( option ).toString().toLowerCase();
                const description = this.getItemDescription( option ).toLowerCase();

                return label.includes( normalizedQuery ) ||
                    value.includes( normalizedQuery ) ||
                    description.includes( normalizedQuery );
            } )
            .slice( 0, this.maxResults );
    },

    /**
     * Open dropdown
     */
    open ()
    {
        if ( this.disabled ) return;

        this.isOpen = true;
        this.isFocused = true;

        // Load data if needed
        if ( this.filteredOptions.length === 0 && !this.isLoading )
        {
            this.handleSearch( this.searchQuery );
        }

        this.$dispatch( 'autocomplete-open' );
    },

    /**
     * Close dropdown
     */
    close ()
    {
        this.isOpen = false;
        this.highlightedIndex = -1;

        // Clear search if not multiple
        if ( !this.multiple && this.selectedItems.length > 0 )
        {
            this.searchQuery = this.getItemLabel( this.selectedItems[ 0 ] );
        }

        this.$dispatch( 'autocomplete-close' );
    },

    /**
     * Toggle dropdown
     */
    toggle ()
    {
        if ( this.isOpen )
        {
            this.close();
        } else
        {
            this.open();
        }
    },

    /**
     * Select option
     */
    selectOption ( option )
    {
        if ( this.multiple )
        {
            if ( !this.isSelected( option ) )
            {
                this.selectedItems.push( option );
            }
            this.searchQuery = '';
        } else
        {
            this.selectedItems = [ option ];
            this.searchQuery = this.getItemLabel( option );
            this.close();
        }

        this.$dispatch( 'autocomplete-select', { option, selected: this.selectedItems } );
    },

    /**
     * Remove selected item
     */
    removeItem ( item )
    {
        const index = this.selectedItems.findIndex( selected =>
            this.getItemValue( selected ) === this.getItemValue( item )
        );

        if ( index > -1 )
        {
            this.selectedItems.splice( index, 1 );

            if ( !this.multiple && this.selectedItems.length === 0 )
            {
                this.searchQuery = '';
            }
        }

        this.$dispatch( 'autocomplete-remove', { item, selected: this.selectedItems } );
    },

    /**
     * Clear all selections
     */
    clearAll ()
    {
        this.selectedItems = [];
        this.searchQuery = '';

        this.$dispatch( 'autocomplete-clear' );
    },

    /**
     * Keyboard navigation methods
     */
    highlightNext ()
    {
        if ( this.highlightedIndex < this.filteredOptions.length - 1 )
        {
            this.highlightedIndex++;
            this.scrollToHighlighted();
        }
    },

    highlightPrevious ()
    {
        if ( this.highlightedIndex > 0 )
        {
            this.highlightedIndex--;
            this.scrollToHighlighted();
        }
    },

    selectHighlighted ()
    {
        if ( this.highlightedIndex >= 0 && this.highlightedIndex < this.filteredOptions.length )
        {
            this.selectOption( this.filteredOptions[ this.highlightedIndex ] );
        }
    },

    scrollToHighlighted ()
    {
        const dropdown = this.$el.querySelector( '.autocomplete-dropdown' );
        const highlighted = dropdown?.querySelector( '.option-highlighted' );

        if ( highlighted && dropdown )
        {
            const dropdownRect = dropdown.getBoundingClientRect();
            const highlightedRect = highlighted.getBoundingClientRect();

            if ( highlightedRect.bottom > dropdownRect.bottom )
            {
                highlighted.scrollIntoView( { block: 'end' } );
            } else if ( highlightedRect.top < dropdownRect.top )
            {
                highlighted.scrollIntoView( { block: 'start' } );
            }
        }
    },

    /**
     * Utility methods
     */
    isSelected ( option )
    {
        return this.selectedItems.some( selected =>
            this.getItemValue( selected ) === this.getItemValue( option )
        );
    },

    getItemValue ( item )
    {
        return typeof item === 'object' ? item[ this.valueField ] : item;
    },

    getItemLabel ( item )
    {
        return typeof item === 'object' ? item[ this.labelField ] : item;
    },

    getItemDescription ( item )
    {
        return typeof item === 'object' ? ( item[ this.descriptionField ] || '' ) : '';
    },

    getItemImage ( item )
    {
        return typeof item === 'object' ? ( item[ this.imageField ] || '' ) : '';
    },

    getSelectedValues ()
    {
        return this.selectedItems.map( item => this.getItemValue( item ) );
    },

    getSelectedLabels ()
    {
        return this.selectedItems.map( item => this.getItemLabel( item ) );
    },

    updateInputValue ()
    {
        const hiddenInput = this.$el.querySelector( 'input[type="hidden"]' );
        if ( hiddenInput )
        {
            const values = this.getSelectedValues();
            hiddenInput.value = this.multiple ? JSON.stringify( values ) : ( values[ 0 ] || '' );
        }
    },

    /**
     * Validation
     */
    validateSelection ()
    {
        const isValid = this.isValidSelection();

        this.$el.classList.toggle( 'invalid', !isValid );

        if ( this.validator && typeof this.validator === 'function' )
        {
            const result = this.validator( this.selectedItems, this.getSelectedValues() );
            this.$dispatch( 'autocomplete-validate', { valid: result, selected: this.selectedItems } );
        }

        return isValid;
    },

    isValidSelection ()
    {
        if ( this.required && this.selectedItems.length === 0 )
        {
            return false;
        }

        return true;
    },

    /**
     * Template rendering
     */
    renderItemTemplate ( item )
    {
        if ( this.customItemTemplate && typeof this.customItemTemplate === 'function' )
        {
            return this.customItemTemplate( item );
        }

        return this.getDefaultItemTemplate( item );
    },

    getDefaultItemTemplate ( item )
    {
        const label = this.getItemLabel( item );
        const description = this.getItemDescription( item );
        const image = this.getItemImage( item );

        let template = `<div class="option-content">`;

        if ( image )
        {
            template += `<img src="${ image }" alt="${ label }" class="option-image">`;
        }

        template += `<div class="option-text">`;
        template += `<div class="option-label">${ label }</div>`;

        if ( description )
        {
            template += `<div class="option-description">${ description }</div>`;
        }

        template += `</div></div>`;

        return template;
    },

    /**
     * Export/Import functionality
     */
    exportData ()
    {
        return {
            selected: this.selectedItems,
            values: this.getSelectedValues(),
            labels: this.getSelectedLabels(),
            searchQuery: this.searchQuery
        };
    },

    importData ( data )
    {
        if ( data.selected )
        {
            this.selectedItems = data.selected;
        }

        if ( data.searchQuery )
        {
            this.searchQuery = data.searchQuery;
        }

        this.updateInputValue();
    },

    /**
     * Refresh data
     */
    async refresh ()
    {
        if ( this.dataSource )
        {
            this.cache.clear();
            await this.loadFromSource( this.searchQuery );
        } else
        {
            this.filteredOptions = this.staticData.slice( 0, this.maxResults );
        }
    }
} );
