import preset from './vendor/filament/support/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        'node_modules/preline/dist/*.js',

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
                'light-dark': '#2D2D2D'
            }
        },
    },
    plugins: [
      require('preline/plugin'),
  ],
}
