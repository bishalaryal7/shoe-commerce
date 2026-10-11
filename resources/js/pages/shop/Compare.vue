<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import ShopLayout from '@/layouts/ShopLayout.vue';
import type { Product } from '@/types/shop';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeftRight, ArrowRight, X } from 'lucide-vue-next';

const { t, num, money } = useLocale();
defineProps<{ products: Product[]; wholesale: boolean }>();
function colors(product: Product) {
    return [...new Set(product.variants.map((variant) => variant.color))]
        .map((color) => t(color))
        .join(', ');
}
function sizes(product: Product) {
    return product.variants
        .filter((variant) => variant.stock > 0)
        .map((variant) => num(variant.size))
        .join(', ');
}
</script>

<template>
    <Head :title="t('Compare shoes')" />
    <ShopLayout>
        <section class="collection wrap compare-page">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">{{ t('MAKE THE RIGHT PICK') }}</span>
                    <h1>{{ t('Compare shoes') }}</h1>
                    <p>
                        {{
                            t(
                                'Compare prices, available sizes, and details side by side.',
                            )
                        }}
                    </p>
                </div>
                <button
                    v-if="products.length"
                    type="button"
                    class="back-shopping"
                    @click="router.delete('/compare', { preserveScroll: true })"
                >
                    {{ t('Clear comparison') }} <X :size="14" />
                </button>
            </div>

            <div v-if="products.length" class="compare-grid">
                <article
                    v-for="product in products"
                    :key="product.id"
                    class="compare-card"
                >
                    <button
                        type="button"
                        class="compare-remove"
                        :aria-label="
                            t('Remove :name from comparison', {
                                name: t(product.name),
                            })
                        "
                        @click="
                            router.post(
                                '/compare/' + product.id,
                                {},
                                { preserveScroll: true },
                            )
                        "
                    >
                        <X :size="17" />
                    </button>
                    <Link
                        :href="'/products/' + product.slug"
                        class="compare-image"
                    >
                        <img
                            :src="product.image"
                            :alt="t(product.name)"
                            loading="lazy"
                        />
                    </Link>
                    <span class="eyebrow">{{ t(product.category) }}</span>
                    <h2>
                        <Link :href="'/products/' + product.slug">{{
                            t(product.name)
                        }}</Link>
                    </h2>
                    <strong class="compare-price"
                        >{{ money(product.price)
                        }}<small>{{
                            wholesale
                                ? t('Your wholesale price / pair')
                                : t('Per pair')
                        }}</small></strong
                    >

                    <dl class="compare-details">
                        <div>
                            <dt>{{ t('Available sizes') }}</dt>
                            <dd>
                                {{
                                    sizes(product) || t('Currently unavailable')
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt>{{ t('Color options') }}</dt>
                            <dd>{{ colors(product) || t('Not specified') }}</dd>
                        </div>
                        <div>
                            <dt>{{ t('Customer rating') }}</dt>
                            <dd v-if="product.review_count">
                                {{ num(product.review_average ?? 0) }} / 5 ·
                                {{
                                    t(':count reviews', {
                                        count: num(product.review_count),
                                    })
                                }}
                            </dd>
                            <dd v-else>{{ t('No reviews yet') }}</dd>
                        </div>
                        <div class="compare-description">
                            <dt>{{ t('Product details') }}</dt>
                            <dd>{{ t(product.description) }}</dd>
                        </div>
                    </dl>
                    <Link
                        :href="'/products/' + product.slug"
                        class="button dark full"
                        >{{ t('View shoe') }} <ArrowRight :size="16"
                    /></Link>
                </article>
            </div>

            <div v-else class="empty-state">
                <span class="empty-icon"><ArrowLeftRight :size="28" /></span>
                <h2>{{ t('Add shoes to compare.') }}</h2>
                <p>
                    {{
                        t(
                            'Choose up to three shoes to compare their prices, sizes, and details.',
                        )
                    }}
                </p>
                <Link href="/" class="button dark"
                    >{{ t('Explore shoes') }} <ArrowRight :size="16"
                /></Link>
            </div>
        </section>
    </ShopLayout>
</template>
