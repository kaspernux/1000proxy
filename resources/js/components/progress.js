/**
 * Advanced Progress Bar Component
 * Supports multiple styles, animations, and real-time updates
 */
export default () => ( {
    // Component State
    value: 0,
    max: 100,
    min: 0,

    // Configuration
    size: 'md', // xs, sm, md, lg, xl
    variant: 'default', // default, success, warning, danger, info, gradient
    showPercentage: true,
    showValue: false,
    animated: true,
    striped: false,

    // Labels
    label: '',
    description: '',

    // Animation
    isAnimating: false,
    animationDuration: 300,

    // Multiple Progress Bars
    segments: [], // For stacked progress bars

    // Lifecycle
    init ()
    {
        this.$watch( 'value', () => this.handleValueChange() );
        this.clampValue();
    },

    // Event Handlers
    setValue ( newValue )
    {
        this.isAnimating = true;
        this.value = this.clamp( newValue );

        setTimeout( () =>
        {
            this.isAnimating = false;
        }, this.animationDuration );
    },

    increment ( amount = 1 )
    {
        this.setValue( this.value + amount );
    },

    decrement ( amount = 1 )
    {
        this.setValue( this.value - amount );
    },

    reset ()
    {
        this.setValue( this.min );
    },

    complete ()
    {
        this.setValue( this.max );
    },

    handleValueChange ()
    {
        this.clampValue();
        this.$dispatch( 'progress-changed', {
            value: this.value,
            percentage: this.getPercentage(),
            isComplete: this.isComplete()
        } );
    },

    // Calculations
    clamp ( value )
    {
        return Math.min( Math.max( value, this.min ), this.max );
    },

    clampValue ()
    {
        this.value = this.clamp( this.value );
    },

    getPercentage ()
    {
        if ( this.max === this.min ) return 0;
        return ( ( this.value - this.min ) / ( this.max - this.min ) ) * 100;
    },

    getWidth ()
    {
        return `${ this.getPercentage() }%`;
    },

    isComplete ()
    {
        return this.value >= this.max;
    },

    // Style Getters
    getContainerClasses ()
    {
        const baseClasses = [
            'relative overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700'
        ];

        // Size classes
        const sizeClasses = {
            xs: 'h-1',
            sm: 'h-2',
            md: 'h-3',
            lg: 'h-4',
            xl: 'h-6'
        };

        return [
            ...baseClasses,
            sizeClasses[ this.size ]
        ].join( ' ' );
    },

    getBarClasses ()
    {
        const baseClasses = [
            'h-full transition-all ease-in-out flex items-center justify-end pr-2'
        ];

        // Variant classes
        const variantClasses = {
            default: 'bg-blue-600',
            success: 'bg-green-600',
            warning: 'bg-yellow-600',
            danger: 'bg-red-600',
            info: 'bg-cyan-600',
            gradient: 'bg-gradient-to-r from-blue-500 to-purple-600'
        };

        // Animation classes
        const animationClasses = [];
        if ( this.animated )
        {
            animationClasses.push( `duration-${ this.animationDuration }` );
        }

        if ( this.striped )
        {
            animationClasses.push(
                'bg-gradient-to-r',
                'bg-[length:1rem_1rem]',
                'animate-pulse'
            );
        }

        return [
            ...baseClasses,
            variantClasses[ this.variant ],
            ...animationClasses
        ].join( ' ' );
    },

    getTextClasses ()
    {
        const baseClasses = [ 'text-xs font-medium text-white' ];

        // Size-based text visibility
        const sizeTextClasses = {
            xs: 'hidden',
            sm: 'hidden',
            md: 'hidden',
            lg: 'block',
            xl: 'block'
        };

        return [
            ...baseClasses,
            sizeTextClasses[ this.size ]
        ].join( ' ' );
    },

    // Display Methods
    getDisplayText ()
    {
        if ( this.showPercentage && this.showValue )
        {
            return `${ this.value }/${ this.max } (${ Math.round( this.getPercentage() ) }%)`;
        }
        if ( this.showPercentage )
        {
            return `${ Math.round( this.getPercentage() ) }%`;
        }
        if ( this.showValue )
        {
            return `${ this.value }/${ this.max }`;
        }
        return '';
    },

    // Stacked Progress Support
    calculateSegmentWidth ( segment )
    {
        const totalValue = this.segments.reduce( ( sum, seg ) => sum + seg.value, 0 );
        if ( totalValue === 0 ) return '0%';
        return `${ ( segment.value / totalValue ) * 100 }%`;
    },

    getSegmentClasses ( segment, index )
    {
        const baseClasses = [
            'h-full transition-all duration-300 ease-in-out',
            'flex items-center justify-center text-xs font-medium text-white'
        ];

        // Default colors for segments
        const colors = [
            'bg-blue-600',
            'bg-green-600',
            'bg-yellow-600',
            'bg-red-600',
            'bg-purple-600',
            'bg-cyan-600'
        ];

        const colorClass = segment.color || colors[ index % colors.length ];

        return [ ...baseClasses, colorClass ].join( ' ' );
    },

    // Accessibility
    getAriaLabel ()
    {
        return `Progress: ${ this.getDisplayText() }${ this.label ? ` - ${ this.label }` : '' }`;
    },

    getAriaValueText ()
    {
        return this.getDisplayText();
    },

    // Validation
    isValid ()
    {
        return this.value >= this.min && this.value <= this.max;
    }
} );
