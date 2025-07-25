/**
 * Accessible Navigation Component
 * Comprehensive navigation accessibility with ARIA support, keyboard navigation,
 * and screen reader optimization for menus, breadcrumbs, and navigation patterns
 */

window.accessibleNavigation = function ( config = {} )
{
    return {
        // Configuration
        menuType: config.menuType || 'horizontal', // 'horizontal', 'vertical', 'mega'
        hasSubmenu: config.hasSubmenu || false,
        keyboardNavigation: config.keyboardNavigation !== false,
        announceNavigation: config.announceNavigation !== false,

        // State
        isOpen: false,
        currentIndex: 0,
        submenuOpen: {},

        // Menu items
        menuItems: [],

        // Accessibility state
        announcer: null,
        menuId: config.menuId || 'main-navigation',

        init ()
        {
            this.setupAccessibilityFeatures();
            this.setupMenuItems();
            this.setupKeyboardNavigation();
            this.setupAriaAttributes();
            this.setupFocusManagement();
        },

        setupAccessibilityFeatures ()
        {
            // Create announcer for navigation changes
            this.announcer = document.createElement( 'div' );
            this.announcer.setAttribute( 'aria-live', 'polite' );
            this.announcer.setAttribute( 'aria-atomic', 'true' );
            this.announcer.className = 'sr-only';
            document.body.appendChild( this.announcer );
        },

        setupMenuItems ()
        {
            this.$nextTick( () =>
            {
                const menuItems = this.$el.querySelectorAll( '[role="menuitem"], a, button' );
                this.menuItems = Array.from( menuItems );

                // Setup roving tabindex
                this.menuItems.forEach( ( item, index ) =>
                {
                    item.setAttribute( 'tabindex', index === 0 ? '0' : '-1' );
                    item.setAttribute( 'role', 'menuitem' );
                } );
            } );
        },

        setupAriaAttributes ()
        {
            this.$nextTick( () =>
            {
                const nav = this.$el.querySelector( 'nav' );
                const menuContainer = this.$el.querySelector( '[role="menu"], ul, .menu-container' );

                if ( nav )
                {
                    nav.setAttribute( 'aria-label', config.navLabel || 'Main navigation' );
                }

                if ( menuContainer )
                {
                    menuContainer.setAttribute( 'role', 'menubar' );
                    menuContainer.setAttribute( 'aria-orientation', this.menuType === 'vertical' ? 'vertical' : 'horizontal' );

                    // Setup menu items
                    const items = menuContainer.querySelectorAll( 'li, .menu-item' );
                    items.forEach( ( item, index ) =>
                    {
                        const link = item.querySelector( 'a, button' );
                        const submenu = item.querySelector( 'ul, .submenu' );

                        if ( link )
                        {
                            link.setAttribute( 'role', 'menuitem' );
                            link.setAttribute( 'tabindex', index === 0 ? '0' : '-1' );

                            if ( submenu )
                            {
                                const submenuId = `submenu-${ index }`;
                                link.setAttribute( 'aria-haspopup', 'true' );
                                link.setAttribute( 'aria-expanded', 'false' );
                                link.setAttribute( 'aria-controls', submenuId );

                                submenu.setAttribute( 'role', 'menu' );
                                submenu.setAttribute( 'id', submenuId );
                                submenu.setAttribute( 'aria-labelledby', link.id || `menuitem-${ index }` );

                                if ( !link.id )
                                {
                                    link.id = `menuitem-${ index }`;
                                }

                                // Setup submenu items
                                const submenuItems = submenu.querySelectorAll( 'a, button' );
                                submenuItems.forEach( subitem =>
                                {
                                    subitem.setAttribute( 'role', 'menuitem' );
                                    subitem.setAttribute( 'tabindex', '-1' );
                                } );
                            }
                        }
                    } );
                }
            } );
        },

        setupKeyboardNavigation ()
        {
            this.$el.addEventListener( 'keydown', ( e ) =>
            {
                if ( !this.keyboardNavigation ) return;

                const currentItem = document.activeElement;
                const isInSubmenu = currentItem.closest( '[role="menu"]' ) !== this.$el.querySelector( '[role="menubar"]' );

                switch ( e.key )
                {
                    case 'ArrowRight':
                        e.preventDefault();
                        if ( this.menuType === 'horizontal' && !isInSubmenu )
                        {
                            this.navigateHorizontal( 1 );
                        } else if ( isInSubmenu )
                        {
                            this.closeSubmenu();
                            this.navigateHorizontal( 1 );
                        } else if ( this.hasSubmenu && this.hasSubmenuOpen( currentItem ) )
                        {
                            this.openSubmenu( currentItem );
                        }
                        break;

                    case 'ArrowLeft':
                        e.preventDefault();
                        if ( this.menuType === 'horizontal' && !isInSubmenu )
                        {
                            this.navigateHorizontal( -1 );
                        } else if ( isInSubmenu )
                        {
                            this.closeSubmenu();
                            this.navigateHorizontal( -1 );
                        }
                        break;

                    case 'ArrowDown':
                        e.preventDefault();
                        if ( this.menuType === 'vertical' && !isInSubmenu )
                        {
                            this.navigateVertical( 1 );
                        } else if ( this.hasSubmenu && this.hasSubmenuOpen( currentItem ) )
                        {
                            this.openSubmenu( currentItem );
                        } else if ( isInSubmenu )
                        {
                            this.navigateSubmenu( 1 );
                        }
                        break;

                    case 'ArrowUp':
                        e.preventDefault();
                        if ( this.menuType === 'vertical' && !isInSubmenu )
                        {
                            this.navigateVertical( -1 );
                        } else if ( isInSubmenu )
                        {
                            this.navigateSubmenu( -1 );
                        }
                        break;

                    case 'Enter':
                    case ' ':
                        if ( this.hasSubmenuOpen( currentItem ) )
                        {
                            e.preventDefault();
                            this.toggleSubmenu( currentItem );
                        }
                        break;

                    case 'Escape':
                        e.preventDefault();
                        if ( isInSubmenu )
                        {
                            this.closeSubmenu();
                            this.focusParentMenuItem();
                        } else
                        {
                            this.closeAllSubmenus();
                        }
                        break;

                    case 'Home':
                        e.preventDefault();
                        this.focusFirstItem();
                        break;

                    case 'End':
                        e.preventDefault();
                        this.focusLastItem();
                        break;
                }
            } );
        },

        navigateHorizontal ( direction )
        {
            const items = this.getMainMenuItems();
            const currentIndex = this.getCurrentItemIndex( items );
            const newIndex = this.getNextIndex( currentIndex, direction, items.length );

            this.focusItem( items[ newIndex ] );
            this.announceNavigation( items[ newIndex ] );
        },

        navigateVertical ( direction )
        {
            const items = this.getMainMenuItems();
            const currentIndex = this.getCurrentItemIndex( items );
            const newIndex = this.getNextIndex( currentIndex, direction, items.length );

            this.focusItem( items[ newIndex ] );
            this.announceNavigation( items[ newIndex ] );
        },

        navigateSubmenu ( direction )
        {
            const submenu = document.activeElement.closest( '[role="menu"]' );
            if ( !submenu ) return;

            const items = submenu.querySelectorAll( '[role="menuitem"]' );
            const currentIndex = Array.from( items ).indexOf( document.activeElement );
            const newIndex = this.getNextIndex( currentIndex, direction, items.length );

            this.focusItem( items[ newIndex ] );
            this.announceNavigation( items[ newIndex ] );
        },

        getMainMenuItems ()
        {
            return this.$el.querySelectorAll( '[role="menubar"] > li [role="menuitem"], [role="menubar"] > .menu-item [role="menuitem"]' );
        },

        getCurrentItemIndex ( items )
        {
            return Array.from( items ).indexOf( document.activeElement );
        },

        getNextIndex ( currentIndex, direction, length )
        {
            if ( direction > 0 )
            {
                return currentIndex === length - 1 ? 0 : currentIndex + 1;
            } else
            {
                return currentIndex === 0 ? length - 1 : currentIndex - 1;
            }
        },

        focusItem ( item )
        {
            // Remove tabindex from all items
            this.getMainMenuItems().forEach( menuItem =>
            {
                menuItem.setAttribute( 'tabindex', '-1' );
            } );

            // Set focus on new item
            item.setAttribute( 'tabindex', '0' );
            item.focus();
        },

        focusFirstItem ()
        {
            const items = this.getMainMenuItems();
            if ( items.length > 0 )
            {
                this.focusItem( items[ 0 ] );
                this.announceNavigation( items[ 0 ] );
            }
        },

        focusLastItem ()
        {
            const items = this.getMainMenuItems();
            if ( items.length > 0 )
            {
                this.focusItem( items[ items.length - 1 ] );
                this.announceNavigation( items[ items.length - 1 ] );
            }
        },

        hasSubmenuOpen ( item )
        {
            const submenu = item.parentElement.querySelector( '[role="menu"]' );
            return submenu && item.getAttribute( 'aria-expanded' ) === 'true';
        },

        toggleSubmenu ( item )
        {
            const isExpanded = item.getAttribute( 'aria-expanded' ) === 'true';

            if ( isExpanded )
            {
                this.closeSubmenu( item );
            } else
            {
                this.openSubmenu( item );
            }
        },

        openSubmenu ( item )
        {
            const submenu = item.parentElement.querySelector( '[role="menu"]' );
            if ( !submenu ) return;

            // Close other submenus
            this.closeAllSubmenus();

            // Open this submenu
            item.setAttribute( 'aria-expanded', 'true' );
            submenu.style.display = 'block';
            submenu.classList.add( 'open' );

            // Focus first submenu item
            const firstSubmenuItem = submenu.querySelector( '[role="menuitem"]' );
            if ( firstSubmenuItem )
            {
                firstSubmenuItem.setAttribute( 'tabindex', '0' );
                firstSubmenuItem.focus();
            }

            this.announceSubmenuOpen( item );
        },

        closeSubmenu ( item = null )
        {
            if ( item )
            {
                const submenu = item.parentElement.querySelector( '[role="menu"]' );
                if ( submenu )
                {
                    item.setAttribute( 'aria-expanded', 'false' );
                    submenu.style.display = 'none';
                    submenu.classList.remove( 'open' );

                    // Reset submenu item tabindexes
                    submenu.querySelectorAll( '[role="menuitem"]' ).forEach( subitem =>
                    {
                        subitem.setAttribute( 'tabindex', '-1' );
                    } );
                }
            } else
            {
                // Close currently open submenu
                const openSubmenu = this.$el.querySelector( '[aria-expanded="true"]' );
                if ( openSubmenu )
                {
                    this.closeSubmenu( openSubmenu );
                }
            }
        },

        closeAllSubmenus ()
        {
            const openSubmenus = this.$el.querySelectorAll( '[aria-expanded="true"]' );
            openSubmenus.forEach( item =>
            {
                this.closeSubmenu( item );
            } );
        },

        focusParentMenuItem ()
        {
            const submenu = document.activeElement.closest( '[role="menu"]' );
            if ( submenu )
            {
                const parentItem = submenu.parentElement.querySelector( '[aria-controls="' + submenu.id + '"]' );
                if ( parentItem )
                {
                    this.focusItem( parentItem );
                }
            }
        },

        setupFocusManagement ()
        {
            // Handle click events for submenu toggles
            this.$el.addEventListener( 'click', ( e ) =>
            {
                const item = e.target.closest( '[aria-haspopup="true"]' );
                if ( item )
                {
                    e.preventDefault();
                    this.toggleSubmenu( item );
                }
            } );

            // Handle mouse hover for submenus (with delay for accessibility)
            this.$el.addEventListener( 'mouseenter', ( e ) =>
            {
                const item = e.target.closest( '[aria-haspopup="true"]' );
                if ( item && config.openOnHover )
                {
                    clearTimeout( this.hoverTimeout );
                    this.hoverTimeout = setTimeout( () =>
                    {
                        this.openSubmenu( item );
                    }, 300 ); // Delay to prevent accidental opening
                }
            }, true );

            this.$el.addEventListener( 'mouseleave', () =>
            {
                if ( config.closeOnLeave )
                {
                    clearTimeout( this.hoverTimeout );
                    this.hoverTimeout = setTimeout( () =>
                    {
                        this.closeAllSubmenus();
                    }, 300 );
                }
            } );

            // Handle focus loss
            this.$el.addEventListener( 'focusout', ( e ) =>
            {
                // Close submenus when focus leaves the navigation
                setTimeout( () =>
                {
                    const focusedElement = document.activeElement;
                    if ( !this.$el.contains( focusedElement ) )
                    {
                        this.closeAllSubmenus();
                    }
                }, 10 );
            } );
        },

        announceNavigation ( item )
        {
            if ( !this.announceNavigation ) return;

            const text = item.textContent.trim();
            const hasSubmenu = item.getAttribute( 'aria-haspopup' ) === 'true';
            const isExpanded = item.getAttribute( 'aria-expanded' ) === 'true';

            let message = `Navigated to ${ text }`;
            if ( hasSubmenu )
            {
                message += isExpanded ? ', submenu open' : ', has submenu';
            }

            this.announcer.textContent = message;
        },

        announceSubmenuOpen ( item )
        {
            if ( !this.announceNavigation ) return;

            const text = item.textContent.trim();
            this.announcer.textContent = `${ text } submenu opened`;
        },

        // Mobile navigation toggle
        toggleMobileMenu ()
        {
            this.isOpen = !this.isOpen;

            const button = this.$el.querySelector( '[aria-controls="' + this.menuId + '"]' );
            const menu = this.$el.querySelector( '#' + this.menuId );

            if ( button && menu )
            {
                button.setAttribute( 'aria-expanded', this.isOpen.toString() );
                menu.style.display = this.isOpen ? 'block' : 'none';

                if ( this.isOpen )
                {
                    // Focus first menu item when opening
                    this.focusFirstItem();
                    this.announcer.textContent = 'Menu opened';
                } else
                {
                    // Focus menu button when closing
                    button.focus();
                    this.announcer.textContent = 'Menu closed';
                }
            }
        },

        // Cleanup when component is destroyed
        destroy ()
        {
            clearTimeout( this.hoverTimeout );

            if ( this.announcer && this.announcer.parentNode )
            {
                this.announcer.parentNode.removeChild( this.announcer );
            }
        }
    };
};

// Accessible breadcrumb component
window.accessibleBreadcrumb = function ( config = {} )
{
    return {
        items: config.items || [],

        init ()
        {
            this.setupBreadcrumbAccessibility();
        },

        setupBreadcrumbAccessibility ()
        {
            this.$nextTick( () =>
            {
                const nav = this.$el.querySelector( 'nav' );
                const list = this.$el.querySelector( 'ol, ul' );

                if ( nav )
                {
                    nav.setAttribute( 'aria-label', 'Breadcrumb navigation' );
                }

                if ( list )
                {
                    const items = list.querySelectorAll( 'li' );
                    items.forEach( ( item, index ) =>
                    {
                        const link = item.querySelector( 'a' );
                        const isLast = index === items.length - 1;

                        if ( link && !isLast )
                        {
                            link.setAttribute( 'aria-label', `Go to ${ link.textContent.trim() }` );
                        }

                        if ( isLast )
                        {
                            item.setAttribute( 'aria-current', 'page' );
                        }
                    } );
                }
            } );
        }
    };
};

// Navigation templates
window.accessibleNavigationTemplate = `
<nav x-data="accessibleNavigation(navConfig)" x-init="init()" class="accessible-navigation">
    <!-- Mobile menu button -->
    <div class="mobile-menu-toggle md:hidden">
        <button
            type="button"
            @click="toggleMobileMenu()"
            :aria-expanded="isOpen"
            :aria-controls="menuId"
            class="p-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
            aria-label="Toggle navigation menu"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path x-show="!isOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                <path x-show="isOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Main navigation menu -->
    <div
        :id="menuId"
        :style="{ display: isOpen || window.innerWidth >= 768 ? 'block' : 'none' }"
        class="navigation-menu"
    >
        <ul role="menubar" :aria-orientation="menuType" class="menu-list">
            <template x-for="(item, index) in menuItems" :key="index">
                <li class="menu-item" :class="{ 'has-submenu': item.submenu }">
                    <a
                        :href="item.url"
                        role="menuitem"
                        :tabindex="index === 0 ? '0' : '-1'"
                        :aria-haspopup="item.submenu ? 'true' : null"
                        :aria-expanded="item.submenu ? 'false' : null"
                        :aria-controls="item.submenu ? 'submenu-' + index : null"
                        class="menu-link"
                        x-text="item.label"
                    ></a>

                    <!-- Submenu -->
                    <ul
                        x-show="item.submenu"
                        :id="'submenu-' + index"
                        role="menu"
                        :aria-labelledby="'menuitem-' + index"
                        class="submenu"
                        style="display: none;"
                    >
                        <template x-for="subitem in item.submenu" :key="subitem.id">
                            <li>
                                <a
                                    :href="subitem.url"
                                    role="menuitem"
                                    tabindex="-1"
                                    class="submenu-link"
                                    x-text="subitem.label"
                                ></a>
                            </li>
                        </template>
                    </ul>
                </li>
            </template>
        </ul>
    </div>
</nav>
`;

window.accessibleBreadcrumbTemplate = `
<nav x-data="accessibleBreadcrumb(breadcrumbConfig)" x-init="init()" aria-label="Breadcrumb navigation" class="accessible-breadcrumb">
    <ol class="breadcrumb-list">
        <template x-for="(item, index) in items" :key="index">
            <li
                class="breadcrumb-item"
                :aria-current="index === items.length - 1 ? 'page' : null"
            >
                <template x-if="index < items.length - 1">
                    <a
                        :href="item.url"
                        :aria-label="'Go to ' + item.label"
                        class="breadcrumb-link"
                        x-text="item.label"
                    ></a>
                </template>

                <template x-if="index === items.length - 1">
                    <span class="breadcrumb-current" x-text="item.label"></span>
                </template>

                <template x-if="index < items.length - 1">
                    <span class="breadcrumb-separator" aria-hidden="true">/</span>
                </template>
            </li>
        </template>
    </ol>
</nav>
`;

// CSS for accessible navigation
const accessibleNavigationCSS = `
.accessible-navigation {
    --nav-focus-color: #3B82F6;
    --nav-hover-color: #EBF4FF;
}

.accessible-navigation [role="menuitem"]:focus {
    outline: 2px solid var(--nav-focus-color);
    outline-offset: 2px;
    background-color: var(--nav-hover-color);
}

.accessible-navigation .submenu {
    position: absolute;
    z-index: 1000;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.accessible-navigation .submenu.open {
    display: block !important;
}

.accessible-breadcrumb .breadcrumb-link:focus {
    outline: 2px solid var(--nav-focus-color);
    outline-offset: 2px;
}

.accessible-breadcrumb [aria-current="page"] {
    font-weight: 600;
    color: #374151;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .accessible-navigation,
    .accessible-breadcrumb {
        --nav-focus-color: #0000FF;
        --nav-hover-color: #FFFF00;
    }

    .accessible-navigation [role="menuitem"],
    .accessible-breadcrumb .breadcrumb-link {
        border: 1px solid transparent;
    }

    .accessible-navigation [role="menuitem"]:focus,
    .accessible-breadcrumb .breadcrumb-link:focus {
        border-color: var(--nav-focus-color);
        background-color: var(--nav-hover-color);
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .accessible-navigation *,
    .accessible-breadcrumb * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* Mobile navigation styles */
@media (max-width: 767px) {
    .accessible-navigation .navigation-menu {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .accessible-navigation .menu-list {
        flex-direction: column;
    }

    .accessible-navigation .submenu {
        position: static;
        box-shadow: none;
        border: none;
        border-top: 1px solid #e5e7eb;
        margin-top: 0.5rem;
        padding-top: 0.5rem;
    }
}
`;

// Inject accessible navigation CSS
if ( !document.getElementById( 'accessibility-navigation-styles' ) )
{
    const style = document.createElement( 'style' );
    style.id = 'accessibility-navigation-styles';
    style.textContent = accessibleNavigationCSS;
    document.head.appendChild( style );
}
