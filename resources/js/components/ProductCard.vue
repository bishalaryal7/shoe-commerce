<script setup lang="ts">
import ProductDetails from '@/components/ProductDetails.vue';
import { money, type Product, type ShopSharedProps } from '@/types/shop';
import { usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
const props = defineProps<{ product: Product; wholesale: boolean }>();
const page = usePage<ShopSharedProps>();
const details = ref<InstanceType<typeof ProductDetails> | null>(null);
const available = computed(() =>
    props.product.variants.filter((v) => v.stock > 0),
);
</script>
<template>
    <article class="product-card">
        <button
            type="button"
            class="product-image"
            :data-color="product.variants[0]?.color"
            :aria-label="'View ' + product.name + ' and choose a size'"
            @click="details?.open()"
        >
            <span class="category-label">{{ product.category }}</span
            ><span v-if="!available.length" class="sold-out-label"
                >Sold out</span
            ><img
                :src="product.image"
                :alt="product.name + ' in ' + product.variants[0]?.color"
                loading="lazy"
            /><span class="quick-view"
                >View details <ArrowUpRight :size="15"
            /></span>
        </button>
        <div class="product-title">
            <h3>
                <button @click="details?.open()">{{ product.name }}</button>
            </h3>
            <strong>{{ money(product.price, page.props.currency) }}</strong>
        </div>
        <div class="product-meta">
            <span
                >{{ product.variants[0]?.color }}
                <span class="meta-dot">·</span>
                {{ available.length }} sizes</span
            ><span v-if="wholesale" class="partner-price">Partner price</span>
        </div>
        <p>{{ product.description }}</p>
        <button
            type="button"
            class="choose-size-button"
            @click="details?.open()"
            :disabled="!available.length"
        >
            {{ available.length ? 'Choose your size' : 'Currently unavailable'
            }}<Plus v-if="available.length" :size="17" /></button
        ><ProductDetails
            ref="details"
            :product="product"
            :wholesale="wholesale"
        />
    </article>
</template>
