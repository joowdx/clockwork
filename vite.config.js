import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import fs from 'fs';

export default defineConfig({
    plugins: [
        laravel([
            'resources/css/print.css',
            'resources/js/inertia.js',
            'resources/js/livewire.js',
        ]),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
            script: {
                defineModel: true,
            }
        }),
    ],
    server: {
        https: {
            key: fs.readFileSync('./docker/private/localhost.key'),
            cert: fs.readFileSync('./docker/private/localhost.crt'),
        },
        host: '0.0.0.0',
        hmr: {
            host: 'localhost',
        }
    },
    resolve: {
        alias: {
            '@': '/resources/js'
        }
    }
});
