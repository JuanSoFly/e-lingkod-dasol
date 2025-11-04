import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/csc-validation.js', 'resources/js/pages/opcr-analytics.js'],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: [
                '**/vendor/**',
                '**/node_modules/**',
                '**/storage/**',
                '**/bootstrap/cache/**',
                '**/public/build/**',
                '**/.git/**',
                '**/documentation/**',
            ]
        }
    }
});
