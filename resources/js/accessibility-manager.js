/**
 * Accessibility Manager
 *
 * Comprehensive accessibility framework for the entire application.
 * Manages ARIA attributes, keyboard navigation, screen reader support,
 * and WCAG 2.1 AA compliance across all components.
 *
 * @version 1.0.0
 * @author ProxyAdmin System
 */

class AccessibilityManager
{
    constructor ()
    {
        this.isScreenReaderActive = false;
        this.isHighContrastMode = false;
        this.isReducedMotionMode = false;
        this.currentFocusElement = null;
        this.focusHistory = [];
        this.announcementQueue = [];
        this.liveRegions = new Map();

        this.init();
    }

    /**
     * Initialize the accessibility manager
     */
    init ()
    {
        this.detectScreenReader();
        this.setupMotionPreferences();
        this.setupContrastPreferences();
        this.createLiveRegions();
        this.setupGlobalKeyboardHandlers();
        this.setupFocusManagement();
        this.initializeARIA();

        // Initialize on DOM ready
        if ( document.readyState === 'loading' )
        {
            document.addEventListener( 'DOMContentLoaded', () => this.onDOMReady() );
        } else
        {
            this.onDOMReady();
        }
    }

    /**
     * Called when DOM is ready
     */
    onDOMReady ()
    {
        this.scanAndEnhanceElements();
        this.setupResponsiveTouchTargets();
        this.initializeValidationAnnouncements();
    }

    /**
     * Detect if screen reader is active
     */
    detectScreenReader ()
    {
        // Check for common screen reader indicators
        const indicators = [
            () => navigator.userAgent.includes( 'NVDA' ),
            () => navigator.userAgent.includes( 'JAWS' ),
            () => window.speechSynthesis && window.speechSynthesis.getVoices().length > 0,
            () => 'speechSynthesis' in window,
            () => document.documentElement.hasAttribute( 'data-screen-reader' )
        ];

        this.isScreenReaderActive = indicators.some( check =>
        {
            try
            {
                return check();
            } catch ( e )
            {
                return false;
            }
        } );

        if ( this.isScreenReaderActive )
        {
            document.documentElement.classList.add( 'screen-reader-active' );
            this.announce( 'Screen reader detected. Enhanced accessibility features activated.', 'polite' );
        }
    }

    /**
     * Setup motion preferences
     */
    setupMotionPreferences ()
    {
        const mediaQuery = window.matchMedia( '(prefers-reduced-motion: reduce)' );
        this.isReducedMotionMode = mediaQuery.matches;

        mediaQuery.addEventListener( 'change', ( e ) =>
        {
            this.isReducedMotionMode = e.matches;
            this.updateMotionStyles();
        } );

        this.updateMotionStyles();
    }

    /**
     * Update motion-related styles
     */
    updateMotionStyles ()
    {
        if ( this.isReducedMotionMode )
        {
            document.documentElement.classList.add( 'reduced-motion' );
            // Disable animations and transitions
            const style = document.createElement( 'style' );
            style.textContent = `
                .reduced-motion *,
                .reduced-motion *::before,
                .reduced-motion *::after {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                    scroll-behavior: auto !important;
                }
            `;
            document.head.appendChild( style );
        } else
        {
            document.documentElement.classList.remove( 'reduced-motion' );
        }
    }

    /**
     * Setup contrast preferences
     */
    setupContrastPreferences ()
    {
        const mediaQuery = window.matchMedia( '(prefers-contrast: high)' );
        this.isHighContrastMode = mediaQuery.matches;

        mediaQuery.addEventListener( 'change', ( e ) =>
        {
            this.isHighContrastMode = e.matches;
            this.updateContrastStyles();
        } );

        this.updateContrastStyles();
    }

    /**
     * Update contrast-related styles
     */
    updateContrastStyles ()
    {
        if ( this.isHighContrastMode )
        {
            document.documentElement.classList.add( 'high-contrast' );
            // Apply high contrast styles
            const style = document.createElement( 'style' );
            style.textContent = `
                .high-contrast {
                    --focus-ring-color: #ff0;
                    --focus-ring-width: 3px;
                    --border-contrast-color: #000;
                    --text-contrast-color: #000;
                    --background-contrast-color: #fff;
                }
                .high-contrast *:focus {
                    outline: var(--focus-ring-width) solid var(--focus-ring-color) !important;
                    outline-offset: 2px !important;
                }
            `;
            document.head.appendChild( style );
        } else
        {
            document.documentElement.classList.remove( 'high-contrast' );
        }
    }

    /**
     * Create ARIA live regions for announcements
     */
    createLiveRegions ()
    {
        // Polite announcements (non-urgent)
        const politeRegion = document.createElement( 'div' );
        politeRegion.setAttribute( 'aria-live', 'polite' );
        politeRegion.setAttribute( 'aria-atomic', 'true' );
        politeRegion.className = 'sr-only live-region-polite';
        politeRegion.id = 'live-region-polite';
        document.body.appendChild( politeRegion );
        this.liveRegions.set( 'polite', politeRegion );

        // Assertive announcements (urgent)
        const assertiveRegion = document.createElement( 'div' );
        assertiveRegion.setAttribute( 'aria-live', 'assertive' );
        assertiveRegion.setAttribute( 'aria-atomic', 'true' );
        assertiveRegion.className = 'sr-only live-region-assertive';
        assertiveRegion.id = 'live-region-assertive';
        document.body.appendChild( assertiveRegion );
        this.liveRegions.set( 'assertive', assertiveRegion );

        // Status announcements
        const statusRegion = document.createElement( 'div' );
        statusRegion.setAttribute( 'role', 'status' );
        statusRegion.setAttribute( 'aria-atomic', 'true' );
        statusRegion.className = 'sr-only live-region-status';
        statusRegion.id = 'live-region-status';
        document.body.appendChild( statusRegion );
        this.liveRegions.set( 'status', statusRegion );
    }

    /**
     * Announce message to screen readers
     */
    announce ( message, priority = 'polite', delay = 100 )
    {
        if ( !message || typeof message !== 'string' ) return;

        this.announcementQueue.push( { message, priority, delay } );
        this.processAnnouncementQueue();
    }

    /**
     * Process the announcement queue
     */
    processAnnouncementQueue ()
    {
        if ( this.announcementQueue.length === 0 ) return;

        const { message, priority, delay } = this.announcementQueue.shift();
        const liveRegion = this.liveRegions.get( priority ) || this.liveRegions.get( 'polite' );

        setTimeout( () =>
        {
            // Clear previous content
            liveRegion.textContent = '';

            // Add new announcement
            setTimeout( () =>
            {
                liveRegion.textContent = message;

                // Process next announcement
                if ( this.announcementQueue.length > 0 )
                {
                    setTimeout( () => this.processAnnouncementQueue(), 1000 );
                }
            }, 100 );
        }, delay );
    }

    /**
     * Setup global keyboard handlers
     */
    setupGlobalKeyboardHandlers ()
    {
        document.addEventListener( 'keydown', ( e ) =>
        {
            this.handleGlobalKeydown( e );
        } );

        // Escape key handler for modals and menus
        document.addEventListener( 'keydown', ( e ) =>
        {
            if ( e.key === 'Escape' )
            {
                this.handleEscapeKey( e );
            }
        } );
    }

    /**
     * Handle global keydown events
     */
    handleGlobalKeydown ( e )
    {
        // Skip navigation (Alt + S)
        if ( e.altKey && e.key === 's' )
        {
            e.preventDefault();
            this.skipToMainContent();
            return;
        }

        // Show focus indicator on Tab
        if ( e.key === 'Tab' )
        {
            document.body.classList.add( 'keyboard-navigation' );
        }

        // Help dialog (F1)
        if ( e.key === 'F1' )
        {
            e.preventDefault();
            this.showAccessibilityHelp();
            return;
        }
    }

    /**
     * Handle escape key
     */
    handleEscapeKey ( e )
    {
        // Close open modals
        const openModal = document.querySelector( '[role="dialog"][aria-modal="true"]' );
        if ( openModal )
        {
            const closeButton = openModal.querySelector( '[data-close-modal]' );
            if ( closeButton )
            {
                closeButton.click();
            }
            return;
        }

        // Close open menus
        const openMenu = document.querySelector( '[role="menu"][aria-expanded="true"]' );
        if ( openMenu )
        {
            const menuTrigger = document.querySelector( `[aria-controls="${ openMenu.id }"]` );
            if ( menuTrigger )
            {
                menuTrigger.setAttribute( 'aria-expanded', 'false' );
                menuTrigger.focus();
            }
            return;
        }

        // Return focus to last element
        if ( this.focusHistory.length > 0 )
        {
            const lastFocus = this.focusHistory.pop();
            if ( lastFocus && document.contains( lastFocus ) )
            {
                lastFocus.focus();
            }
        }
    }

    /**
     * Skip to main content
     */
    skipToMainContent ()
    {
        const mainContent = document.querySelector( 'main, [role="main"], #main-content' );
        if ( mainContent )
        {
            mainContent.focus();
            this.announce( 'Skipped to main content', 'polite' );
        }
    }

    /**
     * Setup focus management
     */
    setupFocusManagement ()
    {
        // Track focus changes
        document.addEventListener( 'focusin', ( e ) =>
        {
            this.currentFocusElement = e.target;
        } );

        // Remove keyboard navigation class on mouse use
        document.addEventListener( 'mousedown', () =>
        {
            document.body.classList.remove( 'keyboard-navigation' );
        } );

        // Trap focus in modals
        document.addEventListener( 'keydown', ( e ) =>
        {
            if ( e.key === 'Tab' )
            {
                const modal = e.target.closest( '[role="dialog"][aria-modal="true"]' );
                if ( modal )
                {
                    this.trapFocus( e, modal );
                }
            }
        } );
    }

    /**
     * Trap focus within an element
     */
    trapFocus ( e, container )
    {
        const focusableElements = this.getFocusableElements( container );
        const firstElement = focusableElements[ 0 ];
        const lastElement = focusableElements[ focusableElements.length - 1 ];

        if ( e.shiftKey )
        {
            if ( document.activeElement === firstElement )
            {
                e.preventDefault();
                lastElement.focus();
            }
        } else
        {
            if ( document.activeElement === lastElement )
            {
                e.preventDefault();
                firstElement.focus();
            }
        }
    }

    /**
     * Get focusable elements within a container
     */
    getFocusableElements ( container )
    {
        const selector = [
            'a[href]',
            'button:not([disabled])',
            'textarea:not([disabled])',
            'input:not([disabled])',
            'select:not([disabled])',
            '[tabindex]:not([tabindex="-1"])',
            '[contenteditable="true"]'
        ].join( ', ' );

        return Array.from( container.querySelectorAll( selector ) )
            .filter( el => !el.hasAttribute( 'inert' ) && this.isVisible( el ) );
    }

    /**
     * Check if element is visible
     */
    isVisible ( element )
    {
        const style = getComputedStyle( element );
        return style.display !== 'none' &&
            style.visibility !== 'hidden' &&
            style.opacity !== '0' &&
            element.offsetWidth > 0 &&
            element.offsetHeight > 0;
    }

    /**
     * Initialize ARIA attributes
     */
    initializeARIA ()
    {
        // Add landmark roles if missing
        this.ensureLandmarkRoles();

        // Enhance form elements
        this.enhanceFormElements();

        // Enhance navigation elements
        this.enhanceNavigationElements();

        // Enhance interactive elements
        this.enhanceInteractiveElements();
    }

    /**
     * Ensure landmark roles are present
     */
    ensureLandmarkRoles ()
    {
        // Main content
        if ( !document.querySelector( 'main, [role="main"]' ) )
        {
            const mainContent = document.querySelector( '#app, .app-content, .main-content' );
            if ( mainContent )
            {
                mainContent.setAttribute( 'role', 'main' );
                mainContent.setAttribute( 'aria-label', 'Main content' );
            }
        }

        // Navigation
        const navElements = document.querySelectorAll( 'nav:not([role])' );
        navElements.forEach( ( nav, index ) =>
        {
            nav.setAttribute( 'role', 'navigation' );
            if ( !nav.hasAttribute( 'aria-label' ) )
            {
                nav.setAttribute( 'aria-label', `Navigation ${ index + 1 }` );
            }
        } );

        // Content info (footer)
        const footer = document.querySelector( 'footer:not([role])' );
        if ( footer )
        {
            footer.setAttribute( 'role', 'contentinfo' );
        }

        // Banner (header)
        const header = document.querySelector( 'header:not([role])' );
        if ( header )
        {
            header.setAttribute( 'role', 'banner' );
        }
    }

    /**
     * Enhance form elements
     */
    enhanceFormElements ()
    {
        // Required field indicators
        const requiredFields = document.querySelectorAll( 'input[required], textarea[required], select[required]' );
        requiredFields.forEach( field =>
        {
            if ( !field.hasAttribute( 'aria-required' ) )
            {
                field.setAttribute( 'aria-required', 'true' );
            }

            // Add visual indicator
            const label = document.querySelector( `label[for="${ field.id }"]` );
            if ( label && !label.querySelector( '.required-indicator' ) )
            {
                const indicator = document.createElement( 'span' );
                indicator.className = 'required-indicator';
                indicator.textContent = ' *';
                indicator.setAttribute( 'aria-label', 'required' );
                label.appendChild( indicator );
            }
        } );

        // Form validation messages
        const invalidFields = document.querySelectorAll( 'input:invalid, textarea:invalid, select:invalid' );
        invalidFields.forEach( field =>
        {
            field.setAttribute( 'aria-invalid', 'true' );
        } );
    }

    /**
     * Enhance navigation elements
     */
    enhanceNavigationElements ()
    {
        // Breadcrumbs
        const breadcrumbs = document.querySelectorAll( '.breadcrumb, .breadcrumbs, [data-breadcrumb]' );
        breadcrumbs.forEach( breadcrumb =>
        {
            if ( !breadcrumb.hasAttribute( 'aria-label' ) )
            {
                breadcrumb.setAttribute( 'aria-label', 'Breadcrumb navigation' );
            }

            const items = breadcrumb.querySelectorAll( 'li, .breadcrumb-item' );
            items.forEach( ( item, index ) =>
            {
                if ( index === items.length - 1 )
                {
                    item.setAttribute( 'aria-current', 'page' );
                }
            } );
        } );

        // Pagination
        const pagination = document.querySelectorAll( '.pagination, [data-pagination]' );
        pagination.forEach( pag =>
        {
            if ( !pag.hasAttribute( 'aria-label' ) )
            {
                pag.setAttribute( 'aria-label', 'Pagination navigation' );
            }

            const currentPage = pag.querySelector( '.active, .current, [aria-current="page"]' );
            if ( currentPage )
            {
                currentPage.setAttribute( 'aria-current', 'page' );
            }
        } );
    }

    /**
     * Enhance interactive elements
     */
    enhanceInteractiveElements ()
    {
        // Buttons without text
        const iconButtons = document.querySelectorAll( 'button:not([aria-label]):not([aria-labelledby])' );
        iconButtons.forEach( button =>
        {
            if ( button.textContent.trim() === '' )
            {
                const icon = button.querySelector( 'i, svg, [class*="icon"]' );
                if ( icon )
                {
                    const purpose = this.guessButtonPurpose( button );
                    if ( purpose )
                    {
                        button.setAttribute( 'aria-label', purpose );
                    }
                }
            }
        } );

        // Toggle buttons
        const toggleButtons = document.querySelectorAll( '[data-toggle], .toggle-button' );
        toggleButtons.forEach( button =>
        {
            if ( !button.hasAttribute( 'aria-pressed' ) )
            {
                button.setAttribute( 'aria-pressed', 'false' );
            }
        } );

        // Collapsible content
        const collapsibleTriggers = document.querySelectorAll( '[data-toggle="collapse"], [data-collapse-trigger]' );
        collapsibleTriggers.forEach( trigger =>
        {
            const target = document.querySelector( trigger.dataset.target || trigger.dataset.collapseTarget );
            if ( target )
            {
                trigger.setAttribute( 'aria-expanded', target.classList.contains( 'show' ) ? 'true' : 'false' );
                trigger.setAttribute( 'aria-controls', target.id || `collapse-${ Date.now() }` );
                if ( !target.id )
                {
                    target.id = trigger.getAttribute( 'aria-controls' );
                }
            }
        } );
    }

    /**
     * Guess button purpose from context
     */
    guessButtonPurpose ( button )
    {
        const className = button.className.toLowerCase();
        const parentText = button.parentElement?.textContent?.toLowerCase() || '';

        const purposes = {
            'edit': [ 'edit', 'pencil', 'modify' ],
            'delete': [ 'delete', 'trash', 'remove', 'cross' ],
            'save': [ 'save', 'check', 'confirm' ],
            'cancel': [ 'cancel', 'close', 'times' ],
            'search': [ 'search', 'magnify' ],
            'menu': [ 'menu', 'bars', 'hamburger' ],
            'close': [ 'close', 'times', 'cross' ],
            'expand': [ 'expand', 'plus', 'chevron-down' ],
            'collapse': [ 'collapse', 'minus', 'chevron-up' ]
        };

        for ( const [ purpose, keywords ] of Object.entries( purposes ) )
        {
            if ( keywords.some( keyword =>
                className.includes( keyword ) ||
                parentText.includes( keyword ) ||
                button.innerHTML.toLowerCase().includes( keyword )
            ) )
            {
                return purpose.charAt( 0 ).toUpperCase() + purpose.slice( 1 );
            }
        }

        return null;
    }

    /**
     * Scan and enhance all elements on the page
     */
    scanAndEnhanceElements ()
    {
        // Data tables
        const tables = document.querySelectorAll( 'table' );
        tables.forEach( table => this.enhanceTable( table ) );

        // Forms
        const forms = document.querySelectorAll( 'form' );
        forms.forEach( form => this.enhanceForm( form ) );

        // Modals
        const modals = document.querySelectorAll( '.modal, [role="dialog"]' );
        modals.forEach( modal => this.enhanceModal( modal ) );
    }

    /**
     * Enhance table accessibility
     */
    enhanceTable ( table )
    {
        if ( !table.hasAttribute( 'role' ) )
        {
            table.setAttribute( 'role', 'table' );
        }

        // Add table caption if missing
        if ( !table.querySelector( 'caption' ) && !table.hasAttribute( 'aria-label' ) )
        {
            const tableTitle = table.previousElementSibling?.textContent || 'Data table';
            table.setAttribute( 'aria-label', tableTitle );
        }

        // Enhance headers
        const headers = table.querySelectorAll( 'th' );
        headers.forEach( header =>
        {
            if ( !header.hasAttribute( 'scope' ) )
            {
                const isColumnHeader = header.closest( 'thead' ) ||
                    header.parentElement.firstElementChild === header;
                header.setAttribute( 'scope', isColumnHeader ? 'col' : 'row' );
            }
        } );

        // Add row/column headers relationship
        const rows = table.querySelectorAll( 'tbody tr' );
        rows.forEach( ( row, rowIndex ) =>
        {
            const cells = row.querySelectorAll( 'td' );
            cells.forEach( ( cell, cellIndex ) =>
            {
                if ( !cell.hasAttribute( 'headers' ) )
                {
                    const columnHeader = table.querySelector( `thead th:nth-child(${ cellIndex + 1 })` );
                    const rowHeader = row.querySelector( 'th' );

                    const headerIds = [];
                    if ( columnHeader?.id ) headerIds.push( columnHeader.id );
                    if ( rowHeader?.id ) headerIds.push( rowHeader.id );

                    if ( headerIds.length > 0 )
                    {
                        cell.setAttribute( 'headers', headerIds.join( ' ' ) );
                    }
                }
            } );
        } );
    }

    /**
     * Enhance form accessibility
     */
    enhanceForm ( form )
    {
        // Add form role if missing
        if ( !form.hasAttribute( 'role' ) )
        {
            form.setAttribute( 'role', 'form' );
        }

        // Enhance field groups
        const fieldsets = form.querySelectorAll( 'fieldset' );
        fieldsets.forEach( fieldset =>
        {
            if ( !fieldset.querySelector( 'legend' ) && !fieldset.hasAttribute( 'aria-label' ) )
            {
                const firstLabel = fieldset.querySelector( 'label' );
                if ( firstLabel )
                {
                    fieldset.setAttribute( 'aria-label', firstLabel.textContent );
                }
            }
        } );

        // Link labels and inputs
        const inputs = form.querySelectorAll( 'input, textarea, select' );
        inputs.forEach( input =>
        {
            if ( !input.hasAttribute( 'aria-label' ) && !input.hasAttribute( 'aria-labelledby' ) )
            {
                const label = form.querySelector( `label[for="${ input.id }"]` );
                if ( !label && input.id )
                {
                    const closestLabel = input.closest( 'label' );
                    if ( closestLabel )
                    {
                        closestLabel.setAttribute( 'for', input.id );
                    }
                }
            }
        } );
    }

    /**
     * Enhance modal accessibility
     */
    enhanceModal ( modal )
    {
        if ( !modal.hasAttribute( 'role' ) )
        {
            modal.setAttribute( 'role', 'dialog' );
        }

        if ( !modal.hasAttribute( 'aria-modal' ) )
        {
            modal.setAttribute( 'aria-modal', 'true' );
        }

        // Add accessible name
        if ( !modal.hasAttribute( 'aria-label' ) && !modal.hasAttribute( 'aria-labelledby' ) )
        {
            const title = modal.querySelector( 'h1, h2, h3, h4, h5, h6, .modal-title' );
            if ( title )
            {
                if ( !title.id )
                {
                    title.id = `modal-title-${ Date.now() }`;
                }
                modal.setAttribute( 'aria-labelledby', title.id );
            } else
            {
                modal.setAttribute( 'aria-label', 'Dialog' );
            }
        }

        // Add description
        const description = modal.querySelector( '.modal-body, .modal-content p' );
        if ( description && !modal.hasAttribute( 'aria-describedby' ) )
        {
            if ( !description.id )
            {
                description.id = `modal-description-${ Date.now() }`;
            }
            modal.setAttribute( 'aria-describedby', description.id );
        }
    }

    /**
     * Setup responsive touch targets
     */
    setupResponsiveTouchTargets ()
    {
        const interactiveElements = document.querySelectorAll( 'button, a, input, textarea, select, [tabindex], [role="button"]' );

        interactiveElements.forEach( element =>
        {
            const rect = element.getBoundingClientRect();
            const minSize = 44; // WCAG minimum touch target size

            if ( rect.width < minSize || rect.height < minSize )
            {
                element.style.minWidth = `${ minSize }px`;
                element.style.minHeight = `${ minSize }px`;
                element.classList.add( 'accessible-touch-target' );
            }
        } );
    }

    /**
     * Initialize validation announcements
     */
    initializeValidationAnnouncements ()
    {
        const forms = document.querySelectorAll( 'form' );

        forms.forEach( form =>
        {
            form.addEventListener( 'submit', ( e ) =>
            {
                const invalidFields = form.querySelectorAll( ':invalid' );
                if ( invalidFields.length > 0 )
                {
                    e.preventDefault();
                    this.announceValidationErrors( invalidFields );
                }
            } );

            // Real-time validation announcements
            const fields = form.querySelectorAll( 'input, textarea, select' );
            fields.forEach( field =>
            {
                field.addEventListener( 'blur', () =>
                {
                    if ( !field.validity.valid )
                    {
                        this.announceFieldError( field );
                    }
                } );

                field.addEventListener( 'input', () =>
                {
                    if ( field.getAttribute( 'aria-invalid' ) === 'true' && field.validity.valid )
                    {
                        field.removeAttribute( 'aria-invalid' );
                        this.announce( `${ this.getFieldLabel( field ) } is now valid`, 'polite' );
                    }
                } );
            } );
        } );
    }

    /**
     * Announce validation errors
     */
    announceValidationErrors ( invalidFields )
    {
        const errorCount = invalidFields.length;
        const message = `Form has ${ errorCount } error${ errorCount > 1 ? 's' : '' }. Please review and correct the highlighted fields.`;

        this.announce( message, 'assertive' );

        // Focus first invalid field
        if ( invalidFields.length > 0 )
        {
            invalidFields[ 0 ].focus();
        }
    }

    /**
     * Announce field error
     */
    announceFieldError ( field )
    {
        const label = this.getFieldLabel( field );
        const errorMessage = field.validationMessage || 'Invalid input';

        field.setAttribute( 'aria-invalid', 'true' );
        this.announce( `${ label }: ${ errorMessage }`, 'assertive' );
    }

    /**
     * Get field label text
     */
    getFieldLabel ( field )
    {
        const label = document.querySelector( `label[for="${ field.id }"]` ) ||
            field.closest( 'label' ) ||
            field.getAttribute( 'aria-label' ) ||
            field.getAttribute( 'placeholder' ) ||
            field.name;

        return typeof label === 'string' ? label : ( label?.textContent || 'Field' );
    }

    /**
     * Show accessibility help dialog
     */
    showAccessibilityHelp ()
    {
        const helpContent = `
            <h2>Accessibility Help</h2>
            <h3>Keyboard Shortcuts</h3>
            <ul>
                <li><kbd>Alt + S</kbd> - Skip to main content</li>
                <li><kbd>F1</kbd> - Show this help dialog</li>
                <li><kbd>Escape</kbd> - Close modals and menus</li>
                <li><kbd>Tab</kbd> - Navigate forward</li>
                <li><kbd>Shift + Tab</kbd> - Navigate backward</li>
                <li><kbd>Enter/Space</kbd> - Activate buttons and links</li>
                <li><kbd>Arrow Keys</kbd> - Navigate menus and tables</li>
            </ul>
            <h3>Screen Reader Support</h3>
            <p>This application is optimized for screen readers with proper ARIA labels, live regions for announcements, and semantic HTML structure.</p>
            <h3>High Contrast Mode</h3>
            <p>The application respects your system's high contrast settings and provides enhanced focus indicators.</p>
        `;

        this.showModal( 'Accessibility Help', helpContent );
    }

    /**
     * Show a modal dialog
     */
    showModal ( title, content )
    {
        // Simple modal implementation for accessibility help
        const modal = document.createElement( 'div' );
        modal.className = 'accessibility-help-modal';
        modal.setAttribute( 'role', 'dialog' );
        modal.setAttribute( 'aria-modal', 'true' );
        modal.setAttribute( 'aria-labelledby', 'help-modal-title' );

        modal.innerHTML = `
            <div class="modal-backdrop" aria-hidden="true"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="help-modal-title">${ title }</h2>
                    <button type="button" class="modal-close" aria-label="Close help dialog">&times;</button>
                </div>
                <div class="modal-body">
                    ${ content }
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary modal-close">Close</button>
                </div>
            </div>
        `;

        // Add styles
        const style = document.createElement( 'style' );
        style.textContent = `
            .accessibility-help-modal {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .accessibility-help-modal .modal-backdrop {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
            }
            .accessibility-help-modal .modal-content {
                background: white;
                border-radius: 8px;
                max-width: 600px;
                max-height: 80vh;
                overflow-y: auto;
                padding: 0;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                position: relative;
                z-index: 1;
            }
            .accessibility-help-modal .modal-header {
                padding: 1rem;
                border-bottom: 1px solid #e9ecef;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .accessibility-help-modal .modal-body {
                padding: 1rem;
            }
            .accessibility-help-modal .modal-footer {
                padding: 1rem;
                border-top: 1px solid #e9ecef;
                text-align: right;
            }
            .accessibility-help-modal kbd {
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 3px;
                padding: 2px 4px;
                font-family: monospace;
                font-size: 0.875em;
            }
        `;
        document.head.appendChild( style );

        document.body.appendChild( modal );

        // Set up close handlers
        const closeButtons = modal.querySelectorAll( '.modal-close' );
        closeButtons.forEach( button =>
        {
            button.addEventListener( 'click', () =>
            {
                document.body.removeChild( modal );
                document.head.removeChild( style );
            } );
        } );

        // Escape key handler
        const escapeHandler = ( e ) =>
        {
            if ( e.key === 'Escape' )
            {
                document.body.removeChild( modal );
                document.head.removeChild( style );
                document.removeEventListener( 'keydown', escapeHandler );
            }
        };
        document.addEventListener( 'keydown', escapeHandler );

        // Focus management
        const firstButton = modal.querySelector( 'button' );
        if ( firstButton )
        {
            firstButton.focus();
        }

        // Trap focus
        modal.addEventListener( 'keydown', ( e ) =>
        {
            if ( e.key === 'Tab' )
            {
                this.trapFocus( e, modal );
            }
        } );
    }

    /**
     * Public API methods
     */

    /**
     * Manually announce a message
     */
    announceMessage ( message, priority = 'polite' )
    {
        this.announce( message, priority );
    }

    /**
     * Enable/disable accessibility features
     */
    toggleAccessibilityMode ( enabled = true )
    {
        if ( enabled )
        {
            document.documentElement.classList.add( 'accessibility-enhanced' );
        } else
        {
            document.documentElement.classList.remove( 'accessibility-enhanced' );
        }
    }

    /**
     * Update page title and announce
     */
    updatePageTitle ( title, announce = true )
    {
        document.title = title;
        if ( announce )
        {
            this.announce( `Page updated: ${ title }`, 'polite' );
        }
    }

    /**
     * Mark region as busy/loading
     */
    setBusy ( element, busy = true, message = 'Loading...' )
    {
        if ( busy )
        {
            element.setAttribute( 'aria-busy', 'true' );
            if ( message )
            {
                this.announce( message, 'polite' );
            }
        } else
        {
            element.removeAttribute( 'aria-busy' );
            this.announce( 'Content loaded', 'polite' );
        }
    }

    /**
     * Update progress indicator
     */
    updateProgress ( element, value, max = 100, text = null )
    {
        element.setAttribute( 'role', 'progressbar' );
        element.setAttribute( 'aria-valuenow', value );
        element.setAttribute( 'aria-valuemin', '0' );
        element.setAttribute( 'aria-valuemax', max );

        if ( text )
        {
            element.setAttribute( 'aria-valuetext', text );
        }

        const percentage = Math.round( ( value / max ) * 100 );
        this.announce( `Progress: ${ percentage }%`, 'polite' );
    }
}

// Initialize accessibility manager when DOM is ready
if ( typeof window !== 'undefined' )
{
    window.AccessibilityManager = AccessibilityManager;

    // Auto-initialize unless explicitly disabled
    if ( !window.disableAccessibilityManager )
    {
        window.accessibilityManager = new AccessibilityManager();
    }
}

// Add screen reader only styles
const srOnlyStyles = `
    .sr-only {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
        white-space: nowrap !important;
        border: 0 !important;
    }

    .sr-only-focusable:active,
    .sr-only-focusable:focus {
        position: static !important;
        width: auto !important;
        height: auto !important;
        padding: inherit !important;
        margin: inherit !important;
        overflow: visible !important;
        clip: auto !important;
        white-space: normal !important;
    }

    .keyboard-navigation *:focus {
        outline: 2px solid #007bff !important;
        outline-offset: 2px !important;
    }

    .accessible-touch-target {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
`;

// Inject styles
if ( typeof document !== 'undefined' )
{
    const styleElement = document.createElement( 'style' );
    styleElement.textContent = srOnlyStyles;
    document.head.appendChild( styleElement );
}

export default AccessibilityManager;
