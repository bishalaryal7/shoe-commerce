<script setup lang="ts">
import ProductCard from '@/components/ProductCard.vue';
import ShopLayout from '@/layouts/ShopLayout.vue';
import type { Product } from '@/types/shop';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    ArrowUpRight,
    Footprints,
    Handshake,
    PackageCheck,
    Search,
} from 'lucide-vue-next';
import { ref } from 'vue';
const props = defineProps<{
    products: {
        data: Product[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { q?: string; category?: string };
    wholesale: boolean;
}>();
const search = ref(props.filters.q || '');
function filter(category?: string) {
    router.get(
        '/',
        { q: search.value || undefined, category },
        { preserveState: true, preserveScroll: true },
    );
}
</script>
<template>
    <Head title="Shoes for every step"
        ><meta
            name="description"
            content="Explore everyday sneakers, running shoes, and outdoor footwear. Shop individual pairs or join our wholesale program."
    /></Head>
    <ShopLayout>
        <section class="hero wrap">
            <div class="hero-copy">
                <div class="eyebrow">
                    <span class="tiny-dot"></span> YOUR NEXT CHAPTER STARTS HERE
                </div>
                <h1>Find your<br />next <em>step.</em></h1>
                <p>
                    Everyday essentials. Weekend adventures.<br />Shoes that
                    move with you, wherever you’re headed.
                </p>
                <a href="#collection" class="button dark"
                    >Explore the collection <ArrowUpRight :size="19"
                /></a>
                <div class="hero-caption">
                    MADE FOR MOVEMENT. CHOSEN BY YOU.
                </div>
            </div>
            <div class="hero-visual">
                <span class="hero-stamp">EVERYDAY<br />IN MOTION</span>
                <div class="hero-circle"></div>
                <img
                    src="/images/shoe-cream.svg"
                    alt="Cream City Low sneaker illustration"
                    class="hero-shoe"
                />
                <div class="hero-product">
                    <span>THE EVERYDAY COLLECTION</span
                    ><strong>City Low <ArrowUpRight :size="20" /></strong>
                </div>
                <span class="hero-index">01 / 06</span>
            </div>
        </section>
        <section class="benefits wrap">
            <div>
                <Footprints :size="21" /><span>Comfort in every step</span>
            </div>
            <div><PackageCheck :size="21" /><span>Sizes EU 36–45</span></div>
            <div><Handshake :size="21" /><span>Retail & wholesale</span></div>
        </section>
        <section id="collection" class="collection wrap">
            <div class="section-heading">
                <div>
                    <div class="eyebrow">THE ROTATION</div>
                    <h2>
                        {{
                            wholesale
                                ? 'Your wholesale collection'
                                : 'Good shoes. Great days.'
                        }}
                    </h2>
                </div>
                <span>{{
                    wholesale
                        ? 'Partner pricing · 12 pair minimum'
                        : 'Find a pair that feels like you.'
                }}</span>
            </div>
            <div class="filter-bar">
                <div class="filter-tabs">
                    <button
                        v-for="category in [
                            'All shoes',
                            'Everyday',
                            'Running',
                            'Outdoor',
                        ]"
                        :key="category"
                        :class="{
                            active:
                                (filters.category || 'All shoes') === category,
                        }"
                        @click="
                            filter(
                                category === 'All shoes' ? undefined : category,
                            )
                        "
                    >
                        {{ category }}
                    </button>
                </div>
                <form
                    class="search-field"
                    @submit.prevent="filter(filters.category)"
                >
                    <Search :size="17" /><input
                        v-model="search"
                        placeholder="Find your pair"
                        aria-label="Search shoes"
                    /><button type="submit" aria-label="Submit search">
                        <ArrowRight :size="16" />
                    </button>
                </form>
            </div>
            <div class="product-grid">
                <ProductCard
                    v-for="product in products.data"
                    :key="product.id"
                    :product="product"
                    :wholesale="wholesale"
                />
            </div>
            <div v-if="!products.data.length" class="empty-state">
                <h3>No shoes found.</h3>
                <p>Try a different search or category.</p>
                <button
                    class="button dark"
                    @click="
                        search = '';
                        filter();
                    "
                >
                    Show all shoes
                </button>
            </div>
            <nav
                v-if="products.links.length > 3"
                class="pagination"
                aria-label="Catalog pages"
            >
                <template v-for="(link, i) in products.links" :key="i"
                    ><Link
                        v-if="link.url"
                        :href="link.url"
                        :class="{ active: link.active }"
                        ><span v-html="link.label" /></Link
                    ><span v-else v-html="link.label"
                /></template>
            </nav>
        </section>
        <section class="wholesale-banner wrap">
            <div>
                <div class="eyebrow">LET’S GROW TOGETHER</div>
                <h2>Great shoes.<br />Better business.</h2>
                <p>
                    Stock your shelves with something worth stepping into.<br />Apply
                    for partner pricing and easy bulk ordering.
                </p>
                <Link href="/wholesale" class="button dark"
                    >Become a partner <ArrowUpRight :size="18"
                /></Link>
            </div>
            <div class="partner-art">
                <span>GOOD<br /><em>COMPANY.</em></span>
                <div class="partner-circle">
                    12+<small>PAIRS PER ORDER</small>
                </div>
            </div>
        </section>
    </ShopLayout>
</template>
