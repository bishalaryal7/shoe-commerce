<script setup lang="ts">
import ProductCard from '@/components/ProductCard.vue';
import ProductDetails from '@/components/ProductDetails.vue';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { money, type Product, type ShopSharedProps } from '@/types/shop';
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
    <Head :title="product.name"
        ><meta name="description" :content="product.description" /><meta
            property="og:title"
            :content="product.name + ' | ' + page.props.name" /><meta
            property="og:description"
            :content="product.description" /></Head
    ><ShopLayout
        ><section class="wrap inner-page product-page">
            <nav class="breadcrumbs" aria-label="Breadcrumb">
                <Link href="/">Shop</Link><ChevronRight :size="12" /><Link
                    :href="'/?category=' + product.category + '#collection'"
                    >{{ product.category }}</Link
                ><ChevronRight :size="12" /><span>{{ product.name }}</span>
            </nav>
            <div class="product-page-grid">
                <div
                    class="product-page-image"
                    :data-color="product.variants[0]?.color"
                >
                    <span class="category-label">{{ product.category }}</span
                    ><img
                        :src="product.image"
                        :alt="product.name"
                        fetchpriority="high"
                    />
                </div>
                <div class="product-page-copy">
                    <span class="eyebrow"
                        >{{ product.category }} COLLECTION</span
                    >
                    <h1>{{ product.name }}</h1>
                    <div class="product-page-price">
                        {{ money(product.price, page.props.currency)
                        }}<span>{{
                            wholesale
                                ? 'Your wholesale price / pair'
                                : 'Per pair'
                        }}</span>
                    </div>
                    <p class="product-page-description">
                        {{ product.description }}
                    </p>
                    <div class="product-page-availability">
                        <span
                            class="availability-dot"
                            :class="{ empty: !available.length }"
                        ></span
                        >{{
                            available.length
                                ? available.length + ' sizes available'
                                : 'Currently sold out'
                        }}
                    </div>
                    <button
                        class="button dark full"
                        :disabled="!available.length"
                        @click="details?.open()"
                    >
                        <ShoppingBag :size="18" />{{
                            available.length
                                ? 'Choose size & add to bag'
                                : 'Currently unavailable'
                        }}
                    </button>
                    <div class="product-page-benefits">
                        <div>
                            <Footprints :size="19" /><span
                                >Choose your EU size</span
                            >
                        </div>
                        <div>
                            <Layers3 :size="19" /><span>{{
                                wholesale
                                    ? '12 pair minimum · Mix sizes and styles'
                                    : 'Individual pairs & wholesale orders'
                            }}</span>
                        </div>
                        <div>
                            <PackageCheck :size="19" /><span
                                >Your order is confirmed by our team</span
                            >
                        </div>
                    </div>
                    <details class="product-faq">
                        <summary>How does ordering work?</summary>
                        <p>
                            Choose a size and quantity, add the shoes to your
                            bag, then enter your delivery details. You can
                            request confirmation or use an available online
                            payment method at checkout.
                        </p>
                    </details>
                    <details class="product-faq">
                        <summary>Can I buy in wholesale?</summary>
                        <p>
                            Apply for a wholesale account. Once approved, sign
                            in to see partner prices and order at least 12 pairs
                            across styles and sizes.
                        </p>
                        <Link href="/wholesale"
                            >Explore wholesale <ArrowRight :size="12"
                        /></Link>
                    </details>
                    <details class="product-faq">
                        <summary>Which size should I choose?</summary>
                        <p>
                            Sizes are listed in EU sizing. Select the size you
                            normally wear; if you’re unsure, request
                            confirmation from our team before paying.
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
                    <span class="eyebrow">MORE GOOD FINDS</span>
                    <h2>Keep your options open.</h2>
                </div>
                <Link href="/" class="back-shopping"
                    >Shop all shoes <ArrowRight :size="14"
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
