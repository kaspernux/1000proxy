/**
 * Multi-Step Wizard Component
 * 
 * Advanced multi-step form with progress indicators, validation, and data persistence
 * Features: progress tracking, step validation, localStorage persistence, responsive design
 */

export default () => ( {
    // Core state management
    currentStep: 1,
    totalSteps: 1,
    isValid: false,
    isSubmitting: false,
    formData: {},
    stepHistory: [],

    // Validation and error handling
    errors: {},
    stepErrors: {},
    validationRules: {},

    // UI state
    progressPercentage: 0,
    showStepPreview: false,
    animationDirection: 'forward',

    // Configuration
    persistenceKey: 'wizard_data',
    validateOnChange: true,
    showProgress: true,
    allowStepSkip: false,

    /**
     * Initialize the wizard component
     */
    init ()
    {
        // Auto-detect total steps from DOM
        this.totalSteps = this.$el.querySelectorAll( '[data-step]' ).length;

        // Load persisted data
        this.loadPersistedData();

        // Set up validation rules from data attributes
        this.setupValidationRules();

        // Update progress
        this.updateProgress();

        // Initialize first step
        this.showStep( this.currentStep );

        // Auto-save on data changes
        this.$watch( 'formData', () =>
        {
            this.saveToLocalStorage();
            if ( this.validateOnChange )
            {
                this.validateCurrentStep();
            }
        } );

        // Keyboard navigation
        this.setupKeyboardNavigation();

        console.log( 'Multi-step wizard initialized:', {
            currentStep: this.currentStep,
            totalSteps: this.totalSteps,
            persistenceKey: this.persistenceKey
        } );
    },

    /**
     * Navigate to specific step
     */
    goToStep ( step, direction = 'forward' )
    {
        if ( step < 1 || step > this.totalSteps ) return;

        // Validate current step before moving forward
        if ( direction === 'forward' && step > this.currentStep )
        {
            if ( !this.validateCurrentStep() )
            {
                this.showErrors();
                return false;
            }
        }

        // Add to history
        this.stepHistory.push( this.currentStep );

        // Set animation direction
        this.animationDirection = direction;

        // Hide current step
        this.hideStep( this.currentStep );

        // Update step
        this.currentStep = step;

        // Show new step with animation
        setTimeout( () =>
        {
            this.showStep( this.currentStep );
            this.updateProgress();
            this.scrollToTop();
        }, 150 );

        // Dispatch step change event
        this.$dispatch( 'step-changed', {
            step: this.currentStep,
            totalSteps: this.totalSteps,
            direction: direction,
            formData: this.formData
        } );

        return true;
    },

    /**
     * Move to next step
     */
    nextStep ()
    {
        if ( this.currentStep < this.totalSteps )
        {
            return this.goToStep( this.currentStep + 1, 'forward' );
        }
        return false;
    },

    /**
     * Move to previous step
     */
    prevStep ()
    {
        if ( this.currentStep > 1 )
        {
            return this.goToStep( this.currentStep - 1, 'backward' );
        }
        return false;
    },

    /**
     * Go to last visited step
     */
    goBack ()
    {
        if ( this.stepHistory.length > 0 )
        {
            const lastStep = this.stepHistory.pop();
            return this.goToStep( lastStep, 'backward' );
        }
        return this.prevStep();
    },

    /**
     * Show specific step
     */
    showStep ( step )
    {
        const stepElement = this.$el.querySelector( `[data-step="${ step }"]` );
        if ( stepElement )
        {
            stepElement.classList.remove( 'hidden', 'step-exit' );
            stepElement.classList.add( 'step-enter', `step-enter-${ this.animationDirection }` );

            // Focus first input in step
            setTimeout( () =>
            {
                const firstInput = stepElement.querySelector( 'input, select, textarea' );
                if ( firstInput )
                {
                    firstInput.focus();
                }
            }, 200 );
        }
    },

    /**
     * Hide specific step
     */
    hideStep ( step )
    {
        const stepElement = this.$el.querySelector( `[data-step="${ step }"]` );
        if ( stepElement )
        {
            stepElement.classList.remove( 'step-enter' );
            stepElement.classList.add( 'step-exit', `step-exit-${ this.animationDirection }` );

            setTimeout( () =>
            {
                stepElement.classList.add( 'hidden' );
                stepElement.classList.remove( 'step-exit', `step-exit-${ this.animationDirection }` );
            }, 150 );
        }
    },

    /**
     * Update progress indicators
     */
    updateProgress ()
    {
        this.progressPercentage = ( this.currentStep / this.totalSteps ) * 100;

        // Update step indicators
        this.$el.querySelectorAll( '[data-step-indicator]' ).forEach( ( indicator, index ) =>
        {
            const stepNumber = index + 1;
            const isCompleted = stepNumber < this.currentStep;
            const isCurrent = stepNumber === this.currentStep;

            indicator.classList.toggle( 'completed', isCompleted );
            indicator.classList.toggle( 'current', isCurrent );
            indicator.classList.toggle( 'pending', stepNumber > this.currentStep );
        } );

        // Update progress bar
        const progressBar = this.$el.querySelector( '[data-progress-bar]' );
        if ( progressBar )
        {
            progressBar.style.width = `${ this.progressPercentage }%`;
        }
    },

    /**
     * Validate current step
     */
    validateCurrentStep ()
    {
        const stepElement = this.$el.querySelector( `[data-step="${ this.currentStep }"]` );
        if ( !stepElement ) return true;

        const stepErrors = {};
        let isStepValid = true;

        // Get all inputs in current step
        const inputs = stepElement.querySelectorAll( 'input, select, textarea' );

        inputs.forEach( input =>
        {
            const fieldName = input.name || input.id;
            const rules = this.validationRules[ fieldName ] || [];
            const value = input.value?.trim();

            // Clear previous errors
            delete this.errors[ fieldName ];

            // Validate each rule
            for ( const rule of rules )
            {
                const result = this.validateField( value, rule, fieldName );
                if ( !result.valid )
                {
                    stepErrors[ fieldName ] = result.message;
                    this.errors[ fieldName ] = result.message;
                    isStepValid = false;
                    break;
                }
            }
        } );

        // Store step validation state
        this.stepErrors[ this.currentStep ] = stepErrors;
        this.isValid = isStepValid;

        return isStepValid;
    },

    /**
     * Validate individual field
     */
    validateField ( value, rule, fieldName )
    {
        switch ( rule.type )
        {
            case 'required':
                return {
                    valid: value && value.length > 0,
                    message: rule.message || `${ fieldName } is required`
                };

            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return {
                    valid: !value || emailRegex.test( value ),
                    message: rule.message || 'Please enter a valid email address'
                };

            case 'min':
                return {
                    valid: !value || value.length >= rule.value,
                    message: rule.message || `Minimum ${ rule.value } characters required`
                };

            case 'max':
                return {
                    valid: !value || value.length <= rule.value,
                    message: rule.message || `Maximum ${ rule.value } characters allowed`
                };

            case 'pattern':
                const regex = new RegExp( rule.pattern );
                return {
                    valid: !value || regex.test( value ),
                    message: rule.message || 'Invalid format'
                };

            case 'custom':
                return rule.validator( value, fieldName );

            default:
                return { valid: true };
        }
    },

    /**
     * Setup validation rules from data attributes
     */
    setupValidationRules ()
    {
        const inputs = this.$el.querySelectorAll( '[data-validation]' );

        inputs.forEach( input =>
        {
            const fieldName = input.name || input.id;
            const validationData = input.dataset.validation;

            try
            {
                this.validationRules[ fieldName ] = JSON.parse( validationData );
            } catch ( e )
            {
                console.warn( 'Invalid validation rules for field:', fieldName );
            }
        } );
    },

    /**
     * Show validation errors
     */
    showErrors ()
    {
        Object.keys( this.errors ).forEach( fieldName =>
        {
            const field = this.$el.querySelector( `[name="${ fieldName }"], #${ fieldName }` );
            if ( field )
            {
                field.classList.add( 'error' );

                // Show error message
                const errorEl = field.parentElement.querySelector( '.error-message' );
                if ( errorEl )
                {
                    errorEl.textContent = this.errors[ fieldName ];
                    errorEl.classList.remove( 'hidden' );
                }
            }
        } );
    },

    /**
     * Clear errors for field
     */
    clearFieldError ( fieldName )
    {
        delete this.errors[ fieldName ];

        const field = this.$el.querySelector( `[name="${ fieldName }"], #${ fieldName }` );
        if ( field )
        {
            field.classList.remove( 'error' );

            const errorEl = field.parentElement.querySelector( '.error-message' );
            if ( errorEl )
            {
                errorEl.classList.add( 'hidden' );
            }
        }
    },

    /**
     * Handle form submission
     */
    async submitForm ()
    {
        // Validate all steps
        if ( !this.validateAllSteps() )
        {
            this.showNotification( 'error', 'Please fix all validation errors before submitting' );
            return false;
        }

        this.isSubmitting = true;

        try
        {
            // Dispatch submit event with form data
            this.$dispatch( 'wizard-submit', {
                formData: this.formData,
                stepData: this.getStepData()
            } );

            // Clear persisted data on successful submit
            this.clearPersistedData();

            this.showNotification( 'success', 'Form submitted successfully!' );

        } catch ( error )
        {
            console.error( 'Form submission error:', error );
            this.showNotification( 'error', 'Failed to submit form. Please try again.' );
        } finally
        {
            this.isSubmitting = false;
        }
    },

    /**
     * Validate all steps
     */
    validateAllSteps ()
    {
        let allValid = true;

        for ( let step = 1; step <= this.totalSteps; step++ )
        {
            const originalStep = this.currentStep;
            this.currentStep = step;

            if ( !this.validateCurrentStep() )
            {
                allValid = false;
            }

            this.currentStep = originalStep;
        }

        return allValid;
    },

    /**
     * Get data for all steps
     */
    getStepData ()
    {
        const stepData = {};

        for ( let step = 1; step <= this.totalSteps; step++ )
        {
            const stepElement = this.$el.querySelector( `[data-step="${ step }"]` );
            if ( stepElement )
            {
                stepData[ step ] = this.getStepFormData( stepElement );
            }
        }

        return stepData;
    },

    /**
     * Get form data for specific step
     */
    getStepFormData ( stepElement )
    {
        const data = {};
        const inputs = stepElement.querySelectorAll( 'input, select, textarea' );

        inputs.forEach( input =>
        {
            const name = input.name || input.id;
            if ( name )
            {
                if ( input.type === 'checkbox' )
                {
                    data[ name ] = input.checked;
                } else if ( input.type === 'radio' )
                {
                    if ( input.checked )
                    {
                        data[ name ] = input.value;
                    }
                } else
                {
                    data[ name ] = input.value;
                }
            }
        } );

        return data;
    },

    /**
     * Persistence methods
     */
    saveToLocalStorage ()
    {
        if ( this.persistenceKey )
        {
            const data = {
                currentStep: this.currentStep,
                formData: this.formData,
                stepHistory: this.stepHistory,
                timestamp: Date.now()
            };

            localStorage.setItem( this.persistenceKey, JSON.stringify( data ) );
        }
    },

    loadPersistedData ()
    {
        if ( this.persistenceKey )
        {
            const saved = localStorage.getItem( this.persistenceKey );
            if ( saved )
            {
                try
                {
                    const data = JSON.parse( saved );

                    // Check if data is not too old (24 hours)
                    if ( Date.now() - data.timestamp < 24 * 60 * 60 * 1000 )
                    {
                        this.currentStep = data.currentStep || 1;
                        this.formData = { ...this.formData, ...data.formData };
                        this.stepHistory = data.stepHistory || [];
                    }
                } catch ( e )
                {
                    console.warn( 'Failed to load persisted wizard data' );
                }
            }
        }
    },

    clearPersistedData ()
    {
        if ( this.persistenceKey )
        {
            localStorage.removeItem( this.persistenceKey );
        }
    },

    /**
     * Keyboard navigation
     */
    setupKeyboardNavigation ()
    {
        this.$el.addEventListener( 'keydown', ( e ) =>
        {
            if ( e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' ) return;

            switch ( e.key )
            {
                case 'ArrowRight':
                case 'ArrowDown':
                    e.preventDefault();
                    this.nextStep();
                    break;

                case 'ArrowLeft':
                case 'ArrowUp':
                    e.preventDefault();
                    this.prevStep();
                    break;

                case 'Home':
                    e.preventDefault();
                    this.goToStep( 1 );
                    break;

                case 'End':
                    e.preventDefault();
                    this.goToStep( this.totalSteps );
                    break;

                case 'Escape':
                    e.preventDefault();
                    this.goBack();
                    break;
            }
        } );
    },

    /**
     * Utility methods
     */
    scrollToTop ()
    {
        this.$el.scrollIntoView( { behavior: 'smooth', block: 'start' } );
    },

    showNotification ( type, message )
    {
        this.$dispatch( 'show-notification', { type, message } );
    },

    /**
     * Export wizard data
     */
    exportData ()
    {
        const data = {
            formData: this.formData,
            stepData: this.getStepData(),
            meta: {
                currentStep: this.currentStep,
                totalSteps: this.totalSteps,
                isValid: this.validateAllSteps(),
                exportedAt: new Date().toISOString()
            }
        };

        const blob = new Blob( [ JSON.stringify( data, null, 2 ) ], { type: 'application/json' } );
        const url = URL.createObjectURL( blob );
        const a = document.createElement( 'a' );
        a.href = url;
        a.download = `wizard-data-${ Date.now() }.json`;
        a.click();
        URL.revokeObjectURL( url );
    },

    /**
     * Reset wizard to initial state
     */
    reset ()
    {
        this.currentStep = 1;
        this.formData = {};
        this.stepHistory = [];
        this.errors = {};
        this.stepErrors = {};
        this.clearPersistedData();
        this.updateProgress();
        this.showStep( 1 );

        // Clear all form inputs
        this.$el.querySelectorAll( 'input, select, textarea' ).forEach( input =>
        {
            if ( input.type === 'checkbox' || input.type === 'radio' )
            {
                input.checked = false;
            } else
            {
                input.value = '';
            }
            input.classList.remove( 'error' );
        } );

        this.$dispatch( 'wizard-reset' );
    }
} );
