<script setup lang="ts">
import ShopLayout from '@/layouts/ShopLayout.vue';
import { money, type CartLine, type ShopSharedProps } from '@/types/shop';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Minus,
    Plus,
    ShieldCheck,
    ShoppingBag,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
const props = defineProps<{ lines: CartLine[]; wholesale: boolean }>();
const page = usePage<ShopSharedProps>();
const busy = ref(false);
const subtotal = computed(() =>
    props.lines.reduce((sum, l) => sum + l.unit_price * l.quantity, 0),
);
const pairs = computed(() =>
    props.lines.reduce((sum, l) => sum + l.quantity, 0),
);
function quantity(line: CartLine, change: number) {
    busy.value = true;
    router.patch(
        '/cart/' + line.id,
        { quantity: line.quantity + change },
        { preserveScroll: true, onFinish: () => (busy.value = false) },
    );
}
function remove(id: number) {
    busy.value = true;
    router.delete('/cart/' + id, {
        preserveScroll: true,
        onFinish: () => (busy.value = false),
    });
}
</script>
<template>
    <Head title="Your bag" /><ShopLayout
        ><section class="wrap inner-page">
            <div class="checkout-steps">
                <span class="active">01 · Your bag</span
                ><span>02 · Your details</span
                ><span>03 · Request received</span>
            </div>
            <h1>
                Your good finds.<span class="bag-item-count"
                    >{{ pairs }} {{ pairs === 1 ? 'pair' : 'pairs' }}</span
                >
            </h1>
            <div v-if="!lines.length" class="empty-state">
                <span class="empty-icon"><ShoppingBag :size="29" /></span>
                <h2>A fresh start for your next step.</h2>
                <p>Your bag is empty. Find a pair that fits your everyday.</p>
                <Link href="/" class="button dark"
                    >Explore the collection <ArrowRight :size="17"
                /></Link>
            </div>
            <div v-else class="checkout-grid">
                <div>
                    <div
                        v-if="page.props.errors.quantity"
                        class="field-error"
                        role="alert"
                    >
                        {{ page.props.errors.quantity }}
                    </div>
                    <div class="cart-items">
                        <article
                            v-for="line in lines"
                            :key="line.id"
                            class="cart-row"
                        >
                            <img :src="line.image" :alt="line.name" />
                            <div class="cart-row-main">
                                <h3>{{ line.name }}</h3>
                                <p>
                                    EU {{ line.size }}
                                    <span class="meta-dot">·</span>
                                    {{ line.color }}
                                </p>
                                <div class="cart-controls">
                                    <div class="quantity-stepper">
                                        <button
                                            type="button"
                                            :disabled="
                                                busy || line.quantity <= 1
                                            "
                                            @click="quantity(line, -1)"
                                            :aria-label="
                                                'Decrease quantity of ' +
                                                line.name
                                            "
                                        >
                                            <Minus :size="12" /></button
                                        ><span
                                            :aria-label="
                                                line.quantity + ' pairs'
                                            "
                                            >{{ line.quantity }}</span
                                        ><button
                                            type="button"
                                            :disabled="
                                                busy ||
                                                line.quantity >=
                                                    Math.min(line.stock, 999)
                                            "
                                            @click="quantity(line, 1)"
                                            :aria-label="
                                                'Increase quantity of ' +
                                                line.name
                                            "
                                        >
                                            <Plus :size="12" />
                                        </button>
                                    </div>
                                    <button
                                        class="remove-item"
                                        :disabled="busy"
                                        @click="remove(line.id)"
                                        :aria-label="
                                            'Remove ' +
                                            line.name +
                                            ' size ' +
                                            line.size
                                        "
                                    >
                                        <Trash2 :size="12" />Remove
                                    </button>
                                </div>
                                <p
                                    v-if="line.quantity > line.stock"
                                    class="field-error"
                                >
                                    Only {{ line.stock }} left. Adjust your
                                    quantity before continuing.
                                </p>
                            </div>
                            <strong>{{
                                money(
                                    line.unit_price * line.quantity,
                                    page.props.currency,
                                )
                            }}</strong>
                        </article>
                    </div>
                    <Link href="/" class="back-shopping"
                        ><ArrowLeft :size="15" />Find another pair</Link
                    >
                </div>
                <aside class="summary-card">
                    <span v-if="wholesale" class="status-pill"
                        >Wholesale order</span
                    >
                    <h2>Looking good.</h2>
                    <div class="summary-line">
                        <span
                            >{{ pairs }}
                            {{ pairs === 1 ? 'pair' : 'pairs' }}</span
                        ><strong>{{
                            money(subtotal, page.props.currency)
                        }}</strong>
                    </div>
                    <div class="summary-line">
                        <span>Shipping & taxes</span
                        ><span>Confirmed by our team</span>
                    </div>
                    <div class="summary-line summary-total">
                        <span>Product subtotal</span
                        ><strong>{{
                            money(subtotal, page.props.currency)
                        }}</strong>
                    </div>
                    <div v-if="wholesale" class="wholesale-progress">
                        <div>
                            <span>{{ Math.min(pairs, 12) }} / 12 pairs</span
                            ><span>{{
                                pairs >= 12
                                    ? 'Minimum reached'
                                    : 'Wholesale minimum'
                            }}</span>
                        </div>
                        <progress
                            :value="Math.min(pairs, 12)"
                            max="12"
                            aria-label="Progress toward 12 pair wholesale minimum"
                        ></progress>
                        <p v-if="pairs < 12">
                            Add {{ 12 - pairs }} more
                            {{ 12 - pairs === 1 ? 'pair' : 'pairs' }} to place a
                            wholesale request.
                        </p>
                    </div>
                    <Link
                        v-if="!wholesale || pairs >= 12"
                        href="/checkout"
                        class="button dark full"
                        >Continue to your details
                        <ArrowRight :size="16" /></Link
                    ><Link v-else href="/" class="button full outline"
                        >Add more pairs <Plus :size="16"
                    /></Link>
                    <div class="summary-note">
                        <ShieldCheck :size="16" /><span
                            >No payment collected here. You’re submitting a
                            request; our team confirms the final total and
                            delivery with you.</span
                        >
                    </div>
                </aside>
            </div>
        </section></ShopLayout
    >
</template>
