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
                'resources/css/filament/user/theme.css',
                'app-modules/he4rt/resources/css/theme.css',
                'app-modules/he4rt/resources/css/3pontos/theme.css',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
