/**
 * Dynamic Form Validation Component
 * 
 * Real-time form validation with advanced rules, custom validators, and visual feedback
 * Features: real-time validation, custom rules, async validation, accessibility compliance
 */

export default () => ( {
    // Core validation state
    fields: {},
    errors: {},
    isValid: false,
    isValidating: false,
    validationRules: {},
    customValidators: {},

    // Configuration
    validateOnChange: true,
    validateOnBlur: true,
    showErrorsOnInput: true,
    debounceDelay: 300,

    // Visual feedback
    showSuccessIndicators: true,
    animateErrors: true,
    highlightErrors: true,

    // Async validation
    pendingValidations: new Set(),
    validationCache: new Map(),

    /**
     * Initialize the form validation
     */
    init ()
    {
        this.setupFormFields();
        this.setupValidationRules();
        this.setupEventListeners();
        this.setupCustomValidators();

        console.log( 'Dynamic form validation initialized:', {
            fieldsCount: Object.keys( this.fields ).length,
            rulesCount: Object.keys( this.validationRules ).length,
            validateOnChange: this.validateOnChange
        } );
    },

    /**
     * Setup form fields from DOM
     */
    setupFormFields ()
    {
        const formElements = this.$el.querySelectorAll( 'input, select, textarea' );

        formElements.forEach( element =>
        {
            const fieldName = element.name || element.id;
            if ( fieldName )
            {
                this.fields[ fieldName ] = {
                    element: element,
                    value: element.value,
                    type: element.type,
                    required: element.hasAttribute( 'required' ) || element.hasAttribute( 'data-required' ),
                    isValid: true,
                    isDirty: false,
                    isTouched: false,
                    isValidating: false,
                    lastValidated: null
                };

                // Set up field watchers
                this.setupFieldWatcher( fieldName, element );
            }
        } );
    },

    /**
     * Setup validation rules from data attributes
     */
    setupValidationRules ()
    {
        Object.keys( this.fields ).forEach( fieldName =>
        {
            const element = this.fields[ fieldName ].element;
            const rulesData = element.dataset.validation || element.dataset.rules;

            if ( rulesData )
            {
                try
                {
                    this.validationRules[ fieldName ] = JSON.parse( rulesData );
                } catch ( e )
                {
                    console.warn( `Invalid validation rules for field ${ fieldName }:`, rulesData );
                }
            } else
            {
                // Auto-generate rules from HTML attributes
                this.validationRules[ fieldName ] = this.generateRulesFromAttributes( element );
            }
        } );
    },

    /**
     * Generate validation rules from HTML attributes
     */
    generateRulesFromAttributes ( element )
    {
        const rules = [];

        // Required validation
        if ( element.hasAttribute( 'required' ) || element.hasAttribute( 'data-required' ) )
        {
            rules.push( {
                type: 'required',
                message: `${ this.getFieldLabel( element ) } is required`
            } );
        }

        // Email validation
        if ( element.type === 'email' )
        {
            rules.push( {
                type: 'email',
                message: 'Please enter a valid email address'
            } );
        }

        // URL validation
        if ( element.type === 'url' )
        {
            rules.push( {
                type: 'url',
                message: 'Please enter a valid URL'
            } );
        }

        // Number validation
        if ( element.type === 'number' )
        {
            if ( element.min !== '' )
            {
                rules.push( {
                    type: 'min_number',
                    value: parseFloat( element.min ),
                    message: `Value must be at least ${ element.min }`
                } );
            }

            if ( element.max !== '' )
            {
                rules.push( {
                    type: 'max_number',
                    value: parseFloat( element.max ),
                    message: `Value must not exceed ${ element.max }`
                } );
            }
        }

        // Length validation
        if ( element.minLength )
        {
            rules.push( {
                type: 'min_length',
                value: parseInt( element.minLength ),
                message: `Minimum ${ element.minLength } characters required`
            } );
        }

        if ( element.maxLength )
        {
            rules.push( {
                type: 'max_length',
                value: parseInt( element.maxLength ),
                message: `Maximum ${ element.maxLength } characters allowed`
            } );
        }

        // Pattern validation
        if ( element.pattern )
        {
            rules.push( {
                type: 'pattern',
                pattern: element.pattern,
                message: element.title || 'Invalid format'
            } );
        }

        return rules;
    },

    /**
     * Setup field event listeners
     */
    setupEventListeners ()
    {
        Object.keys( this.fields ).forEach( fieldName =>
        {
            const element = this.fields[ fieldName ].element;

            // Input event (real-time validation)
            element.addEventListener( 'input', this.debounce( ( e ) =>
            {
                this.handleFieldInput( fieldName, e.target.value );
            }, this.debounceDelay ) );

            // Blur event
            element.addEventListener( 'blur', ( e ) =>
            {
                this.handleFieldBlur( fieldName, e.target.value );
            } );

            // Focus event
            element.addEventListener( 'focus', ( e ) =>
            {
                this.handleFieldFocus( fieldName );
            } );

            // Change event (for selects and checkboxes)
            element.addEventListener( 'change', ( e ) =>
            {
                this.handleFieldChange( fieldName, e.target.value );
            } );
        } );
    },

    /**
     * Setup field watcher for Alpine.js
     */
    setupFieldWatcher ( fieldName, element )
    {
        // Watch for changes in Alpine.js data
        this.$watch( `fields.${ fieldName }.value`, ( newValue ) =>
        {
            if ( newValue !== element.value )
            {
                element.value = newValue;
                this.validateField( fieldName, newValue );
            }
        } );
    },

    /**
     * Handle field input event
     */
    async handleFieldInput ( fieldName, value )
    {
        this.updateFieldState( fieldName, {
            value: value,
            isDirty: true
        } );

        if ( this.validateOnChange )
        {
            await this.validateField( fieldName, value );
        }

        this.updateFormValidation();
    },

    /**
     * Handle field blur event
     */
    async handleFieldBlur ( fieldName, value )
    {
        this.updateFieldState( fieldName, {
            isTouched: true
        } );

        if ( this.validateOnBlur )
        {
            await this.validateField( fieldName, value );
        }

        this.updateFormValidation();
    },

    /**
     * Handle field focus event
     */
    handleFieldFocus ( fieldName )
    {
        // Clear error styling on focus
        if ( this.errors[ fieldName ] )
        {
            this.clearFieldError( fieldName );
        }
    },

    /**
     * Handle field change event
     */
    async handleFieldChange ( fieldName, value )
    {
        this.updateFieldState( fieldName, {
            value: value,
            isDirty: true,
            isTouched: true
        } );

        await this.validateField( fieldName, value );
        this.updateFormValidation();
    },

    /**
     * Validate individual field
     */
    async validateField ( fieldName, value = null )
    {
        const field = this.fields[ fieldName ];
        if ( !field ) return true;

        const fieldValue = value !== null ? value : field.value;
        const rules = this.validationRules[ fieldName ] || [];

        // Skip validation if field is empty and not required
        const hasRequiredRule = rules.some( rule => rule.type === 'required' );
        if ( !fieldValue && !hasRequiredRule )
        {
            this.clearFieldError( fieldName );
            this.updateFieldState( fieldName, { isValid: true } );
            return true;
        }

        // Set validating state
        this.updateFieldState( fieldName, { isValidating: true } );

        try
        {
            // Check cache first
            const cacheKey = `${ fieldName }:${ fieldValue }`;
            if ( this.validationCache.has( cacheKey ) )
            {
                const cachedResult = this.validationCache.get( cacheKey );
                this.handleValidationResult( fieldName, cachedResult );
                return cachedResult.isValid;
            }

            // Add to pending validations
            this.pendingValidations.add( fieldName );

            // Validate against all rules
            for ( const rule of rules )
            {
                const result = await this.validateFieldRule( fieldValue, rule, fieldName );

                if ( !result.isValid )
                {
                    this.handleValidationResult( fieldName, result );
                    this.cacheValidationResult( cacheKey, result );
                    return false;
                }
            }

            // All validations passed
            const successResult = { isValid: true, message: null };
            this.handleValidationResult( fieldName, successResult );
            this.cacheValidationResult( cacheKey, successResult );

            return true;

        } finally
        {
            this.pendingValidations.delete( fieldName );
            this.updateFieldState( fieldName, {
                isValidating: false,
                lastValidated: Date.now()
            } );
        }
    },

    /**
     * Validate field against specific rule
     */
    async validateFieldRule ( value, rule, fieldName )
    {
        switch ( rule.type )
        {
            case 'required':
                return {
                    isValid: value && value.toString().trim().length > 0,
                    message: rule.message || `${ this.getFieldLabel( fieldName ) } is required`
                };

            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return {
                    isValid: !value || emailRegex.test( value ),
                    message: rule.message || 'Please enter a valid email address'
                };

            case 'url':
                try
                {
                    if ( !value ) return { isValid: true };
                    new URL( value );
                    return { isValid: true };
                } catch
                {
                    return {
                        isValid: false,
                        message: rule.message || 'Please enter a valid URL'
                    };
                }

            case 'min_length':
                return {
                    isValid: !value || value.length >= rule.value,
                    message: rule.message || `Minimum ${ rule.value } characters required`
                };

            case 'max_length':
                return {
                    isValid: !value || value.length <= rule.value,
                    message: rule.message || `Maximum ${ rule.value } characters allowed`
                };

            case 'min_number':
                const minNum = parseFloat( value );
                return {
                    isValid: !value || ( !isNaN( minNum ) && minNum >= rule.value ),
                    message: rule.message || `Value must be at least ${ rule.value }`
                };

            case 'max_number':
                const maxNum = parseFloat( value );
                return {
                    isValid: !value || ( !isNaN( maxNum ) && maxNum <= rule.value ),
                    message: rule.message || `Value must not exceed ${ rule.value }`
                };

            case 'pattern':
                const regex = new RegExp( rule.pattern );
                return {
                    isValid: !value || regex.test( value ),
                    message: rule.message || 'Invalid format'
                };

            case 'custom':
                if ( typeof rule.validator === 'function' )
                {
                    return await rule.validator( value, fieldName, this.fields );
                }
                return { isValid: true };

            case 'async':
                return await this.handleAsyncValidation( value, rule, fieldName );

            case 'match':
                const matchField = this.fields[ rule.field ];
                const matchValue = matchField ? matchField.value : '';
                return {
                    isValid: value === matchValue,
                    message: rule.message || `Values do not match`
                };

            default:
                // Check custom validators
                if ( this.customValidators[ rule.type ] )
                {
                    return await this.customValidators[ rule.type ]( value, rule, fieldName );
                }

                return { isValid: true };
        }
    },

    /**
     * Handle async validation
     */
    async handleAsyncValidation ( value, rule, fieldName )
    {
        try
        {
            const response = await fetch( rule.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' )?.content
                },
                body: JSON.stringify( {
                    field: fieldName,
                    value: value,
                    ...rule.params
                } )
            } );

            const result = await response.json();

            return {
                isValid: result.valid || false,
                message: result.message || 'Validation failed'
            };

        } catch ( error )
        {
            console.error( 'Async validation error:', error );
            return {
                isValid: false,
                message: 'Validation service unavailable'
            };
        }
    },

    /**
     * Handle validation result
     */
    handleValidationResult ( fieldName, result )
    {
        if ( result.isValid )
        {
            this.clearFieldError( fieldName );
            this.showFieldSuccess( fieldName );
            this.updateFieldState( fieldName, { isValid: true } );
        } else
        {
            this.showFieldError( fieldName, result.message );
            this.updateFieldState( fieldName, { isValid: false } );
        }
    },

    /**
     * Show field error
     */
    showFieldError ( fieldName, message )
    {
        this.errors[ fieldName ] = message;

        const field = this.fields[ fieldName ];
        if ( field && field.element )
        {
            const element = field.element;

            // Add error class
            element.classList.add( 'error', 'border-red-500', 'focus:border-red-500', 'focus:ring-red-500' );
            element.classList.remove( 'border-green-500', 'focus:border-green-500', 'focus:ring-green-500' );

            // Show error message
            let errorElement = element.parentElement.querySelector( '.error-message' );
            if ( !errorElement )
            {
                errorElement = document.createElement( 'div' );
                errorElement.className = 'error-message text-red-500 text-xs mt-1';
                element.parentElement.appendChild( errorElement );
            }

            errorElement.textContent = message;
            errorElement.classList.remove( 'hidden' );

            // Animate error if enabled
            if ( this.animateErrors )
            {
                errorElement.style.animation = 'errorSlideIn 0.3s ease-out';
            }

            // Add error icon
            this.addFieldIcon( element, 'error' );
        }

        // Dispatch error event
        this.$dispatch( 'field-error', { field: fieldName, message: message } );
    },

    /**
     * Clear field error
     */
    clearFieldError ( fieldName )
    {
        delete this.errors[ fieldName ];

        const field = this.fields[ fieldName ];
        if ( field && field.element )
        {
            const element = field.element;

            // Remove error classes
            element.classList.remove( 'error', 'border-red-500', 'focus:border-red-500', 'focus:ring-red-500' );

            // Hide error message
            const errorElement = element.parentElement.querySelector( '.error-message' );
            if ( errorElement )
            {
                errorElement.classList.add( 'hidden' );
            }

            // Remove error icon
            this.removeFieldIcon( element );
        }
    },

    /**
     * Show field success
     */
    showFieldSuccess ( fieldName )
    {
        if ( !this.showSuccessIndicators ) return;

        const field = this.fields[ fieldName ];
        if ( field && field.element && field.isDirty )
        {
            const element = field.element;

            // Add success class
            element.classList.add( 'border-green-500', 'focus:border-green-500', 'focus:ring-green-500' );
            element.classList.remove( 'border-red-500', 'focus:border-red-500', 'focus:ring-red-500' );

            // Add success icon
            this.addFieldIcon( element, 'success' );
        }
    },

    /**
     * Add field icon
     */
    addFieldIcon ( element, type )
    {
        // Remove existing icon
        this.removeFieldIcon( element );

        const iconContainer = document.createElement( 'div' );
        iconContainer.className = 'field-icon absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none';

        const icon = document.createElement( 'div' );
        icon.className = 'w-5 h-5';

        if ( type === 'error' )
        {
            icon.innerHTML = '<svg class="text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>';
        } else if ( type === 'success' )
        {
            icon.innerHTML = '<svg class="text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>';
        }

        iconContainer.appendChild( icon );

        // Make sure parent is relative positioned
        element.parentElement.style.position = 'relative';
        element.style.paddingRight = '2.5rem';
        element.parentElement.appendChild( iconContainer );
    },

    /**
     * Remove field icon
     */
    removeFieldIcon ( element )
    {
        const existingIcon = element.parentElement.querySelector( '.field-icon' );
        if ( existingIcon )
        {
            existingIcon.remove();
            element.style.paddingRight = '';
        }
    },

    /**
     * Update field state
     */
    updateFieldState ( fieldName, updates )
    {
        if ( this.fields[ fieldName ] )
        {
            Object.assign( this.fields[ fieldName ], updates );
        }
    },

    /**
     * Update overall form validation status
     */
    updateFormValidation ()
    {
        const allFieldsValid = Object.values( this.fields ).every( field => field.isValid );
        const hasErrors = Object.keys( this.errors ).length > 0;
        const hasValidating = Object.values( this.fields ).some( field => field.isValidating );

        this.isValid = allFieldsValid && !hasErrors && !hasValidating;
        this.isValidating = hasValidating;

        // Dispatch form validation event
        this.$dispatch( 'form-validation-updated', {
            isValid: this.isValid,
            isValidating: this.isValidating,
            errors: this.errors,
            fields: this.fields
        } );
    },

    /**
     * Validate entire form
     */
    async validateForm ()
    {
        const validationPromises = Object.keys( this.fields ).map( fieldName =>
            this.validateField( fieldName )
        );

        const results = await Promise.all( validationPromises );
        const allValid = results.every( result => result );

        this.updateFormValidation();

        return allValid;
    },

    /**
     * Setup custom validators
     */
    setupCustomValidators ()
    {
        // Password strength validator
        this.customValidators.password_strength = ( value, rule, fieldName ) =>
        {
            const minLength = rule.minLength || 8;
            const requireUppercase = rule.requireUppercase !== false;
            const requireLowercase = rule.requireLowercase !== false;
            const requireNumbers = rule.requireNumbers !== false;
            const requireSpecial = rule.requireSpecial !== false;

            if ( !value || value.length < minLength )
            {
                return {
                    isValid: false,
                    message: `Password must be at least ${ minLength } characters long`
                };
            }

            if ( requireUppercase && !/[A-Z]/.test( value ) )
            {
                return {
                    isValid: false,
                    message: 'Password must contain at least one uppercase letter'
                };
            }

            if ( requireLowercase && !/[a-z]/.test( value ) )
            {
                return {
                    isValid: false,
                    message: 'Password must contain at least one lowercase letter'
                };
            }

            if ( requireNumbers && !/\d/.test( value ) )
            {
                return {
                    isValid: false,
                    message: 'Password must contain at least one number'
                };
            }

            if ( requireSpecial && !/[!@#$%^&*(),.?":{}|<>]/.test( value ) )
            {
                return {
                    isValid: false,
                    message: 'Password must contain at least one special character'
                };
            }

            return { isValid: true };
        };

        // Credit card validator
        this.customValidators.credit_card = ( value, rule, fieldName ) =>
        {
            if ( !value ) return { isValid: true };

            // Remove spaces and dashes
            const cleaned = value.replace( /[\s-]/g, '' );

            // Basic length and digit check
            if ( !/^\d{13,19}$/.test( cleaned ) )
            {
                return {
                    isValid: false,
                    message: 'Please enter a valid credit card number'
                };
            }

            // Luhn algorithm
            let sum = 0;
            let isEven = false;

            for ( let i = cleaned.length - 1; i >= 0; i-- )
            {
                let digit = parseInt( cleaned.charAt( i ) );

                if ( isEven )
                {
                    digit *= 2;
                    if ( digit > 9 )
                    {
                        digit -= 9;
                    }
                }

                sum += digit;
                isEven = !isEven;
            }

            return {
                isValid: sum % 10 === 0,
                message: 'Please enter a valid credit card number'
            };
        };
    },

    /**
     * Utility methods
     */
    getFieldLabel ( fieldNameOrElement )
    {
        let element;

        if ( typeof fieldNameOrElement === 'string' )
        {
            element = this.fields[ fieldNameOrElement ]?.element;
        } else
        {
            element = fieldNameOrElement;
        }

        if ( !element ) return '';

        // Try to find label
        const label = this.$el.querySelector( `label[for="${ element.id }"]` );
        if ( label )
        {
            return label.textContent.replace( /[*:]/g, '' ).trim();
        }

        // Fallback to placeholder or name
        return element.placeholder || element.name || element.id || 'Field';
    },

    debounce ( func, wait )
    {
        let timeout;
        return function executedFunction ( ...args )
        {
            const later = () =>
            {
                clearTimeout( timeout );
                func( ...args );
            };
            clearTimeout( timeout );
            timeout = setTimeout( later, wait );
        };
    },

    cacheValidationResult ( key, result )
    {
        // Limit cache size
        if ( this.validationCache.size > 100 )
        {
            const firstKey = this.validationCache.keys().next().value;
            this.validationCache.delete( firstKey );
        }

        this.validationCache.set( key, result );
    },

    /**
     * Reset form validation
     */
    reset ()
    {
        this.errors = {};
        this.validationCache.clear();
        this.pendingValidations.clear();

        Object.keys( this.fields ).forEach( fieldName =>
        {
            this.clearFieldError( fieldName );
            this.updateFieldState( fieldName, {
                value: '',
                isValid: true,
                isDirty: false,
                isTouched: false,
                isValidating: false,
                lastValidated: null
            } );

            // Reset DOM element
            const element = this.fields[ fieldName ].element;
            if ( element )
            {
                element.value = '';
                element.classList.remove( 'error', 'border-red-500', 'border-green-500' );
                this.removeFieldIcon( element );
            }
        } );

        this.updateFormValidation();
        this.$dispatch( 'form-reset' );
    },

    /**
     * Get validation summary
     */
    getValidationSummary ()
    {
        const totalFields = Object.keys( this.fields ).length;
        const validFields = Object.values( this.fields ).filter( field => field.isValid ).length;
        const errorCount = Object.keys( this.errors ).length;
        const touchedFields = Object.values( this.fields ).filter( field => field.isTouched ).length;

        return {
            totalFields,
            validFields,
            errorCount,
            touchedFields,
            validationPercentage: Math.round( ( validFields / totalFields ) * 100 ),
            isComplete: this.isValid && touchedFields === totalFields
        };
    }
} );
