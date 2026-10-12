<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import MerchantLayout from '@/layouts/MerchantLayout.vue';
import type { MerchantLine, MerchantStore } from '@/types/merchant';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ store: MerchantStore; lines: MerchantLine[] }>();
const { t, money } = useLocale();
const total = computed(() =>
    props.lines.reduce((sum, line) => sum + line.unit_price * line.quantity, 0),
);
const stockChanged = computed(() =>
    props.lines.some((line) => line.quantity > line.stock),
);
function update(variant: number, quantity: number) {
    router.patch(
        '/stores/' + props.store.slug + '/cart/' + variant,
        { quantity },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="t('Your bag') + ' | ' + store.name" /><MerchantLayout
        :store="store"
        :cart-count="lines.reduce((sum, line) => sum + line.quantity, 0)"
        ><section class="wrap inner-page merchant-cart">
            <span class="eyebrow">{{ store.name }}</span>
            <h1>{{ t('Your bag') }}</h1>
            <div v-if="lines.length" class="checkout-grid">
                <div class="merchant-cart-lines">
                    <article
                        v-for="line in lines"
                        :key="line.variant_id"
                        class="merchant-cart-line"
                    >
                        <img
                            v-if="line.image_url"
                            :src="line.image_url"
                            :alt="line.product"
                        />
                        <div>
                            <Link
                                :href="
                                    '/stores/' +
                                    store.slug +
                                    '/products/' +
                                    line.slug
                                "
                                ><h2>{{ line.product }}</h2></Link
                            >
                            <p>{{ t('Size') }}: {{ line.size }}</p>
                            <strong>{{ money(line.unit_price, 'NPR') }}</strong
                            ><label
                                >{{ t('Quantity')
                                }}<input
                                    type="number"
                                    min="1"
                                    :max="Math.min(20, line.stock)"
                                    :value="line.quantity"
                                    @change="
                                        update(
                                            line.variant_id,
                                            Number(
                                                (
                                                    $event.target as HTMLInputElement
                                                ).value,
                                            ),
                                        )
                                    " /></label
                            ><button
                                class="text-button"
                                @click="update(line.variant_id, 0)"
                            >
                                {{ t('Remove') }}
                            </button>
                        </div>
                    </article>
                </div>
                <aside class="form-card">
                    <h2>{{ t('Order summary') }}</h2>
                    <p>
                        {{ t('Subtotal') }}
                        <strong>{{ money(total, 'NPR') }}</strong>
                    </p>
                    <p class="muted">
                        {{
                            t(
                                'Delivery and payment are arranged with the seller after your request.',
                            )
                        }}
                    </p>
                    <p v-if="stockChanged" class="field-error">
                        {{
                            t(
                                'A size in your bag now has less stock. Update its quantity before checkout.',
                            )
                        }}
                    </p>
                    <Link
                        v-if="!stockChanged"
                        :href="'/stores/' + store.slug + '/checkout'"
                        class="button dark full"
                        >{{ t('Continue to checkout') }}</Link
                    >
                </aside>
            </div>
            <div v-else>
                <p>{{ t('Your bag is empty.') }}</p>
                <Link :href="'/stores/' + store.slug" class="button dark">{{
                    t('Explore collection')
                }}</Link>
            </div>
        </section></MerchantLayout
    >
</template>
