/**
 * Advanced Layout System for 1000proxy Platform
 * 
 * Features:
 * - Flexible CSS Grid-based system
 * - Responsive breakpoint management
 * - Dynamic layout switching
 * - Sidebar and navigation layouts
 * - Sticky headers and footers
 * - Layout customization options
 * - Layout persistence and state management
 * - Performance-optimized with ResizeObserver
 */

class AdvancedLayoutSystem
{
    constructor ()
    {
        this.layouts = new Map();
        this.breakpoints = {
            xs: 0,
            sm: 640,
            md: 768,
            lg: 1024,
            xl: 1280,
            '2xl': 1536
        };
        this.currentBreakpoint = this.getCurrentBreakpoint();
        this.resizeObserver = null;
        this.eventListeners = new Map();
        this.layoutTemplates = new Map();
        this.customizations = this.loadCustomizations();

        this.init();
    }

    init ()
    {
        this.createLayoutTemplates();
        this.setupResizeObserver();
        this.setupEventListeners();
        this.initializeLayouts();

        console.log( '🎨 Advanced Layout System initialized' );
    }

    // =============================================================================
    // Layout Templates
    // =============================================================================

    createLayoutTemplates ()
    {
        // Default Application Layout
        this.layoutTemplates.set( 'app', {
            name: 'Application Layout',
            type: 'app',
            areas: [
                '"header header header"',
                '"sidebar main aside"',
                '"footer footer footer"'
            ],
            columns: '250px 1fr 300px',
            rows: 'auto 1fr auto',
            gap: '0',
            responsive: {
                md: {
                    areas: [
                        '"header"',
                        '"main"',
                        '"footer"'
                    ],
                    columns: '1fr',
                    rows: 'auto 1fr auto'
                }
            },
            customizable: true
        } );

        // Dashboard Layout
        this.layoutTemplates.set( 'dashboard', {
            name: 'Dashboard Layout',
            type: 'dashboard',
            areas: [
                '"toolbar toolbar toolbar toolbar"',
                '"sidebar stats stats widgets"',
                '"sidebar content content widgets"',
                '"sidebar content content widgets"'
            ],
            columns: '280px 1fr 1fr 320px',
            rows: 'auto auto 1fr 1fr',
            gap: '1rem',
            responsive: {
                lg: {
                    areas: [
                        '"toolbar toolbar"',
                        '"sidebar stats"',
                        '"content widgets"',
                        '"content widgets"'
                    ],
                    columns: '280px 1fr',
                    rows: 'auto auto 1fr 1fr'
                },
                md: {
                    areas: [
                        '"toolbar"',
                        '"stats"',
                        '"content"',
                        '"widgets"'
                    ],
                    columns: '1fr',
                    rows: 'auto auto 1fr auto'
                }
            },
            customizable: true
        } );

        // Admin Layout
        this.layoutTemplates.set( 'admin', {
            name: 'Admin Panel Layout',
            type: 'admin',
            areas: [
                '"header header header"',
                '"nav content aside"',
                '"nav content aside"'
            ],
            columns: '240px 1fr 280px',
            rows: 'auto 1fr auto',
            gap: '0',
            responsive: {
                lg: {
                    areas: [
                        '"header header"',
                        '"nav content"',
                        '"nav content"'
                    ],
                    columns: '240px 1fr',
                    rows: 'auto 1fr auto'
                },
                md: {
                    areas: [
                        '"header"',
                        '"content"',
                        '"nav"'
                    ],
                    columns: '1fr',
                    rows: 'auto 1fr auto'
                }
            },
            stickyElements: [ 'header', 'nav' ],
            customizable: true
        } );

        // Documentation Layout
        this.layoutTemplates.set( 'docs', {
            name: 'Documentation Layout',
            type: 'docs',
            areas: [
                '"header header"',
                '"sidebar content"',
                '"sidebar content"'
            ],
            columns: '300px 1fr',
            rows: 'auto 1fr auto',
            gap: '0',
            responsive: {
                md: {
                    areas: [
                        '"header"',
                        '"content"',
                        '"sidebar"'
                    ],
                    columns: '1fr',
                    rows: 'auto 1fr auto'
                }
            },
            stickyElements: [ 'sidebar' ],
            customizable: false
        } );

        // Blog Layout
        this.layoutTemplates.set( 'blog', {
            name: 'Blog Layout',
            type: 'blog',
            areas: [
                '"header header header"',
                '"sidebar content related"',
                '"sidebar content related"',
                '"footer footer footer"'
            ],
            columns: '250px 1fr 300px',
            rows: 'auto 1fr auto auto',
            gap: '2rem 1rem',
            responsive: {
                lg: {
                    areas: [
                        '"header header"',
                        '"content related"',
                        '"content related"',
                        '"footer footer"'
                    ],
                    columns: '1fr 300px',
                    rows: 'auto 1fr auto auto'
                },
                md: {
                    areas: [
                        '"header"',
                        '"content"',
                        '"related"',
                        '"sidebar"',
                        '"footer"'
                    ],
                    columns: '1fr',
                    rows: 'auto 1fr auto auto auto'
                }
            },
            customizable: true
        } );

        // Split Layout
        this.layoutTemplates.set( 'split', {
            name: 'Split Layout',
            type: 'split',
            areas: [
                '"left right"'
            ],
            columns: '1fr 1fr',
            rows: '1fr',
            gap: '1rem',
            responsive: {
                md: {
                    areas: [
                        '"left"',
                        '"right"'
                    ],
                    columns: '1fr',
                    rows: '1fr 1fr'
                }
            },
            customizable: true
        } );
    }

    // =============================================================================
    // Layout Management
    // =============================================================================

    createLayout ( containerId, templateName, options = {} )
    {
        const template = this.layoutTemplates.get( templateName );
        if ( !template )
        {
            throw new Error( `Layout template "${ templateName }" not found` );
        }

        const container = document.getElementById( containerId );
        if ( !container )
        {
            throw new Error( `Container element "${ containerId }" not found` );
        }

        const layout = {
            id: containerId,
            template: templateName,
            container,
            config: { ...template, ...options },
            areas: new Map(),
            isActive: false,
            customizations: this.customizations[ containerId ] || {}
        };

        // Apply customizations
        if ( layout.customizations.columns )
        {
            layout.config.columns = layout.customizations.columns;
        }
        if ( layout.customizations.rows )
        {
            layout.config.rows = layout.customizations.rows;
        }
        if ( layout.customizations.gap )
        {
            layout.config.gap = layout.customizations.gap;
        }

        this.layouts.set( containerId, layout );
        this.applyLayout( layout );

        return layout;
    }

    applyLayout ( layout )
    {
        const { container, config } = layout;
        const breakpointConfig = this.getBreakpointConfig( config );

        // Apply CSS Grid properties
        container.style.display = 'grid';
        container.style.gridTemplateAreas = breakpointConfig.areas.join( ' ' );
        container.style.gridTemplateColumns = breakpointConfig.columns;
        container.style.gridTemplateRows = breakpointConfig.rows;
        container.style.gap = breakpointConfig.gap || config.gap || '0';
        container.style.minHeight = '100vh';

        // Add layout classes
        container.classList.add( 'advanced-layout' );
        container.classList.add( `layout-${ config.type }` );
        container.classList.add( `layout-${ this.currentBreakpoint }` );

        // Apply sticky elements
        if ( config.stickyElements )
        {
            this.applyStickyElements( layout, config.stickyElements );
        }

        // Assign grid areas to child elements
        this.assignGridAreas( layout, breakpointConfig );

        layout.isActive = true;

        this.emit( 'layoutApplied', { layout, breakpoint: this.currentBreakpoint } );
    }

    getBreakpointConfig ( config )
    {
        const responsive = config.responsive || {};

        // Check breakpoints in descending order
        const breakpointKeys = Object.keys( this.breakpoints ).reverse();

        for ( const bp of breakpointKeys )
        {
            if ( window.innerWidth >= this.breakpoints[ bp ] && responsive[ bp ] )
            {
                return { ...config, ...responsive[ bp ] };
            }
        }

        return config;
    }

    assignGridAreas ( layout, config )
    {
        const { container } = layout;
        const areas = this.extractGridAreas( config.areas );

        // Clear existing area assignments
        layout.areas.clear();

        areas.forEach( area =>
        {
            const elements = container.querySelectorAll( `[data-grid-area="${ area }"], .grid-area-${ area }, .${ area }-area` );

            elements.forEach( element =>
            {
                element.style.gridArea = area;
                element.classList.add( 'grid-area', `grid-area-${ area }` );
                layout.areas.set( area, element );
            } );
        } );
    }

    extractGridAreas ( areaStrings )
    {
        const areas = new Set();

        areaStrings.forEach( areaString =>
        {
            const matches = areaString.match( /[a-zA-Z][a-zA-Z0-9_-]*/g );
            if ( matches )
            {
                matches.forEach( area => areas.add( area ) );
            }
        } );

        return Array.from( areas );
    }

    applyStickyElements ( layout, stickyElements )
    {
        stickyElements.forEach( elementName =>
        {
            const element = layout.areas.get( elementName );
            if ( element )
            {
                element.style.position = 'sticky';
                element.style.top = '0';
                element.style.zIndex = '100';
                element.classList.add( 'sticky-element' );
            }
        } );
    }

    // =============================================================================
    // Dynamic Layout Switching
    // =============================================================================

    switchLayout ( containerId, templateName, options = {} )
    {
        const layout = this.layouts.get( containerId );
        if ( !layout )
        {
            throw new Error( `Layout "${ containerId }" not found` );
        }

        const newTemplate = this.layoutTemplates.get( templateName );
        if ( !newTemplate )
        {
            throw new Error( `Layout template "${ templateName }" not found` );
        }

        // Clear current layout
        this.clearLayout( layout );

        // Update layout configuration
        layout.template = templateName;
        layout.config = { ...newTemplate, ...options };

        // Apply new layout
        this.applyLayout( layout );

        this.emit( 'layoutSwitched', { layout, oldTemplate: layout.template, newTemplate: templateName } );
    }

    clearLayout ( layout )
    {
        const { container } = layout;

        // Remove layout styles
        container.style.display = '';
        container.style.gridTemplateAreas = '';
        container.style.gridTemplateColumns = '';
        container.style.gridTemplateRows = '';
        container.style.gap = '';

        // Remove layout classes
        container.classList.remove( 'advanced-layout' );
        container.classList.remove( `layout-${ layout.config.type }` );
        container.classList.remove( `layout-${ this.currentBreakpoint }` );

        // Clear grid area assignments
        layout.areas.forEach( ( element, area ) =>
        {
            element.style.gridArea = '';
            element.classList.remove( 'grid-area', `grid-area-${ area }`, 'sticky-element' );
            element.style.position = '';
            element.style.top = '';
            element.style.zIndex = '';
        } );

        layout.areas.clear();
        layout.isActive = false;
    }

    // =============================================================================
    // Responsive Management
    // =============================================================================

    getCurrentBreakpoint ()
    {
        const width = window.innerWidth;
        const breakpointKeys = Object.keys( this.breakpoints ).reverse();

        for ( const bp of breakpointKeys )
        {
            if ( width >= this.breakpoints[ bp ] )
            {
                return bp;
            }
        }

        return 'xs';
    }

    handleBreakpointChange ()
    {
        const newBreakpoint = this.getCurrentBreakpoint();

        if ( newBreakpoint !== this.currentBreakpoint )
        {
            const oldBreakpoint = this.currentBreakpoint;
            this.currentBreakpoint = newBreakpoint;

            // Update all active layouts
            this.layouts.forEach( layout =>
            {
                if ( layout.isActive )
                {
                    this.applyLayout( layout );
                }
            } );

            this.emit( 'breakpointChanged', { oldBreakpoint, newBreakpoint } );
        }
    }

    setupResizeObserver ()
    {
        if ( window.ResizeObserver )
        {
            this.resizeObserver = new ResizeObserver( entries =>
            {
                // Debounce resize handling
                clearTimeout( this.resizeTimeout );
                this.resizeTimeout = setTimeout( () =>
                {
                    this.handleBreakpointChange();
                }, 100 );
            } );

            this.resizeObserver.observe( document.body );
        } else
        {
            // Fallback to window resize event
            window.addEventListener( 'resize', () =>
            {
                clearTimeout( this.resizeTimeout );
                this.resizeTimeout = setTimeout( () =>
                {
                    this.handleBreakpointChange();
                }, 100 );
            } );
        }
    }

    // =============================================================================
    // Layout Customization
    // =============================================================================

    customizeLayout ( containerId, customizations )
    {
        const layout = this.layouts.get( containerId );
        if ( !layout )
        {
            throw new Error( `Layout "${ containerId }" not found` );
        }

        if ( !layout.config.customizable )
        {
            throw new Error( `Layout "${ containerId }" is not customizable` );
        }

        // Validate customizations
        this.validateCustomizations( customizations );

        // Apply customizations
        layout.customizations = { ...layout.customizations, ...customizations };

        // Update layout configuration
        if ( customizations.columns )
        {
            layout.config.columns = customizations.columns;
        }
        if ( customizations.rows )
        {
            layout.config.rows = customizations.rows;
        }
        if ( customizations.gap )
        {
            layout.config.gap = customizations.gap;
        }

        // Reapply layout
        this.applyLayout( layout );

        // Save customizations
        this.saveCustomizations( containerId, layout.customizations );

        this.emit( 'layoutCustomized', { layout, customizations } );
    }

    validateCustomizations ( customizations )
    {
        // Validate columns
        if ( customizations.columns && !this.isValidGridValue( customizations.columns ) )
        {
            throw new Error( 'Invalid columns value' );
        }

        // Validate rows
        if ( customizations.rows && !this.isValidGridValue( customizations.rows ) )
        {
            throw new Error( 'Invalid rows value' );
        }

        // Validate gap
        if ( customizations.gap && !this.isValidGapValue( customizations.gap ) )
        {
            throw new Error( 'Invalid gap value' );
        }
    }

    isValidGridValue ( value )
    {
        // Basic validation for CSS Grid values
        const validPatterns = [
            /^(\d+px|\d+fr|\d+%|auto|min-content|max-content|\d+rem|\d+em)(\s+(\d+px|\d+fr|\d+%|auto|min-content|max-content|\d+rem|\d+em))*$/,
            /^repeat\(\d+,\s*(\d+px|\d+fr|\d+%|auto|min-content|max-content|\d+rem|\d+em)\)$/,
            /^minmax\((\d+px|\d+fr|\d+%|auto|min-content|max-content|\d+rem|\d+em),\s*(\d+px|\d+fr|\d+%|auto|min-content|max-content|\d+rem|\d+em)\)$/
        ];

        return validPatterns.some( pattern => pattern.test( value ) );
    }

    isValidGapValue ( value )
    {
        const gapPattern = /^(\d+px|\d+rem|\d+em|\d+%)(\s+(\d+px|\d+rem|\d+em|\d+%))?$/;
        return gapPattern.test( value );
    }

    resetLayoutCustomizations ( containerId )
    {
        const layout = this.layouts.get( containerId );
        if ( !layout )
        {
            throw new Error( `Layout "${ containerId }" not found` );
        }

        // Reset to template defaults
        const template = this.layoutTemplates.get( layout.template );
        layout.config = { ...template };
        layout.customizations = {};

        // Reapply layout
        this.applyLayout( layout );

        // Clear saved customizations
        this.saveCustomizations( containerId, {} );

        this.emit( 'layoutReset', { layout } );
    }

    // =============================================================================
    // Sidebar Management
    // =============================================================================

    createSidebar ( containerId, options = {} )
    {
        const defaultOptions = {
            position: 'left', // left, right
            width: '250px',
            collapsible: true,
            collapsed: false,
            overlay: false,
            persistent: true
        };

        const config = { ...defaultOptions, ...options };
        const container = document.getElementById( containerId );

        if ( !container )
        {
            throw new Error( `Sidebar container "${ containerId }" not found` );
        }

        const sidebar = {
            id: containerId,
            container,
            config,
            isCollapsed: config.collapsed,
            isVisible: true
        };

        this.applySidebarStyles( sidebar );

        if ( config.collapsible )
        {
            this.addSidebarToggle( sidebar );
        }

        return sidebar;
    }

    applySidebarStyles ( sidebar )
    {
        const { container, config } = sidebar;

        container.classList.add( 'advanced-sidebar' );
        container.classList.add( `sidebar-${ config.position }` );

        if ( config.collapsible )
        {
            container.classList.add( 'sidebar-collapsible' );
        }

        if ( config.overlay )
        {
            container.classList.add( 'sidebar-overlay' );
        }

        // Apply initial width
        if ( !sidebar.isCollapsed )
        {
            container.style.width = config.width;
        } else
        {
            container.style.width = '0';
        }

        // Add transition
        container.style.transition = 'width 0.3s ease-in-out';
    }

    addSidebarToggle ( sidebar )
    {
        const toggleButton = document.createElement( 'button' );
        toggleButton.className = 'sidebar-toggle';
        toggleButton.innerHTML = sidebar.config.position === 'left' ? '☰' : '☰';
        toggleButton.setAttribute( 'aria-label', 'Toggle sidebar' );

        toggleButton.addEventListener( 'click', () =>
        {
            this.toggleSidebar( sidebar );
        } );

        // Insert toggle button
        if ( sidebar.config.position === 'left' )
        {
            sidebar.container.insertBefore( toggleButton, sidebar.container.firstChild );
        } else
        {
            sidebar.container.appendChild( toggleButton );
        }
    }

    toggleSidebar ( sidebar )
    {
        sidebar.isCollapsed = !sidebar.isCollapsed;

        if ( sidebar.isCollapsed )
        {
            sidebar.container.style.width = '0';
            sidebar.container.classList.add( 'collapsed' );
        } else
        {
            sidebar.container.style.width = sidebar.config.width;
            sidebar.container.classList.remove( 'collapsed' );
        }

        this.emit( 'sidebarToggled', { sidebar, isCollapsed: sidebar.isCollapsed } );
    }

    // =============================================================================
    // Navigation Management
    // =============================================================================

    createNavigation ( containerId, options = {} )
    {
        const defaultOptions = {
            type: 'horizontal', // horizontal, vertical, tabs
            sticky: true,
            items: [],
            activeItem: null
        };

        const config = { ...defaultOptions, ...options };
        const container = document.getElementById( containerId );

        if ( !container )
        {
            throw new Error( `Navigation container "${ containerId }" not found` );
        }

        const navigation = {
            id: containerId,
            container,
            config,
            activeItem: config.activeItem
        };

        this.applyNavigationStyles( navigation );
        this.renderNavigationItems( navigation );

        return navigation;
    }

    applyNavigationStyles ( navigation )
    {
        const { container, config } = navigation;

        container.classList.add( 'advanced-navigation' );
        container.classList.add( `nav-${ config.type }` );

        if ( config.sticky )
        {
            container.classList.add( 'nav-sticky' );
            container.style.position = 'sticky';
            container.style.top = '0';
            container.style.zIndex = '200';
        }
    }

    renderNavigationItems ( navigation )
    {
        const { container, config } = navigation;

        const navList = document.createElement( 'ul' );
        navList.className = 'nav-list';

        config.items.forEach( item =>
        {
            const navItem = this.createNavigationItem( item, navigation );
            navList.appendChild( navItem );
        } );

        container.appendChild( navList );
    }

    createNavigationItem ( item, navigation )
    {
        const listItem = document.createElement( 'li' );
        listItem.className = 'nav-item';

        const link = document.createElement( 'a' );
        link.href = item.href || '#';
        link.textContent = item.label;
        link.className = 'nav-link';

        if ( item.id === navigation.activeItem )
        {
            link.classList.add( 'active' );
        }

        link.addEventListener( 'click', ( e ) =>
        {
            e.preventDefault();
            this.setActiveNavigationItem( navigation, item.id );
            if ( item.onClick )
            {
                item.onClick( item, navigation );
            }
        } );

        listItem.appendChild( link );
        return listItem;
    }

    setActiveNavigationItem ( navigation, itemId )
    {
        const { container } = navigation;

        // Remove active class from all items
        container.querySelectorAll( '.nav-link' ).forEach( link =>
        {
            link.classList.remove( 'active' );
        } );

        // Add active class to selected item
        const activeLink = container.querySelector( `[data-item-id="${ itemId }"]` );
        if ( activeLink )
        {
            activeLink.classList.add( 'active' );
        }

        navigation.activeItem = itemId;

        this.emit( 'navigationItemChanged', { navigation, activeItem: itemId } );
    }

    // =============================================================================
    // Persistence and State Management
    // =============================================================================

    loadCustomizations ()
    {
        try
        {
            const saved = localStorage.getItem( 'layout-customizations' );
            return saved ? JSON.parse( saved ) : {};
        } catch ( error )
        {
            console.warn( 'Failed to load layout customizations:', error );
            return {};
        }
    }

    saveCustomizations ( containerId, customizations )
    {
        try
        {
            this.customizations[ containerId ] = customizations;
            localStorage.setItem( 'layout-customizations', JSON.stringify( this.customizations ) );
        } catch ( error )
        {
            console.warn( 'Failed to save layout customizations:', error );
        }
    }

    // =============================================================================
    // Event System
    // =============================================================================

    on ( eventName, callback )
    {
        if ( !this.eventListeners.has( eventName ) )
        {
            this.eventListeners.set( eventName, [] );
        }
        this.eventListeners.get( eventName ).push( callback );
    }

    off ( eventName, callback )
    {
        if ( this.eventListeners.has( eventName ) )
        {
            const listeners = this.eventListeners.get( eventName );
            const index = listeners.indexOf( callback );
            if ( index > -1 )
            {
                listeners.splice( index, 1 );
            }
        }
    }

    emit ( eventName, data )
    {
        if ( this.eventListeners.has( eventName ) )
        {
            this.eventListeners.get( eventName ).forEach( callback =>
            {
                try
                {
                    callback( data );
                } catch ( error )
                {
                    console.error( `Error in event listener for ${ eventName }:`, error );
                }
            } );
        }
    }

    setupEventListeners ()
    {
        // Keyboard shortcuts
        document.addEventListener( 'keydown', ( e ) =>
        {
            if ( e.ctrlKey || e.metaKey )
            {
                switch ( e.key )
                {
                    case '[':
                        // Toggle left sidebar
                        this.toggleFirstSidebar( 'left' );
                        e.preventDefault();
                        break;
                    case ']':
                        // Toggle right sidebar
                        this.toggleFirstSidebar( 'right' );
                        e.preventDefault();
                        break;
                }
            }
        } );
    }

    toggleFirstSidebar ( position )
    {
        const sidebarSelector = `.sidebar-${ position }`;
        const sidebar = document.querySelector( sidebarSelector );
        if ( sidebar )
        {
            // Find corresponding sidebar object and toggle
            // This would need to be implemented based on how sidebars are tracked
            this.emit( 'sidebarKeyboardToggle', { position } );
        }
    }

    // =============================================================================
    // Utility Methods
    // =============================================================================

    initializeLayouts ()
    {
        // Auto-initialize layouts based on data attributes
        document.querySelectorAll( '[data-layout]' ).forEach( container =>
        {
            const templateName = container.dataset.layout;
            const options = container.dataset.layoutOptions ?
                JSON.parse( container.dataset.layoutOptions ) : {};

            try
            {
                this.createLayout( container.id, templateName, options );
            } catch ( error )
            {
                console.warn( `Failed to initialize layout for ${ container.id }:`, error );
            }
        } );
    }

    getLayout ( containerId )
    {
        return this.layouts.get( containerId );
    }

    getAllLayouts ()
    {
        return Array.from( this.layouts.values() );
    }

    getLayoutTemplate ( templateName )
    {
        return this.layoutTemplates.get( templateName );
    }

    getAllLayoutTemplates ()
    {
        return Array.from( this.layoutTemplates.values() );
    }

    destroy ()
    {
        // Clean up all layouts
        this.layouts.forEach( layout =>
        {
            this.clearLayout( layout );
        } );
        this.layouts.clear();

        // Clean up resize observer
        if ( this.resizeObserver )
        {
            this.resizeObserver.disconnect();
        }

        // Clean up event listeners
        this.eventListeners.clear();

        console.log( '🗑️ Advanced Layout System destroyed' );
    }
}

// =============================================================================
// Alpine.js Integration
// =============================================================================

document.addEventListener( 'alpine:init', () =>
{
    Alpine.data( 'advancedLayoutDemo', () => ( {
        layoutSystem: null,
        currentLayout: 'app',
        availableLayouts: [],
        isCustomizing: false,
        customizations: {
            columns: '',
            rows: '',
            gap: ''
        },
        sidebarCollapsed: false,
        activeNavItem: 'dashboard',

        init ()
        {
            this.layoutSystem = new AdvancedLayoutSystem();
            this.availableLayouts = this.layoutSystem.getAllLayoutTemplates();

            // Create initial layout
            this.createLayout();

            // Setup event listeners
            this.setupEventListeners();
        },

        createLayout ()
        {
            try
            {
                const layout = this.layoutSystem.createLayout( 'demo-layout-container', this.currentLayout );
                console.log( '✅ Layout created:', layout );
            } catch ( error )
            {
                console.error( '❌ Failed to create layout:', error );
            }
        },

        switchLayout ( templateName )
        {
            try
            {
                this.layoutSystem.switchLayout( 'demo-layout-container', templateName );
                this.currentLayout = templateName;
                console.log( '🔄 Layout switched to:', templateName );
            } catch ( error )
            {
                console.error( '❌ Failed to switch layout:', error );
            }
        },

        applyCustomizations ()
        {
            try
            {
                const customizations = {};

                if ( this.customizations.columns )
                {
                    customizations.columns = this.customizations.columns;
                }
                if ( this.customizations.rows )
                {
                    customizations.rows = this.customizations.rows;
                }
                if ( this.customizations.gap )
                {
                    customizations.gap = this.customizations.gap;
                }

                this.layoutSystem.customizeLayout( 'demo-layout-container', customizations );
                this.isCustomizing = false;
                console.log( '🎨 Customizations applied:', customizations );
            } catch ( error )
            {
                console.error( '❌ Failed to apply customizations:', error );
                alert( error.message );
            }
        },

        resetCustomizations ()
        {
            try
            {
                this.layoutSystem.resetLayoutCustomizations( 'demo-layout-container' );
                this.customizations = { columns: '', rows: '', gap: '' };
                console.log( '🔄 Customizations reset' );
            } catch ( error )
            {
                console.error( '❌ Failed to reset customizations:', error );
            }
        },

        toggleSidebar ()
        {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            // This would trigger actual sidebar toggle
            console.log( '📱 Sidebar toggled:', this.sidebarCollapsed );
        },

        setActiveNavItem ( itemId )
        {
            this.activeNavItem = itemId;
            console.log( '🧭 Navigation item activated:', itemId );
        },

        setupEventListeners ()
        {
            this.layoutSystem.on( 'layoutApplied', ( data ) =>
            {
                console.log( '📐 Layout applied:', data );
            } );

            this.layoutSystem.on( 'layoutSwitched', ( data ) =>
            {
                console.log( '🔄 Layout switched:', data );
            } );

            this.layoutSystem.on( 'breakpointChanged', ( data ) =>
            {
                console.log( '📱 Breakpoint changed:', data );
            } );

            this.layoutSystem.on( 'layoutCustomized', ( data ) =>
            {
                console.log( '🎨 Layout customized:', data );
            } );
        }
    } ) );
} );

// Global instance for direct access
window.AdvancedLayoutSystem = AdvancedLayoutSystem;

// CSS classes for styling (to be included in main CSS)
const advancedLayoutCSS = `
.advanced-layout {
    min-height: 100vh;
    display: grid;
}

.grid-area {
    overflow: hidden;
}

.sticky-element {
    position: sticky;
    top: 0;
    z-index: 100;
}

.advanced-sidebar {
    background: var(--sidebar-bg, #f8f9fa);
    border-right: 1px solid var(--border-color, #e0e0e0);
    transition: width 0.3s ease-in-out;
    overflow-x: hidden;
}

.sidebar-right {
    border-right: none;
    border-left: 1px solid var(--border-color, #e0e0e0);
}

.sidebar-collapsed {
    width: 0 !important;
}

.sidebar-toggle {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: var(--primary-color, #007bff);
    color: white;
    border: none;
    border-radius: 4px;
    padding: 0.5rem;
    cursor: pointer;
    z-index: 101;
}

.advanced-navigation {
    background: var(--nav-bg, #ffffff);
    border-bottom: 1px solid var(--border-color, #e0e0e0);
}

.nav-sticky {
    position: sticky;
    top: 0;
    z-index: 200;
}

.nav-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
}

.nav-horizontal .nav-list {
    flex-direction: row;
}

.nav-vertical .nav-list {
    flex-direction: column;
}

.nav-link {
    display: block;
    padding: 1rem;
    text-decoration: none;
    color: var(--text-color, #333);
    transition: background-color 0.2s;
}

.nav-link:hover {
    background-color: var(--hover-bg, #f0f0f0);
}

.nav-link.active {
    background-color: var(--active-bg, #007bff);
    color: var(--active-text, #ffffff);
}

@media (max-width: 768px) {
    .layout-md .advanced-sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        z-index: 300;
    }
    
    .layout-md .sidebar-overlay {
        background: rgba(0, 0, 0, 0.5);
    }
}
`;

// Inject CSS styles
if ( !document.getElementById( 'advanced-layout-styles' ) )
{
    const style = document.createElement( 'style' );
    style.id = 'advanced-layout-styles';
    style.textContent = advancedLayoutCSS;
    document.head.appendChild( style );
}

console.log( '🎨 Advanced Layout System loaded successfully' );
