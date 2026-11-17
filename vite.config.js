import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import { bunny } from 'laravel-vite-plugin/fonts';
import path from 'node:path';

const generateRoutes = process.env.WAYFINDER !== 'skip';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'app-modules/panel-admin/resources/js/app.tsx',
                'app-modules/panel-admin/resources/js/auth.tsx',
                'app-modules/panel-app/resources/js/app.tsx',
            ],
            refresh: true,
            fonts: [
                bunny('Inter', {
                    weights: [400, 500, 600, 700],
                    fallbacks: ['ui-sans-serif', 'system-ui', 'sans-serif'],
                }),
                bunny('Bricolage Grotesque', {
                    weights: [500, 600],
                    fallbacks: ['ui-sans-serif', 'system-ui', 'sans-serif'],
                }),
                bunny('JetBrains Mono', {
                    weights: [400, 500, 600],
                    fallbacks: ['ui-monospace', 'monospace'],
                }),
            ],
        }),
        react(),
        tailwindcss(),
        ...(generateRoutes
            ? [
                  wayfinder({
                      command: 'php artisan wayfinder:generate --path=resources/js/wayfinder --with-form',
                      patterns: [
                          'routes/**/*.php',
                          'app-modules/*/routes/**/*.php',
                          'app-modules/*/src/**/Http/**/*.php',
                      ],
                  }),
              ]
            : []),
    ],
    resolve: {
        alias: {
            '@': path.resolve(import.meta.dirname, 'resources/js'),
            '@admin': path.resolve(import.meta.dirname, 'app-modules/panel-admin/resources/js'),
            '@app': path.resolve(import.meta.dirname, 'app-modules/panel-app/resources/js'),
        },
    },
    server: {
        cors: true,
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/storage/framework/views/**',
                '**/vendor/**',
            ],
        },
    },
    build: {
        minify: 'oxc',
        cssMinify: true,
        chunkSizeWarningLimit: 1600,
        reportCompressedSize: false,
        rolldownOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        return id.toString().split('node_modules/')[1].split('/')[0].toString();
                    }
                },
            },
        },
    },
});
