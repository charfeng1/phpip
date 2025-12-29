import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: ['resources/js/app.js'],
            refresh: true,
        }),
    ],
    build: {
        // Target modern browsers for smaller bundles
        target: 'es2020',
        // Enable minification
        minify: 'esbuild',
        // CSS code splitting
        cssCodeSplit: true,
        // Chunk size warnings at 300KB
        chunkSizeWarningLimit: 300,
        rollupOptions: {
            output: {
                // Manual chunks for better caching
                manualChunks: {
                    // Alpine.js in its own chunk (rarely changes)
                    'alpine': ['alpinejs'],
                },
                // Optimize chunk file names for caching
                chunkFileNames: 'assets/[name]-[hash].js',
                entryFileNames: 'assets/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash].[ext]',
            },
        },
        // Enable source maps for production debugging (optional)
        sourcemap: false,
    },
    // Optimize dependency pre-bundling
    optimizeDeps: {
        include: ['alpinejs'],
    },
});
