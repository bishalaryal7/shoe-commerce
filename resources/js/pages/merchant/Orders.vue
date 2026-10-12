<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import ShopLayout from '@/layouts/ShopLayout.vue';
import type { MerchantOrder } from '@/types/merchant';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    orders: {
        data: (MerchantOrder & { store: { slug: string; name: string } })[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();
const { t, date, money, paginationLabel } = useLocale();
</script>
<template>
    <Head :title="t('Partner store orders')" /><ShopLayout
        ><section class="wrap inner-page">
            <span class="eyebrow">{{ t('YOUR PURCHASES') }}</span>
            <h1>{{ t('Partner store orders') }}</h1>
            <p>
                {{ t('Follow orders placed with independent partner stores.') }}
            </p>
            <div class="merchant-buyer-orders">
                <article
                    v-for="order in orders.data"
                    :key="order.id"
                    class="form-card"
                >
                    <div>
                        <strong>{{ order.store.name }}</strong
                        ><span class="status-pill">{{ t(order.status) }}</span>
                    </div>
                    <p>{{ date(order.created_at) }} · {{ order.reference }}</p>
                    <p v-for="item in order.items" :key="item.id">
                        {{ item.quantity }} × {{ item.name }} · {{ t('Size') }}
                        {{ item.size }}
                    </p>
                    <strong>{{ money(order.total, 'NPR') }}</strong
                    ><Link
                        :href="
                            '/stores/' +
                            order.store.slug +
                            '/orders/' +
                            order.reference
                        "
                        class="button outline"
                        >{{ t('View order') }}</Link
                    >
                </article>
            </div>
            <p v-if="!orders.data.length" class="muted">
                {{ t('No partner store orders yet.') }}
            </p>
            <nav v-if="orders.links.length > 3" class="pagination">
                <Link
                    v-for="(link, index) in orders.links"
                    :key="index"
                    :href="link.url || '#'"
                    :class="{ active: link.active, disabled: !link.url }"
                    >{{ paginationLabel(link.label) }}</Link
                >
            </nav>
        </section></ShopLayout
    >
</template>
