/**
 * esbuild configuration for Rivian Accessory Guide.
 *
 * Usage:
 *   npm run build          – one-time production build
 *   npm run watch          – rebuild on file changes
 */

import esbuild from 'esbuild';

const isWatch = process.argv.includes('--watch');

/** @type {import('esbuild').BuildOptions[]} */
const builds = [
    // Frontend JS — ES module source → IIFE bundle.
    {
        entryPoints: ['frontend/js/rag-frontend.js'],
        outfile: 'frontend/js/rag-frontend.min.js',
        bundle: true,
        format: 'iife',
        target: 'es2020',
        minify: true,
        drop: ['console', 'debugger'],
    },
    // Frontend CSS — minified copy.
    {
        entryPoints: ['frontend/css/rag-frontend.css'],
        outfile: 'frontend/css/rag-frontend.min.css',
        bundle: true,
        minify: true,
    },
];

for (const config of builds) {
    if (isWatch) {
        const ctx = await esbuild.context(config);
        await ctx.watch();
        console.log(`Watching ${config.entryPoints.join(', ')}…`);
    } else {
        await esbuild.build(config);
    }
}

if (!isWatch) {
    console.log('Build complete.');
}
