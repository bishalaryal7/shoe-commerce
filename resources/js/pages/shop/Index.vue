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
const { t, num, paginationLabel } = useLocale();
const props = defineProps<{
    products: {
        data: Product[];
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: {
        q?: string;
        category?: string;
        size?: string;
        rating?: string;
        sort?: string;
    };
    wholesale: boolean;
    savedIds: number[];
    recentlyViewed: Product[];
}>();
const search = ref(props.filters.q || '');
const size = ref(props.filters.size || '');
const rating = ref(props.filters.rating || '');
const sort = ref(props.filters.sort || 'featured');
const loading = ref(false);
const hasFilters = computed(
    () =>
        !!(
            props.filters.q ||
            props.filters.category ||
            props.filters.size ||
            props.filters.rating ||
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
            rating: rating.value || undefined,
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
    rating.value = '';
    sort.value = 'featured';
    filter('');
}
</script>
<template>
    <Head :title="t('Find your next favorite pair')"
        ><meta
            name="description"
            :content="
                t(
                    'Shop everyday sneakers, running shoes, and outdoor footwear. Find your size, shop individual pairs, or apply for wholesale prices.',
                )
            "
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
                        {{
                            t(
                                'EVE Shoes takes its name from Everest: a reminder to keep moving. Find everyday comfort, running shoes and outdoor styles for your next journey.',
                            )
                        }}
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
                        :alt="
                            t(
                                'Everest-inspired snowy mountain landscape at dawn',
                            )
                        "
                        class="eve-hero-image"
                        width="1536"
                        height="1024"
                        fetchpriority="high"
                    />
                    <div class="eve-hero-overlay">
                        <span> {{ t('EVE Shoes / NEPAL') }} </span
                        ><strong>{{ t('Reach higher. Walk further.') }}</strong>
                    </div>
                </div>
            </div>
        </section>
        <section v-if="!hasFilters" class="benefits wrap">
            <div>
                <span class="benefit-icon"><Footprints :size="19" /></span
                ><span
                    ><strong> {{ t('Find your fit') }} </strong
                    ><small> {{ t('Sizes EU 36–45') }} </small></span
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
                                ? t('Your partner collection.')
                                : t('Meet your new rotation.')
                        }}
                    </h2>
                </div>
                <span v-if="wholesale" class="wholesale-pill">
                    {{ t('Wholesale pricing · 12 pair minimum') }} </span
                ><span v-else class="collection-caption">
                    {{ t('A pair for every kind of day.') }}
                </span>
            </div>
            <div class="collection-tools">
                <div class="filter-tabs" :aria-label="t('Shoe categories')">
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
                        :placeholder="t('Search your next pair…')"
                        :aria-label="t('Search shoe names')"
                    /><button
                        type="submit"
                        class="search-submit"
                        :aria-label="t('Search')"
                    >
                        <ArrowRight :size="16" />
                    </button>
                </form>
            </div>
            <div class="catalog-toolbar">
                <span class="result-count" role="status"
                    >{{
                        loading
                            ? t('Finding your shoes…')
                            : t(
                                  products.total === 1
                                      ? ':count style'
                                      : ':count styles',
                                  {
                                      count: num(products.total),
                                  },
                              )
                    }}<button
                        v-if="hasFilters"
                        class="clear-filters"
                        @click="reset()"
                    >
                        {{ t('Clear filters') }} <X :size="12" /></button
                ></span>
                <div class="catalog-selects">
                    <label
                        ><SlidersHorizontal :size="14" /><span class="sr-only">
                            {{ t('Filter by EU size') }} </span
                        ><select v-model="size" @change="filter()">
                            <option value="">{{ t('All sizes') }}</option>
                            <option
                                v-for="n in 10"
                                :key="n"
                                :value="String(n + 35)"
                            >
                                {{ t('EU') }} {{ num(n + 35) }}
                            </option>
                        </select></label
                    ><label
                        ><span class="sr-only">{{ t('Filter by rating') }}</span
                        ><select v-model="rating" @change="filter()">
                            <option value="">{{ t('Any rating') }}</option>
                            <option value="4">{{ t('4 stars & up') }}</option>
                            <option value="3">{{ t('3 stars & up') }}</option>
                        </select></label
                    ><label
                        ><span class="sr-only"> {{ t('Sort products') }} </span
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
                            <option value="name">{{ t('Name: A–Z') }}</option>
                            <option value="rating">{{ t('Top rated') }}</option>
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
                    :saved="savedIds.includes(product.id)"
                />
            </div>
            <div v-if="!products.data.length" class="empty-state">
                <span class="empty-icon"><Search :size="29" /></span>
                <h3>{{ t('No matches this time.') }}</h3>
                <p>
                    {{
                        t(
                            'Try another name, size, or category. Your next pair is out there.',
                        )
                    }}
                </p>
                <button class="button dark" @click="reset()">
                    {{ t('Reset filters') }} <ArrowRight :size="16" />
                </button>
            </div>
            <nav
                v-if="products.links.length > 3"
                class="pagination"
                :aria-label="t('Catalog pages')"
            >
                <template v-for="(link, i) in products.links" :key="i"
                    ><Link
                        v-if="link.url"
                        :href="link.url"
                        :class="{ active: link.active }"
                        :aria-current="link.active ? 'page' : undefined"
                        ><span v-text="paginationLabel(link.label)" /></Link
                    ><span v-else v-text="paginationLabel(link.label)"
                /></template>
            </nav>
        </section>
        <section
            v-if="recentlyViewed.length"
            class="collection wrap discovery-section"
        >
            <div class="section-heading">
                <div>
                    <span class="eyebrow">{{
                        t('PICK UP WHERE YOU LEFT OFF')
                    }}</span>
                    <h2>{{ t('Recently viewed shoes') }}</h2>
                </div>
                <button
                    type="button"
                    class="back-shopping"
                    @click="
                        router.delete('/recently-viewed', {
                            preserveScroll: true,
                        })
                    "
                >
                    {{ t('Clear history') }} <X :size="14" />
                </button>
            </div>
            <div class="product-grid">
                <ProductCard
                    v-for="product in recentlyViewed"
                    :key="product.id"
                    :product="product"
                    :wholesale="wholesale"
                    :saved="savedIds.includes(product.id)"
                />
            </div>
        </section>
        <section class="wholesale-banner wrap">
            <div class="partner-panel">
                <div>
                    <span class="eyebrow">
                        {{ t('GOOD BUSINESS STARTS HERE') }}
                    </span>
                    <h2>
                        {{ t('Your shelves.') }} <br /><em>
                            {{ t('Our next chapter.') }}
                        </em>
                    </h2>
                    <p>
                        {{
                            t(
                                'More pairs. More possibilities. Apply for private partner pricing, mix your sizes, and build your next bulk order.',
                            )
                        }}
                    </p>
                    <Link href="/wholesale" class="button dark">
                        {{ t('Let’s work together') }} <ArrowUpRight :size="18"
                    /></Link>
                </div>
                <div class="partner-art">
                    <span class="partner-large"
                        >{{ num(12) }}<span>+</span></span
                    ><span class="partner-caption">
                        {{ t('PAIRS TO GET STARTED') }}
                    </span>
                    <div class="partner-tags">
                        <span> {{ t('Mix your styles') }} </span
                        ><span> {{ t('Find your sizes') }} </span
                        ><span> {{ t('Grow together ↗') }} </span>
                    </div>
                </div>
            </div>
        </section>
    </ShopLayout>
</template>
