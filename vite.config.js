import { defineConfig } from 'vite';
import fullReload from 'vite-plugin-full-reload';
import tailwindcss from '@tailwindcss/vite';
import { readdirSync } from 'fs';

const componentAssets = readdirSync('inc/Blocks', { recursive: true })
    .filter(f => f.endsWith('style.css') || f.endsWith('script.js'))
    .map(f => `inc/Blocks/${f}`);

export default defineConfig({
    plugins: [
        tailwindcss(),
        fullReload(['**/*.php', 'resources/views/**/*.twig']),
    ],
    build: {
        outDir: 'public/build',
        emptyDirOnBuild: true,
        manifest: 'manifest.json', // Output manifest to build root, not .vite/
        rollupOptions: {
            input: [
                'resources/css/app.css',
                'resources/scss/app.scss',
                'resources/js/app.js',
                ...componentAssets,
            ],
        },
    },
    server: {
        host: 'localhost',
        port: 5173,
        strictPort: true,
        cors: true,
        watch: {
            usePolling: true, // Better for some environments like Local by Flywheel
        },
    },
});