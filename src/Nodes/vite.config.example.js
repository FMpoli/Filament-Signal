import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

/**
 * Configuration for building Voodflow Nodes
 * Usage: vite build --config vite.config.js
 */
export default defineConfig({
    plugins: [react()],
    build: {
        // Output directory for the bundle
        outDir: 'dist',
        emptyOutDir: true,
        lib: {
            // Path to your node's main component
            entry: resolve(__dirname, 'components/StackNode.jsx'), // CHANGE THIS to your component path
            // Global variable name for the component (must match manifest 'javascript.component')
            name: 'StackNode', // CHANGE THIS to match your component class name
            fileName: () => 'stack-node.js', // CHANGE THIS to your desired bundle filename
            formats: ['umd'], // UMD format ensures compatibility with window globals
        },
        rollupOptions: {
            // CRITICAL: Do not bundle React, let the Voodflow Core provide it via window variables
            external: ['react', 'react-dom', 'react-dom/client', 'reactflow'],
            output: {
                globals: {
                    react: 'React',
                    'react-dom': 'ReactDOM',
                    'react-dom/client': 'ReactDOM', // Map client to ReactDOM (exposed as window.ReactDOM)
                    reactflow: 'ReactFlow',
                },
                // Ensure default export is handled correctly for UMD
                exports: 'named',
            }
        },
        minify: 'esbuild',
    },
    define: {
        'process.env.NODE_ENV': '"production"'
    }
});
