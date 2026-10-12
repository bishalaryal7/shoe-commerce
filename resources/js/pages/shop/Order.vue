<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { type CustomerOrder, type PaymentOption } from '@/types/shop';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Check,
    Clock3,
    Copy,
    CreditCard,
    PackageCheck,
    RefreshCw,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
const { t, label, num, date, money } = useLocale();
const props = defineProps<{
    order: CustomerOrder;
    paymentOptions: PaymentOption[];
    accessUrl: string | null;
    resumePayment: { url: string; provider: string; sandbox: boolean } | null;
    canRequestReturn: boolean;
    returnWindowDays: number;
    returnRequests: {
        id: number;
        item: { name: string; size: string; quantity: number } | null;
        action: string;
        reason: string;
        details: string | null;
        status: string;
        staff_note: string | null;
        created_at: string;
    }[];
}>();
const page = usePage();
const form = useForm({ provider: 'khalti', access_url: props.accessUrl });
const check = useForm({ access_url: props.accessUrl });
const copied = ref(false);
const cancelForm = useForm({ access_url: props.accessUrl });
const returnForm = useForm({
    order_item_id: props.order.items[0]?.id ?? 0,
    action: 'exchange',
    reason: 'wrong_size',
    details: '',
});
const confirmCancel = ref(false);
const canCancel = computed(
    () =>
        props.order.status === 'pending_confirmation' &&
        props.order.payment_status === 'unpaid',
);
const paid = computed(() => props.order.payment_status === 'paid');
const stage = computed(() =>
    props.order.status === 'fulfilled'
        ? 4
        : props.order.status === 'shipped'
          ? 3
          : props.order.status === 'confirmed'
            ? 2
            : 1,
);
async function copy() {
    if (!props.accessUrl) return;
    try {
        await navigator.clipboard.writeText(props.accessUrl);
        copied.value = true;
    } catch {
        copied.value = false;
    }
}
function pay(provider: 'khalti' | 'paypay') {
    form.provider = provider;
    form.post('/orders/' + props.order.reference + '/pay', {
        preserveScroll: true,
    });
}
function requestReturn() {
    returnForm.post('/orders/' + props.order.reference + '/returns', {
        preserveScroll: true,
        onSuccess: () => returnForm.reset('details'),
    });
}
</script>
<template>
    <Head :title="t('Your order')" /><ShopLayout
        ><section class="wrap inner-page">
            <span class="eyebrow"> {{ t('YOUR NEXT STEP') }} </span>
            <h1>
                {{
                    order.status === 'cancelled'
                        ? t('Order cancelled.')
                        : paid
                          ? t('You’re all set.')
                          : t('Your order is in.')
                }}
            </h1>
            <div class="order-overview">
                <span class="status-pill" :data-status="order.status">{{
                    label(order.status)
                }}</span
                ><span class="status-pill" :data-status="order.payment_status">
                    {{ t('Payment:') }} {{ label(order.payment_status) }}</span
                ><span>{{ date(order.created_at) }}</span>
            </div>
            <div v-if="order.status !== 'cancelled'" class="order-timeline">
                <div
                    v-for="(step, index) in [
                        t('Order received'),
                        t('Order confirmed'),
                        t('Order shipped'),
                        t('Delivered'),
                    ]"
                    :key="step"
                    :class="{ done: stage > index }"
                >
                    <span
                        ><Check v-if="stage > index" :size="16" /><Clock3
                            v-else
                            :size="16" /></span
                    ><strong>{{ step }}</strong>
                </div>
            </div>
            <div class="checkout-grid">
                <div>
                    <div class="form-card">
                        <h2>{{ t('Your order details') }}</h2>
                        <p class="order-reference">{{ order.reference }}</p>
                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="summary-line"
                        >
                            <span
                                ><strong>{{ t(item.name) }}</strong
                                ><small>
                                    {{ t('EU') }} {{ num(item.size) }} ·
                                    {{ t(item.color) }} ·
                                    {{ num(item.quantity) }} {{ t('pair(s)') }}
                                </small></span
                            ><strong>{{
                                money(
                                    item.unit_price * item.quantity,
                                    order.currency,
                                )
                            }}</strong>
                        </div>
                        <div class="delivery-details">
                            <div>
                                <span class="eyebrow">
                                    {{ t('DELIVER TO') }}
                                </span>
                                <h3>{{ order.name }}</h3>
                                <p>{{ order.address }}</p>
                                <p>{{ order.phone }}</p>
                            </div>
                            <div>
                                <span class="eyebrow">
                                    {{ t('ORDER UPDATES') }}
                                </span>
                                <p>{{ order.email }}</p>
                                <p class="muted">
                                    {{
                                        t(
                                            'Keep your private tracking link to check this order later.',
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                        <button
                            v-if="accessUrl"
                            class="button outline"
                            @click="copy()"
                        >
                            <Copy :size="15" />{{
                                copied
                                    ? t('Tracking link copied')
                                    : t('Copy tracking link')
                            }}</button
                        ><a
                            v-if="accessUrl"
                            :href="accessUrl"
                            class="text-button tracking-fallback"
                        >
                            {{ t('Open private tracking link') }}
                        </a>
                    </div>
                    <div
                        v-if="order.tracking_number"
                        class="delivery-details tracking-details"
                    >
                        <div>
                            <span class="eyebrow">{{
                                t('DELIVERY TRACKING')
                            }}</span>
                            <h3>{{ order.tracking_carrier }}</h3>
                            <p>
                                {{ t('Tracking number') }}:
                                <strong>{{ order.tracking_number }}</strong>
                            </p>
                        </div>
                        <div>
                            <span class="eyebrow">{{ t('LAST UPDATE') }}</span>
                            <p>{{ label(order.status) }}</p>
                            <p v-if="order.shipped_at">
                                {{ date(order.shipped_at) }}
                            </p>
                        </div>
                    </div>
                    <Link href="/" class="back-shopping">
                        {{ t('Keep exploring') }} <ArrowRight :size="14"
                    /></Link>
                </div>
                <aside class="summary-card">
                    <h2>{{ t('Order total') }}</h2>
                    <div class="summary-line">
                        <span>{{ t('Subtotal') }}</span
                        ><strong>{{
                            money(order.subtotal, order.currency)
                        }}</strong>
                    </div>
                    <div v-if="order.discount_amount" class="summary-line">
                        <span
                            >{{ t('Discount') }}
                            <small>{{ order.promotion_code }}</small></span
                        ><strong
                            >−{{
                                money(order.discount_amount, order.currency)
                            }}</strong
                        >
                    </div>
                    <div class="summary-line">
                        <span>{{ t('Shipping') }}</span
                        ><span>{{
                            money(order.shipping_amount, order.currency)
                        }}</span>
                    </div>
                    <div class="summary-line">
                        <span>{{ t('Tax') }}</span
                        ><span>{{
                            money(order.tax_amount, order.currency)
                        }}</span>
                    </div>
                    <div class="summary-line summary-total">
                        <span>{{ t('Total') }}</span
                        ><strong>{{
                            money(order.total, order.currency)
                        }}</strong>
                    </div>
                    <p
                        v-if="
                            order.status === 'cancelled' &&
                            order.payment_status === 'unpaid'
                        "
                    >
                        {{ t('This order is cancelled. No payment is due.') }}
                    </p>
                    <p v-else-if="paid" class="payment-confirmation">
                        <Check :size="16" />
                        {{ t('Payment verified. Thank you.') }}
                    </p>
                    <p v-else-if="order.payment_status === 'refunded'">
                        {{
                            t('Your payment has been refunded by the provider.')
                        }}
                    </p>
                    <p v-else-if="order.payment_status === 'review'">
                        {{
                            t(
                                'Our team needs to review this payment. Please don’t pay again while it is being checked.',
                            )
                        }}
                    </p>
                    <p v-else-if="order.payment_status === 'pending'">
                        {{
                            t(
                                'We’re waiting for the payment provider. If you already paid, check the payment status before trying again.',
                            )
                        }}
                    </p>
                    <p
                        v-else-if="
                            order.payment_method === 'cod' &&
                            order.status !== 'cancelled'
                        "
                        class="payment-confirmation"
                    >
                        {{
                            t(
                                'Cash on delivery: pay :amount when your shoes arrive.',
                                { amount: money(order.total, order.currency) },
                            )
                        }}
                    </p>
                    <p v-else>
                        {{
                            t(
                                'No payment has been recorded yet. Our team will confirm your order and payment arrangements.',
                            )
                        }}
                    </p>
                    <p
                        v-for="(error, key) in page.props.errors"
                        :key="key"
                        class="field-error"
                        role="alert"
                    >
                        {{ t(error) }}
                    </p>
                    <a
                        v-if="resumePayment"
                        :href="resumePayment.url"
                        class="button dark full"
                        ><CreditCard :size="16" />
                        {{
                            t('Continue :provider checkout', {
                                provider: label(resumePayment.provider),
                            })
                        }}
                        <span
                            v-if="resumePayment.sandbox"
                            class="sandbox-badge"
                        >
                            {{ t('Test') }}
                        </span></a
                    >
                    <button
                        v-for="option in paymentOptions"
                        :key="option.id"
                        class="button dark full"
                        :disabled="form.processing"
                        @click="pay(option.id)"
                    >
                        <CreditCard :size="16" /> {{ t('Pay with') }}
                        {{ t(option.name)
                        }}<span v-if="option.sandbox" class="sandbox-badge">
                            {{ t('Test') }}
                        </span></button
                    ><button
                        v-if="
                            ['khalti', 'paypay'].includes(
                                order.payment_method,
                            ) &&
                            ['pending', 'review'].includes(order.payment_status)
                        "
                        class="button full outline"
                        :disabled="check.processing"
                        @click="
                            check.post(
                                '/orders/' + order.reference + '/verify',
                                { preserveScroll: true },
                            )
                        "
                    >
                        <RefreshCw :size="14" />{{
                            check.processing
                                ? t('Checking…')
                                : t('Check payment status')
                        }}
                    </button>
                    <div v-if="canCancel" class="summary-note">
                        <div v-if="confirmCancel">
                            <p>
                                {{
                                    t(
                                        'Cancel this order and release your reserved shoes?',
                                    )
                                }}
                            </p>
                            <button
                                class="button full outline"
                                :disabled="cancelForm.processing"
                                @click="
                                    cancelForm.post(
                                        '/orders/' +
                                            order.reference +
                                            '/cancel',
                                        {
                                            preserveScroll: true,
                                            onSuccess: () =>
                                                (confirmCancel = false),
                                        },
                                    )
                                "
                            >
                                {{
                                    cancelForm.processing
                                        ? t('Cancelling…')
                                        : t('Yes, cancel order')
                                }}
                            </button>
                            <button
                                class="text-button"
                                :disabled="cancelForm.processing"
                                @click="confirmCancel = false"
                            >
                                {{ t('Keep my order') }}
                            </button>
                        </div>
                        <button
                            v-else
                            class="text-button"
                            @click="confirmCancel = true"
                        >
                            {{ t('Cancel unpaid order') }}
                        </button>
                    </div>
                    <div class="summary-note">
                        <PackageCheck :size="17" /><span>
                            {{
                                t(
                                    'You can follow your order’s progress here anytime.',
                                )
                            }}
                        </span>
                    </div>
                </aside>
            </div>
            <section
                v-if="canRequestReturn || returnRequests.length"
                class="return-center"
            >
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">{{
                            t('AFTER-SALES SUPPORT')
                        }}</span>
                        <h2>{{ t('Returns and exchanges') }}</h2>
                    </div>
                </div>
                <p>
                    {{
                        t(
                            'Eligible delivered orders can request an exchange or refund within :days days of delivery.',
                            { days: num(returnWindowDays) },
                        )
                    }}
                </p>
                <form
                    v-if="canRequestReturn"
                    class="return-request-form"
                    @submit.prevent="requestReturn"
                >
                    <label
                        >{{ t('Shoe to return') }}
                        <select
                            v-model.number="returnForm.order_item_id"
                            required
                        >
                            <option
                                v-for="item in order.items"
                                :key="item.id"
                                :value="item.id"
                            >
                                {{ t(item.name) }} · {{ t('EU') }}
                                {{ num(item.size) }} · {{ num(item.quantity) }}
                                {{ t('pair(s)') }}
                            </option>
                        </select>
                    </label>
                    <label
                        >{{ t('What would you like?') }}
                        <select v-model="returnForm.action" required>
                            <option value="exchange">
                                {{ t('Exchange for another size or pair') }}
                            </option>
                            <option value="refund">
                                {{ t('Ask about a refund') }}
                            </option>
                        </select>
                    </label>
                    <label
                        >{{ t('Reason for your request') }}
                        <select v-model="returnForm.reason" required>
                            <option value="wrong_size">
                                {{ t('Wrong size or fit') }}
                            </option>
                            <option value="defective">
                                {{ t('Item arrived damaged or defective') }}
                            </option>
                            <option value="wrong_item">
                                {{ t('I received a different item') }}
                            </option>
                            <option value="changed_mind">
                                {{ t('I changed my mind') }}
                            </option>
                            <option value="other">
                                {{ t('Another reason') }}
                            </option>
                        </select>
                    </label>
                    <label class="return-details"
                        >{{ t('More details (optional)') }}
                        <textarea
                            v-model="returnForm.details"
                            rows="3"
                            maxlength="1000"
                            :placeholder="t('Tell our team what would help.')"
                        />
                    </label>
                    <p
                        v-for="(error, key) in returnForm.errors"
                        :key="key"
                        class="field-error"
                        role="alert"
                    >
                        {{ t(error) }}
                    </p>
                    <button
                        class="button dark"
                        :disabled="returnForm.processing"
                    >
                        {{
                            returnForm.processing
                                ? t('Sending…')
                                : t('Send return request')
                        }}
                    </button>
                    <small>{{
                        t(
                            'Our team will review your request and message you with next steps. This does not issue a refund automatically.',
                        )
                    }}</small>
                </form>
                <div v-if="returnRequests.length" class="return-request-list">
                    <article
                        v-for="request in returnRequests"
                        :key="request.id"
                        class="return-request-card"
                    >
                        <div class="section-heading">
                            <strong
                                >{{ t(request.item?.name ?? 'Order item') }} ·
                                {{
                                    t(
                                        request.action === 'refund'
                                            ? 'Refund request'
                                            : 'Exchange request',
                                    )
                                }}</strong
                            ><span
                                class="status-pill"
                                :data-status="request.status"
                                >{{ label(request.status) }}</span
                            >
                        </div>
                        <p>
                            {{ t(request.reason)
                            }}<span v-if="request.details">
                                · {{ request.details }}</span
                            >
                        </p>
                        <p v-if="request.staff_note" class="return-staff-note">
                            <strong>{{ t('Message from our team') }}</strong
                            ><br />{{ request.staff_note }}
                        </p>
                    </article>
                </div>
            </section>
        </section></ShopLayout
    >
</template>
