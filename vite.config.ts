import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                // Nova: display — cím és gombfelirat.
                bunny('Chakra Petch', {
                    variable: '--nova-font-display',
                    weights: [600, 700],
                    subsets: ['latin', 'latin-ext'],
                    fallbacks: [
                        'Bahnschrift',
                        'DIN Alternate',
                        'system-ui',
                        'sans-serif',
                    ],
                }),
                // Nova: sans — minden futó szöveg.
                bunny('Space Grotesk', {
                    variable: '--nova-font-sans',
                    weights: [400, 500, 600],
                    subsets: ['latin', 'latin-ext'],
                    fallbacks: [
                        'Avenir Next',
                        'system-ui',
                        'Segoe UI',
                        'sans-serif',
                    ],
                }),
                // Nova: mono — szám, kód, azonosító, mikrofelirat.
                bunny('JetBrains Mono', {
                    variable: '--nova-font-mono',
                    weights: [400, 500, 600],
                    subsets: ['latin', 'latin-ext'],
                    fallbacks: [
                        'ui-monospace',
                        'SFMono-Regular',
                        'Menlo',
                        'Consolas',
                        'monospace',
                    ],
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ]),
    server: {
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
    lint: {
        ignorePatterns: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
            'tailwind.config.js',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
        },
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        ignorePatterns: [
            '.github/**',
            'composer.json',
            'resources/js/components/ui/*',
            'resources/views/mail/*',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            stylesheet: 'resources/css/app.css',
        },
    },
});
