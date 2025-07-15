/**
 * Advanced Toggle Switch Component
 * Supports various sizes, colors, labels, and states
 */
export default () => ( {
    // Component State
    checked: false,
    disabled: false,
    loading: false,

    // Configuration
    size: 'md', // xs, sm, md, lg, xl
    variant: 'default', // default, success, warning, danger, info
    showLabels: true,
    labelOn: 'On',
    labelOff: 'Off',
    description: '',

    // Animation
    isAnimating: false,

    // Lifecycle
    init ()
    {
        this.$watch( 'checked', () => this.handleChange() );
    },

    // Event Handlers
    toggle ()
    {
        if ( this.disabled || this.loading ) return;

        this.isAnimating = true;
        this.checked = !this.checked;

        // Reset animation state
        setTimeout( () =>
        {
            this.isAnimating = false;
        }, 200 );
    },

    handleChange ()
    {
        this.$dispatch( 'toggle-changed', {
            checked: this.checked,
            value: this.checked
        } );
    },

    // Style Getters
    getSwitchClasses ()
    {
        const baseClasses = [
            'relative inline-flex items-center cursor-pointer transition-all duration-200 ease-in-out',
            'focus:outline-none focus:ring-2 focus:ring-offset-2 rounded-full'
        ];

        // Size classes
        const sizeClasses = {
            xs: 'w-8 h-4',
            sm: 'w-10 h-5',
            md: 'w-12 h-6',
            lg: 'w-14 h-7',
            xl: 'w-16 h-8'
        };

        // Variant classes
        const variantClasses = {
            default: this.checked ? 'bg-blue-600' : 'bg-gray-300',
            success: this.checked ? 'bg-green-600' : 'bg-gray-300',
            warning: this.checked ? 'bg-yellow-600' : 'bg-gray-300',
            danger: this.checked ? 'bg-red-600' : 'bg-gray-300',
            info: this.checked ? 'bg-cyan-600' : 'bg-gray-300'
        };

        const focusClasses = {
            default: 'focus:ring-blue-500',
            success: 'focus:ring-green-500',
            warning: 'focus:ring-yellow-500',
            danger: 'focus:ring-red-500',
            info: 'focus:ring-cyan-500'
        };

        // State classes
        const stateClasses = [];
        if ( this.disabled )
        {
            stateClasses.push( 'opacity-50', 'cursor-not-allowed' );
        }
        if ( this.loading )
        {
            stateClasses.push( 'cursor-wait' );
        }

        return [
            ...baseClasses,
            sizeClasses[ this.size ],
            variantClasses[ this.variant ],
            focusClasses[ this.variant ],
            ...stateClasses
        ].join( ' ' );
    },

    getThumbClasses ()
    {
        const baseClasses = [
            'bg-white rounded-full shadow-lg transform transition-transform duration-200 ease-in-out',
            'flex items-center justify-center'
        ];

        // Size classes for thumb
        const sizeClasses = {
            xs: 'w-3 h-3',
            sm: 'w-4 h-4',
            md: 'w-5 h-5',
            lg: 'w-6 h-6',
            xl: 'w-7 h-7'
        };

        // Transform classes based on state
        const transformClasses = {
            xs: this.checked ? 'translate-x-4' : 'translate-x-0',
            sm: this.checked ? 'translate-x-5' : 'translate-x-0',
            md: this.checked ? 'translate-x-6' : 'translate-x-0',
            lg: this.checked ? 'translate-x-7' : 'translate-x-0',
            xl: this.checked ? 'translate-x-9' : 'translate-x-0'
        };

        // Animation classes
        const animationClasses = this.isAnimating ? [ 'animate-pulse' ] : [];

        return [
            ...baseClasses,
            sizeClasses[ this.size ],
            transformClasses[ this.size ],
            ...animationClasses
        ].join( ' ' );
    },

    getIconSize ()
    {
        const iconSizes = {
            xs: 'w-2 h-2',
            sm: 'w-2.5 h-2.5',
            md: 'w-3 h-3',
            lg: 'w-3.5 h-3.5',
            xl: 'w-4 h-4'
        };

        return iconSizes[ this.size ];
    },

    // Accessibility
    getAriaLabel ()
    {
        if ( this.description )
        {
            return `${ this.description }: ${ this.checked ? this.labelOn : this.labelOff }`;
        }
        return `Toggle switch: ${ this.checked ? this.labelOn : this.labelOff }`;
    },

    // Validation
    isValid ()
    {
        return !this.disabled && !this.loading;
    }
} );
