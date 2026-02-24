import { defineConfig } from 'vite';
import fullReload from 'vite-plugin-full-reload';

export default defineConfig({
    plugins: [
        fullReload(['**/*.php', 'resources/views/**/*.twig']),
    ],
    build: {
        outDir: 'public/build',
        emptyDirOnBuild: true,
        manifest: 'manifest.json', // Output manifest to build root, not .vite/
        rollupOptions: {
            input: [
                'resources/scss/app.scss',
                'resources/js/app.js'
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