<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { type CartLine, type ShopSharedProps } from '@/types/shop';
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
const { t, num, money } = useLocale();
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
    <Head :title="t('Your bag')" /><ShopLayout
        ><section class="wrap inner-page">
            <div class="checkout-steps">
                <span class="active"> {{ t('01 · Your bag') }} </span
                ><span> {{ t('02 · Your details') }} </span
                ><span> {{ t('03 · Request received') }} </span>
            </div>
            <h1>
                {{ t('Your good finds.') }}
                <span class="bag-item-count"
                    >{{ num(pairs) }}
                    {{ pairs === 1 ? t('pair') : t('pairs') }}</span
                >
            </h1>
            <div v-if="!lines.length" class="empty-state">
                <span class="empty-icon"><ShoppingBag :size="29" /></span>
                <h2>{{ t('A fresh start for your next step.') }}</h2>
                <p>
                    {{
                        t(
                            'Your bag is empty. Find a pair that fits your everyday.',
                        )
                    }}
                </p>
                <Link href="/" class="button dark">
                    {{ t('Explore the collection') }} <ArrowRight :size="17"
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
                            <img :src="line.image" :alt="t(line.name)" />
                            <div class="cart-row-main">
                                <h3>{{ t(line.name) }}</h3>
                                <p>
                                    {{ t('EU') }} {{ num(line.size) }}
                                    <span class="meta-dot">·</span>
                                    {{ t(line.color) }}
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
                                                t(
                                                    'Decrease quantity of :name',
                                                    { name: t(line.name) },
                                                )
                                            "
                                        >
                                            <Minus :size="12" /></button
                                        ><span
                                            :aria-label="
                                                t(':count pairs', {
                                                    count: num(line.quantity),
                                                })
                                            "
                                            >{{ num(line.quantity) }}</span
                                        ><button
                                            type="button"
                                            :disabled="
                                                busy ||
                                                line.quantity >=
                                                    Math.min(line.stock, 999)
                                            "
                                            @click="quantity(line, 1)"
                                            :aria-label="
                                                t(
                                                    'Increase quantity of :name',
                                                    { name: t(line.name) },
                                                )
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
                                            t('Remove :name, size :size', {
                                                name: t(line.name),
                                                size: num(line.size),
                                            })
                                        "
                                    >
                                        <Trash2 :size="12" /> {{ t('Remove') }}
                                    </button>
                                </div>
                                <p
                                    v-if="line.quantity > line.stock"
                                    class="field-error"
                                >
                                    {{
                                        t(
                                            'Only :count pairs remain. Adjust your quantity before continuing.',
                                            { count: num(line.stock) },
                                        )
                                    }}
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
                        ><ArrowLeft :size="15" /> {{ t('Find another pair') }}
                    </Link>
                </div>
                <aside class="summary-card">
                    <span v-if="wholesale" class="status-pill">
                        {{ t('Wholesale order') }}
                    </span>
                    <h2>{{ t('Looking good.') }}</h2>
                    <div class="summary-line">
                        <span
                            >{{ num(pairs) }}
                            {{ pairs === 1 ? t('pair') : t('pairs') }}</span
                        ><strong>{{
                            money(subtotal, page.props.currency)
                        }}</strong>
                    </div>
                    <div class="summary-line">
                        <span> {{ t('Shipping & taxes') }} </span
                        ><span>{{ t('Calculated at checkout') }}</span>
                    </div>
                    <div class="summary-line summary-total">
                        <span> {{ t('Product subtotal') }} </span
                        ><strong>{{
                            money(subtotal, page.props.currency)
                        }}</strong>
                    </div>
                    <div v-if="wholesale" class="wholesale-progress">
                        <div>
                            <span
                                >{{ num(Math.min(pairs, 12)) }}
                                {{ t('/ 12 pairs') }} </span
                            ><span>{{
                                pairs >= 12
                                    ? t('Minimum reached')
                                    : t('Wholesale minimum')
                            }}</span>
                        </div>
                        <progress
                            :value="Math.min(pairs, 12)"
                            max="12"
                            :aria-label="
                                t('Progress toward 12 pair wholesale minimum')
                            "
                        ></progress>
                        <p v-if="pairs < 12">
                            {{
                                t(
                                    'Add :count more pairs to place a wholesale request.',
                                    { count: num(12 - pairs) },
                                )
                            }}
                        </p>
                    </div>
                    <Link
                        v-if="!wholesale || pairs >= 12"
                        href="/checkout"
                        class="button dark full"
                    >
                        {{ t('Continue to your details') }}
                        <ArrowRight :size="16" /></Link
                    ><Link v-else href="/" class="button full outline">
                        {{ t('Add more pairs') }} <Plus :size="16"
                    /></Link>
                    <div class="summary-note">
                        <ShieldCheck :size="16" /><span>
                            {{
                                t(
                                    'Review your final total and choose a payment method at checkout.',
                                )
                            }}
                        </span>
                    </div>
                </aside>
            </div>
        </section></ShopLayout
    >
</template>
