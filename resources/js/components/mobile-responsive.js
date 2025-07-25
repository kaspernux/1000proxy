/**
 * Mobile & Responsive Optimization JavaScript
 *
 * Comprehensive mobile-first JavaScript system with touch gesture support,
 * responsive behavior management, and mobile-specific performance optimizations.
 *
 * @version 1.0.0
 * @author ProxyAdmin System
 */

class MobileResponsiveManager
{
    constructor ()
    {
        this.breakpoints = {
            xs: 320,
            sm: 480,
            md: 768,
            lg: 1024,
            xl: 1200,
            xxl: 1440
        };

        this.currentBreakpoint = this.getCurrentBreakpoint();
        this.isTouch = this.detectTouchDevice();
        this.isMobile = this.currentBreakpoint === 'xs' || this.currentBreakpoint === 'sm';
        this.isTablet = this.currentBreakpoint === 'md';

        this.touchStartX = 0;
        this.touchStartY = 0;
        this.touchEndX = 0;
        this.touchEndY = 0;

        this.resizeObserver = null;
        this.intersectionObserver = null;

        this.init();
    }

    /**
     * Initialize the mobile responsive system
     */
    init ()
    {
        this.setupViewport();
        this.initializeResponsiveObservers();
        this.initializeTouchHandlers();
        this.initializeMobileNavigation();
        this.initializeGestureSupport();
        this.initializePerformanceOptimizations();
        this.initializeMobileModals();
        this.initializeScrollEnhancements();
        this.initializeOrientationHandling();
        this.bindEvents();

        // Set initial mobile classes
        this.updateMobileClasses();

        console.log( 'MobileResponsiveManager initialized', {
            breakpoint: this.currentBreakpoint,
            isTouch: this.isTouch,
            isMobile: this.isMobile,
            isTablet: this.isTablet
        } );
    }

    /**
     * Detect if device supports touch
     * @returns {boolean}
     */
    detectTouchDevice ()
    {
        return 'ontouchstart' in window ||
            navigator.maxTouchPoints > 0 ||
            navigator.msMaxTouchPoints > 0;
    }

    /**
     * Get current responsive breakpoint
     * @returns {string}
     */
    getCurrentBreakpoint ()
    {
        const width = window.innerWidth;

        if ( width >= this.breakpoints.xxl ) return 'xxl';
        if ( width >= this.breakpoints.xl ) return 'xl';
        if ( width >= this.breakpoints.lg ) return 'lg';
        if ( width >= this.breakpoints.md ) return 'md';
        if ( width >= this.breakpoints.sm ) return 'sm';
        return 'xs';
    }

    /**
     * Setup viewport meta tag optimization
     */
    setupViewport ()
    {
        let viewport = document.querySelector( 'meta[name="viewport"]' );
        if ( !viewport )
        {
            viewport = document.createElement( 'meta' );
            viewport.name = 'viewport';
            document.head.appendChild( viewport );
        }

        // Optimize viewport for mobile
        viewport.content = 'width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no';

        // Add mobile web app capabilities
        this.addMobileMetaTags();
    }

    /**
     * Add mobile web app meta tags
     */
    addMobileMetaTags ()
    {
        const metaTags = [
            { name: 'mobile-web-app-capable', content: 'yes' },
            { name: 'apple-mobile-web-app-capable', content: 'yes' },
            { name: 'apple-mobile-web-app-status-bar-style', content: 'default' },
            { name: 'theme-color', content: '#3b82f6' },
            { name: 'msapplication-TileColor', content: '#3b82f6' }
        ];

        metaTags.forEach( tag =>
        {
            if ( !document.querySelector( `meta[name="${ tag.name }"]` ) )
            {
                const meta = document.createElement( 'meta' );
                meta.name = tag.name;
                meta.content = tag.content;
                document.head.appendChild( meta );
            }
        } );
    }

    /**
     * Initialize responsive observers
     */
    initializeResponsiveObservers ()
    {
        // Resize Observer for responsive components
        if ( window.ResizeObserver )
        {
            this.resizeObserver = new ResizeObserver( entries =>
            {
                this.handleResize( entries );
            } );

            // Observe body for global resize handling
            this.resizeObserver.observe( document.body );
        }

        // Intersection Observer for lazy loading and performance
        if ( window.IntersectionObserver )
        {
            this.intersectionObserver = new IntersectionObserver( entries =>
            {
                this.handleIntersection( entries );
            }, {
                threshold: 0.1,
                rootMargin: '50px'
            } );
        }
    }

    /**
     * Handle resize events
     * @param {Array} entries
     */
    handleResize ( entries )
    {
        const newBreakpoint = this.getCurrentBreakpoint();

        if ( newBreakpoint !== this.currentBreakpoint )
        {
            const oldBreakpoint = this.currentBreakpoint;
            this.currentBreakpoint = newBreakpoint;
            this.isMobile = newBreakpoint === 'xs' || newBreakpoint === 'sm';
            this.isTablet = newBreakpoint === 'md';

            this.updateMobileClasses();
            this.handleBreakpointChange( oldBreakpoint, newBreakpoint );

            // Dispatch custom event
            window.dispatchEvent( new CustomEvent( 'breakpointChange', {
                detail: { oldBreakpoint, newBreakpoint, isMobile: this.isMobile }
            } ) );
        }

        // Handle responsive tables
        this.handleResponsiveTables();

        // Update touch targets
        this.updateTouchTargets();
    }

    /**
     * Handle intersection events for performance
     * @param {Array} entries
     */
    handleIntersection ( entries )
    {
        entries.forEach( entry =>
        {
            if ( entry.isIntersecting )
            {
                // Lazy load images
                if ( entry.target.hasAttribute( 'data-src' ) )
                {
                    this.lazyLoadImage( entry.target );
                }

                // Initialize components when visible
                if ( entry.target.hasAttribute( 'data-mobile-component' ) )
                {
                    this.initializeMobileComponent( entry.target );
                }
            }
        } );
    }

    /**
     * Initialize touch gesture handlers
     */
    initializeTouchHandlers ()
    {
        if ( !this.isTouch ) return;

        document.addEventListener( 'touchstart', ( e ) =>
        {
            this.handleTouchStart( e );
        }, { passive: true } );

        document.addEventListener( 'touchmove', ( e ) =>
        {
            this.handleTouchMove( e );
        }, { passive: false } );

        document.addEventListener( 'touchend', ( e ) =>
        {
            this.handleTouchEnd( e );
        }, { passive: true } );

        // Add touch feedback to interactive elements
        this.addTouchFeedback();
    }

    /**
     * Handle touch start
     * @param {TouchEvent} e
     */
    handleTouchStart ( e )
    {
        if ( e.touches.length === 1 )
        {
            this.touchStartX = e.touches[ 0 ].clientX;
            this.touchStartY = e.touches[ 0 ].clientY;
        }

        // Add touch feedback class
        const target = e.target.closest( '.touch-feedback' );
        if ( target )
        {
            target.classList.add( 'touching' );
        }
    }

    /**
     * Handle touch move
     * @param {TouchEvent} e
     */
    handleTouchMove ( e )
    {
        // Prevent scrolling on certain elements
        const target = e.target.closest( '.prevent-scroll' );
        if ( target )
        {
            e.preventDefault();
        }
    }

    /**
     * Handle touch end
     * @param {TouchEvent} e
     */
    handleTouchEnd ( e )
    {
        if ( e.changedTouches.length === 1 )
        {
            this.touchEndX = e.changedTouches[ 0 ].clientX;
            this.touchEndY = e.changedTouches[ 0 ].clientY;

            this.detectGesture();
        }

        // Remove touch feedback class
        const target = e.target.closest( '.touch-feedback' );
        if ( target )
        {
            target.classList.remove( 'touching' );
        }
    }

    /**
     * Detect swipe gestures
     */
    detectGesture ()
    {
        const deltaX = this.touchEndX - this.touchStartX;
        const deltaY = this.touchEndY - this.touchStartY;
        const minSwipeDistance = 50;

        if ( Math.abs( deltaX ) > Math.abs( deltaY ) )
        {
            // Horizontal swipe
            if ( Math.abs( deltaX ) > minSwipeDistance )
            {
                const direction = deltaX > 0 ? 'right' : 'left';
                this.handleSwipe( direction, deltaX );
            }
        } else
        {
            // Vertical swipe
            if ( Math.abs( deltaY ) > minSwipeDistance )
            {
                const direction = deltaY > 0 ? 'down' : 'up';
                this.handleSwipe( direction, deltaY );
            }
        }
    }

    /**
     * Handle swipe gestures
     * @param {string} direction
     * @param {number} distance
     */
    handleSwipe ( direction, distance )
    {
        // Handle mobile menu swipe
        if ( direction === 'right' && this.touchStartX < 50 )
        {
            this.openMobileMenu();
        } else if ( direction === 'left' && this.isMobileMenuOpen() )
        {
            this.closeMobileMenu();
        }

        // Dispatch custom swipe event
        window.dispatchEvent( new CustomEvent( 'mobileSwipe', {
            detail: { direction, distance }
        } ) );
    }

    /**
     * Initialize mobile navigation
     */
    initializeMobileNavigation ()
    {
        // Create mobile menu if it doesn't exist
        this.createMobileMenu();

        // Initialize hamburger menu
        this.initializeHamburgerMenu();

        // Handle navigation items
        this.initializeNavigationItems();
    }

    /**
     * Create mobile menu structure
     */
    createMobileMenu ()
    {
        if ( document.querySelector( '.mobile-menu' ) ) return;

        const mobileMenu = document.createElement( 'div' );
        mobileMenu.className = 'mobile-menu';
        mobileMenu.innerHTML = `
            <div class="mobile-menu-header">
                <h3>Navigation</h3>
                <button class="mobile-menu-close" aria-label="Close menu">
                    <span class="sr-only">Close menu</span>
                    ×
                </button>
            </div>
            <nav class="mobile-menu-nav">
                <!-- Navigation items will be populated dynamically -->
            </nav>
        `;

        const overlay = document.createElement( 'div' );
        overlay.className = 'mobile-menu-overlay';

        document.body.appendChild( overlay );
        document.body.appendChild( mobileMenu );

        // Populate navigation items
        this.populateMobileNavigation();
    }

    /**
     * Populate mobile navigation from existing navigation
     */
    populateMobileNavigation ()
    {
        const mobileNav = document.querySelector( '.mobile-menu-nav' );
        const existingNav = document.querySelector( 'nav:not(.mobile-menu-nav)' );

        if ( !mobileNav || !existingNav ) return;

        const navItems = existingNav.querySelectorAll( 'a[href]' );
        navItems.forEach( item =>
        {
            const mobileItem = document.createElement( 'a' );
            mobileItem.href = item.href;
            mobileItem.textContent = item.textContent;
            mobileItem.className = 'mobile-nav-item';

            if ( item.classList.contains( 'active' ) )
            {
                mobileItem.classList.add( 'active' );
            }

            mobileNav.appendChild( mobileItem );
        } );
    }

    /**
     * Initialize hamburger menu button
     */
    initializeHamburgerMenu ()
    {
        // Create hamburger button if it doesn't exist
        let hamburger = document.querySelector( '.hamburger-menu' );

        if ( !hamburger )
        {
            hamburger = document.createElement( 'button' );
            hamburger.className = 'hamburger-menu';
            hamburger.setAttribute( 'aria-label', 'Toggle navigation menu' );
            hamburger.innerHTML = `
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            `;

            // Add to header or navigation area
            const header = document.querySelector( 'header' ) || document.querySelector( '.navbar' );
            if ( header )
            {
                header.appendChild( hamburger );
            }
        }

        // Add event listeners
        hamburger.addEventListener( 'click', () =>
        {
            this.toggleMobileMenu();
        } );
    }

    /**
     * Initialize navigation item behaviors
     */
    initializeNavigationItems ()
    {
        // Close mobile menu when item is clicked
        document.addEventListener( 'click', ( e ) =>
        {
            if ( e.target.matches( '.mobile-nav-item' ) )
            {
                this.closeMobileMenu();
            }
        } );

        // Handle overlay click
        const overlay = document.querySelector( '.mobile-menu-overlay' );
        if ( overlay )
        {
            overlay.addEventListener( 'click', () =>
            {
                this.closeMobileMenu();
            } );
        }

        // Handle close button
        const closeBtn = document.querySelector( '.mobile-menu-close' );
        if ( closeBtn )
        {
            closeBtn.addEventListener( 'click', () =>
            {
                this.closeMobileMenu();
            } );
        }
    }

    /**
     * Toggle mobile menu
     */
    toggleMobileMenu ()
    {
        if ( this.isMobileMenuOpen() )
        {
            this.closeMobileMenu();
        } else
        {
            this.openMobileMenu();
        }
    }

    /**
     * Open mobile menu
     */
    openMobileMenu ()
    {
        const menu = document.querySelector( '.mobile-menu' );
        const overlay = document.querySelector( '.mobile-menu-overlay' );
        const hamburger = document.querySelector( '.hamburger-menu' );

        if ( menu ) menu.classList.add( 'open' );
        if ( overlay ) overlay.classList.add( 'active' );
        if ( hamburger ) hamburger.classList.add( 'active' );

        // Prevent body scroll
        document.body.style.overflow = 'hidden';

        // Focus management
        const firstNavItem = menu?.querySelector( '.mobile-nav-item' );
        if ( firstNavItem )
        {
            firstNavItem.focus();
        }
    }

    /**
     * Close mobile menu
     */
    closeMobileMenu ()
    {
        const menu = document.querySelector( '.mobile-menu' );
        const overlay = document.querySelector( '.mobile-menu-overlay' );
        const hamburger = document.querySelector( '.hamburger-menu' );

        if ( menu ) menu.classList.remove( 'open' );
        if ( overlay ) overlay.classList.remove( 'active' );
        if ( hamburger ) hamburger.classList.remove( 'active' );

        // Restore body scroll
        document.body.style.overflow = '';
    }

    /**
     * Check if mobile menu is open
     * @returns {boolean}
     */
    isMobileMenuOpen ()
    {
        const menu = document.querySelector( '.mobile-menu' );
        return menu?.classList.contains( 'open' ) || false;
    }

    /**
     * Initialize gesture support
     */
    initializeGestureSupport ()
    {
        // Pinch to zoom prevention on specific elements
        document.addEventListener( 'gesturestart', ( e ) =>
        {
            const target = e.target.closest( '.prevent-zoom' );
            if ( target )
            {
                e.preventDefault();
            }
        } );

        // Double tap to zoom prevention
        let lastTouchEnd = 0;
        document.addEventListener( 'touchend', ( e ) =>
        {
            const now = new Date().getTime();
            const target = e.target.closest( '.prevent-double-tap' );

            if ( target && now - lastTouchEnd <= 300 )
            {
                e.preventDefault();
            }
            lastTouchEnd = now;
        }, false );
    }

    /**
     * Initialize performance optimizations
     */
    initializePerformanceOptimizations ()
    {
        // Lazy load images
        this.initializeLazyLoading();

        // Optimize scroll performance
        this.optimizeScrollPerformance();

        // Preload critical resources
        this.preloadCriticalResources();

        // Initialize virtual scrolling for large lists
        this.initializeVirtualScrolling();
    }

    /**
     * Initialize lazy loading
     */
    initializeLazyLoading ()
    {
        const lazyImages = document.querySelectorAll( 'img[data-src]' );

        lazyImages.forEach( img =>
        {
            if ( this.intersectionObserver )
            {
                this.intersectionObserver.observe( img );
            } else
            {
                // Fallback for browsers without IntersectionObserver
                this.lazyLoadImage( img );
            }
        } );
    }

    /**
     * Lazy load image
     * @param {HTMLElement} img
     */
    lazyLoadImage ( img )
    {
        const src = img.getAttribute( 'data-src' );
        if ( src )
        {
            img.src = src;
            img.removeAttribute( 'data-src' );
            img.classList.add( 'loaded' );
        }
    }

    /**
     * Optimize scroll performance
     */
    optimizeScrollPerformance ()
    {
        let scrollTimeout;
        let isScrolling = false;

        const handleScroll = () =>
        {
            if ( !isScrolling )
            {
                isScrolling = true;
                requestAnimationFrame( () =>
                {
                    // Handle scroll-dependent UI updates
                    this.updateScrollDependentElements();
                    isScrolling = false;
                } );
            }

            clearTimeout( scrollTimeout );
            scrollTimeout = setTimeout( () =>
            {
                // Handle scroll end
                this.handleScrollEnd();
            }, 150 );
        };

        window.addEventListener( 'scroll', handleScroll, { passive: true } );
    }

    /**
     * Update scroll-dependent elements
     */
    updateScrollDependentElements ()
    {
        const scrollY = window.scrollY;

        // Update sticky headers
        const stickyHeaders = document.querySelectorAll( '.sticky-header' );
        stickyHeaders.forEach( header =>
        {
            if ( scrollY > 100 )
            {
                header.classList.add( 'scrolled' );
            } else
            {
                header.classList.remove( 'scrolled' );
            }
        } );

        // Update scroll progress indicators
        const progressBars = document.querySelectorAll( '.scroll-progress' );
        const documentHeight = document.documentElement.scrollHeight - window.innerHeight;
        const progress = Math.min( scrollY / documentHeight, 1 );

        progressBars.forEach( bar =>
        {
            bar.style.transform = `scaleX(${ progress })`;
        } );
    }

    /**
     * Handle scroll end
     */
    handleScrollEnd ()
    {
        // Trigger lazy loading check
        const lazyElements = document.querySelectorAll( '[data-mobile-component]:not(.initialized)' );
        lazyElements.forEach( element =>
        {
            const rect = element.getBoundingClientRect();
            if ( rect.top < window.innerHeight && rect.bottom > 0 )
            {
                this.initializeMobileComponent( element );
            }
        } );
    }

    /**
     * Initialize mobile modals
     */
    initializeMobileModals ()
    {
        const modalTriggers = document.querySelectorAll( '[data-mobile-modal]' );

        modalTriggers.forEach( trigger =>
        {
            trigger.addEventListener( 'click', ( e ) =>
            {
                e.preventDefault();
                const modalId = trigger.getAttribute( 'data-mobile-modal' );
                this.openMobileModal( modalId );
            } );
        } );

        // Handle modal close events
        document.addEventListener( 'click', ( e ) =>
        {
            if ( e.target.matches( '.modal-mobile-close' ) ||
                e.target.matches( '.modal-mobile-overlay' ) )
            {
                this.closeMobileModal();
            }
        } );

        // Handle escape key
        document.addEventListener( 'keydown', ( e ) =>
        {
            if ( e.key === 'Escape' && this.isMobileModalOpen() )
            {
                this.closeMobileModal();
            }
        } );
    }

    /**
     * Open mobile modal
     * @param {string} modalId
     */
    openMobileModal ( modalId )
    {
        const modal = document.getElementById( modalId );
        if ( !modal ) return;

        modal.classList.add( 'active' );
        document.body.style.overflow = 'hidden';

        // Focus management
        const firstFocusable = modal.querySelector( 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])' );
        if ( firstFocusable )
        {
            firstFocusable.focus();
        }
    }

    /**
     * Close mobile modal
     */
    closeMobileModal ()
    {
        const activeModal = document.querySelector( '.modal-mobile.active' );
        if ( activeModal )
        {
            activeModal.classList.remove( 'active' );
            document.body.style.overflow = '';
        }
    }

    /**
     * Check if mobile modal is open
     * @returns {boolean}
     */
    isMobileModalOpen ()
    {
        return document.querySelector( '.modal-mobile.active' ) !== null;
    }

    /**
     * Initialize scroll enhancements
     */
    initializeScrollEnhancements ()
    {
        // Smooth scroll for anchor links
        document.addEventListener( 'click', ( e ) =>
        {
            const anchor = e.target.closest( 'a[href^="#"]' );
            if ( anchor )
            {
                e.preventDefault();
                const targetId = anchor.getAttribute( 'href' ).slice( 1 );
                const target = document.getElementById( targetId );

                if ( target )
                {
                    target.scrollIntoView( {
                        behavior: 'smooth',
                        block: 'start'
                    } );
                }
            }
        } );

        // Add momentum scrolling for iOS
        document.body.style.webkitOverflowScrolling = 'touch';
    }

    /**
     * Initialize orientation handling
     */
    initializeOrientationHandling ()
    {
        const handleOrientationChange = () =>
        {
            // Add class for orientation
            document.body.classList.remove( 'portrait', 'landscape' );
            document.body.classList.add(
                window.innerHeight > window.innerWidth ? 'portrait' : 'landscape'
            );

            // Handle viewport height changes (iOS Safari)
            setTimeout( () =>
            {
                this.updateViewportHeight();
            }, 100 );
        };

        window.addEventListener( 'orientationchange', handleOrientationChange );
        window.addEventListener( 'resize', handleOrientationChange );

        // Initial call
        handleOrientationChange();
    }

    /**
     * Update viewport height for mobile browsers
     */
    updateViewportHeight ()
    {
        const vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty( '--vh', `${ vh }px` );
    }

    /**
     * Handle responsive tables
     */
    handleResponsiveTables ()
    {
        const tables = document.querySelectorAll( '.table-mobile' );

        tables.forEach( table =>
        {
            if ( this.isMobile )
            {
                // Add data labels for mobile view
                const headers = Array.from( table.querySelectorAll( 'thead th' ) );
                const rows = table.querySelectorAll( 'tbody tr' );

                rows.forEach( row =>
                {
                    const cells = row.querySelectorAll( 'td' );
                    cells.forEach( ( cell, index ) =>
                    {
                        if ( headers[ index ] )
                        {
                            cell.setAttribute( 'data-label', headers[ index ].textContent );
                        }
                    } );
                } );
            }
        } );
    }

    /**
     * Update touch targets
     */
    updateTouchTargets ()
    {
        if ( !this.isTouch ) return;

        const touchTargets = document.querySelectorAll( '.touch-target' );

        touchTargets.forEach( target =>
        {
            const rect = target.getBoundingClientRect();
            if ( rect.width < 44 || rect.height < 44 )
            {
                target.style.minWidth = '44px';
                target.style.minHeight = '44px';
                target.style.display = 'inline-flex';
                target.style.alignItems = 'center';
                target.style.justifyContent = 'center';
            }
        } );
    }

    /**
     * Add touch feedback to elements
     */
    addTouchFeedback ()
    {
        const touchElements = document.querySelectorAll( '.touch-feedback' );

        touchElements.forEach( element =>
        {
            element.style.position = 'relative';
            element.style.overflow = 'hidden';
        } );
    }

    /**
     * Update mobile classes on body
     */
    updateMobileClasses ()
    {
        const body = document.body;

        // Remove all breakpoint classes
        Object.keys( this.breakpoints ).forEach( bp =>
        {
            body.classList.remove( `breakpoint-${ bp }` );
        } );

        // Add current breakpoint class
        body.classList.add( `breakpoint-${ this.currentBreakpoint }` );

        // Add device type classes
        body.classList.toggle( 'is-mobile', this.isMobile );
        body.classList.toggle( 'is-tablet', this.isTablet );
        body.classList.toggle( 'is-touch', this.isTouch );
        body.classList.toggle( 'is-desktop', !this.isMobile && !this.isTablet );
    }

    /**
     * Handle breakpoint changes
     * @param {string} oldBreakpoint
     * @param {string} newBreakpoint
     */
    handleBreakpointChange ( oldBreakpoint, newBreakpoint )
    {
        // Close mobile menu when switching to desktop
        if ( ( oldBreakpoint === 'xs' || oldBreakpoint === 'sm' ) &&
            ( newBreakpoint !== 'xs' && newBreakpoint !== 'sm' ) )
        {
            this.closeMobileMenu();
        }

        // Reinitialize components that need responsive handling
        this.reinitializeResponsiveComponents();
    }

    /**
     * Reinitialize responsive components
     */
    reinitializeResponsiveComponents ()
    {
        // Reinitialize tables
        this.handleResponsiveTables();

        // Reinitialize touch targets
        this.updateTouchTargets();

        // Update mobile navigation
        this.populateMobileNavigation();
    }

    /**
     * Preload critical resources
     */
    preloadCriticalResources ()
    {
        // Preload critical CSS
        const criticalCSS = [
            '/css/mobile-responsive.css',
            '/css/components.css'
        ];

        criticalCSS.forEach( href =>
        {
            const link = document.createElement( 'link' );
            link.rel = 'preload';
            link.as = 'style';
            link.href = href;
            document.head.appendChild( link );
        } );
    }

    /**
     * Initialize virtual scrolling for large lists
     */
    initializeVirtualScrolling ()
    {
        const virtualLists = document.querySelectorAll( '.virtual-scroll' );

        virtualLists.forEach( list =>
        {
            if ( list.children.length > 50 )
            {
                this.setupVirtualScrolling( list );
            }
        } );
    }

    /**
     * Setup virtual scrolling for a list
     * @param {HTMLElement} list
     */
    setupVirtualScrolling ( list )
    {
        // Implementation would depend on specific requirements
        // This is a placeholder for virtual scrolling setup
        console.log( 'Virtual scrolling setup for:', list );
    }

    /**
     * Initialize mobile component
     * @param {HTMLElement} element
     */
    initializeMobileComponent ( element )
    {
        if ( element.classList.contains( 'initialized' ) ) return;

        const componentType = element.getAttribute( 'data-mobile-component' );

        switch ( componentType )
        {
            case 'lazy-table':
                this.initializeLazyTable( element );
                break;
            case 'touch-slider':
                this.initializeTouchSlider( element );
                break;
            case 'mobile-form':
                this.initializeMobileForm( element );
                break;
            default:
                console.log( 'Unknown mobile component type:', componentType );
        }

        element.classList.add( 'initialized' );
    }

    /**
     * Initialize lazy table
     * @param {HTMLElement} table
     */
    initializeLazyTable ( table )
    {
        // Add mobile-friendly table enhancements
        table.classList.add( 'table-mobile' );
        this.handleResponsiveTables();
    }

    /**
     * Initialize touch slider
     * @param {HTMLElement} slider
     */
    initializeTouchSlider ( slider )
    {
        // Add touch gesture support for sliders
        let startX = 0;
        let currentIndex = 0;
        const slides = slider.querySelectorAll( '.slide' );

        slider.addEventListener( 'touchstart', ( e ) =>
        {
            startX = e.touches[ 0 ].clientX;
        } );

        slider.addEventListener( 'touchend', ( e ) =>
        {
            const endX = e.changedTouches[ 0 ].clientX;
            const diff = startX - endX;

            if ( Math.abs( diff ) > 50 )
            {
                if ( diff > 0 && currentIndex < slides.length - 1 )
                {
                    currentIndex++;
                } else if ( diff < 0 && currentIndex > 0 )
                {
                    currentIndex--;
                }

                slider.style.transform = `translateX(-${ currentIndex * 100 }%)`;
            }
        } );
    }

    /**
     * Initialize mobile form
     * @param {HTMLElement} form
     */
    initializeMobileForm ( form )
    {
        // Add mobile-friendly form enhancements
        form.classList.add( 'form-mobile' );

        // Prevent zoom on iOS when focusing inputs
        const inputs = form.querySelectorAll( 'input, select, textarea' );
        inputs.forEach( input =>
        {
            if ( input.style.fontSize === '' || parseFloat( input.style.fontSize ) < 16 )
            {
                input.style.fontSize = '16px';
            }
        } );
    }

    /**
     * Bind global events
     */
    bindEvents ()
    {
        // Handle window resize
        window.addEventListener( 'resize', () =>
        {
            clearTimeout( this.resizeTimeout );
            this.resizeTimeout = setTimeout( () =>
            {
                this.handleResize( [] );
            }, 100 );
        } );

        // Handle visibility change
        document.addEventListener( 'visibilitychange', () =>
        {
            if ( document.hidden )
            {
                // Pause non-essential processes
                this.pauseNonEssentialProcesses();
            } else
            {
                // Resume processes
                this.resumeProcesses();
            }
        } );

        // Handle online/offline
        window.addEventListener( 'online', () =>
        {
            this.handleConnectionChange( true );
        } );

        window.addEventListener( 'offline', () =>
        {
            this.handleConnectionChange( false );
        } );
    }

    /**
     * Pause non-essential processes
     */
    pauseNonEssentialProcesses ()
    {
        // Pause animations, timers, etc.
        document.body.classList.add( 'paused' );
    }

    /**
     * Resume processes
     */
    resumeProcesses ()
    {
        // Resume animations, timers, etc.
        document.body.classList.remove( 'paused' );
    }

    /**
     * Handle connection changes
     * @param {boolean} isOnline
     */
    handleConnectionChange ( isOnline )
    {
        document.body.classList.toggle( 'offline', !isOnline );

        // Dispatch custom event
        window.dispatchEvent( new CustomEvent( 'connectionChange', {
            detail: { isOnline }
        } ) );
    }

    /**
     * Get public API
     * @returns {Object}
     */
    getAPI ()
    {
        return {
            // Properties
            currentBreakpoint: this.currentBreakpoint,
            isMobile: this.isMobile,
            isTablet: this.isTablet,
            isTouch: this.isTouch,

            // Methods
            openMobileMenu: () => this.openMobileMenu(),
            closeMobileMenu: () => this.closeMobileMenu(),
            toggleMobileMenu: () => this.toggleMobileMenu(),
            openMobileModal: ( id ) => this.openMobileModal( id ),
            closeMobileModal: () => this.closeMobileModal(),
            getCurrentBreakpoint: () => this.getCurrentBreakpoint(),
            updateMobileClasses: () => this.updateMobileClasses(),

            // Events
            on: ( event, callback ) =>
            {
                window.addEventListener( event, callback );
            },
            off: ( event, callback ) =>
            {
                window.removeEventListener( event, callback );
            }
        };
    }

    /**
     * Destroy the mobile responsive manager
     */
    destroy ()
    {
        // Clean up observers
        if ( this.resizeObserver )
        {
            this.resizeObserver.disconnect();
        }

        if ( this.intersectionObserver )
        {
            this.intersectionObserver.disconnect();
        }

        // Remove event listeners
        // (In a real implementation, you'd need to store references to remove them)

        // Remove mobile classes
        document.body.classList.remove(
            'is-mobile', 'is-tablet', 'is-touch', 'is-desktop',
            ...Object.keys( this.breakpoints ).map( bp => `breakpoint-${ bp }` )
        );

        console.log( 'MobileResponsiveManager destroyed' );
    }
}

// Global initialization
let mobileResponsiveManager;

// Initialize when DOM is ready
if ( document.readyState === 'loading' )
{
    document.addEventListener( 'DOMContentLoaded', () =>
    {
        mobileResponsiveManager = new MobileResponsiveManager();
        window.MobileResponsive = mobileResponsiveManager.getAPI();
    } );
} else
{
    mobileResponsiveManager = new MobileResponsiveManager();
    window.MobileResponsive = mobileResponsiveManager.getAPI();
}

// Export for module systems
if ( typeof module !== 'undefined' && module.exports )
{
    module.exports = MobileResponsiveManager;
}

// Auto-initialize CSS classes and mobile detection
document.documentElement.classList.add(
    'js-enabled',
    navigator.maxTouchPoints > 0 ? 'touch-enabled' : 'no-touch'
);

// Add initial mobile classes
const isMobileDevice = window.innerWidth <= 768;
document.body.classList.toggle( 'mobile-device', isMobileDevice );

console.log( 'Mobile & Responsive Optimization loaded' );
