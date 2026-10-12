<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import ShopLayout from '@/layouts/ShopLayout.vue';
import type { MerchantStore } from '@/types/merchant';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    stores: {
        data: (MerchantStore & { products_count: number })[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { q?: string };
}>();
const { t, num, paginationLabel } = useLocale();
const query = ref(props.filters.q ?? '');
</script>
<template>
    <Head :title="t('Partner stores')" /><ShopLayout
        ><section class="wrap inner-page">
            <span class="eyebrow">{{ t('INDEPENDENT SELLERS') }}</span>
            <h1>{{ t('Partner stores') }}</h1>
            <p>
                {{
                    t(
                        'Discover shops from approved wholesale partners. Each seller manages their own products and orders.',
                    )
                }}
            </p>
            <form
                class="merchant-filters"
                @submit.prevent="
                    router.get('/stores', { q: query || undefined })
                "
            >
                <label
                    ><input
                        v-model="query"
                        type="search"
                        maxlength="100"
                        :placeholder="t('Search stores')" /></label
                ><button class="button dark">{{ t('Search') }}</button>
            </form>
            <div class="merchant-store-grid">
                <Link
                    v-for="store in stores.data"
                    :key="store.id"
                    :href="'/stores/' + store.slug"
                    class="merchant-store-card"
                    ><div
                        class="merchant-store-card-banner"
                        :style="
                            store.banner_url
                                ? {
                                      backgroundImage:
                                          'url(' + store.banner_url + ')',
                                  }
                                : { backgroundColor: store.accent }
                        "
                    ></div>
                    <img
                        v-if="store.logo_url"
                        :src="store.logo_url"
                        :alt="''"
                    />
                    <h2>{{ store.name }}</h2>
                    <p>{{ store.tagline || store.description }}</p>
                    <small>{{
                        t(':count products', {
                            count: num(store.products_count),
                        })
                    }}</small></Link
                >
            </div>
            <p v-if="!stores.data.length" class="muted">
                {{ t('No stores found yet.') }}
            </p>
            <nav v-if="stores.links.length > 3" class="pagination">
                <Link
                    v-for="(link, index) in stores.links"
                    :key="index"
                    :href="link.url || '#'"
                    :class="{ active: link.active, disabled: !link.url }"
                    >{{ paginationLabel(link.label) }}</Link
                >
            </nav>
        </section></ShopLayout
    >
</template>
