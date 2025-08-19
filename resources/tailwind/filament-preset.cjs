// Local Tailwind preset to replace removed Filament v4 preset.
// Keeps lightweight theme extensions and optionally enables @tailwindcss/forms if available.

const plugins = [];

try
{
    // Optional – only if installed. Safe fallback if missing.
    const forms = require( '@tailwindcss/forms' );
    plugins.push( forms );
} catch ( e )
{
    // no-op if plugin isn't installed
}

module.exports = {
    theme: {
        extend: {
            // Keep neutral radius and transitions similar to Filament defaults
            borderRadius: {
                lg: '0.5rem',
                xl: '0.75rem',
                '2xl': '1rem',
            },
            transitionDuration: {
                250: '250ms',
            },
            // Minimal color hooks that play well with CSS variables Filament uses
            colors: {
                primary: {
                    DEFAULT: 'rgb(var(--fi-color-primary-600, 217 119 6) / <alpha-value>)',
                    50: 'rgb(var(--fi-color-primary-50, 255 251 235) / <alpha-value>)',
                    100: 'rgb(var(--fi-color-primary-100, 254 243 199) / <alpha-value>)',
                    200: 'rgb(var(--fi-color-primary-200, 253 230 138) / <alpha-value>)',
                    300: 'rgb(var(--fi-color-primary-300, 252 211 77) / <alpha-value>)',
                    400: 'rgb(var(--fi-color-primary-400, 251 191 36) / <alpha-value>)',
                    500: 'rgb(var(--fi-color-primary-500, 245 158 11) / <alpha-value>)',
                    600: 'rgb(var(--fi-color-primary-600, 217 119 6) / <alpha-value>)',
                    700: 'rgb(var(--fi-color-primary-700, 180 83 9) / <alpha-value>)',
                    800: 'rgb(var(--fi-color-primary-800, 146 64 14) / <alpha-value>)',
                    900: 'rgb(var(--fi-color-primary-900, 120 53 15) / <alpha-value>)',
                },
            },
            keyframes: {
                'fade-in': {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                'fade-out': {
                    '0%': { opacity: '1' },
                    '100%': { opacity: '0' },
                },
            },
            animation: {
                'fade-in': 'fade-in 150ms ease-out',
                'fade-out': 'fade-out 150ms ease-in',
            },
        },
    },
    plugins,
};
