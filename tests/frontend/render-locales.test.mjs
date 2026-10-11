import { createInertiaApp } from '@inertiajs/vue3';
import vue from '@vitejs/plugin-vue';
import { parse } from '@vue/compiler-dom';
import { renderToString } from '@vue/server-renderer';
import assert from 'node:assert/strict';
import { resolve } from 'node:path';
import test from 'node:test';
import { createServer } from 'vite';
import { createSSRApp, h } from 'vue';
import { fixtures } from './shop-fixtures.mjs';

// Render the real Inertia pages without opening a port or requiring MySQL.
test('all customer, admin and account pages render in Nepali without English interface copy', async () => {
    const root = resolve(import.meta.dirname, '../..');
    const server = await createServer({
        root,
        configFile: false,
        plugins: [vue()],
        resolve: { alias: { '@': root + '/resources/js' } },
        server: { middlewareMode: true, hmr: false, ws: false, watch: null },
        appType: 'custom',
    });
    try {
        for (const [name, props] of Object.entries(fixtures)) {
            const component = (
                await server.ssrLoadModule(
                    '/resources/js/pages/' + name + '.vue',
                )
            ).default;
            const rendered = await createInertiaApp({
                page: {
                    component: name,
                    url: '/',
                    version: null,
                    props: {
                        name: 'EVE',
                        locale: 'ne',
                        currency: 'NPR',
                        cartCount: 1,
                        auth: {
                            user: {
                                id: 1,
                                name: 'परीक्षण ग्राहक',
                                email: 'buyer@example.com',
                                is_admin: true,
                                email_verified_at: '2026-10-11',
                            },
                        },
                        flash: {},
                        errors: {},
                        sidebarOpen: true,
                        ...props,
                    },
                },
                render: renderToString,
                resolve: () => component,
                setup: ({ App, props, plugin }) =>
                    createSSRApp({ render: () => h(App, props) }).use(plugin),
            });
            const walk = (node) => {
                if (['style', 'script'].includes(node.tag)) return;
                if (node.type === 2) {
                    // Brand exceptions and identifiers entered by a customer/staff are preserved verbatim.
                    const copy = node.content
                        .replaceAll('Reach higher. Walk further.', '')
                        .replaceAll('EVE', '')
                        .replaceAll('ORDER-TEST', '')
                        .replaceAll('CITY-LOW-40', '')
                        .replaceAll('buyer@example.com', '');
                    assert.doesNotMatch(
                        copy,
                        /[A-Za-z]/,
                        `${name}: ${node.content}`,
                    );
                }
                for (const child of node.children ?? []) walk(child);
            };
            walk(parse(rendered.body));
            assert.match(rendered.body, /[\u0900-\u097f]/, name);
        }
    } finally {
        await server.close();
    }
});
