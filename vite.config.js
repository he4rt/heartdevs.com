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
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
