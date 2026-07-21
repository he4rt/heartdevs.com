import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament/admin/theme.css',
                'resources/css/filament/app/theme.css',
                'app-modules/he4rt/resources/css/theme.css',
                'app-modules/he4rt/resources/css/themes/3pontos/theme.css',
                'app-modules/docs/resources/css/theme.css',
                'app-modules/portal/resources/css/retrospective.css',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        cors: true,
    },
    build: {
        minify: 'terser',
        cssMinify: true,
        chunkSizeWarningLimit: 1600,
        rolldownOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        // Creates chunks based on the package name
                        return id.toString().split('node_modules/')[1].split('/')[0].toString();
                    }
                },
            },
        },
    },
});
