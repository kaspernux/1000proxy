/**
 * Accessible Form Components
 * Comprehensive form accessibility with ARIA support, validation announcements,
 * and keyboard navigation for all form interactions
 */

window.accessibleForm = function ( config = {} )
{
    return {
        // Configuration
        formId: config.formId || 'accessible-form',
        validateOnBlur: config.validateOnBlur !== false,
        validateOnInput: config.validateOnInput !== false,
        announceErrors: config.announceErrors !== false,
        showValidationSummary: config.showValidationSummary !== false,

        // State
        fields: config.fields || {},
        errors: {},
        touched: {},
        isSubmitting: false,
        submitAttempted: false,

        // Accessibility state
        errorSummaryId: 'form-error-summary',
        announcer: null,
        focusTrap: null,

        // Validation rules
        validationRules: config.validationRules || {},

        init ()
        {
            this.setupAccessibilityFeatures();
            this.setupValidationAnnouncements();
            this.setupKeyboardNavigation();
            this.setupFormSubmission();
            this.setupFieldValidation();
            this.enhanceFormElements();
        },

        setupAccessibilityFeatures ()
        {
            // Create announcer for validation messages
            this.announcer = document.createElement( 'div' );
            this.announcer.setAttribute( 'aria-live', 'assertive' );
            this.announcer.setAttribute( 'aria-atomic', 'true' );
            this.announcer.className = 'sr-only';
            document.body.appendChild( this.announcer );

            // Setup form attributes
            this.$nextTick( () =>
            {
                const form = this.$el.querySelector( 'form' );
                if ( form )
                {
                    form.setAttribute( 'novalidate', '' ); // Use custom validation
                    form.setAttribute( 'aria-label', config.formLabel || 'Form' );

                    if ( this.showValidationSummary )
                    {
                        form.setAttribute( 'aria-describedby', this.errorSummaryId );
                    }
                }
            } );
        },

        enhanceFormElements ()
        {
            this.$nextTick( () =>
            {
                // Enhance input fields
                const inputs = this.$el.querySelectorAll( 'input, textarea, select' );
                inputs.forEach( input =>
                {
                    this.enhanceField( input );
                } );

                // Enhance buttons
                const buttons = this.$el.querySelectorAll( 'button' );
                buttons.forEach( button =>
                {
                    this.enhanceButton( button );
                } );

                // Enhance fieldsets and legends
                const fieldsets = this.$el.querySelectorAll( 'fieldset' );
                fieldsets.forEach( fieldset =>
                {
                    this.enhanceFieldset( fieldset );
                } );
            } );
        },

        enhanceField ( field )
        {
            const fieldName = field.name || field.id;
            if ( !fieldName ) return;

            // Add required ARIA attributes
            field.setAttribute( 'aria-describedby', this.getFieldDescribedBy( fieldName ) );

            // Add validation attributes
            if ( this.validationRules[ fieldName ] )
            {
                const rules = this.validationRules[ fieldName ];

                if ( rules.required )
                {
                    field.setAttribute( 'required', '' );
                    field.setAttribute( 'aria-required', 'true' );
                }

                if ( rules.minLength )
                {
                    field.setAttribute( 'minlength', rules.minLength );
                }

                if ( rules.maxLength )
                {
                    field.setAttribute( 'maxlength', rules.maxLength );
                }

                if ( rules.pattern )
                {
                    field.setAttribute( 'pattern', rules.pattern );
                }

                if ( rules.type === 'email' )
                {
                    field.setAttribute( 'type', 'email' );
                }

                if ( rules.type === 'tel' )
                {
                    field.setAttribute( 'type', 'tel' );
                }
            }

            // Add event listeners for validation
            field.addEventListener( 'blur', () =>
            {
                if ( this.validateOnBlur )
                {
                    this.validateField( fieldName );
                    this.touched[ fieldName ] = true;
                }
            } );

            field.addEventListener( 'input', () =>
            {
                if ( this.validateOnInput && this.touched[ fieldName ] )
                {
                    this.validateField( fieldName );
                }
            } );

            // Add invalid/valid state handling
            this.updateFieldAriaAttributes( field, fieldName );
        },

        enhanceButton ( button )
        {
            // Ensure buttons have proper type
            if ( !button.hasAttribute( 'type' ) )
            {
                button.setAttribute( 'type', 'button' );
            }

            // Add loading state handling for submit buttons
            if ( button.type === 'submit' )
            {
                button.addEventListener( 'click', () =>
                {
                    if ( this.isSubmitting )
                    {
                        button.setAttribute( 'aria-busy', 'true' );
                        button.setAttribute( 'aria-describedby', 'submit-status' );

                        if ( !document.getElementById( 'submit-status' ) )
                        {
                            const status = document.createElement( 'div' );
                            status.id = 'submit-status';
                            status.className = 'sr-only';
                            status.textContent = 'Form is being submitted, please wait';
                            document.body.appendChild( status );
                        }
                    }
                } );
            }
        },

        enhanceFieldset ( fieldset )
        {
            const legend = fieldset.querySelector( 'legend' );
            if ( legend )
            {
                // Ensure fieldset is properly labeled
                if ( !fieldset.hasAttribute( 'aria-labelledby' ) && legend.id )
                {
                    fieldset.setAttribute( 'aria-labelledby', legend.id );
                }
            }

            // Handle radio button groups and checkboxes
            const radioGroups = fieldset.querySelectorAll( 'input[type="radio"]' );
            const checkboxes = fieldset.querySelectorAll( 'input[type="checkbox"]' );

            if ( radioGroups.length > 0 )
            {
                this.enhanceRadioGroup( fieldset, radioGroups );
            }

            if ( checkboxes.length > 0 )
            {
                this.enhanceCheckboxGroup( fieldset, checkboxes );
            }
        },

        enhanceRadioGroup ( fieldset, radioButtons )
        {
            const groupName = radioButtons[ 0 ].name;

            radioButtons.forEach( ( radio, index ) =>
            {
                // Add roving tabindex for keyboard navigation
                radio.setAttribute( 'tabindex', index === 0 ? '0' : '-1' );

                radio.addEventListener( 'keydown', ( e ) =>
                {
                    this.handleRadioNavigation( e, radioButtons, index );
                } );

                radio.addEventListener( 'focus', () =>
                {
                    radioButtons.forEach( r => r.setAttribute( 'tabindex', '-1' ) );
                    radio.setAttribute( 'tabindex', '0' );
                } );
            } );
        },

        enhanceCheckboxGroup ( fieldset, checkboxes )
        {
            checkboxes.forEach( checkbox =>
            {
                // Add role if part of a group
                if ( checkboxes.length > 1 )
                {
                    checkbox.setAttribute( 'role', 'checkbox' );
                }
            } );
        },

        handleRadioNavigation ( event, radioButtons, currentIndex )
        {
            let newIndex = currentIndex;

            switch ( event.key )
            {
                case 'ArrowDown':
                case 'ArrowRight':
                    event.preventDefault();
                    newIndex = ( currentIndex + 1 ) % radioButtons.length;
                    break;
                case 'ArrowUp':
                case 'ArrowLeft':
                    event.preventDefault();
                    newIndex = currentIndex === 0 ? radioButtons.length - 1 : currentIndex - 1;
                    break;
                default:
                    return;
            }

            radioButtons[ newIndex ].focus();
            radioButtons[ newIndex ].checked = true;
            this.fields[ radioButtons[ newIndex ].name ] = radioButtons[ newIndex ].value;
        },

        getFieldDescribedBy ( fieldName )
        {
            const describedBy = [];

            // Add help text
            describedBy.push( `${ fieldName }-help` );

            // Add error message if exists
            if ( this.errors[ fieldName ] )
            {
                describedBy.push( `${ fieldName }-error` );
            }

            return describedBy.join( ' ' );
        },

        updateFieldAriaAttributes ( field, fieldName )
        {
            const hasError = !!this.errors[ fieldName ];

            field.setAttribute( 'aria-invalid', hasError ? 'true' : 'false' );
            field.setAttribute( 'aria-describedby', this.getFieldDescribedBy( fieldName ) );

            // Update visual state
            if ( hasError )
            {
                field.classList.add( 'border-red-500', 'focus:ring-red-500' );
                field.classList.remove( 'border-gray-300', 'focus:ring-blue-500' );
            } else
            {
                field.classList.remove( 'border-red-500', 'focus:ring-red-500' );
                field.classList.add( 'border-gray-300', 'focus:ring-blue-500' );
            }
        },

        validateField ( fieldName )
        {
            const field = this.$el.querySelector( `[name="${ fieldName }"], #${ fieldName }` );
            if ( !field ) return true;

            const value = field.value.trim();
            const rules = this.validationRules[ fieldName ];

            if ( !rules ) return true;

            // Clear previous error
            delete this.errors[ fieldName ];

            // Required validation
            if ( rules.required && !value )
            {
                this.errors[ fieldName ] = rules.messages?.required || `${ this.getFieldLabel( fieldName ) } is required`;
                this.announceFieldError( fieldName, this.errors[ fieldName ] );
                this.updateFieldAriaAttributes( field, fieldName );
                return false;
            }

            // Length validation
            if ( value && rules.minLength && value.length < rules.minLength )
            {
                this.errors[ fieldName ] = rules.messages?.minLength || `${ this.getFieldLabel( fieldName ) } must be at least ${ rules.minLength } characters`;
                this.announceFieldError( fieldName, this.errors[ fieldName ] );
                this.updateFieldAriaAttributes( field, fieldName );
                return false;
            }

            if ( value && rules.maxLength && value.length > rules.maxLength )
            {
                this.errors[ fieldName ] = rules.messages?.maxLength || `${ this.getFieldLabel( fieldName ) } must be no more than ${ rules.maxLength } characters`;
                this.announceFieldError( fieldName, this.errors[ fieldName ] );
                this.updateFieldAriaAttributes( field, fieldName );
                return false;
            }

            // Pattern validation
            if ( value && rules.pattern && !new RegExp( rules.pattern ).test( value ) )
            {
                this.errors[ fieldName ] = rules.messages?.pattern || `${ this.getFieldLabel( fieldName ) } format is invalid`;
                this.announceFieldError( fieldName, this.errors[ fieldName ] );
                this.updateFieldAriaAttributes( field, fieldName );
                return false;
            }

            // Email validation
            if ( value && rules.type === 'email' && !this.isValidEmail( value ) )
            {
                this.errors[ fieldName ] = rules.messages?.email || `${ this.getFieldLabel( fieldName ) } must be a valid email address`;
                this.announceFieldError( fieldName, this.errors[ fieldName ] );
                this.updateFieldAriaAttributes( field, fieldName );
                return false;
            }

            // Custom validation
            if ( rules.custom && typeof rules.custom === 'function' )
            {
                const customResult = rules.custom( value, this.fields );
                if ( customResult !== true )
                {
                    this.errors[ fieldName ] = customResult;
                    this.announceFieldError( fieldName, this.errors[ fieldName ] );
                    this.updateFieldAriaAttributes( field, fieldName );
                    return false;
                }
            }

            // Field is valid
            this.updateFieldAriaAttributes( field, fieldName );
            return true;
        },

        validateForm ()
        {
            let isValid = true;

            Object.keys( this.validationRules ).forEach( fieldName =>
            {
                if ( !this.validateField( fieldName ) )
                {
                    isValid = false;
                }
                this.touched[ fieldName ] = true;
            } );

            if ( !isValid )
            {
                this.announceValidationSummary();
                this.focusFirstError();
            }

            return isValid;
        },

        announceFieldError ( fieldName, message )
        {
            if ( this.announceErrors )
            {
                setTimeout( () =>
                {
                    this.announcer.textContent = `${ this.getFieldLabel( fieldName ) }: ${ message }`;
                }, 100 );
            }
        },

        announceValidationSummary ()
        {
            const errorCount = Object.keys( this.errors ).length;
            if ( errorCount > 0 )
            {
                const message = `Form contains ${ errorCount } error${ errorCount > 1 ? 's' : '' }. Please review and correct the highlighted fields.`;
                this.announcer.textContent = message;
            }
        },

        focusFirstError ()
        {
            const firstErrorField = Object.keys( this.errors )[ 0 ];
            if ( firstErrorField )
            {
                const field = this.$el.querySelector( `[name="${ firstErrorField }"], #${ firstErrorField }` );
                if ( field )
                {
                    field.focus();
                    field.scrollIntoView( { behavior: 'smooth', block: 'center' } );
                }
            }
        },

        getFieldLabel ( fieldName )
        {
            const field = this.$el.querySelector( `[name="${ fieldName }"], #${ fieldName }` );
            if ( !field ) return fieldName;

            // Try to find associated label
            const label = this.$el.querySelector( `label[for="${ field.id }"]` ) ||
                field.closest( 'label' ) ||
                this.$el.querySelector( `label[for="${ fieldName }"]` );

            if ( label )
            {
                return label.textContent.trim().replace( '*', '' ).trim();
            }

            // Try aria-label
            if ( field.getAttribute( 'aria-label' ) )
            {
                return field.getAttribute( 'aria-label' );
            }

            // Try placeholder as fallback
            if ( field.placeholder )
            {
                return field.placeholder;
            }

            return fieldName;
        },

        isValidEmail ( email )
        {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test( email );
        },

        setupFormSubmission ()
        {
            this.$el.addEventListener( 'submit', ( e ) =>
            {
                e.preventDefault();
                this.submitAttempted = true;

                if ( this.validateForm() )
                {
                    this.handleFormSubmit();
                }
            } );
        },

        async handleFormSubmit ()
        {
            this.isSubmitting = true;

            try
            {
                const submitButton = this.$el.querySelector( 'button[type="submit"]' );
                if ( submitButton )
                {
                    submitButton.setAttribute( 'aria-busy', 'true' );
                    submitButton.disabled = true;
                }

                // Simulate form submission (replace with actual submission logic)
                await new Promise( resolve => setTimeout( resolve, 1000 ) );

                this.announcer.textContent = 'Form submitted successfully';

                // Reset form if needed
                if ( config.resetOnSuccess )
                {
                    this.resetForm();
                }

            } catch ( error )
            {
                this.announcer.textContent = 'Form submission failed. Please try again.';
                console.error( 'Form submission error:', error );
            } finally
            {
                this.isSubmitting = false;

                const submitButton = this.$el.querySelector( 'button[type="submit"]' );
                if ( submitButton )
                {
                    submitButton.setAttribute( 'aria-busy', 'false' );
                    submitButton.disabled = false;
                }
            }
        },

        resetForm ()
        {
            this.fields = {};
            this.errors = {};
            this.touched = {};
            this.submitAttempted = false;

            // Reset form elements
            const form = this.$el.querySelector( 'form' );
            if ( form )
            {
                form.reset();
            }

            // Update ARIA attributes
            this.$nextTick( () =>
            {
                this.enhanceFormElements();
            } );
        },

        setupKeyboardNavigation ()
        {
            // Handle Escape key to clear errors
            this.$el.addEventListener( 'keydown', ( e ) =>
            {
                if ( e.key === 'Escape' )
                {
                    this.clearFieldErrors();
                }
            } );
        },

        clearFieldErrors ()
        {
            this.errors = {};

            this.$nextTick( () =>
            {
                const fields = this.$el.querySelectorAll( 'input, textarea, select' );
                fields.forEach( field =>
                {
                    const fieldName = field.name || field.id;
                    if ( fieldName )
                    {
                        this.updateFieldAriaAttributes( field, fieldName );
                    }
                } );
            } );
        },

        setupValidationAnnouncements ()
        {
            // Announce validation summary when errors change
            this.$watch( 'errors', () =>
            {
                if ( this.submitAttempted && Object.keys( this.errors ).length > 0 )
                {
                    this.announceValidationSummary();
                }
            } );
        },

        // Get validation summary for screen readers
        get validationSummary ()
        {
            const errorCount = Object.keys( this.errors ).length;
            if ( errorCount === 0 ) return '';

            return `This form contains ${ errorCount } error${ errorCount > 1 ? 's' : '' }:
${ Object.entries( this.errors ).map( ( [ field, message ] ) => `${ this.getFieldLabel( field ) }: ${ message }` ).join( ', ' ) }`;
        },

        // Cleanup when component is destroyed
        destroy ()
        {
            if ( this.announcer && this.announcer.parentNode )
            {
                this.announcer.parentNode.removeChild( this.announcer );
            }

            const statusElement = document.getElementById( 'submit-status' );
            if ( statusElement && statusElement.parentNode )
            {
                statusElement.parentNode.removeChild( statusElement );
            }
        }
    };
};

// Enhanced form template with accessibility features
window.accessibleFormTemplate = `
<div class="accessible-form" x-data="accessibleForm(formConfig)" x-init="init()">
    <!-- Validation summary for screen readers -->
    <div
        x-show="showValidationSummary && submitAttempted && Object.keys(errors).length > 0"
        :id="errorSummaryId"
        class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md"
        role="alert"
        aria-live="assertive"
    >
        <h3 class="text-sm font-medium text-red-800 mb-2">Please correct the following errors:</h3>
        <ul class="text-sm text-red-700 list-disc list-inside">
            <template x-for="[field, message] in Object.entries(errors)" :key="field">
                <li>
                    <button
                        type="button"
                        class="underline hover:no-underline focus:outline-none focus:ring-2 focus:ring-red-500"
                        @click="$el.closest('.accessible-form').querySelector('[name=\\"' + field + '\\"], #' + field).focus()"
                        x-text="getFieldLabel(field) + ': ' + message"
                    ></button>
                </li>
            </template>
        </ul>
    </div>

    <form x-ref="form" @submit.prevent="handleFormSubmit()">
        <!-- Form fields will be inserted here -->
        <slot></slot>

        <!-- Submit button with loading state -->
        <div class="mt-6">
            <button
                type="submit"
                :disabled="isSubmitting"
                :aria-busy="isSubmitting"
                class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-400 disabled:cursor-not-allowed"
            >
                <span x-show="!isSubmitting" x-text="config.submitText || 'Submit'"></span>
                <span x-show="isSubmitting" class="flex items-center justify-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="config.submitTextLoading || 'Submitting...'"></span>
                </span>
            </button>
        </div>
    </form>
</div>
`;

// Field component template
window.accessibleFieldTemplate = `
<div class="form-field mb-4" x-data="{ fieldName: field.name || field.id }">
    <!-- Label -->
    <label
        :for="fieldName"
        class="block text-sm font-medium text-gray-700 mb-1"
        :class="{ 'text-red-700': errors[fieldName] }"
    >
        <span x-text="field.label"></span>
        <span x-show="field.required" class="text-red-500 ml-1" aria-label="required">*</span>
    </label>

    <!-- Help text -->
    <div
        x-show="field.helpText"
        :id="fieldName + '-help'"
        class="text-sm text-gray-600 mb-1"
        x-text="field.helpText"
    ></div>

    <!-- Input field -->
    <input
        :type="field.type || 'text'"
        :id="fieldName"
        :name="fieldName"
        x-model="fields[fieldName]"
        :required="field.required"
        :placeholder="field.placeholder"
        :aria-describedby="getFieldDescribedBy(fieldName)"
        :aria-invalid="errors[fieldName] ? 'true' : 'false'"
        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
        :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': errors[fieldName] }"
    />

    <!-- Error message -->
    <div
        x-show="errors[fieldName]"
        :id="fieldName + '-error'"
        class="mt-1 text-sm text-red-600"
        role="alert"
        aria-live="polite"
        x-text="errors[fieldName]"
    ></div>
</div>
`;

// CSS for accessible forms
const accessibleFormCSS = `
.accessible-form .form-field:focus-within label {
    color: #3B82F6;
}

.accessible-form input:focus,
.accessible-form textarea:focus,
.accessible-form select:focus {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.accessible-form [aria-invalid="true"] {
    border-color: #EF4444;
}

.accessible-form [aria-invalid="true"]:focus {
    border-color: #EF4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .accessible-form {
        --form-border-color: #000;
        --form-error-color: #CC0000;
        --form-focus-color: #0000FF;
    }

    .accessible-form input,
    .accessible-form textarea,
    .accessible-form select {
        border-color: var(--form-border-color);
    }

    .accessible-form [aria-invalid="true"] {
        border-color: var(--form-error-color);
    }

    .accessible-form input:focus,
    .accessible-form textarea:focus,
    .accessible-form select:focus {
        outline: 2px solid var(--form-focus-color);
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .accessible-form * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
`;

// Inject accessible form CSS
if ( !document.getElementById( 'accessibility-form-styles' ) )
{
    const style = document.createElement( 'style' );
    style.id = 'accessibility-form-styles';
    style.textContent = accessibleFormCSS;
    document.head.appendChild( style );
}
