// Theme Switcher Component with System Detection and Smooth Transitions
function themeSwitcher ()
{
    return {
        // Current theme state
        currentTheme: 'system', // 'light', 'dark', 'system'
        actualTheme: 'light', // The actual theme being used
        systemTheme: 'light', // System preference
        isTransitioning: false,

        // Theme configuration
        themes: {
            light: {
                name: 'Light',
                icon: '☀️',
                description: 'Light mode for better visibility in bright environments'
            },
            dark: {
                name: 'Dark',
                icon: '🌙',
                description: 'Dark mode for reduced eye strain in low light'
            },
            system: {
                name: 'System',
                icon: '💻',
                description: 'Follow your system preference'
            }
        },

        // UI state
        showOptions: false,
        animationDuration: 300,

        // Initialize theme switcher
        init ()
        {
            this.$nextTick( () =>
            {
                this.detectSystemTheme();
                this.loadSavedTheme();
                this.applyTheme();
                this.setupSystemListener();
                this.setupAnimationClass();
            } );
        },

        // Detect system theme preference
        detectSystemTheme ()
        {
            if ( window.matchMedia && window.matchMedia( '(prefers-color-scheme: dark)' ).matches )
            {
                this.systemTheme = 'dark';
            } else
            {
                this.systemTheme = 'light';
            }
        },

        // Load saved theme from localStorage
        loadSavedTheme ()
        {
            const savedTheme = localStorage.getItem( 'proxy-theme' );
            if ( savedTheme && [ 'light', 'dark', 'system' ].includes( savedTheme ) )
            {
                this.currentTheme = savedTheme;
            }
        },

        // Save theme to localStorage
        saveTheme ()
        {
            localStorage.setItem( 'proxy-theme', this.currentTheme );
        },

        // Apply theme to document
        applyTheme ()
        {
            let themeToApply = this.currentTheme;

            // If system theme is selected, use the detected system preference
            if ( this.currentTheme === 'system' )
            {
                themeToApply = this.systemTheme;
            }

            // Update actual theme
            this.actualTheme = themeToApply;

            // Apply to document with smooth transition
            this.isTransitioning = true;

            // Add transition class
            document.documentElement.classList.add( 'theme-transitioning' );

            // Apply theme
            document.documentElement.setAttribute( 'data-theme', themeToApply );
            document.documentElement.className = document.documentElement.className
                .replace( /theme-\w+/g, '' ) + ` theme-${ themeToApply }`;

            // Update meta theme-color for mobile browsers
            this.updateMetaThemeColor( themeToApply );

            // Remove transition class after animation
            setTimeout( () =>
            {
                document.documentElement.classList.remove( 'theme-transitioning' );
                this.isTransitioning = false;
            }, this.animationDuration );
        },

        // Update meta theme-color for mobile browsers
        updateMetaThemeColor ( theme )
        {
            const metaThemeColor = document.querySelector( 'meta[name="theme-color"]' );
            if ( metaThemeColor )
            {
                const colors = {
                    light: '#ffffff',
                    dark: '#111827'
                };
                metaThemeColor.content = colors[ theme ] || colors.light;
            }
        },

        // Setup system theme change listener
        setupSystemListener ()
        {
            if ( window.matchMedia )
            {
                const mediaQuery = window.matchMedia( '(prefers-color-scheme: dark)' );

                mediaQuery.addEventListener( 'change', ( e ) =>
                {
                    this.systemTheme = e.matches ? 'dark' : 'light';

                    // If currently using system theme, apply the new system preference
                    if ( this.currentTheme === 'system' )
                    {
                        this.applyTheme();
                    }
                } );
            }
        },

        // Setup smooth transition animations
        setupAnimationClass ()
        {
            const style = document.createElement( 'style' );
            style.textContent = `
                .theme-transitioning,
                .theme-transitioning *,
                .theme-transitioning *::before,
                .theme-transitioning *::after {
                    transition: background-color ${ this.animationDuration }ms ease-in-out,
                               border-color ${ this.animationDuration }ms ease-in-out,
                               color ${ this.animationDuration }ms ease-in-out,
                               fill ${ this.animationDuration }ms ease-in-out,
                               stroke ${ this.animationDuration }ms ease-in-out,
                               opacity ${ this.animationDuration }ms ease-in-out,
                               box-shadow ${ this.animationDuration }ms ease-in-out,
                               transform ${ this.animationDuration }ms ease-in-out !important;
                }
            `;
            document.head.appendChild( style );
        },

        // Switch to specific theme
        switchTheme ( theme )
        {
            if ( ![ 'light', 'dark', 'system' ].includes( theme ) || this.isTransitioning )
            {
                return;
            }

            this.currentTheme = theme;
            this.saveTheme();
            this.applyTheme();
            this.showOptions = false;

            // Dispatch custom event for other components
            this.dispatchThemeChange();

            // Provide user feedback
            this.showThemeNotification( theme );
        },

        // Set specific theme
        setTheme ( theme )
        {
            if ( [ 'light', 'dark', 'system' ].includes( theme ) )
            {
                this.switchTheme( theme );
            }
        },

        // Toggle between light and dark (ignoring system)
        toggleTheme ()
        {
            const nextTheme = this.actualTheme === 'light' ? 'dark' : 'light';
            this.switchTheme( nextTheme );
        },

        // Dispatch theme change event
        dispatchThemeChange ()
        {
            const event = new CustomEvent( 'themeChanged', {
                detail: {
                    theme: this.currentTheme,
                    actualTheme: this.actualTheme
                }
            } );
            window.dispatchEvent( event );
        },

        // Show theme change notification
        showThemeNotification ( theme )
        {
            const themeName = this.themes[ theme ].name;
            const message = theme === 'system'
                ? `Theme set to follow system preference (${ this.systemTheme })`
                : `Switched to ${ themeName.toLowerCase() } theme`;

            if ( window.showNotification )
            {
                window.showNotification( 'success', message );
            }
        },

        // Toggle theme options dropdown
        toggleOptions ()
        {
            this.showOptions = !this.showOptions;
        },

        // Close options when clicking outside
        closeOptions ()
        {
            this.showOptions = false;
        },

        // Get current theme icon
        getCurrentIcon ()
        {
            return this.themes[ this.currentTheme ].icon;
        },

        // Get current theme name
        getCurrentName ()
        {
            return this.themes[ this.currentTheme ].name;
        },

        // Check if theme is active
        isThemeActive ( theme )
        {
            return this.currentTheme === theme;
        },

        // Get theme description
        getThemeDescription ( theme )
        {
            return this.themes[ theme ].description;
        },

        // Auto-detect best theme based on time of day
        autoDetectTheme ()
        {
            const hour = new Date().getHours();
            const isDaytime = hour >= 6 && hour < 18;
            const suggestedTheme = isDaytime ? 'light' : 'dark';

            this.switchTheme( suggestedTheme );
        },

        // Get theme stats for analytics
        getThemeStats ()
        {
            return {
                currentTheme: this.currentTheme,
                actualTheme: this.actualTheme,
                systemTheme: this.systemTheme,
                browserSupport: {
                    matchMedia: !!window.matchMedia,
                    prefersColorScheme: window.matchMedia ?
                        window.matchMedia( '(prefers-color-scheme)' ).matches : false
                }
            };
        },

        // Import/Export theme preferences
        exportThemeConfig ()
        {
            return {
                theme: this.currentTheme,
                timestamp: new Date().toISOString(),
                version: '1.0'
            };
        },

        importThemeConfig ( config )
        {
            if ( config && config.theme && [ 'light', 'dark', 'system' ].includes( config.theme ) )
            {
                this.switchTheme( config.theme );
                return true;
            }
            return false;
        },

        // Keyboard shortcuts
        handleKeydown ( event )
        {
            // Ctrl/Cmd + Shift + T to toggle theme
            if ( ( event.ctrlKey || event.metaKey ) && event.shiftKey && event.key === 'T' )
            {
                event.preventDefault();
                this.toggleTheme();
            }

            // Escape to close options
            if ( event.key === 'Escape' && this.showOptions )
            {
                this.closeOptions();
            }
        },

        // Touch/swipe gestures for mobile
        handleSwipe ( direction )
        {
            if ( direction === 'left' || direction === 'right' )
            {
                this.toggleTheme();
            }
        },

        // Component cleanup
        destroy ()
        {
            // Remove event listeners if needed
            document.removeEventListener( 'keydown', this.handleKeydown );
        }
    };
}

// Export for global use
window.themeSwitcher = themeSwitcher;

console.log( '✅ Enhanced Theme Switcher component loaded' );
