<script setup lang="ts">
import ProductCard from '@/components/ProductCard.vue';
import ProductDetails from '@/components/ProductDetails.vue';
import { useLocale } from '@/composables/useLocale';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { type Product, type ShopSharedProps } from '@/types/shop';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    ChevronRight,
    Footprints,
    Layers3,
    PackageCheck,
    ShoppingBag,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
const { t, num, money } = useLocale();
const props = defineProps<{
    product: Product;
    wholesale: boolean;
    related: Product[];
}>();
const page = usePage<ShopSharedProps>();
const details = ref<InstanceType<typeof ProductDetails> | null>(null);
const available = computed(() =>
    props.product.variants.filter((v) => v.stock > 0),
);
</script>
<template>
    <Head :title="t(product.name)"
        ><meta name="description" :content="product.description" /><meta
            property="og:title"
            :content="product.name + ' | ' + page.props.name" /><meta
            property="og:description"
            :content="product.description" /></Head
    ><ShopLayout
        ><section class="wrap inner-page product-page">
            <nav class="breadcrumbs" :aria-label="t('Breadcrumb')">
                <Link href="/">{{ t('Shop') }}</Link
                ><ChevronRight :size="12" /><Link
                    :href="'/?category=' + product.category + '#collection'"
                    >{{ t(product.category) }}</Link
                ><ChevronRight :size="12" /><span>{{ t(product.name) }}</span>
            </nav>
            <div class="product-page-grid">
                <div
                    class="product-page-image"
                    :data-color="product.variants[0]?.color"
                >
                    <span class="category-label">{{ t(product.category) }}</span
                    ><img
                        :src="product.image"
                        :alt="t(product.name)"
                        fetchpriority="high"
                    />
                </div>
                <div class="product-page-copy">
                    <span class="eyebrow"
                        >{{ t(product.category) }} {{ t('COLLECTION') }}
                    </span>
                    <h1>{{ t(product.name) }}</h1>
                    <div class="product-page-price">
                        {{ money(product.price, page.props.currency)
                        }}<span>{{
                            wholesale
                                ? t('Your wholesale price / pair')
                                : t('Per pair')
                        }}</span>
                    </div>
                    <p class="product-page-description">
                        {{ t(product.description) }}
                    </p>
                    <div class="product-page-availability">
                        <span
                            class="availability-dot"
                            :class="{ empty: !available.length }"
                        ></span
                        >{{
                            available.length
                                ? t(':count sizes available', {
                                      count: num(available.length),
                                  })
                                : t('Currently sold out')
                        }}
                    </div>
                    <button
                        class="button dark full"
                        :disabled="!available.length"
                        @click="details?.open()"
                    >
                        <ShoppingBag :size="18" />{{
                            available.length
                                ? t('Choose size & add to bag')
                                : t('Currently unavailable')
                        }}
                    </button>
                    <div class="product-page-benefits">
                        <div>
                            <Footprints :size="19" /><span>
                                {{ t('Choose your EU size') }}
                            </span>
                        </div>
                        <div>
                            <Layers3 :size="19" /><span>{{
                                wholesale
                                    ? t(
                                          '12 pair minimum · Mix sizes and styles',
                                      )
                                    : t('Individual pairs & wholesale orders')
                            }}</span>
                        </div>
                        <div>
                            <PackageCheck :size="19" /><span>
                                {{ t('Your order is confirmed by our team') }}
                            </span>
                        </div>
                    </div>
                    <details class="product-faq">
                        <summary>{{ t('How does ordering work?') }}</summary>
                        <p>
                            {{
                                t(
                                    'Choose a size and quantity, add the shoes to your bag, then enter your delivery details. You can request confirmation or use an available online payment method at checkout.',
                                )
                            }}
                        </p>
                    </details>
                    <details class="product-faq">
                        <summary>{{ t('Can I buy in wholesale?') }}</summary>
                        <p>
                            {{
                                t(
                                    'Apply for a wholesale account. Once approved, sign in to see partner prices and order at least 12 pairs across styles and sizes.',
                                )
                            }}
                        </p>
                        <Link href="/wholesale"
                            >{{ t('Explore wholesale') }}
                            <ArrowRight :size="12"
                        /></Link>
                    </details>
                    <details class="product-faq">
                        <summary>
                            {{ t('Which size should I choose?') }}
                        </summary>
                        <p>
                            {{
                                t(
                                    'Sizes are listed in EU sizing. Select the size you normally wear; if you’re unsure, request confirmation from our team before paying.',
                                )
                            }}
                        </p>
                    </details>
                </div>
            </div>
            <ProductDetails
                ref="details"
                :product="product"
                :wholesale="wholesale"
            />
        </section>
        <section v-if="related.length" class="collection wrap">
            <div class="section-heading">
                <div>
                    <span class="eyebrow"> {{ t('MORE GOOD FINDS') }} </span>
                    <h2>{{ t('Keep your options open.') }}</h2>
                </div>
                <Link href="/" class="back-shopping">
                    {{ t('Shop all shoes') }} <ArrowRight :size="14"
                /></Link>
            </div>
            <div class="product-grid">
                <ProductCard
                    v-for="item in related"
                    :key="item.id"
                    :product="item"
                    :wholesale="wholesale"
                />
            </div></section
    ></ShopLayout>
</template>
