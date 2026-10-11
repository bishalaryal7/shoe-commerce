<script setup lang="ts">
import ProductCard from '@/components/ProductCard.vue';
import { useLocale } from '@/composables/useLocale';
import ShopLayout from '@/layouts/ShopLayout.vue';
import type { Product } from '@/types/shop';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Heart } from 'lucide-vue-next';

const { t, num } = useLocale();
defineProps<{ products: Product[]; wholesale: boolean }>();
</script>

<template>
    <Head :title="t('Saved shoes')" />
    <ShopLayout>
        <section class="collection wrap saved-page">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">{{ t('YOUR SHORTLIST') }}</span>
                    <h1>{{ t('Saved shoes') }}</h1>
                    <p>
                        {{
                            t(':count shoes saved for later', {
                                count: num(products.length),
                            })
                        }}
                    </p>
                </div>
                <Link href="/" class="back-shopping"
                    >{{ t('Keep exploring') }} <ArrowRight :size="15"
                /></Link>
            </div>
            <div v-if="products.length" class="product-grid">
                <ProductCard
                    v-for="product in products"
                    :key="product.id"
                    :product="product"
                    :wholesale="wholesale"
                    saved
                />
            </div>
            <div v-else class="empty-state">
                <span class="empty-icon"><Heart :size="29" /></span>
                <h2>{{ t('Your saved list is empty.') }}</h2>
                <p>
                    {{
                        t(
                            'Tap the heart on any shoe to save it here while you decide.',
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
