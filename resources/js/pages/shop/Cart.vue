<script setup lang="ts">
import ShopLayout from '@/layouts/ShopLayout.vue';
import { money, type CartLine } from '@/types/shop';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const props = defineProps<{ lines: CartLine[]; wholesale: boolean }>();
const page = usePage();
const subtotal = computed(() =>
    props.lines.reduce((sum, l) => sum + l.unit_price * l.quantity, 0),
);
const pairs = computed(() =>
    props.lines.reduce((sum, l) => sum + l.quantity, 0),
);
</script>
<template>
    <Head title="Your bag" /><ShopLayout
        ><section class="wrap inner-page">
            <div class="eyebrow">THE GOOD STUFF</div>
            <h1>
                Your bag<span class="muted"> ({{ pairs }})</span>
            </h1>
            <div v-if="!lines.length" class="empty-state">
                <h2>Your next pair is waiting.</h2>
                <p>Your bag is empty. Explore the collection to get started.</p>
                <Link href="/" class="button dark">Find your shoes ↗</Link>
            </div>
            <div v-else class="checkout-grid">
                <div>
                    <article
                        v-for="line in lines"
                        :key="line.id"
                        class="cart-row"
                    >
                        <img :src="line.image" :alt="line.name" />
                        <div>
                            <h3>{{ line.name }}</h3>
                            <p>
                                EU {{ line.size }} · {{ line.color }} ·
                                {{ line.quantity }} pair(s)
                            </p>
                            <button
                                class="text-button"
                                @click="
                                    router.delete('/cart/' + line.id, {
                                        preserveScroll: true,
                                    })
                                "
                            >
                                Remove
                            </button>
                        </div>
                        <strong>{{
                            money(
                                line.unit_price * line.quantity,
                                page.props.currency as string,
                            )
                        }}</strong>
                    </article>
                    <Link href="/" class="text-button"
                        >← Continue shopping</Link
                    >
                </div>
                <aside class="summary-card">
                    <h2>Order summary</h2>
                    <div class="summary-line">
                        <span>{{ pairs }} pairs</span
                        ><strong>{{
                            money(subtotal, page.props.currency as string)
                        }}</strong>
                    </div>
                    <p>
                        Shipping and applicable taxes will be confirmed by our
                        team. No payment is collected online yet.
                    </p>
                    <p v-if="wholesale && pairs < 12" class="field-error">
                        Add {{ 12 - pairs }} more pairs to meet the wholesale
                        minimum.
                    </p>
                    <Link v-else href="/checkout" class="button dark full"
                        >Continue to order request ↗</Link
                    >
                </aside>
            </div>
        </section></ShopLayout
    >
</template>
