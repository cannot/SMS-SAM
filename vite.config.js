import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                
                // เพิ่มไฟล์ CSS/JS เฉพาะ
                // 'resources/css/admin.css',
                // 'resources/css/dashboard.css',
                // 'resources/js/admin.js',
                // 'resources/js/dashboard.js',
                // 'resources/js/notifications.js',
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '~bootstrap': resolve(__dirname, 'node_modules/bootstrap'),
            '~bootstrap-icons': resolve(__dirname, 'node_modules/bootstrap-icons'),
            '~@fortawesome': resolve(__dirname, 'node_modules/@fortawesome'),
            '~datatables.net': resolve(__dirname, 'node_modules/datatables.net'),
            '~select2': resolve(__dirname, 'node_modules/select2'),
            '~sweetalert2': resolve(__dirname, 'node_modules/sweetalert2'),
        }
    },
    css: {
        preprocessorOptions: {
            scss: {
                additionalData: `@import "bootstrap/scss/functions"; @import "bootstrap/scss/variables";`
            }
        }
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor': ['bootstrap', 'jquery'],
                    'icons': ['bootstrap-icons', '@fortawesome/fontawesome-free'],
                    'ui': ['select2', 'sweetalert2', 'datatables.net']
                }
            }
        }
    }
});