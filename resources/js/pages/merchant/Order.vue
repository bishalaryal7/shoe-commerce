<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import MerchantLayout from '@/layouts/MerchantLayout.vue';
import type { MerchantOrder, MerchantStore } from '@/types/merchant';
import { Head, Link } from '@inertiajs/vue3';
defineProps<{ store: MerchantStore; order: MerchantOrder }>();
const { t, money, date } = useLocale();
</script>
<template>
    <Head :title="t('Order details') + ' | ' + store.name" /><MerchantLayout
        :store="store"
        ><section class="wrap inner-page merchant-order">
            <span class="eyebrow">{{ store.name }}</span>
            <h1>{{ t('Order request received') }}</h1>
            <p>
                {{
                    t(
                        'The seller will contact you about delivery and payment. No online payment has been taken.',
                    )
                }}
            </p>
            <div class="form-card">
                <p>
                    <strong>{{ t('Order number') }}:</strong>
                    {{ order.reference }}
                </p>
                <p>
                    <strong>{{ t('Status') }}:</strong> {{ t(order.status) }}
                </p>
                <p>
                    <strong>{{ t('Placed') }}:</strong>
                    {{ date(order.created_at) }}
                </p>
                <p>
                    <strong>{{ t('Delivery address') }}:</strong>
                    {{ order.address }}
                </p>
                <h2>{{ t('Your items') }}</h2>
                <p v-for="item in order.items" :key="item.id">
                    {{ item.quantity }} × {{ item.name }} · {{ t('Size') }}
                    {{ item.size }} —
                    {{ money(item.unit_price * item.quantity, 'NPR') }}
                </p>
                <strong
                    >{{ t('Total') }}: {{ money(order.total, 'NPR') }}</strong
                >
            </div>
            <Link
                :href="
                    '/stores/' +
                    store.slug +
                    '/messages?subject=' +
                    encodeURIComponent('Order ' + order.reference)
                "
                class="button outline"
                >{{ t('Message the seller about this order') }}</Link
            >
        </section></MerchantLayout
    >
</template>
