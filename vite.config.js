import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '~bootstrap': resolve(__dirname, 'node_modules/bootstrap'),
            '~@fortawesome': resolve(__dirname, 'node_modules/@fortawesome'),
            '~datatables.net': resolve(__dirname, 'node_modules/datatables.net'),
            '~select2': resolve(__dirname, 'node_modules/select2'),
            '~sweetalert2': resolve(__dirname, 'node_modules/sweetalert2'),
        }
    },
    css: {
        devSourcemap: true,
        preprocessorOptions: {
            scss: {
                additionalData: `@import "bootstrap/scss/functions"; @import "bootstrap/scss/variables";`
            }
        }
    },
    build: {
        sourcemap: false,
        outDir: 'public/build',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor': ['bootstrap', 'jquery'],
                    'fontawesome': ['@fortawesome/fontawesome-free'],
                    'ui': ['select2', 'sweetalert2', 'datatables.net']
                }
            }
        },
        commonjsOptions: {
            include: [/bootstrap/, /jquery/, /node_modules/]
        }
    },
    esbuild: {
        sourcemap: false
    },
    optimizeDeps: {
        include: ['bootstrap', 'jquery', 'select2', 'sweetalert2', 'datatables.net'],
        force: true
    },
    server: {
        fs: {
            strict: false
        }
    }
});