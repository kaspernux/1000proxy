/**
 * Color Theme Management JavaScript
 * Handles dynamic color theme switching and accessibility features
 */

class ColorThemeManager
{
    constructor ()
    {
        this.currentTheme = 'light';
        this.userPreferences = this.loadPreferences();
        this.init();
    }

    init ()
    {
        this.detectSystemPreference();
        this.applyStoredTheme();
        this.setupEventListeners();
        this.setupAccessibilityFeatures();
    }

    detectSystemPreference ()
    {
        if ( window.matchMedia && window.matchMedia( '(prefers-color-scheme: dark)' ).matches )
        {
            this.currentTheme = 'dark';
        }

        // Detect high contrast preference
        if ( window.matchMedia && window.matchMedia( '(prefers-contrast: high)' ).matches )
        {
            this.currentTheme = 'high-contrast';
        }

        // Detect reduced motion preference
        if ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches )
        {
            document.documentElement.classList.add( 'reduce-motion' );
        }
    }

    applyTheme ( theme )
    {
        // Remove existing theme classes
        document.documentElement.removeAttribute( 'data-theme' );
        document.documentElement.classList.remove( 'theme-light', 'theme-dark', 'theme-colorblind', 'theme-high-contrast' );

        // Apply new theme
        this.currentTheme = theme;
        document.documentElement.setAttribute( 'data-theme', theme );
        document.documentElement.classList.add( `theme-${ theme }` );

        // Store preference
        this.savePreference( 'theme', theme );

        // Dispatch theme change event
        window.dispatchEvent( new CustomEvent( 'themeChanged', {
            detail: { theme: theme }
        } ) );
    }

    applyStoredTheme ()
    {
        const storedTheme = this.userPreferences.theme || this.currentTheme;
        this.applyTheme( storedTheme );
    }

    toggleTheme ()
    {
        const themes = [ 'light', 'dark' ];
        const currentIndex = themes.indexOf( this.currentTheme );
        const nextIndex = ( currentIndex + 1 ) % themes.length;
        this.applyTheme( themes[ nextIndex ] );
    }

    applyCountryTheme ( countryCode )
    {
        document.documentElement.classList.remove(
            'country-us', 'country-uk', 'country-de', 'country-jp', 'country-sg'
        );

        if ( countryCode )
        {
            document.documentElement.classList.add( `country-${ countryCode.toLowerCase() }` );
            this.savePreference( 'countryTheme', countryCode );
        }
    }

    applyBrandTheme ( brandType )
    {
        document.documentElement.classList.remove(
            'brand-premium', 'brand-gaming', 'brand-streaming', 'brand-business'
        );

        if ( brandType )
        {
            document.documentElement.classList.add( `brand-${ brandType.toLowerCase() }` );
            this.savePreference( 'brandTheme', brandType );
        }
    }

    setupEventListeners ()
    {
        // Listen for system theme changes
        if ( window.matchMedia )
        {
            window.matchMedia( '(prefers-color-scheme: dark)' ).addEventListener( 'change', ( e ) =>
            {
                if ( !this.userPreferences.theme )
                {
                    this.applyTheme( e.matches ? 'dark' : 'light' );
                }
            } );

            window.matchMedia( '(prefers-contrast: high)' ).addEventListener( 'change', ( e ) =>
            {
                if ( e.matches )
                {
                    this.applyTheme( 'high-contrast' );
                }
            } );
        }

        // Setup theme toggle buttons
        document.addEventListener( 'click', ( e ) =>
        {
            if ( e.target.matches( '[data-theme-toggle]' ) )
            {
                this.toggleTheme();
            }

            if ( e.target.matches( '[data-theme-select]' ) )
            {
                const theme = e.target.dataset.themeSelect;
                this.applyTheme( theme );
            }

            if ( e.target.matches( '[data-country-theme]' ) )
            {
                const country = e.target.dataset.countryTheme;
                this.applyCountryTheme( country );
            }

            if ( e.target.matches( '[data-brand-theme]' ) )
            {
                const brand = e.target.dataset.brandTheme;
                this.applyBrandTheme( brand );
            }
        } );
    }

    setupAccessibilityFeatures ()
    {
        // High contrast mode toggle
        this.setupHighContrastMode();

        // Color blind friendly mode
        this.setupColorBlindMode();

        // Focus management
        this.setupFocusManagement();
    }

    setupHighContrastMode ()
    {
        const highContrastToggle = document.querySelector( '[data-high-contrast-toggle]' );
        if ( highContrastToggle )
        {
            highContrastToggle.addEventListener( 'click', () =>
            {
                if ( this.currentTheme === 'high-contrast' )
                {
                    this.applyTheme( 'light' );
                } else
                {
                    this.applyTheme( 'high-contrast' );
                }
            } );
        }
    }

    setupColorBlindMode ()
    {
        const colorBlindToggle = document.querySelector( '[data-colorblind-toggle]' );
        if ( colorBlindToggle )
        {
            colorBlindToggle.addEventListener( 'click', () =>
            {
                if ( this.currentTheme === 'colorblind' )
                {
                    this.applyTheme( 'light' );
                } else
                {
                    this.applyTheme( 'colorblind' );
                }
            } );
        }
    }

    setupFocusManagement ()
    {
        // Ensure focus is visible for keyboard navigation
        document.addEventListener( 'keydown', ( e ) =>
        {
            if ( e.key === 'Tab' )
            {
                document.body.classList.add( 'keyboard-navigation' );
            }
        } );

        document.addEventListener( 'mousedown', () =>
        {
            document.body.classList.remove( 'keyboard-navigation' );
        } );
    }

    getStatusColor ( status )
    {
        const statusColors = {
            'online': 'var(--color-status-online)',
            'offline': 'var(--color-status-offline)',
            'maintenance': 'var(--color-status-maintenance)',
            'partial': 'var(--color-status-partial)',
            'unknown': 'var(--color-status-unknown)'
        };

        return statusColors[ status ] || statusColors.unknown;
    }

    getPerformanceColor ( performance )
    {
        const performanceColors = {
            'excellent': 'var(--color-performance-excellent)',
            'good': 'var(--color-performance-good)',
            'fair': 'var(--color-performance-fair)',
            'poor': 'var(--color-performance-poor)'
        };

        return performanceColors[ performance ] || performanceColors.fair;
    }

    getBrandColors ( brandType )
    {
        const brandColors = {
            'premium': {
                primary: 'var(--color-brand-premium-primary)',
                secondary: 'var(--color-brand-premium-secondary)',
                accent: 'var(--color-brand-premium-accent)',
                bg: 'var(--color-brand-premium-bg)'
            },
            'gaming': {
                primary: 'var(--color-brand-gaming-primary)',
                secondary: 'var(--color-brand-gaming-secondary)',
                accent: 'var(--color-brand-gaming-accent)',
                bg: 'var(--color-brand-gaming-bg)'
            },
            'streaming': {
                primary: 'var(--color-brand-streaming-primary)',
                secondary: 'var(--color-brand-streaming-secondary)',
                accent: 'var(--color-brand-streaming-accent)',
                bg: 'var(--color-brand-streaming-bg)'
            },
            'business': {
                primary: 'var(--color-brand-business-primary)',
                secondary: 'var(--color-brand-business-secondary)',
                accent: 'var(--color-brand-business-accent)',
                bg: 'var(--color-brand-business-bg)'
            }
        };

        return brandColors[ brandType ] || brandColors.business;
    }

    generateColorPalette ( baseColor )
    {
        // Generate a color palette from a base color
        const palette = {};
        const baseHsl = this.hexToHsl( baseColor );

        // Generate 10 shades
        for ( let i = 0; i < 10; i++ )
        {
            const lightness = 95 - ( i * 10 );
            palette[ `${ i * 100 + 50 }` ] = this.hslToHex( baseHsl.h, baseHsl.s, lightness );
        }

        return palette;
    }

    hexToHsl ( hex )
    {
        const r = parseInt( hex.slice( 1, 3 ), 16 ) / 255;
        const g = parseInt( hex.slice( 3, 5 ), 16 ) / 255;
        const b = parseInt( hex.slice( 5, 7 ), 16 ) / 255;

        const max = Math.max( r, g, b );
        const min = Math.min( r, g, b );
        let h, s, l = ( max + min ) / 2;

        if ( max === min )
        {
            h = s = 0;
        } else
        {
            const d = max - min;
            s = l > 0.5 ? d / ( 2 - max - min ) : d / ( max + min );

            switch ( max )
            {
                case r: h = ( g - b ) / d + ( g < b ? 6 : 0 ); break;
                case g: h = ( b - r ) / d + 2; break;
                case b: h = ( r - g ) / d + 4; break;
            }
            h /= 6;
        }

        return {
            h: Math.round( h * 360 ),
            s: Math.round( s * 100 ),
            l: Math.round( l * 100 )
        };
    }

    hslToHex ( h, s, l )
    {
        l /= 100;
        const a = s * Math.min( l, 1 - l ) / 100;
        const f = n =>
        {
            const k = ( n + h / 30 ) % 12;
            const color = l - a * Math.max( Math.min( k - 3, 9 - k, 1 ), -1 );
            return Math.round( 255 * color ).toString( 16 ).padStart( 2, '0' );
        };
        return `#${ f( 0 ) }${ f( 8 ) }${ f( 4 ) }`;
    }

    checkColorContrast ( color1, color2 )
    {
        // Calculate WCAG contrast ratio
        const getLuminance = ( color ) =>
        {
            const rgb = this.hexToRgb( color );
            const [ r, g, b ] = [ rgb.r, rgb.g, rgb.b ].map( c =>
            {
                c = c / 255;
                return c <= 0.03928 ? c / 12.92 : Math.pow( ( c + 0.055 ) / 1.055, 2.4 );
            } );
            return 0.2126 * r + 0.7152 * g + 0.0722 * b;
        };

        const lum1 = getLuminance( color1 );
        const lum2 = getLuminance( color2 );
        const brightest = Math.max( lum1, lum2 );
        const darkest = Math.min( lum1, lum2 );

        return ( brightest + 0.05 ) / ( darkest + 0.05 );
    }

    hexToRgb ( hex )
    {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec( hex );
        return result ? {
            r: parseInt( result[ 1 ], 16 ),
            g: parseInt( result[ 2 ], 16 ),
            b: parseInt( result[ 3 ], 16 )
        } : null;
    }

    isAccessibleContrast ( color1, color2, level = 'AA' )
    {
        const contrast = this.checkColorContrast( color1, color2 );
        const thresholds = {
            'AA': 4.5,
            'AAA': 7,
            'AA_LARGE': 3,
            'AAA_LARGE': 4.5
        };

        return contrast >= thresholds[ level ];
    }

    savePreference ( key, value )
    {
        this.userPreferences[ key ] = value;
        localStorage.setItem( 'colorThemePreferences', JSON.stringify( this.userPreferences ) );
    }

    loadPreferences ()
    {
        try
        {
            const stored = localStorage.getItem( 'colorThemePreferences' );
            return stored ? JSON.parse( stored ) : {};
        } catch ( error )
        {
            console.warn( 'Failed to load color theme preferences:', error );
            return {};
        }
    }

    exportTheme ()
    {
        return {
            theme: this.currentTheme,
            preferences: this.userPreferences,
            customColors: this.getCustomColors()
        };
    }

    importTheme ( themeData )
    {
        if ( themeData.theme )
        {
            this.applyTheme( themeData.theme );
        }

        if ( themeData.preferences )
        {
            this.userPreferences = { ...this.userPreferences, ...themeData.preferences };
            this.savePreference( 'imported', true );
        }

        if ( themeData.customColors )
        {
            this.applyCustomColors( themeData.customColors );
        }
    }

    getCustomColors ()
    {
        const computedStyle = getComputedStyle( document.documentElement );
        const customColors = {};

        // Extract all CSS custom properties (variables)
        for ( let i = 0; i < document.styleSheets.length; i++ )
        {
            try
            {
                const rules = document.styleSheets[ i ].cssRules || document.styleSheets[ i ].rules;
                for ( let j = 0; j < rules.length; j++ )
                {
                    if ( rules[ j ].style )
                    {
                        for ( let k = 0; k < rules[ j ].style.length; k++ )
                        {
                            const prop = rules[ j ].style[ k ];
                            if ( prop.startsWith( '--color-' ) )
                            {
                                customColors[ prop ] = computedStyle.getPropertyValue( prop ).trim();
                            }
                        }
                    }
                }
            } catch ( e )
            {
                // Skip stylesheets that can't be accessed due to CORS
            }
        }

        return customColors;
    }

    applyCustomColors ( colors )
    {
        const root = document.documentElement;
        Object.entries( colors ).forEach( ( [ property, value ] ) =>
        {
            if ( property.startsWith( '--color-' ) )
            {
                root.style.setProperty( property, value );
            }
        } );
    }

    resetToDefaults ()
    {
        // Remove all custom CSS properties
        const root = document.documentElement;
        const customProps = this.getCustomColors();
        Object.keys( customProps ).forEach( prop =>
        {
            root.style.removeProperty( prop );
        } );

        // Reset theme
        this.applyTheme( 'light' );

        // Clear preferences
        localStorage.removeItem( 'colorThemePreferences' );
        this.userPreferences = {};
    }
}

// Alpine.js component for color theme management
window.colorThemeManager = () => ( {
    themeManager: null,
    currentTheme: 'light',
    availableThemes: [ 'light', 'dark', 'colorblind', 'high-contrast' ],
    countryThemes: [ 'us', 'uk', 'de', 'jp', 'sg' ],
    brandThemes: [ 'premium', 'gaming', 'streaming', 'business' ],

    init ()
    {
        this.themeManager = new ColorThemeManager();
        this.currentTheme = this.themeManager.currentTheme;

        // Listen for theme changes
        window.addEventListener( 'themeChanged', ( e ) =>
        {
            this.currentTheme = e.detail.theme;
        } );
    },

    switchTheme ( theme )
    {
        this.themeManager.applyTheme( theme );
    },

    toggleTheme ()
    {
        this.themeManager.toggleTheme();
    },

    applyCountryTheme ( country )
    {
        this.themeManager.applyCountryTheme( country );
    },

    applyBrandTheme ( brand )
    {
        this.themeManager.applyBrandTheme( brand );
    },

    getStatusColor ( status )
    {
        return this.themeManager.getStatusColor( status );
    },

    getPerformanceColor ( performance )
    {
        return this.themeManager.getPerformanceColor( performance );
    },

    isCurrentTheme ( theme )
    {
        return this.currentTheme === theme;
    },

    exportSettings ()
    {
        const themeData = this.themeManager.exportTheme();
        const blob = new Blob( [ JSON.stringify( themeData, null, 2 ) ], { type: 'application/json' } );
        const url = URL.createObjectURL( blob );
        const a = document.createElement( 'a' );
        a.href = url;
        a.download = 'theme-settings.json';
        a.click();
        URL.revokeObjectURL( url );
    },

    importSettings ( event )
    {
        const file = event.target.files[ 0 ];
        if ( file )
        {
            const reader = new FileReader();
            reader.onload = ( e ) =>
            {
                try
                {
                    const themeData = JSON.parse( e.target.result );
                    this.themeManager.importTheme( themeData );
                } catch ( error )
                {
                    console.error( 'Failed to import theme settings:', error );
                }
            };
            reader.readAsText( file );
        }
    },

    resetToDefaults ()
    {
        this.themeManager.resetToDefaults();
        this.currentTheme = 'light';
    }
} );

// Initialize global theme manager
document.addEventListener( 'DOMContentLoaded', () =>
{
    window.globalColorThemeManager = new ColorThemeManager();
} );

console.log( '✅ Advanced Color System loaded' );
