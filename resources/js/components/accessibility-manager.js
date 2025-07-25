/**
 * Accessibility Manager Component
 * Comprehensive accessibility features including ARIA support, keyboard navigation, 
 * screen reader compatibility, and color contrast management
 */

window.accessibilityManager = function ( config = {} )
{
    return {
        // Configuration
        announceChanges: config.announceChanges !== false,
        keyboardNavigation: config.keyboardNavigation !== false,
        colorContrastMode: config.colorContrastMode || 'normal', // 'normal', 'high', 'auto'
        reducedMotion: false,
        screenReaderActive: false,

        // ARIA Live Regions
        liveRegions: {
            polite: null,
            assertive: null
        },

        // Keyboard Navigation State
        focusableElements: [],
        currentFocusIndex: 0,
        trapFocus: false,

        // Color Contrast State
        contrastRatio: 4.5, // WCAG AA standard
        contrastAdjustments: {},

        // Screen Reader Detection
        screenReaderDetected: false,

        // Touch/Mobile Accessibility
        touchTarget: 44, // Minimum touch target size in pixels

        // Initialize accessibility manager
        init ()
        {
            this.detectSystemPreferences();
            this.createLiveRegions();
            this.setupKeyboardNavigation();
            this.setupScreenReaderDetection();
            this.setupColorContrastMode();
            this.addAccessibilityStyles();
            this.monitorFocusableElements();
            this.setupTouchAccessibility();

            // Announce component is ready
            this.announce( 'Accessibility features activated', 'polite' );

            console.log( '✅ Accessibility Manager initialized' );
        },

        // Detect system accessibility preferences
        detectSystemPreferences ()
        {
            // Detect reduced motion preference
            if ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches )
            {
                this.reducedMotion = true;
                document.documentElement.classList.add( 'reduce-motion' );
            }

            // Detect high contrast preference
            if ( window.matchMedia && window.matchMedia( '(prefers-contrast: high)' ).matches )
            {
                this.colorContrastMode = 'high';
                document.documentElement.classList.add( 'high-contrast' );
            }

            // Listen for preference changes
            if ( window.matchMedia )
            {
                window.matchMedia( '(prefers-reduced-motion: reduce)' ).addEventListener( 'change', ( e ) =>
                {
                    this.reducedMotion = e.matches;
                    this.toggleReducedMotion( e.matches );
                } );

                window.matchMedia( '(prefers-contrast: high)' ).addEventListener( 'change', ( e ) =>
                {
                    this.colorContrastMode = e.matches ? 'high' : 'normal';
                    this.toggleHighContrast( e.matches );
                } );
            }
        },

        // Create ARIA live regions for announcements
        createLiveRegions ()
        {
            // Polite live region (non-interrupting)
            this.liveRegions.polite = document.createElement( 'div' );
            this.liveRegions.polite.setAttribute( 'aria-live', 'polite' );
            this.liveRegions.polite.setAttribute( 'aria-atomic', 'true' );
            this.liveRegions.polite.className = 'sr-only';
            this.liveRegions.polite.id = 'aria-live-polite';

            // Assertive live region (interrupting)
            this.liveRegions.assertive = document.createElement( 'div' );
            this.liveRegions.assertive.setAttribute( 'aria-live', 'assertive' );
            this.liveRegions.assertive.setAttribute( 'aria-atomic', 'true' );
            this.liveRegions.assertive.className = 'sr-only';
            this.liveRegions.assertive.id = 'aria-live-assertive';

            document.body.appendChild( this.liveRegions.polite );
            document.body.appendChild( this.liveRegions.assertive );
        },

        // Announce message to screen readers
        announce ( message, priority = 'polite' )
        {
            if ( !this.announceChanges ) return;

            const region = this.liveRegions[ priority ];
            if ( region )
            {
                // Clear and set new message
                region.textContent = '';
                setTimeout( () =>
                {
                    region.textContent = message;
                }, 100 );
            }
        },

        // Setup keyboard navigation
        setupKeyboardNavigation ()
        {
            if ( !this.keyboardNavigation ) return;

            document.addEventListener( 'keydown', ( e ) =>
            {
                this.handleGlobalKeyboard( e );
            } );

            // Add skip links
            this.addSkipLinks();

            // Enhance tab navigation
            this.enhanceTabNavigation();
        },

        // Handle global keyboard shortcuts
        handleGlobalKeyboard ( event )
        {
            const { key, altKey, ctrlKey, metaKey, shiftKey } = event;

            // Skip to main content (Alt + S)
            if ( altKey && key === 's' )
            {
                event.preventDefault();
                this.skipToMain();
                return;
            }

            // Skip to navigation (Alt + N)
            if ( altKey && key === 'n' )
            {
                event.preventDefault();
                this.skipToNavigation();
                return;
            }

            // Toggle accessibility panel (Alt + A)
            if ( altKey && key === 'a' )
            {
                event.preventDefault();
                this.toggleAccessibilityPanel();
                return;
            }

            // Announce current page/section (Alt + H)
            if ( altKey && key === 'h' )
            {
                event.preventDefault();
                this.announceCurrentLocation();
                return;
            }

            // Focus management in modal/trap contexts
            if ( this.trapFocus )
            {
                this.handleFocusTrap( event );
            }
        },

        // Add skip links for keyboard navigation
        addSkipLinks ()
        {
            const skipLinks = document.createElement( 'nav' );
            skipLinks.className = 'skip-links';
            skipLinks.setAttribute( 'aria-label', 'Skip navigation' );

            skipLinks.innerHTML = `
                <a href="#main-content" class="skip-link">Skip to main content</a>
                <a href="#main-navigation" class="skip-link">Skip to navigation</a>
                <a href="#search" class="skip-link">Skip to search</a>
            `;

            document.body.insertBefore( skipLinks, document.body.firstChild );
        },

        // Skip to main content
        skipToMain ()
        {
            const main = document.getElementById( 'main-content' ) ||
                document.querySelector( 'main' ) ||
                document.querySelector( '[role="main"]' );

            if ( main )
            {
                main.focus();
                main.scrollIntoView( { behavior: 'smooth', block: 'start' } );
                this.announce( 'Skipped to main content' );
            }
        },

        // Skip to navigation
        skipToNavigation ()
        {
            const nav = document.getElementById( 'main-navigation' ) ||
                document.querySelector( 'nav' ) ||
                document.querySelector( '[role="navigation"]' );

            if ( nav )
            {
                const firstLink = nav.querySelector( 'a, button' );
                if ( firstLink )
                {
                    firstLink.focus();
                    this.announce( 'Skipped to navigation' );
                }
            }
        },

        // Enhance tab navigation with visual indicators
        enhanceTabNavigation ()
        {
            // Add enhanced focus styles
            document.addEventListener( 'keydown', ( e ) =>
            {
                if ( e.key === 'Tab' )
                {
                    document.body.classList.add( 'using-keyboard' );
                }
            } );

            document.addEventListener( 'mousedown', () =>
            {
                document.body.classList.remove( 'using-keyboard' );
            } );
        },

        // Focus trap for modals and overlays
        trapFocusIn ( container )
        {
            this.trapFocus = true;
            this.focusableElements = this.getFocusableElements( container );
            this.currentFocusIndex = 0;

            if ( this.focusableElements.length > 0 )
            {
                this.focusableElements[ 0 ].focus();
            }
        },

        // Release focus trap
        releaseFocusTrap ()
        {
            this.trapFocus = false;
            this.focusableElements = [];
            this.currentFocusIndex = 0;
        },

        // Handle focus trap keyboard navigation
        handleFocusTrap ( event )
        {
            if ( event.key !== 'Tab' ) return;

            event.preventDefault();

            if ( event.shiftKey )
            {
                // Shift + Tab (backwards)
                this.currentFocusIndex = this.currentFocusIndex <= 0
                    ? this.focusableElements.length - 1
                    : this.currentFocusIndex - 1;
            } else
            {
                // Tab (forwards)
                this.currentFocusIndex = this.currentFocusIndex >= this.focusableElements.length - 1
                    ? 0
                    : this.currentFocusIndex + 1;
            }

            this.focusableElements[ this.currentFocusIndex ].focus();
        },

        // Get all focusable elements in container
        getFocusableElements ( container )
        {
            const selectors = [
                'a[href]',
                'button:not([disabled])',
                'input:not([disabled])',
                'select:not([disabled])',
                'textarea:not([disabled])',
                '[tabindex]:not([tabindex="-1"])',
                '[contenteditable="true"]'
            ].join( ', ' );

            return Array.from( container.querySelectorAll( selectors ) )
                .filter( el => !el.hasAttribute( 'disabled' ) && this.isVisible( el ) );
        },

        // Check if element is visible
        isVisible ( element )
        {
            const style = window.getComputedStyle( element );
            return style.display !== 'none' &&
                style.visibility !== 'hidden' &&
                style.opacity !== '0';
        },

        // Screen reader detection
        setupScreenReaderDetection ()
        {
            // Basic screen reader detection
            this.screenReaderDetected = !!(
                window.navigator.userAgent.match( /NVDA|JAWS|VoiceOver|TalkBack/i ) ||
                window.speechSynthesis ||
                window.navigator.userAgent.match( /Windows NT.*rv:/i )
            );

            if ( this.screenReaderDetected )
            {
                document.documentElement.classList.add( 'screen-reader-active' );
                this.screenReaderActive = true;
            }
        },

        // Color contrast management
        setupColorContrastMode ()
        {
            this.contrastAdjustments = {
                normal: {
                    textColor: 'rgb(31, 41, 55)', // gray-800
                    backgroundColor: 'rgb(255, 255, 255)', // white
                    linkColor: 'rgb(59, 130, 246)', // blue-500
                    borderColor: 'rgb(229, 231, 235)' // gray-200
                },
                high: {
                    textColor: 'rgb(0, 0, 0)', // black
                    backgroundColor: 'rgb(255, 255, 255)', // white
                    linkColor: 'rgb(0, 0, 255)', // pure blue
                    borderColor: 'rgb(0, 0, 0)' // black
                }
            };
        },

        // Toggle high contrast mode
        toggleHighContrast ( enable = !document.documentElement.classList.contains( 'high-contrast' ) )
        {
            if ( enable )
            {
                document.documentElement.classList.add( 'high-contrast' );
                this.colorContrastMode = 'high';
                this.announce( 'High contrast mode enabled' );
            } else
            {
                document.documentElement.classList.remove( 'high-contrast' );
                this.colorContrastMode = 'normal';
                this.announce( 'High contrast mode disabled' );
            }

            this.applyContrastAdjustments();
        },

        // Apply contrast adjustments
        applyContrastAdjustments ()
        {
            const adjustments = this.contrastAdjustments[ this.colorContrastMode ];
            const root = document.documentElement;

            root.style.setProperty( '--accessibility-text-color', adjustments.textColor );
            root.style.setProperty( '--accessibility-bg-color', adjustments.backgroundColor );
            root.style.setProperty( '--accessibility-link-color', adjustments.linkColor );
            root.style.setProperty( '--accessibility-border-color', adjustments.borderColor );
        },

        // Toggle reduced motion
        toggleReducedMotion ( enable = !document.documentElement.classList.contains( 'reduce-motion' ) )
        {
            if ( enable )
            {
                document.documentElement.classList.add( 'reduce-motion' );
                this.reducedMotion = true;
                this.announce( 'Reduced motion enabled' );
            } else
            {
                document.documentElement.classList.remove( 'reduce-motion' );
                this.reducedMotion = false;
                this.announce( 'Reduced motion disabled' );
            }
        },

        // Add accessibility styles
        addAccessibilityStyles ()
        {
            const style = document.createElement( 'style' );
            style.textContent = `
                /* Skip links */
                .skip-links {
                    position: fixed;
                    top: 0;
                    left: 0;
                    z-index: 9999;
                    transform: translateY(-100%);
                    transition: transform 0.3s ease;
                }
                
                .skip-links:focus-within {
                    transform: translateY(0);
                }
                
                .skip-link {
                    position: absolute;
                    top: 0;
                    left: 0;
                    background: #000;
                    color: #fff;
                    padding: 8px 16px;
                    text-decoration: none;
                    font-weight: bold;
                    transform: translateY(-100%);
                    transition: transform 0.3s ease;
                }
                
                .skip-link:focus {
                    transform: translateY(0);
                }
                
                /* Enhanced focus indicators for keyboard users */
                .using-keyboard *:focus {
                    outline: 3px solid #4f46e5 !important;
                    outline-offset: 2px !important;
                }
                
                /* High contrast mode */
                .high-contrast * {
                    color: var(--accessibility-text-color, #000) !important;
                    background-color: var(--accessibility-bg-color, #fff) !important;
                    border-color: var(--accessibility-border-color, #000) !important;
                }
                
                .high-contrast a {
                    color: var(--accessibility-link-color, #00f) !important;
                    text-decoration: underline !important;
                }
                
                /* Reduced motion */
                .reduce-motion *,
                .reduce-motion *::before,
                .reduce-motion *::after {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                    scroll-behavior: auto !important;
                }
                
                /* Screen reader enhancements */
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
                
                /* Touch accessibility */
                @media (pointer: coarse) {
                    button, a, input, select, textarea, [tabindex]:not([tabindex="-1"]) {
                        min-height: 44px;
                        min-width: 44px;
                    }
                }
                
                /* Screen reader active enhancements */
                .screen-reader-active .sr-enhanced {
                    position: static !important;
                    width: auto !important;
                    height: auto !important;
                    margin: 0 !important;
                    padding: 0.25rem !important;
                    overflow: visible !important;
                    clip: auto !important;
                    white-space: normal !important;
                    background: #f3f4f6 !important;
                    border: 1px solid #d1d5db !important;
                    border-radius: 0.25rem !important;
                }
            `;

            document.head.appendChild( style );
        },

        // Touch accessibility enhancements
        setupTouchAccessibility ()
        {
            // Ensure touch targets meet minimum size requirements
            this.monitorTouchTargets();

            // Add touch feedback for interactive elements
            this.addTouchFeedback();
        },

        // Monitor touch target sizes
        monitorTouchTargets ()
        {
            const observer = new MutationObserver( () =>
            {
                this.checkTouchTargetSizes();
            } );

            observer.observe( document.body, {
                childList: true,
                subtree: true
            } );

            // Initial check
            this.checkTouchTargetSizes();
        },

        // Check and fix touch target sizes
        checkTouchTargetSizes ()
        {
            if ( window.matchMedia && !window.matchMedia( '(pointer: coarse)' ).matches )
            {
                return; // Not a touch device
            }

            const interactiveElements = document.querySelectorAll( 'button, a, input, select, textarea, [onclick], [role="button"]' );

            interactiveElements.forEach( element =>
            {
                const rect = element.getBoundingClientRect();
                if ( rect.width < this.touchTarget || rect.height < this.touchTarget )
                {
                    element.style.minWidth = `${ this.touchTarget }px`;
                    element.style.minHeight = `${ this.touchTarget }px`;
                    element.style.display = element.style.display || 'inline-flex';
                    element.style.alignItems = 'center';
                    element.style.justifyContent = 'center';
                }
            } );
        },

        // Add touch feedback
        addTouchFeedback ()
        {
            document.addEventListener( 'touchstart', ( e ) =>
            {
                if ( e.target.matches( 'button, a, [onclick], [role="button"]' ) )
                {
                    e.target.classList.add( 'touch-active' );
                }
            } );

            document.addEventListener( 'touchend', ( e ) =>
            {
                if ( e.target.matches( 'button, a, [onclick], [role="button"]' ) )
                {
                    setTimeout( () =>
                    {
                        e.target.classList.remove( 'touch-active' );
                    }, 150 );
                }
            } );
        },

        // Monitor focusable elements
        monitorFocusableElements ()
        {
            const observer = new MutationObserver( () =>
            {
                this.updateFocusableElements();
            } );

            observer.observe( document.body, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: [ 'tabindex', 'disabled', 'aria-hidden' ]
            } );
        },

        // Update focusable elements list
        updateFocusableElements ()
        {
            this.focusableElements = this.getFocusableElements( document.body );
        },

        // Announce current location
        announceCurrentLocation ()
        {
            const heading = document.querySelector( 'h1, [role="heading"][aria-level="1"]' );
            const breadcrumbs = document.querySelector( '[aria-label*="breadcrumb"], .breadcrumb' );

            let location = 'Unknown page';

            if ( heading )
            {
                location = heading.textContent.trim();
            } else if ( document.title )
            {
                location = document.title;
            }

            if ( breadcrumbs )
            {
                const crumbText = Array.from( breadcrumbs.querySelectorAll( 'a, span' ) )
                    .map( el => el.textContent.trim() )
                    .filter( text => text )
                    .join( ' > ' );

                if ( crumbText )
                {
                    location = `${ crumbText } > ${ location }`;
                }
            }

            this.announce( `Current location: ${ location }`, 'assertive' );
        },

        // Toggle accessibility panel
        toggleAccessibilityPanel ()
        {
            let panel = document.getElementById( 'accessibility-panel' );

            if ( !panel )
            {
                panel = this.createAccessibilityPanel();
            }

            const isVisible = !panel.classList.contains( 'hidden' );
            panel.classList.toggle( 'hidden', isVisible );

            if ( !isVisible )
            {
                this.trapFocusIn( panel );
                this.announce( 'Accessibility panel opened' );
            } else
            {
                this.releaseFocusTrap();
                this.announce( 'Accessibility panel closed' );
            }
        },

        // Create accessibility control panel
        createAccessibilityPanel ()
        {
            const panel = document.createElement( 'div' );
            panel.id = 'accessibility-panel';
            panel.className = 'accessibility-panel fixed top-4 right-4 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg p-6 w-80 z-50 hidden';
            panel.setAttribute( 'role', 'dialog' );
            panel.setAttribute( 'aria-labelledby', 'accessibility-panel-title' );
            panel.setAttribute( 'aria-modal', 'true' );

            panel.innerHTML = `
                <div class="flex items-center justify-between mb-4">
                    <h2 id="accessibility-panel-title" class="text-lg font-semibold">Accessibility Settings</h2>
                    <button type="button" aria-label="Close accessibility panel" onclick="accessibilityManager().toggleAccessibilityPanel()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <label class="flex items-center space-x-3">
                        <input type="checkbox" ${ this.colorContrastMode === 'high' ? 'checked' : '' } 
                               onchange="accessibilityManager().toggleHighContrast(this.checked)" 
                               class="form-checkbox">
                        <span>High Contrast Mode</span>
                    </label>
                    
                    <label class="flex items-center space-x-3">
                        <input type="checkbox" ${ this.reducedMotion ? 'checked' : '' } 
                               onchange="accessibilityManager().toggleReducedMotion(this.checked)" 
                               class="form-checkbox">
                        <span>Reduce Motion</span>
                    </label>
                    
                    <label class="flex items-center space-x-3">
                        <input type="checkbox" ${ this.announceChanges ? 'checked' : '' } 
                               onchange="accessibilityManager().announceChanges = this.checked" 
                               class="form-checkbox">
                        <span>Screen Reader Announcements</span>
                    </label>
                    
                    <label class="flex items-center space-x-3">
                        <input type="checkbox" ${ this.keyboardNavigation ? 'checked' : '' } 
                               onchange="accessibilityManager().keyboardNavigation = this.checked" 
                               class="form-checkbox">
                        <span>Enhanced Keyboard Navigation</span>
                    </label>
                </div>
                
                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-600">
                    <h3 class="text-sm font-medium mb-2">Keyboard Shortcuts</h3>
                    <div class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                        <div>Alt + S: Skip to main content</div>
                        <div>Alt + N: Skip to navigation</div>
                        <div>Alt + A: Toggle this panel</div>
                        <div>Alt + H: Announce location</div>
                    </div>
                </div>
            `;

            document.body.appendChild( panel );
            return panel;
        },

        // Get current accessibility status
        getAccessibilityStatus ()
        {
            return {
                announceChanges: this.announceChanges,
                keyboardNavigation: this.keyboardNavigation,
                colorContrastMode: this.colorContrastMode,
                reducedMotion: this.reducedMotion,
                screenReaderActive: this.screenReaderActive,
                focusTrapActive: this.trapFocus,
                touchAccessibility: window.matchMedia && window.matchMedia( '(pointer: coarse)' ).matches
            };
        },

        // ARIA utilities
        aria: {
            // Set ARIA attributes
            set ( element, attributes )
            {
                Object.keys( attributes ).forEach( key =>
                {
                    element.setAttribute( `aria-${ key }`, attributes[ key ] );
                } );
            },

            // Get ARIA attribute
            get ( element, attribute )
            {
                return element.getAttribute( `aria-${ attribute }` );
            },

            // Toggle ARIA attribute
            toggle ( element, attribute, value1, value2 )
            {
                const current = element.getAttribute( `aria-${ attribute }` );
                element.setAttribute( `aria-${ attribute }`, current === value1 ? value2 : value1 );
            },

            // Describe element for screen readers
            describe ( element, description )
            {
                let descId = element.getAttribute( 'aria-describedby' );
                if ( !descId )
                {
                    descId = `desc-${ Math.random().toString( 36 ).substr( 2, 9 ) }`;
                    element.setAttribute( 'aria-describedby', descId );

                    const descElement = document.createElement( 'div' );
                    descElement.id = descId;
                    descElement.className = 'sr-only';
                    descElement.textContent = description;
                    document.body.appendChild( descElement );
                }
            }
        }
    };
};

// Global accessibility utilities
window.a11yUtils = {
    // Quick announcement
    announce ( message, priority = 'polite' )
    {
        if ( window.accessibilityManagerInstance )
        {
            window.accessibilityManagerInstance.announce( message, priority );
        }
    },

    // Focus management
    focus: {
        trap ( container )
        {
            if ( window.accessibilityManagerInstance )
            {
                window.accessibilityManagerInstance.trapFocusIn( container );
            }
        },

        release ()
        {
            if ( window.accessibilityManagerInstance )
            {
                window.accessibilityManagerInstance.releaseFocusTrap();
            }
        },

        skipTo ( selector )
        {
            const element = document.querySelector( selector );
            if ( element )
            {
                element.focus();
                element.scrollIntoView( { behavior: 'smooth', block: 'start' } );
            }
        }
    },

    // ARIA helpers
    aria: {
        hide ( element )
        {
            element.setAttribute( 'aria-hidden', 'true' );
        },

        show ( element )
        {
            element.removeAttribute( 'aria-hidden' );
        },

        busy ( element, isBusy = true )
        {
            element.setAttribute( 'aria-busy', isBusy.toString() );
        }
    }
};

console.log( '✅ Accessibility Manager component loaded' );
