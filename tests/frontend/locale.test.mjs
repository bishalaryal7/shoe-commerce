import { parse as parseTemplate } from '@vue/compiler-dom';
import { parse } from '@vue/compiler-sfc';
import assert from 'node:assert/strict';
import { readFileSync, readdirSync } from 'node:fs';
import { join, resolve } from 'node:path';
import test from 'node:test';
import {
    formatMoney,
    labels,
    number,
    slogan,
    translate,
} from '../../resources/js/i18n/locale.ts';

const root = resolve(import.meta.dirname, '../..');
const dictionary = JSON.parse(readFileSync(join(root, 'lang/ne.json'), 'utf8'));
const files = (dir) =>
    readdirSync(dir, { withFileTypes: true }).flatMap((entry) =>
        entry.isDirectory()
            ? files(join(dir, entry.name))
            : [join(dir, entry.name)],
    );
const exempt = new Set(['EVE Shoes', slogan]);

test('English and Nepali preserve EVE Shoes and its slogan while translating complete sentences', () => {
    for (const locale of ['en', 'ne'])
        for (const brand of exempt)
            assert.equal(translate(locale, brand), brand);
    assert.equal(
        translate('ne', 'Continue to :provider to complete payment securely.', {
            provider: 'खल्ती',
        }),
        'सुरक्षित रूपमा भुक्तानी पूरा गर्न खल्ती मा जानुहोस्।',
    );
    assert.equal(
        translate('en', 'Add :count more pairs to place a wholesale request.', {
            count: 3,
        }),
        'Add 3 more pairs to place a wholesale request.',
    );
    assert.equal(
        translate('ne', 'Hello :name,', { name: '<b>:count</b>' }),
        'नमस्ते <b>:count</b>,',
    );
});

test('Nepali numerals and money retain the underlying amount including JPY zero decimals', () => {
    assert.equal(number('ne', 12), '१२');
    assert.equal(number('en', 12), '12');
    assert.match(formatMoney('ne', 12345, 'NPR'), /१२३\.४५/);
    assert.match(formatMoney('en', 12345, 'NPR'), /123\.45/);
    assert.match(formatMoney('ne', 123, 'JPY'), /१२३/);
    assert.doesNotMatch(formatMoney('ne', 123, 'JPY'), /१\.२३/);
});

test('all application status labels and template translation keys have Nepali text', () => {
    for (const label of Object.values(labels))
        assert.match(
            dictionary[label] ?? '',
            /[\u0900-\u097f]/,
            `Missing status: ${label}`,
        );
    for (const file of files(join(root, 'resources/js')).filter((file) =>
        /\.(vue|ts)$/.test(file),
    )) {
        const source = readFileSync(file, 'utf8');
        for (const match of source.matchAll(/\bt\(\s*'((?:\\.|[^'\\])*)'/g)) {
            const key = match[1].replaceAll("\\'", "'");
            if (!exempt.has(key))
                assert.match(
                    dictionary[key] ?? '',
                    /[\u0900-\u097f]/,
                    `${file}: ${key}`,
                );
        }
    }
});

test('storefront, admin, auth and account templates have no hardcoded English UI text', () => {
    for (const file of files(join(root, 'resources/js')).filter(
        (file) => file.endsWith('.vue') && !file.endsWith('/Welcome.vue'),
    )) {
        const { descriptor } = parse(readFileSync(file, 'utf8'));
        if (!descriptor.template) continue;
        const visit = (node) => {
            if (node.type === 2) {
                const text = node.content.replace(/\s+/g, ' ').trim();
                if (!exempt.has(text))
                    assert.doesNotMatch(text, /[A-Za-z]/, `${file}: ${text}`);
            }
            if (node.type === 1)
                for (const prop of node.props) {
                    if (
                        prop.type === 6 &&
                        prop.value &&
                        [
                            'title',
                            'description',
                            'placeholder',
                            'aria-label',
                            'alt',
                        ].includes(prop.name)
                    ) {
                        assert.doesNotMatch(
                            prop.value.content,
                            /[A-Za-z]/,
                            `${file}: ${prop.name}`,
                        );
                    }
                    // Translating an option's text must never change submitted enum values.
                    if (node.tag === 'option')
                        assert.ok(
                            node.props.some(
                                (p) =>
                                    p.name === 'value' ||
                                    p.arg?.content === 'value',
                            ),
                            `${file}: option needs a stable value`,
                        );
                }
            for (const child of node.children ?? []) visit(child);
        };
        visit(parseTemplate(descriptor.template.content));
    }
});

test('translation placeholders exactly match their English sentences', () => {
    const placeholders = (text) =>
        [...text.matchAll(/:([A-Za-z_]+)/g)].map((m) => m[1]).sort();
    for (const [english, nepali] of Object.entries(dictionary))
        assert.deepEqual(placeholders(nepali), placeholders(english), english);
});
