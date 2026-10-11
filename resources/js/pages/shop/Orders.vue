<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { type CustomerOrder } from '@/types/shop';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, PackageCheck, ShoppingBag } from 'lucide-vue-next';
import { ref } from 'vue';
const { t, label, num, date, money, paginationLabel } = useLocale();
const reordering = ref<string | null>(null);
function orderAgain(reference: string) {
    router.post(
        '/orders/' + reference + '/reorder',
        {},
        {
            preserveScroll: true,
            onStart: () => (reordering.value = reference),
            onFinish: () => (reordering.value = null),
        },
    );
}
defineProps<{
    orders: {
        data: CustomerOrder[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();
</script>
<template>
    <Head :title="t('Your orders')" /><ShopLayout
        ><section class="wrap inner-page">
            <span class="eyebrow"> {{ t('YOUR ACCOUNT') }} </span>
            <h1>{{ t('Your orders.') }}</h1>
            <div v-if="!orders.data.length" class="empty-state">
                <span class="empty-icon"><ShoppingBag :size="28" /></span>
                <h2>{{ t('Your first good find is waiting.') }}</h2>
                <p>
                    {{ t('Orders placed while signed in will appear here.') }}
                </p>
                <Link href="/" class="button dark">
                    {{ t('Find your next pair') }} <ArrowRight :size="16"
                /></Link>
            </div>
            <article
                v-for="order in orders.data"
                :key="order.reference"
                class="customer-order-card"
            >
                <div class="customer-order-meta">
                    <span>{{ date(order.created_at) }}</span
                    ><span class="status-pill" :data-status="order.status">{{
                        label(order.status)
                    }}</span
                    ><span
                        class="status-pill"
                        :data-status="order.payment_status"
                        >{{ label(order.payment_status) }}</span
                    ><strong>{{ money(order.total, order.currency) }}</strong>
                </div>
                <h3>
                    {{ order.items.map((item) => t(item.name)).join(', ') }}
                </h3>
                <p>
                    {{
                        num(
                            order.items.reduce(
                                (sum, item) => sum + item.quantity,
                                0,
                            ),
                        )
                    }}
                    {{ t('pairs ·') }} {{ order.reference }}
                </p>
                <Link
                    :href="'/orders/track/' + order.reference"
                    class="button outline"
                >
                    {{ t('View order') }} <ArrowRight :size="15"
                /></Link>
                <button
                    v-if="
                        order.status === 'fulfilled' &&
                        order.payment_status === 'paid'
                    "
                    type="button"
                    class="button reorder-button outline"
                    :disabled="reordering === order.reference"
                    @click="orderAgain(order.reference)"
                >
                    <PackageCheck :size="15" />{{ t('Order again') }}
                </button>
            </article>
            <nav
                v-if="orders.links.length > 3"
                class="pagination"
                :aria-label="t('Order pages')"
            >
                <template v-for="(link, i) in orders.links" :key="i"
                    ><Link
                        v-if="link.url"
                        :href="link.url"
                        :class="{ active: link.active }"
                        ><span v-text="paginationLabel(link.label)" /></Link
                    ><span v-else v-text="paginationLabel(link.label)"
                /></template>
            </nav></section
    ></ShopLayout>
</template>
