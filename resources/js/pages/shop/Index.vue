<script setup lang="ts">
import ProductCard from '@/components/ProductCard.vue';
import { useLocale } from '@/composables/useLocale';
import ShopLayout from '@/layouts/ShopLayout.vue';
import type { Product } from '@/types/shop';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    ArrowUpRight,
    Footprints,
    Layers3,
    PackageCheck,
    Search,
    SlidersHorizontal,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
const { t } = useLocale();
const props = defineProps<{
    products: {
        data: Product[];
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { q?: string; category?: string; size?: string; sort?: string };
    wholesale: boolean;
}>();
const search = ref(props.filters.q || '');
const size = ref(props.filters.size || '');
const sort = ref(props.filters.sort || 'featured');
const loading = ref(false);
const hasFilters = computed(
    () =>
        !!(
            props.filters.q ||
            props.filters.category ||
            props.filters.size ||
            (props.filters.sort && props.filters.sort !== 'featured')
        ),
);
function filter(category = props.filters.category) {
    router.get(
        '/',
        {
            q: search.value || undefined,
            category,
            size: size.value || undefined,
            sort: sort.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onStart: () => (loading.value = true),
            onFinish: () => (loading.value = false),
        },
    );
}
function reset() {
    search.value = '';
    size.value = '';
    sort.value = 'featured';
    filter('');
}
</script>
<template>
    <Head title="Find your next favorite pair"
        ><meta
            name="description"
            content="Shop everyday sneakers, running shoes, and outdoor footwear. Find your size, shop individual pairs, or apply for wholesale prices."
    /></Head>
    <ShopLayout>
        <section v-if="!hasFilters" class="hero wrap">
            <div class="hero-panel">
                <div class="hero-copy">
                    <span class="hero-badge"
                        ><span class="tiny-dot"></span>
                        {{ t('FOR EVERY WAY YOU MOVE') }}
                    </span>
                    <h1>{{ t('Reach higher. Walk further.') }}</h1>
                    <p>
                        EVE takes its name from Everest: a reminder to keep
                        moving. Find everyday comfort, running shoes and outdoor
                        styles for your next journey.
                    </p>
                    <div class="hero-buttons">
                        <a href="#collection" class="button lime"
                            >{{ t('Find my pair') }}
                            <ArrowUpRight :size="18" /></a
                        ><Link href="/wholesale" class="hero-secondary"
                            >{{ t('Buying in bulk?') }} <ArrowRight :size="16"
                        /></Link>
                    </div>
                    <div class="hero-caption">
                        <span class="caption-line"></span>
                        {{ t('GOOD SHOES. NO OVERTHINKING.') }}
                    </div>
                </div>
                <div class="hero-visual eve-hero-visual">
                    <img
                        src="/images/eve-everest-hero.webp"
                        alt="Everest-inspired snowy mountain landscape at dawn"
                        class="eve-hero-image"
                        width="1536"
                        height="1024"
                        fetchpriority="high"
                    />
                    <div class="eve-hero-overlay">
                        <span>EVE / NEPAL</span
                        ><strong>{{ t('Reach higher. Walk further.') }}</strong>
                    </div>
                </div>
            </div>
        </section>
        <section v-if="!hasFilters" class="benefits wrap">
            <div>
                <span class="benefit-icon"><Footprints :size="19" /></span
                ><span
                    ><strong>Find your fit</strong
                    ><small>Sizes EU 36–45</small></span
                >
            </div>
            <div>
                <span class="benefit-icon"><Layers3 :size="19" /></span
                ><span
                    ><strong> {{ t('One pair or a dozen') }} </strong
                    ><small> {{ t('Retail & wholesale') }} </small></span
                >
            </div>
            <div>
                <span class="benefit-icon"><PackageCheck :size="19" /></span
                ><span
                    ><strong> {{ t('Easy to order') }} </strong
                    ><small>
                        {{ t('Your team confirms the details') }}
                    </small></span
                >
            </div>
        </section>
        <section id="collection" class="collection wrap">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">
                        {{ t('YOUR NEXT FAVORITES') }}
                    </span>
                    <h2>
                        {{
                            wholesale
                                ? 'Your partner collection.'
                                : 'Meet your new rotation.'
                        }}
                    </h2>
                </div>
                <span v-if="wholesale" class="wholesale-pill"
                    >Wholesale pricing · 12 pair minimum</span
                ><span v-else class="collection-caption">
                    {{ t('A pair for every kind of day.') }}
                </span>
            </div>
            <div class="collection-tools">
                <div class="filter-tabs" aria-label="Shoe categories">
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
                        :aria-pressed="
                            (filters.category || 'All shoes') === category
                        "
                        @click="
                            filter(category === 'All shoes' ? '' : category)
                        "
                    >
                        {{ t(category) }}
                    </button>
                </div>
                <form class="search-field" @submit.prevent="filter()">
                    <Search :size="17" /><input
                        v-model="search"
                        type="search"
                        maxlength="100"
                        placeholder="Search your next pair…"
                        aria-label="Search shoe names"
                    /><button
                        type="submit"
                        class="search-submit"
                        aria-label="Search"
                    >
                        <ArrowRight :size="16" />
                    </button>
                </form>
            </div>
            <div class="catalog-toolbar">
                <span class="result-count" role="status"
                    >{{
                        loading
                            ? 'Finding your shoes…'
                            : products.total +
                              (products.total === 1 ? ' style' : ' styles')
                    }}<button
                        v-if="hasFilters"
                        class="clear-filters"
                        @click="reset()"
                    >
                        Clear filters <X :size="12" /></button
                ></span>
                <div class="catalog-selects">
                    <label
                        ><SlidersHorizontal :size="14" /><span class="sr-only"
                            >Filter by EU size</span
                        ><select v-model="size" @change="filter()">
                            <option value="">All sizes</option>
                            <option
                                v-for="n in 10"
                                :key="n"
                                :value="String(n + 35)"
                            >
                                EU {{ n + 35 }}
                            </option>
                        </select></label
                    ><label
                        ><span class="sr-only">Sort products</span
                        ><select v-model="sort" @change="filter()">
                            <option value="featured">
                                {{ t('Featured') }}
                            </option>
                            <option value="price_low">
                                {{ t('Price: low to high') }}
                            </option>
                            <option value="price_high">
                                {{ t('Price: high to low') }}
                            </option>
                            <option value="name">Name: A–Z</option>
                        </select></label
                    >
                </div>
            </div>
            <div
                class="product-grid"
                :class="{ 'is-loading': loading }"
                :aria-busy="loading"
            >
                <ProductCard
                    v-for="product in products.data"
                    :key="product.id"
                    :product="product"
                    :wholesale="wholesale"
                />
            </div>
            <div v-if="!products.data.length" class="empty-state">
                <span class="empty-icon"><Search :size="29" /></span>
                <h3>No matches this time.</h3>
                <p>
                    Try another name, size, or category. Your next pair is out
                    there.
                </p>
                <button class="button dark" @click="reset()">
                    {{ t('Reset filters') }} <ArrowRight :size="16" />
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
                        :aria-current="link.active ? 'page' : undefined"
                        ><span v-html="link.label" /></Link
                    ><span v-else v-html="link.label"
                /></template>
            </nav>
        </section>
        <section class="wholesale-banner wrap">
            <div class="partner-panel">
                <div>
                    <span class="eyebrow">GOOD BUSINESS STARTS HERE</span>
                    <h2>Your shelves.<br /><em>Our next chapter.</em></h2>
                    <p>
                        More pairs. More possibilities. Apply for private
                        partner pricing, mix your sizes, and build your next
                        bulk order.
                    </p>
                    <Link href="/wholesale" class="button dark"
                        >Let’s work together <ArrowUpRight :size="18"
                    /></Link>
                </div>
                <div class="partner-art">
                    <span class="partner-large">12<span>+</span></span
                    ><span class="partner-caption">PAIRS TO GET STARTED</span>
                    <div class="partner-tags">
                        <span>Mix your styles</span><span>Find your sizes</span
                        ><span>Grow together ↗</span>
                    </div>
                </div>
            </div>
        </section>
    </ShopLayout>
</template>
