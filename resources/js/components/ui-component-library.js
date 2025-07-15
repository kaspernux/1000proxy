/**
 * Custom UI Component Library
 * Comprehensive collection of reusable UI components with advanced features
 */

// Base Component Class
class UIComponent
{
    constructor ( element, options = {} )
    {
        this.element = typeof element === 'string' ? document.querySelector( element ) : element;
        this.options = { ...this.constructor.defaultOptions, ...options };
        this.id = this.generateId();
        this.isInitialized = false;
        this.eventListeners = new Map();

        if ( this.element )
        {
            this.init();
        }
    }

    static defaultOptions = {};

    // Initialize component
    init ()
    {
        if ( this.isInitialized ) return;

        this.element.setAttribute( 'data-ui-component', this.constructor.name );
        this.element.setAttribute( 'data-ui-id', this.id );

        this.setupEvents();
        this.render();
        this.isInitialized = true;

        this.emit( 'initialized' );
    }

    // Generate unique ID
    generateId ()
    {
        return `ui_${ this.constructor.name.toLowerCase() }_${ Math.random().toString( 36 ).substr( 2, 9 ) }`;
    }

    // Setup event listeners
    setupEvents ()
    {
        // Override in subclasses
    }

    // Render component
    render ()
    {
        // Override in subclasses
    }

    // Event handling
    on ( event, callback )
    {
        if ( !this.eventListeners.has( event ) )
        {
            this.eventListeners.set( event, [] );
        }
        this.eventListeners.get( event ).push( callback );
    }

    off ( event, callback )
    {
        if ( this.eventListeners.has( event ) )
        {
            const listeners = this.eventListeners.get( event );
            const index = listeners.indexOf( callback );
            if ( index > -1 )
            {
                listeners.splice( index, 1 );
            }
        }
    }

    emit ( event, data = null )
    {
        if ( this.eventListeners.has( event ) )
        {
            this.eventListeners.get( event ).forEach( callback => callback( data ) );
        }

        // Dispatch DOM event
        this.element.dispatchEvent( new CustomEvent( `ui:${ event }`, { detail: data } ) );
    }

    // Destroy component
    destroy ()
    {
        this.eventListeners.clear();
        this.element.removeAttribute( 'data-ui-component' );
        this.element.removeAttribute( 'data-ui-id' );
        this.isInitialized = false;
        this.emit( 'destroyed' );
    }

    // Update options
    updateOptions ( newOptions )
    {
        this.options = { ...this.options, ...newOptions };
        this.render();
        this.emit( 'optionsUpdated', this.options );
    }
}

// Advanced Button Component
class UIButton extends UIComponent
{
    static defaultOptions = {
        variant: 'primary',
        size: 'medium',
        loadingText: 'Loading...',
        disabled: false,
        ripple: true,
        icon: null,
        iconPosition: 'left'
    };

    setupEvents ()
    {
        this.element.addEventListener( 'click', this.handleClick.bind( this ) );

        if ( this.options.ripple )
        {
            this.element.addEventListener( 'click', this.createRipple.bind( this ) );
        }
    }

    render ()
    {
        const { variant, size, disabled, icon, iconPosition } = this.options;

        // Apply variant classes
        const variantClasses = {
            primary: 'bg-blue-500 hover:bg-blue-600 text-white',
            secondary: 'bg-gray-500 hover:bg-gray-600 text-white',
            success: 'bg-green-500 hover:bg-green-600 text-white',
            danger: 'bg-red-500 hover:bg-red-600 text-white',
            warning: 'bg-yellow-500 hover:bg-yellow-600 text-white',
            outline: 'border-2 border-blue-500 text-blue-500 hover:bg-blue-500 hover:text-white'
        };

        // Apply size classes
        const sizeClasses = {
            small: 'px-2 py-1 text-sm',
            medium: 'px-4 py-2',
            large: 'px-6 py-3 text-lg'
        };

        // Base classes
        let classes = [
            'inline-flex items-center justify-center',
            'font-medium rounded-md transition-all duration-200',
            'focus:outline-none focus:ring-2 focus:ring-offset-2',
            'disabled:opacity-50 disabled:cursor-not-allowed',
            'relative overflow-hidden'
        ];

        classes.push( variantClasses[ variant ] || variantClasses.primary );
        classes.push( sizeClasses[ size ] || sizeClasses.medium );

        this.element.className = classes.join( ' ' );
        this.element.disabled = disabled;

        // Add icon if specified
        if ( icon )
        {
            this.addIcon( icon, iconPosition );
        }
    }

    addIcon ( icon, position = 'left' )
    {
        const iconElement = document.createElement( 'span' );
        iconElement.className = `ui-button-icon ${ position === 'left' ? 'mr-2' : 'ml-2' }`;
        iconElement.innerHTML = icon;

        if ( position === 'left' )
        {
            this.element.insertBefore( iconElement, this.element.firstChild );
        } else
        {
            this.element.appendChild( iconElement );
        }
    }

    handleClick ( event )
    {
        if ( this.isLoading || this.options.disabled )
        {
            event.preventDefault();
            return;
        }

        this.emit( 'click', event );
    }

    createRipple ( event )
    {
        const button = event.currentTarget;
        const rect = button.getBoundingClientRect();
        const size = Math.max( rect.width, rect.height );
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;

        const ripple = document.createElement( 'span' );
        ripple.style.cssText = `
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            transform: scale(0);
            animation: ui-ripple 0.6s linear;
            width: ${ size }px;
            height: ${ size }px;
            left: ${ x }px;
            top: ${ y }px;
        `;

        button.appendChild( ripple );

        setTimeout( () =>
        {
            ripple.remove();
        }, 600 );
    }

    // Loading state methods
    setLoading ( loading = true )
    {
        this.isLoading = loading;

        if ( loading )
        {
            this.originalText = this.element.textContent;
            this.element.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                ${ this.options.loadingText }
            `;
            this.element.disabled = true;
        } else
        {
            this.element.textContent = this.originalText;
            this.element.disabled = this.options.disabled;
        }

        this.emit( 'loadingChanged', loading );
    }
}

// Advanced Form Input Component
class UIInput extends UIComponent
{
    static defaultOptions = {
        type: 'text',
        placeholder: '',
        label: null,
        helper: null,
        error: null,
        required: false,
        validation: null,
        debounceMs: 300,
        mask: null,
        autocomplete: true
    };

    setupEvents ()
    {
        this.debounceTimer = null;

        this.input = this.element.querySelector( 'input' ) || this.element;
        this.input.addEventListener( 'input', this.handleInput.bind( this ) );
        this.input.addEventListener( 'blur', this.handleBlur.bind( this ) );
        this.input.addEventListener( 'focus', this.handleFocus.bind( this ) );
        this.input.addEventListener( 'keydown', this.handleKeydown.bind( this ) );
    }

    render ()
    {
        const { type, placeholder, label, helper, error, required } = this.options;

        // Create wrapper if needed
        if ( this.element.tagName.toLowerCase() !== 'div' )
        {
            const wrapper = document.createElement( 'div' );
            wrapper.className = 'ui-input-wrapper';
            this.element.parentNode.insertBefore( wrapper, this.element );
            wrapper.appendChild( this.element );
            this.wrapper = wrapper;
        } else
        {
            this.wrapper = this.element;
        }

        this.wrapper.className = 'ui-input-wrapper space-y-2';

        // Clear wrapper content
        this.wrapper.innerHTML = '';

        // Add label
        if ( label )
        {
            const labelElement = document.createElement( 'label' );
            labelElement.className = 'block text-sm font-medium text-gray-700 dark:text-gray-300';
            labelElement.textContent = label + ( required ? ' *' : '' );
            this.wrapper.appendChild( labelElement );
        }

        // Create input container
        const inputContainer = document.createElement( 'div' );
        inputContainer.className = 'relative';

        // Create input
        this.input = document.createElement( 'input' );
        this.input.type = type;
        this.input.placeholder = placeholder;
        this.input.required = required;
        this.input.className = this.getInputClasses();

        inputContainer.appendChild( this.input );
        this.wrapper.appendChild( inputContainer );

        // Add helper text
        if ( helper )
        {
            const helperElement = document.createElement( 'p' );
            helperElement.className = 'text-sm text-gray-500 dark:text-gray-400';
            helperElement.textContent = helper;
            this.wrapper.appendChild( helperElement );
        }

        // Add error message container
        this.errorElement = document.createElement( 'p' );
        this.errorElement.className = 'text-sm text-red-600 dark:text-red-400 hidden';
        this.wrapper.appendChild( this.errorElement );

        if ( error )
        {
            this.setError( error );
        }

        this.setupEvents();
    }

    getInputClasses ()
    {
        const baseClasses = [
            'block w-full px-3 py-2 border rounded-md shadow-sm',
            'placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500',
            'focus:border-transparent transition-colors duration-200',
            'dark:bg-gray-700 dark:border-gray-600 dark:text-white'
        ];

        if ( this.hasError )
        {
            baseClasses.push( 'border-red-300 focus:ring-red-500' );
        } else
        {
            baseClasses.push( 'border-gray-300 dark:border-gray-600' );
        }

        return baseClasses.join( ' ' );
    }

    handleInput ( event )
    {
        const value = event.target.value;

        // Apply mask if specified
        if ( this.options.mask )
        {
            const maskedValue = this.applyMask( value );
            if ( maskedValue !== value )
            {
                event.target.value = maskedValue;
            }
        }

        // Clear previous timer
        if ( this.debounceTimer )
        {
            clearTimeout( this.debounceTimer );
        }

        // Debounced validation and event emission
        this.debounceTimer = setTimeout( () =>
        {
            this.validate();
            this.emit( 'input', { value: event.target.value, isValid: this.isValid } );
        }, this.options.debounceMs );
    }

    handleBlur ( event )
    {
        this.validate();
        this.emit( 'blur', { value: event.target.value, isValid: this.isValid } );
    }

    handleFocus ( event )
    {
        this.clearError();
        this.emit( 'focus', { value: event.target.value } );
    }

    handleKeydown ( event )
    {
        this.emit( 'keydown', event );

        // Handle special keys
        if ( event.key === 'Enter' )
        {
            this.emit( 'submit', { value: event.target.value } );
        }
    }

    applyMask ( value )
    {
        const { mask } = this.options;
        if ( !mask ) return value;

        // Simple masking implementation
        const patterns = {
            phone: value => value.replace( /\D/g, '' ).replace( /(\d{3})(\d{3})(\d{4})/, '($1) $2-$3' ),
            creditCard: value => value.replace( /\D/g, '' ).replace( /(\d{4})(?=\d)/g, '$1 ' ),
            ssn: value => value.replace( /\D/g, '' ).replace( /(\d{3})(\d{2})(\d{4})/, '$1-$2-$3' )
        };

        return patterns[ mask ] ? patterns[ mask ]( value ) : value;
    }

    validate ()
    {
        const value = this.input.value;
        const { validation, required } = this.options;

        this.isValid = true;
        this.clearError();

        // Required validation
        if ( required && !value.trim() )
        {
            this.setError( 'This field is required' );
            return false;
        }

        // Custom validation
        if ( validation && typeof validation === 'function' )
        {
            const result = validation( value );
            if ( result !== true )
            {
                this.setError( result || 'Invalid input' );
                return false;
            }
        }

        // Built-in validation patterns
        const patterns = {
            email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            phone: /^\(\d{3}\) \d{3}-\d{4}$/,
            url: /^https?:\/\/.+/
        };

        if ( patterns[ this.options.type ] && value && !patterns[ this.options.type ].test( value ) )
        {
            this.setError( `Please enter a valid ${ this.options.type }` );
            return false;
        }

        return true;
    }

    setError ( message )
    {
        this.hasError = true;
        this.isValid = false;
        this.errorElement.textContent = message;
        this.errorElement.classList.remove( 'hidden' );
        this.input.className = this.getInputClasses();
        this.emit( 'error', message );
    }

    clearError ()
    {
        this.hasError = false;
        this.errorElement.classList.add( 'hidden' );
        this.input.className = this.getInputClasses();
    }

    getValue ()
    {
        return this.input.value;
    }

    setValue ( value )
    {
        this.input.value = value;
        this.validate();
    }

    focus ()
    {
        this.input.focus();
    }

    disable ()
    {
        this.input.disabled = true;
        this.input.classList.add( 'opacity-50', 'cursor-not-allowed' );
    }

    enable ()
    {
        this.input.disabled = false;
        this.input.classList.remove( 'opacity-50', 'cursor-not-allowed' );
    }
}

// Advanced Modal Component
class UIModal extends UIComponent
{
    static defaultOptions = {
        size: 'medium',
        closable: true,
        backdrop: true,
        keyboard: true,
        animation: 'fade',
        position: 'center'
    };

    setupEvents ()
    {
        if ( this.options.closable )
        {
            // Close button
            const closeBtn = this.element.querySelector( '[data-ui-close]' );
            if ( closeBtn )
            {
                closeBtn.addEventListener( 'click', () => this.hide() );
            }

            // Backdrop click
            if ( this.options.backdrop )
            {
                this.element.addEventListener( 'click', ( e ) =>
                {
                    if ( e.target === this.element )
                    {
                        this.hide();
                    }
                } );
            }

            // Escape key
            if ( this.options.keyboard )
            {
                document.addEventListener( 'keydown', this.handleKeydown.bind( this ) );
            }
        }
    }

    render ()
    {
        const { size, animation, position } = this.options;

        // Apply modal classes
        const sizeClasses = {
            small: 'max-w-md',
            medium: 'max-w-lg',
            large: 'max-w-2xl',
            xlarge: 'max-w-4xl',
            full: 'max-w-none m-4'
        };

        const positionClasses = {
            center: 'items-center',
            top: 'items-start pt-16',
            bottom: 'items-end pb-16'
        };

        this.element.className = [
            'fixed inset-0 z-50 overflow-y-auto',
            'flex justify-center',
            positionClasses[ position ] || positionClasses.center
        ].join( ' ' );

        // Create backdrop
        this.backdrop = document.createElement( 'div' );
        this.backdrop.className = 'fixed inset-0 bg-black bg-opacity-50 transition-opacity duration-300';
        this.element.appendChild( this.backdrop );

        // Create modal container
        this.container = document.createElement( 'div' );
        this.container.className = [
            'relative bg-white dark:bg-gray-800 rounded-lg shadow-xl',
            'transform transition-all duration-300',
            'w-full mx-4',
            sizeClasses[ size ] || sizeClasses.medium
        ].join( ' ' );

        this.element.appendChild( this.container );

        // Move original content to container
        const content = this.element.innerHTML;
        this.element.innerHTML = '';
        this.element.appendChild( this.backdrop );
        this.element.appendChild( this.container );
        this.container.innerHTML = content;

        // Initially hidden
        this.element.style.display = 'none';
    }

    handleKeydown ( event )
    {
        if ( event.key === 'Escape' && this.isVisible )
        {
            this.hide();
        }
    }

    show ()
    {
        this.element.style.display = 'flex';
        this.isVisible = true;

        // Add body class to prevent scrolling
        document.body.classList.add( 'overflow-hidden' );

        // Trigger animation
        requestAnimationFrame( () =>
        {
            this.backdrop.classList.add( 'opacity-100' );
            this.container.classList.add( 'opacity-100', 'scale-100' );
            this.container.classList.remove( 'opacity-0', 'scale-95' );
        } );

        this.emit( 'show' );
    }

    hide ()
    {
        this.backdrop.classList.remove( 'opacity-100' );
        this.backdrop.classList.add( 'opacity-0' );
        this.container.classList.remove( 'opacity-100', 'scale-100' );
        this.container.classList.add( 'opacity-0', 'scale-95' );

        setTimeout( () =>
        {
            this.element.style.display = 'none';
            this.isVisible = false;
            document.body.classList.remove( 'overflow-hidden' );
            this.emit( 'hide' );
        }, 300 );
    }

    toggle ()
    {
        if ( this.isVisible )
        {
            this.hide();
        } else
        {
            this.show();
        }
    }
}

// Advanced Table Component
class UITable extends UIComponent
{
    static defaultOptions = {
        sortable: true,
        filterable: true,
        paginated: true,
        pageSize: 10,
        selectable: false,
        responsive: true,
        stickyHeader: false
    };

    constructor ( element, options = {} )
    {
        super( element, options );
        this.data = [];
        this.filteredData = [];
        this.currentPage = 1;
        this.sortColumn = null;
        this.sortDirection = 'asc';
        this.selectedRows = new Set();
    }

    setupEvents ()
    {
        // Header click for sorting
        this.element.addEventListener( 'click', ( e ) =>
        {
            const th = e.target.closest( 'th[data-sortable]' );
            if ( th )
            {
                this.sort( th.dataset.sortable );
            }
        } );

        // Row selection
        if ( this.options.selectable )
        {
            this.element.addEventListener( 'change', ( e ) =>
            {
                if ( e.target.type === 'checkbox' )
                {
                    this.handleRowSelection( e );
                }
            } );
        }

        // Filter input
        const filterInput = this.element.querySelector( '[data-ui-filter]' );
        if ( filterInput )
        {
            filterInput.addEventListener( 'input', ( e ) =>
            {
                this.filter( e.target.value );
            } );
        }
    }

    render ()
    {
        const { responsive, stickyHeader, paginated } = this.options;

        let classes = [ 'ui-table', 'w-full' ];

        if ( responsive )
        {
            classes.push( 'overflow-x-auto' );
        }

        if ( stickyHeader )
        {
            classes.push( 'sticky-header' );
        }

        this.element.className = classes.join( ' ' );

        this.renderTable();

        if ( paginated )
        {
            this.renderPagination();
        }
    }

    renderTable ()
    {
        const tbody = this.element.querySelector( 'tbody' );
        if ( !tbody ) return;

        tbody.innerHTML = '';

        const startIndex = ( this.currentPage - 1 ) * this.options.pageSize;
        const endIndex = startIndex + this.options.pageSize;
        const pageData = this.filteredData.slice( startIndex, endIndex );

        pageData.forEach( ( row, index ) =>
        {
            const tr = document.createElement( 'tr' );
            tr.className = 'hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200';
            tr.dataset.index = startIndex + index;

            if ( this.options.selectable )
            {
                const td = document.createElement( 'td' );
                td.className = 'px-6 py-4';
                td.innerHTML = `<input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">`;
                tr.appendChild( td );
            }

            Object.entries( row ).forEach( ( [ key, value ] ) =>
            {
                const td = document.createElement( 'td' );
                td.className = 'px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white';
                td.textContent = value;
                tr.appendChild( td );
            } );

            tbody.appendChild( tr );
        } );
    }

    renderPagination ()
    {
        const totalPages = Math.ceil( this.filteredData.length / this.options.pageSize );

        let paginationContainer = this.element.parentNode.querySelector( '.ui-table-pagination' );
        if ( !paginationContainer )
        {
            paginationContainer = document.createElement( 'div' );
            paginationContainer.className = 'ui-table-pagination flex items-center justify-between mt-4';
            this.element.parentNode.appendChild( paginationContainer );
        }

        paginationContainer.innerHTML = `
            <div class="text-sm text-gray-700 dark:text-gray-300">
                Showing ${ ( this.currentPage - 1 ) * this.options.pageSize + 1 } to 
                ${ Math.min( this.currentPage * this.options.pageSize, this.filteredData.length ) } of 
                ${ this.filteredData.length } results
            </div>
            <div class="flex space-x-1">
                <button class="px-3 py-1 text-sm border rounded ${ this.currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100' }" 
                        onclick="this.closest('.ui-table').uiComponent.previousPage()" 
                        ${ this.currentPage === 1 ? 'disabled' : '' }>Previous</button>
                <button class="px-3 py-1 text-sm border rounded ${ this.currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100' }" 
                        onclick="this.closest('.ui-table').uiComponent.nextPage()"
                        ${ this.currentPage === totalPages ? 'disabled' : '' }>Next</button>
            </div>
        `;
    }

    setData ( data )
    {
        this.data = data;
        this.filteredData = [ ...data ];
        this.currentPage = 1;
        this.render();
        this.emit( 'dataChanged', data );
    }

    sort ( column )
    {
        if ( this.sortColumn === column )
        {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else
        {
            this.sortColumn = column;
            this.sortDirection = 'asc';
        }

        this.filteredData.sort( ( a, b ) =>
        {
            const aVal = a[ column ];
            const bVal = b[ column ];

            if ( aVal < bVal ) return this.sortDirection === 'asc' ? -1 : 1;
            if ( aVal > bVal ) return this.sortDirection === 'asc' ? 1 : -1;
            return 0;
        } );

        this.render();
        this.emit( 'sorted', { column, direction: this.sortDirection } );
    }

    filter ( query )
    {
        if ( !query )
        {
            this.filteredData = [ ...this.data ];
        } else
        {
            this.filteredData = this.data.filter( row =>
                Object.values( row ).some( value =>
                    String( value ).toLowerCase().includes( query.toLowerCase() )
                )
            );
        }

        this.currentPage = 1;
        this.render();
        this.emit( 'filtered', { query, results: this.filteredData.length } );
    }

    nextPage ()
    {
        const totalPages = Math.ceil( this.filteredData.length / this.options.pageSize );
        if ( this.currentPage < totalPages )
        {
            this.currentPage++;
            this.render();
            this.emit( 'pageChanged', this.currentPage );
        }
    }

    previousPage ()
    {
        if ( this.currentPage > 1 )
        {
            this.currentPage--;
            this.render();
            this.emit( 'pageChanged', this.currentPage );
        }
    }

    handleRowSelection ( event )
    {
        const row = event.target.closest( 'tr' );
        const index = parseInt( row.dataset.index );

        if ( event.target.checked )
        {
            this.selectedRows.add( index );
        } else
        {
            this.selectedRows.delete( index );
        }

        this.emit( 'selectionChanged', Array.from( this.selectedRows ) );
    }
}

// Notification Component
class UINotification extends UIComponent
{
    static defaultOptions = {
        type: 'info',
        duration: 5000,
        closable: true,
        position: 'top-right',
        animation: 'slide'
    };

    static container = null;
    static notifications = [];

    static createContainer ()
    {
        if ( !UINotification.container )
        {
            UINotification.container = document.createElement( 'div' );
            UINotification.container.className = 'fixed top-4 right-4 z-50 space-y-4';
            UINotification.container.id = 'ui-notifications-container';
            document.body.appendChild( UINotification.container );
        }
        return UINotification.container;
    }

    static show ( message, options = {} )
    {
        const notification = new UINotification( null, { ...options, message } );
        return notification;
    }

    constructor ( element, options = {} )
    {
        // Create element if not provided
        if ( !element )
        {
            element = document.createElement( 'div' );
            UINotification.createContainer().appendChild( element );
        }

        super( element, options );
        UINotification.notifications.push( this );
    }

    render ()
    {
        const { type, message, closable, duration } = this.options;

        const typeClasses = {
            info: 'bg-blue-500 text-white',
            success: 'bg-green-500 text-white',
            warning: 'bg-yellow-500 text-white',
            error: 'bg-red-500 text-white'
        };

        const typeIcons = {
            info: 'ℹ️',
            success: '✅',
            warning: '⚠️',
            error: '❌'
        };

        this.element.className = [
            'flex items-center p-4 rounded-lg shadow-lg',
            'transform transition-all duration-300',
            'translate-x-full opacity-0',
            typeClasses[ type ] || typeClasses.info
        ].join( ' ' );

        this.element.innerHTML = `
            <span class="mr-3">${ typeIcons[ type ] || typeIcons.info }</span>
            <span class="flex-1">${ message }</span>
            ${ closable ? '<button class="ml-3 text-white hover:text-gray-200">×</button>' : '' }
        `;

        if ( closable )
        {
            const closeBtn = this.element.querySelector( 'button' );
            closeBtn.addEventListener( 'click', () => this.hide() );
        }

        // Show animation
        requestAnimationFrame( () =>
        {
            this.element.classList.remove( 'translate-x-full', 'opacity-0' );
            this.element.classList.add( 'translate-x-0', 'opacity-100' );
        } );

        // Auto hide
        if ( duration > 0 )
        {
            setTimeout( () => this.hide(), duration );
        }
    }

    hide ()
    {
        this.element.classList.add( 'translate-x-full', 'opacity-0' );
        this.element.classList.remove( 'translate-x-0', 'opacity-100' );

        setTimeout( () =>
        {
            this.element.remove();
            const index = UINotification.notifications.indexOf( this );
            if ( index > -1 )
            {
                UINotification.notifications.splice( index, 1 );
            }
            this.emit( 'hidden' );
        }, 300 );
    }
}

// Add required CSS for animations
const style = document.createElement( 'style' );
style.textContent = `
    @keyframes ui-ripple {
        to { transform: scale(4); opacity: 0; }
    }
    
    .ui-table.sticky-header thead th {
        position: sticky;
        top: 0;
        background: white;
        z-index: 10;
    }
    
    .dark .ui-table.sticky-header thead th {
        background: #1f2937;
    }
`;
document.head.appendChild( style );

// Alpine.js Integration
document.addEventListener( 'alpine:init', () =>
{
    Alpine.magic( 'ui', () => ( {
        button: ( element, options ) => new UIButton( element, options ),
        input: ( element, options ) => new UIInput( element, options ),
        modal: ( element, options ) => new UIModal( element, options ),
        table: ( element, options ) => new UITable( element, options ),
        notify: ( message, options ) => UINotification.show( message, options )
    } ) );

    // UI Component Alpine Data
    Alpine.data( 'uiComponents', () => ( {
        // Demo data
        tableData: [
            { id: 1, name: 'John Doe', email: 'john@example.com', role: 'Admin', status: 'Active' },
            { id: 2, name: 'Jane Smith', email: 'jane@example.com', role: 'User', status: 'Active' },
            { id: 3, name: 'Bob Johnson', email: 'bob@example.com', role: 'User', status: 'Inactive' },
            { id: 4, name: 'Alice Brown', email: 'alice@example.com', role: 'Editor', status: 'Active' },
            { id: 5, name: 'Charlie Wilson', email: 'charlie@example.com', role: 'User', status: 'Pending' }
        ],

        // Form data
        formData: {
            name: '',
            email: '',
            phone: '',
            message: ''
        },

        // Component instances
        components: {},

        // Initialize
        init ()
        {
            this.initializeComponents();
        },

        // Initialize all UI components
        initializeComponents ()
        {
            // Initialize buttons
            document.querySelectorAll( '[data-ui-button]' ).forEach( button =>
            {
                this.components[ button.id ] = new UIButton( button, {
                    variant: button.dataset.variant || 'primary',
                    size: button.dataset.size || 'medium'
                } );
            } );

            // Initialize inputs
            document.querySelectorAll( '[data-ui-input]' ).forEach( input =>
            {
                this.components[ input.id ] = new UIInput( input, {
                    type: input.dataset.type || 'text',
                    validation: this.getValidation( input.dataset.validation )
                } );
            } );

            // Initialize modals
            document.querySelectorAll( '[data-ui-modal]' ).forEach( modal =>
            {
                this.components[ modal.id ] = new UIModal( modal, {
                    size: modal.dataset.size || 'medium'
                } );
            } );

            // Initialize tables
            document.querySelectorAll( '[data-ui-table]' ).forEach( table =>
            {
                const tableComponent = new UITable( table, {
                    sortable: table.dataset.sortable !== 'false',
                    filterable: table.dataset.filterable !== 'false',
                    paginated: table.dataset.paginated !== 'false'
                } );
                tableComponent.setData( this.tableData );
                this.components[ table.id ] = tableComponent;
            } );
        },

        // Get validation function
        getValidation ( type )
        {
            const validations = {
                email: ( value ) =>
                {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    return emailRegex.test( value ) || 'Please enter a valid email address';
                },
                phone: ( value ) =>
                {
                    const phoneRegex = /^\(\d{3}\) \d{3}-\d{4}$/;
                    return phoneRegex.test( value ) || 'Please enter a valid phone number';
                },
                required: ( value ) =>
                {
                    return value.trim().length > 0 || 'This field is required';
                }
            };

            return validations[ type ];
        },

        // Test button actions
        testButton ( type )
        {
            const button = this.components[ `demo-button-${ type }` ];
            if ( button )
            {
                button.setLoading( true );
                setTimeout( () =>
                {
                    button.setLoading( false );
                    UINotification.show( `${ type } button clicked!`, { type: 'success' } );
                }, 2000 );
            }
        },

        // Show modal
        showModal ( modalId )
        {
            const modal = this.components[ modalId ];
            if ( modal )
            {
                modal.show();
            }
        },

        // Hide modal
        hideModal ( modalId )
        {
            const modal = this.components[ modalId ];
            if ( modal )
            {
                modal.hide();
            }
        },

        // Show notification
        showNotification ( type )
        {
            const messages = {
                info: 'This is an info notification',
                success: 'Operation completed successfully!',
                warning: 'Please review your input',
                error: 'An error occurred while processing'
            };

            UINotification.show( messages[ type ], { type } );
        },

        // Submit form
        submitForm ()
        {
            let isValid = true;

            // Validate all inputs
            Object.keys( this.formData ).forEach( field =>
            {
                const input = this.components[ `demo-input-${ field }` ];
                if ( input && !input.validate() )
                {
                    isValid = false;
                }
            } );

            if ( isValid )
            {
                UINotification.show( 'Form submitted successfully!', { type: 'success' } );
                console.log( 'Form data:', this.formData );
            } else
            {
                UINotification.show( 'Please fix the errors in the form', { type: 'error' } );
            }
        },

        // Add table row
        addTableRow ()
        {
            const newRow = {
                id: this.tableData.length + 1,
                name: 'New User',
                email: 'new@example.com',
                role: 'User',
                status: 'Active'
            };

            this.tableData.push( newRow );

            const table = this.components[ 'demo-table' ];
            if ( table )
            {
                table.setData( this.tableData );
            }

            UINotification.show( 'Row added to table!', { type: 'success' } );
        },

        // Clear table
        clearTable ()
        {
            this.tableData = [];
            const table = this.components[ 'demo-table' ];
            if ( table )
            {
                table.setData( this.tableData );
            }
            UINotification.show( 'Table cleared!', { type: 'info' } );
        }
    } ) );
} );

// Export components
window.UIComponents = {
    UIComponent,
    UIButton,
    UIInput,
    UIModal,
    UITable,
    UINotification
};
