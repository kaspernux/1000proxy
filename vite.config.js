import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig( {

    plugins: [
        tailwindcss(),
        laravel( {
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/filament-chart-dataset-persistence.js',
                // Admin panel runtime assets (removed custom admin theme JS)
            ],
            refresh: [
                'app/Filament/**',
                'app/Forms/Components/**',
                'app/Livewire/**',
                'app/Infolists/Components/**',
                'app/Providers/Filament/**',
                'app/Tables/Columns/**',
                'resources/scss/**',  // Watch SCSS files for changes
                // (removed custom admin theme JS and CSS)
            ],
        } ),
    ],
    css: {
        preprocessorOptions: {
            scss: {},
        },
    },
} );
