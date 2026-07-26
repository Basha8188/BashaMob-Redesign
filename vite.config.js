import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/admin.css',
                'resources/css/auth.css',
                'resources/css/auth-theme.css',
                'resources/css/assistant.css',
                'resources/css/violet.css',
                'resources/js/storefront.js',
                'resources/js/auth.js',
                'resources/js/admin.js',
                'resources/js/theme-manager.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
