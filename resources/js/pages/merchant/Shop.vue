<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import MerchantLayout from '@/layouts/MerchantLayout.vue';
import type { MerchantProduct, MerchantStore } from '@/types/merchant';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, Search } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    store: MerchantStore;
    products: {
        data: MerchantProduct[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { q?: string; category?: string };
    cartCount: number;
}>();
const { t, money, paginationLabel } = useLocale();
const query = ref(props.filters.q ?? '');
const category = ref(props.filters.category ?? '');
function search() {
    router.get(
        '/stores/' + props.store.slug,
        { q: query.value || undefined, category: category.value || undefined },
        { preserveState: true },
    );
}
</script>

<template>
    <Head :title="store.name"
        ><meta
            name="description"
            :content="store.description || store.tagline || store.name"
    /></Head>
    <MerchantLayout :store="store" :cart-count="cartCount">
        <section
            class="merchant-hero"
            :style="
                store.banner_url
                    ? {
                          backgroundImage:
                              'linear-gradient(90deg, #132923d9, #13292370), url(' +
                              store.banner_url +
                              ')',
                      }
                    : {}
            "
        >
            <div class="wrap">
                <span class="eyebrow">{{ t('WELCOME TO OUR STORE') }}</span>
                <h1>{{ store.name }}</h1>
                <p>{{ store.tagline || store.description }}</p>
                <a href="#merchant-collection" class="button light"
                    >{{ t('Explore collection') }} <ArrowRight :size="16"
                /></a>
            </div>
        </section>
        <section id="merchant-collection" class="wrap merchant-collection">
            <div class="merchant-section-head">
                <div>
                    <span class="eyebrow">{{
                        t('CURATED BY THE SELLER')
                    }}</span>
                    <h2>{{ t('Discover the collection') }}</h2>
                </div>
                <Link
                    :href="'/stores/' + store.slug + '/messages'"
                    class="text-button"
                    >{{ t('Questions? Message the seller') }}</Link
                >
            </div>
            <form class="merchant-filters" @submit.prevent="search">
                <label
                    ><Search :size="17" /><input
                        v-model="query"
                        type="search"
                        maxlength="100"
                        :placeholder="t('Search this store')" /></label
                ><select
                    v-model="category"
                    :aria-label="t('Category')"
                    @change="search"
                >
                    <option value="">{{ t('All categories') }}</option>
                    <option value="Shoes">{{ t('Shoes') }}</option>
                    <option value="Clothing">{{ t('Clothing') }}</option>
                    <option value="Accessories">
                        {{ t('Accessories') }}
                    </option></select
                ><button class="button dark">{{ t('Search') }}</button>
            </form>
            <div v-if="products.data.length" class="merchant-product-grid">
                <Link
                    v-for="product in products.data"
                    :key="product.id"
                    :href="
                        '/stores/' + store.slug + '/products/' + product.slug
                    "
                    class="merchant-product-card"
                    ><div class="merchant-product-image">
                        <img
                            v-if="product.image_url"
                            :src="product.image_url"
                            :alt="product.name"
                            loading="lazy"
                        /><span v-else>{{ t('Photo coming soon') }}</span>
                    </div>
                    <div class="merchant-product-meta">
                        <span>{{ t(product.category) }}</span>
                        <h3>{{ product.name }}</h3>
                        <strong>{{ money(product.price, 'NPR') }}</strong>
                    </div></Link
                >
            </div>
            <p v-else class="muted">
                {{ t('No products match your search.') }}
            </p>
            <nav
                v-if="products.links.length > 3"
                class="pagination"
                :aria-label="t('Product pages')"
            >
                <Link
                    v-for="(link, index) in products.links"
                    :key="index"
                    :href="link.url || '#'"
                    :class="{ disabled: !link.url, active: link.active }"
                    >{{ paginationLabel(link.label) }}</Link
                >
            </nav>
        </section>
        <section v-if="store.description" class="wrap merchant-about">
            <div>
                <span class="eyebrow">{{ t('OUR STORY') }}</span>
                <h2>{{ t('About this store') }}</h2>
            </div>
            <div>
                <p>{{ store.description }}</p>
                <Link
                    :href="'/stores/' + store.slug + '/messages'"
                    class="button outline"
                    >{{ t('Contact the seller') }}</Link
                >
            </div>
        </section>
    </MerchantLayout>
</template>
