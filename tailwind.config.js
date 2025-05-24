import preset from './vendor/filament/support/tailwind.config.preset'

export default {
    presets: [ preset ],
    darkMode: 'class',
    content: [
        './app/Filament/**/*.php',
        './resources/views/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        'node_modules/preline/dist/*.js',
        './resources/views/**/*.blade.php',
        './resources/views/*.blade.php',
        './resources/js/**/*.js',

    ],
    theme: {
        extend: {
            colors: {
                'dark-green': '#004B23',
                'dark-green-600': '#006400',
                'dark-green-500': '#007200',
                'dark-green-400': '#008000',
                'accent-green': '#38B000',
                'light-green': '#70E000',
                'light-green-200': '#9EF01A',
                'light-green-100': '#CCFF33',
                'light-dark': '#2D2D2D',
                'yellow-600': '#FFD60A',
                'accent-yellow': '#FFC300',
                'blue-500': '#2196F3',
                'orange-800': '#F9A825',
                'bg-color-dark': '#002311',
                'dark-text': '#FFFFFF',
                'dark-hover': '#FFD60A',
                'dark-gray': '#1A1A1A',
            }
        },
    },
    plugins: [
        require( 'preline/plugin' ),
    ],
}
