import { defineConfig } from 'vite';
import fullReload from 'vite-plugin-full-reload';
import tailwindcss from '@tailwindcss/vite';
import { readdirSync } from 'fs';

const componentAssets = readdirSync('inc/Blocks', { recursive: true })
    .filter(f => f.endsWith('style.css') || f.endsWith('style.scss') || f.endsWith('script.js'))
    .map(f => `inc/Blocks/${f}`);

export default defineConfig(({ command }) => ({
    // './' makes font/asset URLs relative in the compiled CSS so they
    // resolve correctly from any subdirectory (e.g. WordPress theme paths).
    // Dev mode must stay '/' — Vite's HMR breaks with a relative base when
    // scripts are served cross-origin (localhost:5173 → taw.local).
    base: command === 'build' ? './' : '/',
    plugins: [
        tailwindcss(),
        fullReload(['**/*.php', 'resources/views/**/*.twig']),
    ],
    build: {
        outDir: 'public/build',
        emptyDirOnBuild: true,
        manifest: 'manifest.json',
        rollupOptions: {
            input: [
                'resources/scss/critical.scss',
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
        // Tell Vite to embed full absolute URLs for assets in injected CSS.
        // Without this, Vite writes `/resources/fonts/...` (absolute path)
        // which the browser resolves against the page origin (taw.local),
        // not the Vite server (localhost:5173) — causing font 404s.
        origin: 'http://localhost:5173',
        watch: {
            usePolling: true,
        },
    },
}));