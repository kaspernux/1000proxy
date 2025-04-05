import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from 'tailwindcss';

export default defineConfig( {
    server: {
        host: '0.0.0.0',  // Permet d'accéder au serveur depuis d'autres machines ou adresses
        port: 5173,
        cors: true,       // Active les en-têtes CORS pour toutes les requêtes
    },
    plugins: [
        laravel( {
            input: [ 'resources/css/app.css', 'resources/js/app.js' ],
            refresh: [
                'app/Filament/**',
                'app/Forms/Components/**',
                'app/Livewire/**',
                'app/Infolists/Components/**',
                'app/Providers/Filament/**',
                'app/Tables/Columns/**',
            ],
        } ),
        tailwindcss(),
    ],
} );
