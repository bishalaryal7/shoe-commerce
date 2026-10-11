<script setup lang="ts">
import ProductDetails from '@/components/ProductDetails.vue';
import { useLocale } from '@/composables/useLocale';
import { type Product, type ShopSharedProps } from '@/types/shop';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Heart, Plus, Star } from 'lucide-vue-next';
import { computed, ref } from 'vue';
const { t, num, money } = useLocale();
const props = defineProps<{
    product: Product;
    wholesale: boolean;
    saved?: boolean;
}>();
const page = usePage<ShopSharedProps>();
const details = ref<InstanceType<typeof ProductDetails> | null>(null);
const saving = ref(false);
function toggleSaved() {
    router.post(
        '/saved/' + props.product.id,
        {},
        {
            preserveScroll: true,
            onStart: () => (saving.value = true),
            onFinish: () => (saving.value = false),
        },
    );
}
const available = computed(() =>
    props.product.variants.filter((v) => v.stock > 0),
);
</script>
<template>
    <article class="product-card">
        <button
            type="button"
            class="save-button"
            :class="{ saved }"
            :aria-label="
                t(
                    saved
                        ? 'Remove :name from saved shoes'
                        : 'Save :name for later',
                    { name: t(product.name) },
                )
            "
            :aria-pressed="!!saved"
            :disabled="saving"
            @click="toggleSaved"
        >
            <Heart :size="19" :fill="saved ? 'currentColor' : 'none'" />
        </button>
        <button
            type="button"
            class="product-image"
            :data-color="product.variants[0]?.color"
            :aria-label="
                t('View :name and choose a size', { name: t(product.name) })
            "
            @click="details?.open()"
        >
            <span class="category-label">{{ t(product.category) }}</span
            ><span v-if="!available.length" class="sold-out-label">
                {{ t('Sold out') }} </span
            ><img
                :src="product.image"
                :alt="
                    t(':name in :color', {
                        name: t(product.name),
                        color: t(product.variants[0]?.color || ''),
                    })
                "
                loading="lazy"
            /><span class="quick-view">
                {{ t('Quick look') }} <ArrowUpRight :size="15"
            /></span>
        </button>
        <div class="product-title">
            <h3>
                <Link :href="'/products/' + product.slug">{{
                    t(product.name)
                }}</Link>
            </h3>
            <strong>{{ money(product.price, page.props.currency) }}</strong>
        </div>
        <div class="product-meta">
            <span
                >{{ t(product.variants[0]?.color) }}
                <span class="meta-dot">·</span>
                {{ num(available.length) }} {{ t('sizes') }} </span
            ><span v-if="wholesale" class="partner-price">
                {{ t('Partner price') }}
            </span>
        </div>
        <div v-if="product.review_count" class="product-rating">
            <Star :size="13" fill="currentColor" />{{
                num(product.review_average ?? 0)
            }}
            <span
                >·
                {{
                    t(':count reviews', { count: num(product.review_count) })
                }}</span
            >
        </div>
        <p>{{ t(product.description) }}</p>
        <button
            type="button"
            class="choose-size-button"
            @click="details?.open()"
            :disabled="!available.length"
        >
            {{
                available.length
                    ? t('Choose your size')
                    : t('Currently unavailable')
            }}<Plus v-if="available.length" :size="17" /></button
        ><ProductDetails
            ref="details"
            :product="product"
            :wholesale="wholesale"
        />
    </article>
</template>
