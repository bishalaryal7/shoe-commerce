<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import MerchantLayout from '@/layouts/MerchantLayout.vue';
import type { MerchantProduct, MerchantStore } from '@/types/merchant';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { MessageCircle, ShoppingBag } from 'lucide-vue-next';

const props = defineProps<{ store: MerchantStore; product: MerchantProduct }>();
const { t, num, money } = useLocale();
const form = useForm({
    variant_id:
        props.product.variants.find((variant) => variant.stock > 0)?.id ?? 0,
    quantity: 1,
});
</script>

<template>
    <Head :title="product.name + ' | ' + store.name"
        ><meta name="description" :content="product.description"
    /></Head>
    <MerchantLayout :store="store"
        ><section class="wrap merchant-detail">
            <nav class="breadcrumbs">
                <Link :href="'/stores/' + store.slug">{{ store.name }}</Link> /
                {{ product.name }}
            </nav>
            <div class="merchant-detail-grid">
                <div class="merchant-detail-image">
                    <img
                        v-if="product.image_url"
                        :src="product.image_url"
                        :alt="product.name"
                    /><span v-else>{{ t('Photo coming soon') }}</span>
                </div>
                <div class="merchant-detail-copy">
                    <span class="eyebrow">{{ t(product.category) }}</span>
                    <h1>{{ product.name }}</h1>
                    <strong class="merchant-price">{{
                        money(product.price, 'NPR')
                    }}</strong>
                    <p>{{ product.description }}</p>
                    <form
                        @submit.prevent="
                            form.post('/stores/' + store.slug + '/cart')
                        "
                    >
                        <label
                            >{{ t('Choose a size')
                            }}<select v-model.number="form.variant_id" required>
                                <option
                                    v-for="variant in product.variants"
                                    :key="variant.id"
                                    :value="variant.id"
                                    :disabled="!variant.stock"
                                >
                                    {{ variant.size }} ·
                                    {{
                                        variant.stock
                                            ? t(':count available', {
                                                  count: num(variant.stock),
                                              })
                                            : t('Sold out')
                                    }}
                                </option>
                            </select></label
                        ><label
                            >{{ t('Quantity')
                            }}<input
                                v-model.number="form.quantity"
                                type="number"
                                min="1"
                                max="20"
                                required
                        /></label>
                        <p
                            v-if="
                                form.errors.variant_id || form.errors.quantity
                            "
                            class="field-error"
                        >
                            {{ form.errors.variant_id || form.errors.quantity }}
                        </p>
                        <button
                            class="button dark full"
                            :disabled="
                                form.processing ||
                                !product.variants.some(
                                    (variant) => variant.stock,
                                )
                            "
                        >
                            <ShoppingBag :size="17" />{{ t('Add to bag') }}
                        </button>
                    </form>
                    <Link
                        :href="
                            '/stores/' +
                            store.slug +
                            '/messages?subject=' +
                            encodeURIComponent('Question about ' + product.name)
                        "
                        class="button outline"
                        ><MessageCircle :size="17" />{{
                            t('Message the seller')
                        }}</Link
                    >
                    <p class="muted">
                        {{
                            t(
                                'This item is sold and fulfilled by the independent seller.',
                            )
                        }}
                    </p>
                </div>
            </div>
        </section></MerchantLayout
    >
</template>
