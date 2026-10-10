import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { execFileSync } from 'node:child_process';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        {
            name: 'shop-release-revision',
            apply: 'build',
            generateBundle() {
                this.emitFile({
                    type: 'asset',
                    fileName: 'revision.json',
                    source: JSON.stringify({
                        commit: execFileSync('git', ['rev-parse', 'HEAD'], { encoding: 'utf8' }).trim(),
                        dirty: execFileSync('git', ['status', '--porcelain'], { encoding: 'utf8' }).trim().length > 0,
                    }),
                });
            },
        },
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
