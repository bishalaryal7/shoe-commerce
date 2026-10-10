<script setup lang="ts">
import ShopLayout from '@/layouts/ShopLayout.vue';
import {
    money,
    type CartLine,
    type PaymentOption,
    type ShopSharedProps,
} from '@/types/shop';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    ClipboardCheck,
    ShieldCheck,
    WalletCards,
} from 'lucide-vue-next';
const props = defineProps<{
    lines: CartLine[];
    wholesale: boolean;
    token: string;
    paymentOptions: PaymentOption[];
    shippingAmount: number;
    taxBasisPoints: number;
}>();
const page = usePage<ShopSharedProps>();
const user = page.props.auth.user;
const form = useForm({
    name: user?.name || '',
    email: user?.email || '',
    phone: '',
    address: '',
    token: props.token,
    payment_method: 'request',
});
const subtotal = props.lines.reduce(
    (sum, l) => sum + l.unit_price * l.quantity,
    0,
);
const tax = Math.floor((subtotal * props.taxBasisPoints + 5000) / 10000);
const total = subtotal + props.shippingAmount + tax;
</script>
<template>
    <Head title="Checkout" /><ShopLayout
        ><section class="wrap inner-page">
            <div class="checkout-steps">
                <span>01 · Your bag</span
                ><span class="active">02 · Checkout</span
                ><span>03 · Your order</span>
            </div>
            <h1>Let’s make it yours.</h1>
            <div class="checkout-grid">
                <form
                    class="form-card"
                    @submit.prevent="form.post('/checkout')"
                >
                    <h2>Where should your next pair go?</h2>
                    <div class="checkout-field-grid">
                        <label
                            >Full name<input
                                v-model="form.name"
                                required
                                maxlength="150"
                                autocomplete="name"
                            /><span class="field-error">{{
                                form.errors.name
                            }}</span></label
                        ><label
                            >Email address<input
                                v-model="form.email"
                                required
                                type="email"
                                maxlength="254"
                                autocomplete="email"
                            /><span class="field-error">{{
                                form.errors.email
                            }}</span></label
                        >
                    </div>
                    <label
                        >Phone number<input
                            v-model="form.phone"
                            type="tel"
                            required
                            maxlength="40"
                            autocomplete="tel"
                            placeholder="Your contact number"
                        /><span class="field-error">{{
                            form.errors.phone
                        }}</span></label
                    ><label
                        >Delivery address<textarea
                            v-model="form.address"
                            rows="3"
                            required
                            maxlength="1000"
                            autocomplete="street-address"
                            placeholder="Street, area, city, and a nearby landmark"
                        /><span class="field-error">{{
                            form.errors.address
                        }}</span></label
                    >
                    <h2 class="payment-heading">How would you like to pay?</h2>
                    <label
                        class="payment-choice"
                        :class="{ selected: form.payment_method === 'request' }"
                        ><input
                            v-model="form.payment_method"
                            type="radio"
                            value="request"
                        /><ClipboardCheck :size="20" /><span
                            ><strong>Request confirmation</strong
                            ><small
                                >Place your order request. Arrange payment with
                                our team.</small
                            ></span
                        ></label
                    ><label
                        v-for="option in paymentOptions"
                        :key="option.id"
                        class="payment-choice"
                        :class="{ selected: form.payment_method === option.id }"
                        ><input
                            v-model="form.payment_method"
                            type="radio"
                            :value="option.id"
                        /><WalletCards :size="20" /><span
                            ><strong
                                >{{ option.name }}
                                <span
                                    v-if="option.sandbox"
                                    class="sandbox-badge"
                                    >Test mode</span
                                ></strong
                            ><small
                                >Continue to {{ option.name }} to complete
                                payment securely.</small
                            ></span
                        ></label
                    >
                    <p v-if="!paymentOptions.length" class="checkout-help">
                        Online payment is currently unavailable. You can still
                        place an order request.
                    </p>
                    <p
                        v-if="form.errors.payment_method"
                        class="field-error"
                        role="alert"
                    >
                        {{ form.errors.payment_method }}
                    </p>
                    <p
                        v-if="page.props.errors.cart"
                        class="field-error"
                        role="alert"
                    >
                        {{ page.props.errors.cart }}
                    </p>
                    <button
                        class="button dark full"
                        :disabled="form.processing"
                    >
                        {{
                            form.processing
                                ? 'Preparing your order…'
                                : form.payment_method === 'request'
                                  ? 'Place order request'
                                  : 'Continue to payment'
                        }}<ArrowRight :size="16" />
                    </button>
                    <p class="checkout-help">
                        {{
                            form.payment_method === 'request'
                                ? 'No payment is collected when you submit a request.'
                                : 'Your order is marked paid only after the provider confirms it.'
                        }}
                    </p>
                    <Link href="/cart" class="back-shopping"
                        ><ArrowLeft :size="14" />Back to your bag</Link
                    >
                </form>
                <aside class="summary-card">
                    <h2>{{ wholesale ? 'Wholesale order' : 'Your order' }}</h2>
                    <div
                        v-for="line in lines"
                        :key="line.id"
                        class="summary-line"
                    >
                        <span
                            >{{ line.name
                            }}<small
                                >EU {{ line.size }} × {{ line.quantity }}</small
                            ></span
                        ><strong>{{
                            money(
                                line.unit_price * line.quantity,
                                page.props.currency,
                            )
                        }}</strong>
                    </div>
                    <div class="summary-line">
                        <span>Subtotal</span
                        ><span>{{ money(subtotal, page.props.currency) }}</span>
                    </div>
                    <div class="summary-line">
                        <span>Shipping</span
                        ><span>{{
                            money(shippingAmount, page.props.currency)
                        }}</span>
                    </div>
                    <div class="summary-line">
                        <span>Tax</span
                        ><span>{{ money(tax, page.props.currency) }}</span>
                    </div>
                    <div class="summary-line summary-total">
                        <span>Total</span
                        ><strong>{{
                            money(total, page.props.currency)
                        }}</strong>
                    </div>
                    <div class="summary-note">
                        <ShieldCheck :size="17" /><span
                            >Prices and stock are checked securely when you
                            place your order.</span
                        >
                    </div>
                </aside>
            </div>
        </section></ShopLayout
    >
</template>
