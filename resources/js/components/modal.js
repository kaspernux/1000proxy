/**
 * Advanced Modal Component with Backdrop Blur and Animations
 * Supports various sizes, animations, and accessibility features
 */
export default () => ( {
    // Component State
    isOpen: false,
    isAnimating: false,
    allowBackdropClose: true,
    allowEscapeClose: true,
    preventScroll: true,

    // Configuration
    size: 'md', // xs, sm, md, lg, xl, full
    position: 'center', // center, top, bottom
    animation: 'fade', // fade, slide-up, slide-down, scale, none
    backdrop: 'blur', // blur, dark, light, none

    // Content
    title: '',
    description: '',
    showCloseButton: true,

    // Lifecycle
    init ()
    {
        this.$watch( 'isOpen', ( value ) =>
        {
            if ( value )
            {
                this.openModal();
            } else
            {
                this.closeModal();
            }
        } );

        this.setupEventListeners();
    },

    // Event Handlers
    open ()
    {
        this.isOpen = true;
    },

    close ()
    {
        if ( this.isAnimating ) return;
        this.isOpen = false;
    },

    openModal ()
    {
        this.isAnimating = true;

        // Prevent body scroll if enabled
        if ( this.preventScroll )
        {
            document.body.style.overflow = 'hidden';
        }

        // Focus management
        this.$nextTick( () =>
        {
            const firstFocusable = this.$refs.modal?.querySelector(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            firstFocusable?.focus();
        } );

        // Animation completion
        setTimeout( () =>
        {
            this.isAnimating = false;
        }, 300 );

        this.$dispatch( 'modal-opened' );
    },

    closeModal ()
    {
        this.isAnimating = true;

        // Restore body scroll
        if ( this.preventScroll )
        {
            document.body.style.overflow = '';
        }

        // Animation completion
        setTimeout( () =>
        {
            this.isAnimating = false;
            this.$dispatch( 'modal-closed' );
        }, 300 );
    },

    handleBackdropClick ( event )
    {
        if ( this.allowBackdropClose && event.target === event.currentTarget )
        {
            this.close();
        }
    },

    // Event Listeners
    setupEventListeners ()
    {
        // Escape key handler
        document.addEventListener( 'keydown', ( e ) =>
        {
            if ( e.key === 'Escape' && this.isOpen && this.allowEscapeClose )
            {
                this.close();
            }
        } );

        // Focus trap
        this.$el.addEventListener( 'keydown', ( e ) =>
        {
            if ( e.key === 'Tab' && this.isOpen )
            {
                this.trapFocus( e );
            }
        } );
    },

    trapFocus ( event )
    {
        const focusableElements = this.$refs.modal?.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );

        if ( !focusableElements || focusableElements.length === 0 ) return;

        const firstElement = focusableElements[ 0 ];
        const lastElement = focusableElements[ focusableElements.length - 1 ];

        if ( event.shiftKey )
        {
            if ( document.activeElement === firstElement )
            {
                event.preventDefault();
                lastElement.focus();
            }
        } else
        {
            if ( document.activeElement === lastElement )
            {
                event.preventDefault();
                firstElement.focus();
            }
        }
    },

    // Style Getters
    getOverlayClasses ()
    {
        const baseClasses = [
            'fixed inset-0 z-50 flex items-center justify-center p-4',
            'transition-all duration-300 ease-in-out'
        ];

        // Position classes
        const positionClasses = {
            center: 'items-center justify-center',
            top: 'items-start justify-center pt-16',
            bottom: 'items-end justify-center pb-16'
        };

        // Backdrop classes
        const backdropClasses = {
            blur: 'backdrop-blur-sm bg-black/50',
            dark: 'bg-black/75',
            light: 'bg-white/75',
            none: 'bg-transparent'
        };

        // Animation classes
        const animationClasses = this.isOpen ? 'opacity-100' : 'opacity-0';

        return [
            ...baseClasses,
            positionClasses[ this.position ],
            backdropClasses[ this.backdrop ],
            animationClasses
        ].join( ' ' );
    },

    getModalClasses ()
    {
        const baseClasses = [
            'relative bg-white rounded-lg shadow-xl max-h-[90vh] overflow-hidden',
            'transition-all duration-300 ease-in-out'
        ];

        // Size classes
        const sizeClasses = {
            xs: 'w-full max-w-xs',
            sm: 'w-full max-w-sm',
            md: 'w-full max-w-md',
            lg: 'w-full max-w-lg',
            xl: 'w-full max-w-xl',
            '2xl': 'w-full max-w-2xl',
            '3xl': 'w-full max-w-3xl',
            '4xl': 'w-full max-w-4xl',
            '5xl': 'w-full max-w-5xl',
            full: 'w-full h-full max-w-none max-h-none rounded-none'
        };

        // Animation classes
        const animationClasses = {
            fade: this.isOpen ? 'opacity-100 scale-100' : 'opacity-0 scale-95',
            'slide-up': this.isOpen ? 'translate-y-0 opacity-100' : 'translate-y-full opacity-0',
            'slide-down': this.isOpen ? 'translate-y-0 opacity-100' : '-translate-y-full opacity-0',
            scale: this.isOpen ? 'scale-100 opacity-100' : 'scale-50 opacity-0',
            none: this.isOpen ? 'opacity-100' : 'opacity-0'
        };

        return [
            ...baseClasses,
            sizeClasses[ this.size ],
            animationClasses[ this.animation ]
        ].join( ' ' );
    },

    // Content Methods
    hasHeader ()
    {
        return this.title || this.showCloseButton || this.$refs.header?.children.length > 0;
    },

    hasFooter ()
    {
        return this.$refs.footer?.children.length > 0;
    },

    // Accessibility
    getAriaLabel ()
    {
        return this.title || 'Modal dialog';
    },

    getAriaDescribedBy ()
    {
        return this.description ? 'modal-description' : null;
    }
} );
