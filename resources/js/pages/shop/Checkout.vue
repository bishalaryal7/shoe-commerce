<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import { validateContact } from '@/composables/validateContact';
import ShopLayout from '@/layouts/ShopLayout.vue';
import {
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
import { nextTick } from 'vue';
const { t, num, money } = useLocale();
const props = defineProps<{
    lines: CartLine[];
    wholesale: boolean;
    token: string;
    paymentOptions: PaymentOption[];
    shippingAmount: number;
    taxBasisPoints: number;
    cashOnDelivery?: boolean;
}>();
const page = usePage<ShopSharedProps>();
const user = page.props.auth.user;
const form = useForm({
    name: user?.name || '',
    email: user?.email || '',
    phone: user?.phone || '',
    address: '',
    token: props.token,
    payment_method: props.cashOnDelivery
        ? 'cod'
        : (props.paymentOptions[0]?.id ?? 'request'),
});
function submit() {
    form.clearErrors();
    const errors = validateContact(form, page.props.locale === 'ne');
    if (Object.keys(errors).length) {
        form.setError(errors);
        nextTick(() =>
            document
                .getElementById('checkout-' + Object.keys(errors)[0])
                ?.focus(),
        );
        return;
    }
    form.post('/checkout', { preserveScroll: true });
}
const subtotal = props.lines.reduce(
    (sum, l) => sum + l.unit_price * l.quantity,
    0,
);
const tax = Math.floor((subtotal * props.taxBasisPoints + 5000) / 10000);
const total = subtotal + props.shippingAmount + tax;
</script>
<template>
    <Head :title="t('Checkout')" /><ShopLayout
        ><section class="wrap inner-page">
            <div class="checkout-steps">
                <span> {{ t('01 · Your bag') }} </span
                ><span class="active"> {{ t('02 · Checkout') }} </span
                ><span> {{ t('03 · Your order') }} </span>
            </div>
            <h1>{{ t('Let’s make it yours.') }}</h1>
            <div class="checkout-grid">
                <form class="form-card" novalidate @submit.prevent="submit">
                    <h2>{{ t('Where should your next pair go?') }}</h2>
                    <div class="checkout-field-grid">
                        <label
                            >{{ t('Full name')
                            }}<input
                                id="checkout-name"
                                v-model="form.name"
                                :aria-invalid="!!form.errors.name"
                                :aria-describedby="'checkout-name-error'"
                                required
                                maxlength="150"
                                autocomplete="name"
                            /><span
                                id="checkout-name-error"
                                class="field-error"
                                role="alert"
                                >{{ form.errors.name }}</span
                            ></label
                        ><label
                            >{{ t('Email address')
                            }}<input
                                id="checkout-email"
                                v-model="form.email"
                                :aria-invalid="!!form.errors.email"
                                :aria-describedby="'checkout-email-error'"
                                required
                                type="email"
                                maxlength="254"
                                autocomplete="email"
                            /><span
                                id="checkout-email-error"
                                class="field-error"
                                role="alert"
                                >{{ form.errors.email }}</span
                            ></label
                        >
                    </div>
                    <label
                        >{{ t('Phone number')
                        }}<input
                            id="checkout-phone"
                            v-model="form.phone"
                            :aria-invalid="!!form.errors.phone"
                            :aria-describedby="'checkout-phone-error'"
                            type="tel"
                            required
                            maxlength="40"
                            autocomplete="tel"
                            :placeholder="t('Your contact number')"
                        /><span
                            id="checkout-phone-error"
                            class="field-error"
                            role="alert"
                            >{{ form.errors.phone }}</span
                        ></label
                    ><label
                        >{{ t('Delivery address')
                        }}<textarea
                            id="checkout-address"
                            v-model="form.address"
                            :aria-invalid="!!form.errors.address"
                            :aria-describedby="'checkout-address-error'"
                            rows="3"
                            required
                            maxlength="1000"
                            autocomplete="street-address"
                            :placeholder="
                                t('Street, area, city, and a nearby landmark')
                            "
                        /><span
                            id="checkout-address-error"
                            class="field-error"
                            role="alert"
                            >{{ form.errors.address }}</span
                        ></label
                    >
                    <p
                        v-if="form.errors.token"
                        class="field-error"
                        role="alert"
                    >
                        {{ form.errors.token }}
                    </p>
                    <h2 class="payment-heading">
                        {{ t('How would you like to pay?') }}
                    </h2>
                    <label
                        v-if="cashOnDelivery"
                        class="payment-choice"
                        :class="{ selected: form.payment_method === 'cod' }"
                    >
                        <input
                            v-model="form.payment_method"
                            type="radio"
                            value="cod"
                            name="payment_method"
                        />
                        <WalletCards :size="20" /><span
                            ><strong>{{ t('Cash on delivery') }}</strong
                            ><small>{{
                                t(
                                    'Pay in cash when your shoes arrive. Our team will call to confirm delivery.',
                                )
                            }}</small></span
                        >
                    </label>
                    <label
                        class="payment-choice"
                        :class="{ selected: form.payment_method === 'request' }"
                        ><input
                            v-model="form.payment_method"
                            type="radio"
                            name="payment_method"
                            value="request"
                        /><ClipboardCheck :size="20" /><span
                            ><strong>{{ t('Request confirmation') }}</strong
                            ><small>
                                {{
                                    t(
                                        'Place your order request. Arrange payment with our team.',
                                    )
                                }}
                            </small></span
                        ></label
                    ><label
                        v-for="option in paymentOptions"
                        :key="option.id"
                        class="payment-choice"
                        :class="{ selected: form.payment_method === option.id }"
                        ><input
                            v-model="form.payment_method"
                            type="radio"
                            name="payment_method"
                            :value="option.id"
                        /><WalletCards :size="20" /><span
                            ><strong
                                >{{ t(option.name) }}
                                <span
                                    v-if="option.sandbox"
                                    class="sandbox-badge"
                                >
                                    {{ t('Test mode') }}
                                </span></strong
                            ><small>
                                {{
                                    t(
                                        'Continue to :provider to complete payment securely.',
                                        { provider: t(option.name) },
                                    )
                                }}
                            </small></span
                        ></label
                    >
                    <p v-if="!paymentOptions.length" class="checkout-help">
                        {{
                            t(
                                cashOnDelivery
                                    ? 'Online payment is currently unavailable. Cash on delivery is available.'
                                    : 'Online payment is currently unavailable. You can still place an order request.',
                            )
                        }}
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
                                ? t('Preparing your order…')
                                : form.payment_method === 'cod'
                                  ? t('Place order · pay on delivery')
                                  : form.payment_method === 'request'
                                    ? t('Place order request')
                                    : t('Continue to payment')
                        }}<ArrowRight :size="16" />
                    </button>
                    <p class="checkout-help">
                        {{
                            form.payment_method === 'cod'
                                ? t(
                                      'Pay the displayed total on delivery. No online payment is taken now.',
                                  )
                                : form.payment_method === 'request'
                                  ? t(
                                        'No payment is collected when you submit a request.',
                                    )
                                  : t(
                                        'Your order is marked paid only after the provider confirms it.',
                                    )
                        }}
                    </p>
                    <Link href="/cart" class="back-shopping"
                        ><ArrowLeft :size="14" />{{
                            t('Back to your bag')
                        }}</Link
                    >
                </form>
                <aside class="summary-card">
                    <h2>
                        {{ wholesale ? t('Wholesale order') : t('Your order') }}
                    </h2>
                    <div
                        v-for="line in lines"
                        :key="line.id"
                        class="summary-line"
                    >
                        <span
                            >{{ t(line.name)
                            }}<small>
                                {{ t('EU') }} {{ num(line.size) }} ×
                                {{ num(line.quantity) }}</small
                            ></span
                        ><strong>{{
                            money(
                                line.unit_price * line.quantity,
                                page.props.currency,
                            )
                        }}</strong>
                    </div>
                    <div class="summary-line">
                        <span>{{ t('Subtotal') }}</span
                        ><span>{{ money(subtotal, page.props.currency) }}</span>
                    </div>
                    <div class="summary-line">
                        <span>{{ t('Shipping') }}</span
                        ><span>{{
                            money(shippingAmount, page.props.currency)
                        }}</span>
                    </div>
                    <div class="summary-line">
                        <span>{{ t('Tax') }}</span
                        ><span>{{ money(tax, page.props.currency) }}</span>
                    </div>
                    <div class="summary-line summary-total">
                        <span>{{ t('Total') }}</span
                        ><strong>{{
                            money(total, page.props.currency)
                        }}</strong>
                    </div>
                    <div class="summary-note">
                        <ShieldCheck :size="17" /><span>
                            {{
                                t(
                                    'Prices and stock are checked securely when you place your order.',
                                )
                            }}
                        </span>
                    </div>
                </aside>
            </div>
        </section></ShopLayout
    >
</template>
