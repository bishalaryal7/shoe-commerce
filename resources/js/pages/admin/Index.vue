<script setup lang="ts">
import ProductCreate from '@/components/ProductCreate.vue';
import ProductEditor from '@/components/ProductEditor.vue';
import ProductPhoto from '@/components/ProductPhoto.vue';
import { useLocale } from '@/composables/useLocale';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type Product } from '@/types/shop';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
const { t, label, num, date, money, paginationLabel } = useLocale();
type Order = {
    id: number;
    reference: string;
    name: string;
    email: string;
    phone: string;
    address: string;
    status: string;
    channel: string;
    currency: string;
    subtotal: number;
    total: number;
    payment_status: string;
    payment_method: string;
    tracking_carrier: string | null;
    tracking_number: string | null;
    events: {
        id: number;
        event: string;
        created_at: string;
        actor: { name: string } | null;
        details: Record<string, string | number> | null;
    }[];
    items: {
        id: number;
        name: string;
        size: string;
        quantity: number;
        sku: string;
    }[];
};
type Application = {
    id: number;
    company: string;
    phone: string;
    message: string;
    status: string;
    user: { name: string; email: string };
};
type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
};
const props = defineProps<{
    team: { id: number; name: string; email: string }[];
    products: Product[];
    orders: Paginated<Order>;
    filters: { search?: string; status?: string; payment?: string };
    applications: Application[];
    payments: Paginated<{
        id: number;
        reference: string;
        provider: string;
        provider_id: string | null;
        amount: number;
        currency: string;
        status: string;
        sandbox: boolean;
    }>;
    integrations: {
        signIn?: { google: boolean; facebook: boolean; phone: boolean };
        cashOnDelivery?: boolean;
        khalti: boolean;
        paypay: boolean;
        mail: string;
        pendingEmails: number;
    };
    stats: {
        orders: number;
        pending: number;
        stock: number;
        low_stock: number;
        applications: number;
        return_requests: number;
    };
    lowStockThreshold: number;
    promotions: {
        id: number;
        code: string;
        type: string;
        amount: number;
        minimum_subtotal: number;
        usage_limit: number | null;
        uses_count: number;
        channel: string;
        active: boolean;
    }[];
    questions: Paginated<{
        id: number;
        question: string;
        answer: string | null;
        product: { name: string; slug: string };
        user: { name: string } | null;
    }>;
    reviews: Paginated<{
        id: number;
        rating: number;
        title: string;
        body: string;
        seller_reply: string | null;
        user: { name: string; email: string };
        product: { name: string; slug: string };
    }>;
    conversations: Paginated<{
        id: number;
        subject: string;
        status: string;
        user: { name: string; email: string };
        messages: {
            id: number;
            body: string;
            is_staff: boolean;
            created_at: string;
            sender: { name: string };
        }[];
    }>;
    returnRequests: Paginated<{
        id: number;
        action: string;
        reason: string;
        details: string | null;
        status: string;
        staff_note: string | null;
        order: {
            reference: string;
            name: string;
            email: string;
            phone: string;
        };
        item: { name: string; size: string; quantity: number };
        user: { name: string; email: string };
    }>;
}>();
const page = usePage();
const requestedTab = new URL(page.url, 'http://eve.local').searchParams.get(
    'tab',
);
const tab = ref(
    [
        'Orders',
        'Returns',
        'Inventory',
        'Payments',
        'Partners',
        'Promotions',
        'Reviews',
        'Questions',
        'Messages',
        'Team',
    ].includes(requestedTab ?? '')
        ? requestedTab!
        : 'Orders',
);
const inventoryFile = ref<File | null>(null);
const filters = ref({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    payment: props.filters.payment ?? '',
});
function filterOrders() {
    router.get('/admin', filters.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
const busy = ref(false);
const teamEmail = ref('');
const removingMember = ref<number | null>(null);
function removeMember(id: number) {
    router.delete('/admin/team/' + id, {
        preserveScroll: true,
        onSuccess: () => (removingMember.value = null),
    });
}
const receipts = ref<Record<number, string>>({});
const shipping = ref<Record<number, { carrier: string; number: string }>>({});
const promotion = ref({
    code: '',
    type: 'percent',
    amount: 10,
    minimum_subtotal: 0,
    usage_limit: null as number | null,
    channel: 'all',
    starts_at: '',
    ends_at: '',
});
const answers = ref<Record<number, string>>({});
const reviewReplies = ref<Record<number, string>>({});
const customerReplies = ref<Record<number, string>>({});
const returnNotes = ref<Record<number, string>>({});
function post(url: string, data: Record<string, any> = {}) {
    busy.value = true;
    router.post(url, data, {
        preserveScroll: true,
        onFinish: () => (busy.value = false),
    });
}
function patch(url: string, data: Record<string, any>) {
    busy.value = true;
    router.patch(url, data, {
        preserveScroll: true,
        onFinish: () => (busy.value = false),
    });
}
function reviewReturn(
    id: number,
    status: 'approved' | 'declined' | 'completed',
) {
    patch('/admin/returns/' + id, {
        status,
        staff_note: returnNotes.value[id] ?? '',
    });
}
function shippingForm(id: number) {
    return (shipping.value[id] ??= { carrier: '', number: '' });
}
function createPromotion() {
    post('/admin/promotions', { ...promotion.value });
}
function stock(id: number, event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.checkValidity())
        patch('/admin/variants/' + id, { stock: Number(input.value) });
}
function importInventory() {
    if (!inventoryFile.value) return;
    busy.value = true;
    router.post(
        '/admin/inventory/import',
        { inventory_file: inventoryFile.value },
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => (inventoryFile.value = null),
            onFinish: () => (busy.value = false),
        },
    );
}
</script>
<template>
    <Head :title="t('Team dashboard')" /><AdminLayout v-model:tab="tab"
        ><section class="wrap inner-page admin-page">
            <div class="eyebrow">{{ t('TEAM WORKSPACE') }}</div>
            <h1>
                {{
                    tab === 'Orders'
                        ? t('Orders, at a glance.')
                        : tab === 'Returns'
                          ? t('Help customers find the right fit.')
                          : tab === 'Inventory'
                            ? t('Keep your shelves moving.')
                            : tab === 'Payments'
                              ? t('Every payment, accounted for.')
                              : tab === 'Promotions'
                                ? t('Offers that bring customers back.')
                                : tab === 'Reviews'
                                  ? t('Customer reviews, answered with care.')
                                  : tab === 'Messages'
                                    ? t(
                                          'Every customer conversation, in one place.',
                                      )
                                    : tab === 'Questions'
                                      ? t(
                                            'Help shoppers choose with confidence.',
                                        )
                                      : tab === 'Team'
                                        ? t('Your people. Your workspace.')
                                        : t('Good partners. Better business.')
                }}
            </h1>
            <p class="workspace-description">
                {{
                    tab === 'Orders'
                        ? t(
                              'Review requests and keep every next step on track.',
                          )
                        : tab === 'Returns'
                          ? t(
                                'Review exchange and refund requests, then keep customers updated on the next step.',
                            )
                          : tab === 'Inventory'
                            ? t(
                                  'Manage your collection, pricing, and available sizes.',
                              )
                            : tab === 'Payments'
                              ? t(
                                    'Check provider status and monitor your integrations.',
                                )
                              : tab === 'Promotions'
                                ? t(
                                      'Create discount codes for seasonal campaigns and wholesale orders.',
                                  )
                                : tab === 'Reviews'
                                  ? t(
                                        'Reply publicly to verified buyer feedback and let customers know their experience matters.',
                                    )
                                  : tab === 'Messages'
                                    ? t(
                                          'Reply privately to order, delivery, fit, and product questions from your customers.',
                                      )
                                    : tab === 'Questions'
                                      ? t(
                                            'Answer product questions and publish useful fit, material, and care advice.',
                                        )
                                      : tab === 'Team'
                                        ? t(
                                              'Manage access for registered team members.',
                                          )
                                        : t(
                                              'Review applications and manage your wholesale relationships.',
                                          )
                }}
            </p>
            <div class="stats-grid">
                <div
                    v-for="(value, key) in stats"
                    :key="key"
                    class="summary-card"
                >
                    <span>{{ label(key) }}</span
                    ><strong>{{ num(value) }}</strong>
                </div>
            </div>
            <p
                v-for="(error, key) in page.props.errors"
                :key="key"
                class="field-error"
                role="alert"
            >
                {{ t(error) }}
            </p>
            <div v-if="tab === 'Orders'">
                <form class="order-actions" @submit.prevent="filterOrders">
                    <label>
                        {{ t('Find an order') }}
                        <input
                            v-model="filters.search"
                            maxlength="150"
                            :placeholder="
                                t('Reference, customer, email or phone')
                            "
                    /></label>
                    <label>
                        {{ t('Order status') }}
                        <select v-model="filters.status">
                            <option value="">{{ t('All statuses') }}</option>
                            <option
                                v-for="status in [
                                    'pending_confirmation',
                                    'confirmed',
                                    'shipped',
                                    'fulfilled',
                                    'cancelled',
                                ]"
                                :key="status"
                                :value="status"
                            >
                                {{ label(status) }}
                            </option>
                        </select></label
                    >
                    <label>
                        {{ t('Payment') }}
                        <select v-model="filters.payment">
                            <option value="">{{ t('All payments') }}</option>
                            <option
                                v-for="status in [
                                    'unpaid',
                                    'pending',
                                    'paid',
                                    'review',
                                    'refunded',
                                ]"
                                :key="status"
                                :value="status"
                            >
                                {{ label(status) }}
                            </option>
                        </select></label
                    >
                    <button class="button dark">{{ t('Search') }}</button>
                    <button
                        type="button"
                        class="text-button"
                        @click="
                            filters = { search: '', status: '', payment: '' };
                            filterOrders();
                        "
                    >
                        {{ t('Reset') }}
                    </button>
                </form>
                <p class="muted">
                    {{ num(orders.total) }}
                    {{
                        t(
                            'matching orders. Fulfillment requires verified online payment or a recorded offline receipt.',
                        )
                    }}
                </p>
                <div v-if="!orders.data.length" class="empty-state">
                    {{ t('No order requests yet.') }}
                </div>
                <article
                    v-for="order in orders.data"
                    :key="order.id"
                    class="admin-card"
                >
                    <div class="section-heading">
                        <div>
                            <h3>{{ order.name }}</h3>
                            <p>{{ order.email }} · {{ order.phone }}</p>
                        </div>
                        <span class="status-pill">{{
                            label(order.status)
                        }}</span>
                    </div>
                    <p>{{ order.address }}</p>
                    <small
                        >{{ order.reference }} ·
                        {{ label(order.channel) }}</small
                    >
                    <div
                        v-for="item in order.items"
                        :key="item.id"
                        class="summary-line"
                    >
                        <span
                            >{{ t(item.name) }} {{ t('· EU') }}
                            {{ num(item.size) }}</span
                        ><span
                            >{{ num(item.quantity) }} {{ t('pairs ·') }}
                            {{ item.sku }}</span
                        >
                    </div>
                    <details class="order-audit">
                        <summary>{{ t('Order history') }}</summary>
                        <div
                            v-for="event in order.events"
                            :key="event.id"
                            class="summary-line"
                        >
                            <span
                                >{{ label(event.event)
                                }}<small
                                    >{{
                                        event.actor?.name ??
                                        t('System / customer')
                                    }}
                                    ·
                                    {{ date(event.created_at, true) }}</small
                                ><small v-if="event.details">{{
                                    Object.entries(event.details)
                                        .map(
                                            ([key, value]) =>
                                                label(key) +
                                                ': ' +
                                                (typeof value === 'number'
                                                    ? num(value)
                                                    : label(value)),
                                        )
                                        .join(' · ')
                                }}</small></span
                            >
                        </div>
                        <p v-if="!order.events.length" class="muted">
                            {{ t('No recorded events for this older order.') }}
                        </p>
                    </details>
                    <p class="muted">
                        {{ t('Payment:') }} {{ label(order.payment_status) }} ·
                        {{ label(order.payment_method) }}
                    </p>
                    <form
                        v-if="
                            ['request', 'cod'].includes(order.payment_method) &&
                            order.payment_status === 'unpaid' &&
                            ['pending_confirmation', 'confirmed'].includes(
                                order.status,
                            )
                        "
                        class="order-actions"
                        @submit.prevent="
                            post(
                                '/admin/orders/' +
                                    order.id +
                                    '/offline-payment',
                                { reference: receipts[order.id] },
                            )
                        "
                    >
                        <label>
                            {{ t('Receipt or bank reference') }}
                            <input
                                v-model="receipts[order.id]"
                                required
                                maxlength="150"
                                :placeholder="t('Verified receipt reference')"
                        /></label>
                        <button class="button outline" :disabled="busy">
                            {{ t('Record received payment') }}
                        </button>
                    </form>
                    <div class="order-actions">
                        <strong>{{ money(order.total, order.currency) }}</strong
                        ><button
                            v-if="order.status === 'pending_confirmation'"
                            class="button dark"
                            :disabled="busy"
                            @click="
                                patch('/admin/orders/' + order.id, {
                                    status: 'confirmed',
                                })
                            "
                        >
                            {{ t('Confirm') }}
                        </button>
                        <form
                            v-if="
                                order.status === 'confirmed' &&
                                order.payment_status === 'paid'
                            "
                            class="order-actions"
                            @submit.prevent="
                                patch(
                                    '/admin/orders/' + order.id + '/shipping',
                                    {
                                        status: 'shipped',
                                        tracking_carrier: shippingForm(order.id)
                                            .carrier,
                                        tracking_number: shippingForm(order.id)
                                            .number,
                                    },
                                )
                            "
                        >
                            <label
                                >{{ t('Delivery carrier')
                                }}<input
                                    v-model="shippingForm(order.id).carrier"
                                    required
                                    maxlength="100"
                                    :placeholder="t('Carrier name')"
                            /></label>
                            <label
                                >{{ t('Tracking number')
                                }}<input
                                    v-model="shippingForm(order.id).number"
                                    required
                                    maxlength="120"
                                    :placeholder="t('Enter tracking number')"
                            /></label>
                            <button class="button dark" :disabled="busy">
                                {{ t('Mark shipped and notify customer') }}
                            </button>
                        </form>
                        <button
                            v-if="order.status === 'shipped'"
                            class="button dark"
                            :disabled="busy"
                            @click="
                                patch(
                                    '/admin/orders/' + order.id + '/shipping',
                                    { status: 'fulfilled' },
                                )
                            "
                        >
                            {{ t('Mark delivered') }}</button
                        ><button
                            v-if="
                                ['pending_confirmation', 'confirmed'].includes(
                                    order.status,
                                ) &&
                                !['paid', 'pending', 'review'].includes(
                                    order.payment_status,
                                )
                            "
                            class="button outline"
                            :disabled="busy"
                            @click="
                                patch('/admin/orders/' + order.id, {
                                    status: 'cancelled',
                                })
                            "
                        >
                            {{ t('Cancel & restore stock') }}
                        </button>
                    </div>
                </article>
            </div>
            <nav
                v-if="tab === 'Orders' && orders.links.length > 3"
                class="pagination"
                :aria-label="t('Staff order pages')"
            >
                <template v-for="(link, i) in orders.links" :key="i"
                    ><Link
                        v-if="link.url"
                        :href="link.url"
                        :class="{ active: link.active }"
                        preserve-state
                        preserve-scroll
                        ><span v-text="paginationLabel(link.label)" /></Link
                    ><span v-else v-text="paginationLabel(link.label)"
                /></template>
            </nav>
            <div v-if="tab === 'Returns'">
                <p v-if="!returnRequests.data.length" class="empty-state">
                    {{ t('No return or exchange requests yet.') }}
                </p>
                <article
                    v-for="item in returnRequests.data"
                    :key="item.id"
                    class="admin-card return-admin-card"
                >
                    <div class="section-heading">
                        <div>
                            <h3>
                                {{ t(item.item.name) }} · {{ t('EU') }}
                                {{ num(item.item.size) }}
                            </h3>
                            <small
                                >{{ item.user.name }} ·
                                {{ item.user.email }}</small
                            >
                        </div>
                        <span class="status-pill" :data-status="item.status">{{
                            label(item.status)
                        }}</span>
                    </div>
                    <p>
                        <strong>{{
                            t(
                                item.action === 'refund'
                                    ? 'Refund request'
                                    : 'Exchange request',
                            )
                        }}</strong>
                        · {{ t(item.reason) }}
                    </p>
                    <p v-if="item.details">{{ item.details }}</p>
                    <p>
                        {{ t('Order') }} {{ item.order.reference }} ·
                        {{ item.order.phone }}
                    </p>
                    <p v-if="item.staff_note" class="return-staff-note">
                        <strong>{{ t('Previous team message') }}</strong
                        ><br />{{ item.staff_note }}
                    </p>
                    <div
                        v-if="
                            item.status === 'requested' ||
                            item.status === 'approved'
                        "
                        class="return-admin-controls"
                    >
                        <label
                            >{{ t('Message to customer (optional)')
                            }}<textarea
                                v-model="returnNotes[item.id]"
                                maxlength="1000"
                                :placeholder="
                                    t(
                                        'Explain the next step or what the customer should do.',
                                    )
                                "
                            />
                        </label>
                        <div
                            class="return-admin-actions"
                            v-if="item.status === 'requested'"
                        >
                            <button
                                class="button dark"
                                type="button"
                                :disabled="busy"
                                @click="reviewReturn(item.id, 'approved')"
                            >
                                {{ t('Approve request') }}
                            </button>
                            <button
                                class="button outline"
                                type="button"
                                :disabled="busy"
                                @click="reviewReturn(item.id, 'declined')"
                            >
                                {{ t('Decline request') }}
                            </button>
                        </div>
                        <button
                            v-else
                            class="button dark"
                            type="button"
                            :disabled="busy"
                            @click="reviewReturn(item.id, 'completed')"
                        >
                            {{ t('Mark completed') }}
                        </button>
                    </div>
                </article>
                <nav
                    v-if="returnRequests.links.length > 3"
                    class="pagination"
                    :aria-label="t('Return request pages')"
                >
                    <template v-for="(link, i) in returnRequests.links" :key="i"
                        ><Link
                            v-if="link.url"
                            :href="link.url"
                            :class="{ active: link.active }"
                            preserve-state
                            preserve-scroll
                            ><span v-text="paginationLabel(link.label)" /></Link
                        ><span v-else v-text="paginationLabel(link.label)"
                    /></template>
                </nav>
            </div>
            <div v-if="tab === 'Payments'">
                <div class="admin-card">
                    <h3>{{ t('Integrations') }}</h3>
                    <p>
                        {{ t('Cash on delivery') }}:
                        {{
                            integrations.cashOnDelivery
                                ? t('Enabled')
                                : t('Disabled')
                        }}
                    </p>
                    <p v-if="integrations.signIn">
                        {{ t('Google') }}:
                        {{
                            integrations.signIn.google
                                ? t('configured')
                                : t('needs credentials')
                        }}
                        · {{ t('Facebook') }}:
                        {{
                            integrations.signIn.facebook
                                ? t('configured')
                                : t('needs credentials')
                        }}
                        · {{ t('Phone sign-in') }}:
                        {{
                            integrations.signIn.phone
                                ? t('configured')
                                : t('needs credentials')
                        }}
                    </p>
                    <p class="muted">
                        {{
                            t(
                                'Provider credentials must be configured before online payments or social and phone sign-in become available.',
                            )
                        }}
                    </p>
                    <p>
                        {{ t('Khalti:') }}
                        {{
                            integrations.khalti
                                ? t('configured')
                                : t('needs credentials')
                        }}
                        {{ t('· PayPay:') }}
                        {{
                            integrations.paypay
                                ? t('configured')
                                : t('needs credentials')
                        }}
                    </p>
                    <p>
                        {{ t('Email transport:') }}
                        {{ label(integrations.mail) }} ·
                        {{ num(integrations.pendingEmails) }}
                        {{ t('unsent notifications') }}
                    </p>
                </div>
                <p class="muted">
                    {{ num(payments.total) }}
                    {{
                        t(
                            'attempts. Verification asks the provider for its authoritative status. Sandbox attempts do not represent live payments.',
                        )
                    }}
                </p>
                <article
                    v-for="payment in payments.data"
                    :key="payment.id"
                    class="admin-card"
                >
                    <h3>
                        {{ label(payment.provider) }} ·
                        {{ label(payment.status) }}
                    </h3>
                    <p>{{ payment.reference }}</p>
                    <p>
                        {{ money(payment.amount, payment.currency) }} ·
                        {{ payment.sandbox ? t('Sandbox') : t('Live') }}
                    </p>
                    <button
                        v-if="payment.provider_id"
                        class="button outline"
                        :disabled="busy"
                        @click="
                            post('/admin/payments/' + payment.id + '/verify')
                        "
                    >
                        {{ t('Verify with provider') }}
                    </button>
                    <p v-else class="muted">
                        {{
                            t(
                                'No provider session ID. Check the merchant dashboard before retrying or releasing stock.',
                            )
                        }}
                    </p>
                </article>
            </div>
            <nav
                v-if="tab === 'Payments' && payments.links.length > 3"
                class="pagination"
                :aria-label="t('Payment attempt pages')"
            >
                <template v-for="(link, i) in payments.links" :key="i"
                    ><Link
                        v-if="link.url"
                        :href="link.url"
                        :class="{ active: link.active }"
                        preserve-state
                        preserve-scroll
                        ><span v-text="paginationLabel(link.label)" /></Link
                    ><span v-else v-text="paginationLabel(link.label)"
                /></template>
            </nav>
            <div v-if="tab === 'Inventory'" class="admin-products">
                <div class="order-actions">
                    <p class="muted">
                        {{
                            t(
                                'Download a spreadsheet of every size, SKU, price and stock level.',
                            )
                        }}
                    </p>
                    <a class="button outline" href="/admin/inventory/export">
                        {{ t('Export inventory CSV') }}
                    </a>
                </div>
                <form
                    class="inventory-import"
                    @submit.prevent="importInventory"
                >
                    <label>
                        {{ t('Update stock from CSV') }}
                        <input
                            type="file"
                            accept=".csv,text/csv,text/plain"
                            required
                            @change="
                                inventoryFile =
                                    ($event.target as HTMLInputElement)
                                        .files?.[0] ?? null
                            "
                        />
                    </label>
                    <p class="muted">
                        {{
                            t(
                                'Edit only the Stock column in your export. Up to 1,000 sizes per upload. Every SKU and quantity is checked before any changes are saved.',
                            )
                        }}
                    </p>
                    <button
                        class="button dark"
                        :disabled="busy || !inventoryFile"
                    >
                        {{ busy ? t('Importing…') : t('Import stock updates') }}
                    </button>
                </form>
                <ProductCreate />
                <article
                    v-for="product in products"
                    :key="product.id"
                    class="admin-card"
                >
                    <h3>{{ t(product.name) }}</h3>
                    <ProductEditor :product="product" /><ProductPhoto
                        :product="product"
                    />
                    <p class="muted">
                        {{
                            t(
                                'Available pairs by size. Stock changes save when you leave a field.',
                            )
                        }}
                    </p>
                    <div class="variant-grid">
                        <label
                            v-for="variant in product.variants"
                            :key="variant.id"
                        >
                            {{ t('EU') }} {{ num(variant.size)
                            }}<span
                                v-if="
                                    product.active &&
                                    variant.stock <= lowStockThreshold
                                "
                                class="stock-warning"
                                :class="{
                                    'stock-warning-empty': variant.stock === 0,
                                }"
                                >{{
                                    variant.stock === 0
                                        ? t('Out of stock')
                                        : t('Low stock')
                                }}</span
                            ><input
                                :value="variant.stock"
                                type="number"
                                min="0"
                                max="1000000"
                                :disabled="busy"
                                :aria-label="
                                    t('Stock for :name, size :size', {
                                        name: t(product.name),
                                        size: num(variant.size),
                                    })
                                "
                                @change="stock(variant.id, $event)"
                        /></label>
                    </div>
                </article>
            </div>
            <div v-if="tab === 'Team'">
                <div class="admin-card">
                    <h2>{{ t('Give your team access') }}</h2>
                    <p>
                        {{
                            t(
                                'Ask the person to register an account first, then enter their email. Staff can manage orders, products, payments and team access.',
                            )
                        }}
                    </p>
                    <form
                        class="order-actions"
                        @submit.prevent="
                            post('/admin/team', { email: teamEmail })
                        "
                    >
                        <label>
                            {{ t('Registered email') }}
                            <input
                                v-model="teamEmail"
                                type="email"
                                required
                                maxlength="254"
                                autocomplete="off" /></label
                        ><button class="button dark" :disabled="busy">
                            {{ t('Grant staff access') }}
                        </button>
                    </form>
                </div>
                <article
                    v-for="member in team"
                    :key="member.id"
                    class="admin-card"
                >
                    <h3>{{ member.name }}</h3>
                    <p>{{ member.email }}</p>
                    <template
                        v-if="member.id !== (page.props.auth as any).user.id"
                        ><p v-if="removingMember === member.id">
                            {{ t("Remove this person's staff access?") }}
                        </p>
                        <button
                            v-if="removingMember === member.id"
                            class="button outline"
                            @click="removeMember(member.id)"
                        >
                            {{ t('Confirm removal') }}</button
                        ><button
                            v-else
                            class="text-button"
                            @click="removingMember = member.id"
                        >
                            {{ t('Remove staff access') }}</button
                        ><button
                            v-if="removingMember === member.id"
                            class="text-button"
                            @click="removingMember = null"
                        >
                            {{ t('Keep access') }}
                        </button></template
                    >
                    <p v-else class="muted">
                        {{ t('Your account · Staff access') }}
                    </p>
                </article>
            </div>
            <div v-if="tab === 'Partners'">
                <div v-if="!applications.length" class="empty-state">
                    {{ t('No wholesale applications yet.') }}
                </div>
                <article
                    v-for="application in applications"
                    :key="application.id"
                    class="admin-card"
                >
                    <div class="section-heading">
                        <h3>{{ application.company }}</h3>
                        <span class="status-pill">{{
                            label(application.status)
                        }}</span>
                    </div>
                    <p>
                        {{ application.user.name }} ·
                        {{ application.user.email }} · {{ application.phone }}
                    </p>
                    <p>{{ application.message }}</p>
                    <div class="order-actions">
                        <button
                            v-if="application.status !== 'approved'"
                            class="button dark"
                            :disabled="busy"
                            @click="
                                patch('/admin/wholesale/' + application.id, {
                                    status: 'approved',
                                })
                            "
                        >
                            {{ t('Approve partner') }}</button
                        ><button
                            v-if="application.status !== 'rejected'"
                            class="button outline"
                            :disabled="busy"
                            @click="
                                patch('/admin/wholesale/' + application.id, {
                                    status: 'rejected',
                                })
                            "
                        >
                            {{
                                application.status === 'approved'
                                    ? t('Revoke approval')
                                    : t('Reject')
                            }}
                        </button>
                    </div>
                </article>
            </div>
            <div v-if="tab === 'Promotions'">
                <form
                    class="admin-card form-card"
                    @submit.prevent="createPromotion()"
                >
                    <h2>{{ t('Create a discount code') }}</h2>
                    <label
                        >{{ t('Discount code')
                        }}<input
                            v-model="promotion.code"
                            required
                            minlength="3"
                            maxlength="40"
                            pattern="[A-Za-z0-9_-]+"
                    /></label>
                    <div class="checkout-field-grid">
                        <label
                            >{{ t('Discount type')
                            }}<select v-model="promotion.type">
                                <option value="percent">
                                    {{ t('Percentage') }}
                                </option>
                                <option value="fixed">
                                    {{ t('Fixed amount') }}
                                </option>
                            </select></label
                        ><label
                            >{{
                                promotion.type === 'percent'
                                    ? t('Discount percentage')
                                    : t('Discount amount in minor units')
                            }}<input
                                v-model.number="promotion.amount"
                                type="number"
                                min="1"
                                :max="
                                    promotion.type === 'percent'
                                        ? 100
                                        : 100000000
                                "
                                required
                        /></label>
                    </div>
                    <label
                        >{{ t('Minimum order subtotal')
                        }}<input
                            v-model.number="promotion.minimum_subtotal"
                            type="number"
                            min="0"
                    /></label>
                    <p class="muted">
                        {{
                            t(
                                'For NPR, 100 paisa equals NPR 1. Minimum subtotal and fixed discounts use your currency’s minor units.',
                            )
                        }}
                    </p>
                    <div class="checkout-field-grid">
                        <label
                            >{{ t('Order channel')
                            }}<select v-model="promotion.channel">
                                <option value="all">
                                    {{ t('All orders') }}
                                </option>
                                <option value="retail">
                                    {{ t('Retail') }}
                                </option>
                                <option value="wholesale">
                                    {{ t('Wholesale') }}
                                </option>
                            </select></label
                        ><label
                            >{{ t('Usage limit')
                            }}<input
                                v-model.number="promotion.usage_limit"
                                type="number"
                                min="1"
                                :placeholder="t('Unlimited')"
                        /></label>
                    </div>
                    <div class="checkout-field-grid">
                        <label
                            >{{ t('Starts at')
                            }}<input
                                v-model="promotion.starts_at"
                                type="datetime-local" /></label
                        ><label
                            >{{ t('Ends at')
                            }}<input
                                v-model="promotion.ends_at"
                                type="datetime-local"
                        /></label>
                    </div>
                    <button class="button dark" :disabled="busy">
                        {{ t('Create code') }}
                    </button>
                </form>
                <article
                    v-for="item in promotions"
                    :key="item.id"
                    class="admin-card"
                >
                    <div class="section-heading">
                        <h3>{{ item.code }}</h3>
                        <span class="status-pill">{{
                            item.active ? t('Active') : t('Paused')
                        }}</span>
                    </div>
                    <p>
                        {{
                            item.type === 'percent'
                                ? num(item.amount) + '%'
                                : money(
                                      item.amount,
                                      String(page.props.currency),
                                  )
                        }}
                        · {{ label(item.channel) }} ·
                        {{ t(':count uses', { count: num(item.uses_count) }) }}
                    </p>
                    <button
                        class="text-button"
                        @click="patch('/admin/promotions/' + item.id, {})"
                    >
                        {{ item.active ? t('Pause code') : t('Activate code') }}
                    </button>
                </article>
                <p v-if="!promotions.length" class="empty-state">
                    {{ t('No discount codes yet.') }}
                </p>
            </div>
            <div v-if="tab === 'Questions'">
                <p v-if="!questions.data.length" class="empty-state">
                    {{ t('No product questions yet.') }}
                </p>
                <article
                    v-for="item in questions.data"
                    :key="item.id"
                    class="admin-card"
                >
                    <h3>{{ t(item.product.name) }}</h3>
                    <p>{{ item.question }}</p>
                    <p v-if="item.answer" class="muted">{{ item.answer }}</p>
                    <form
                        class="order-actions"
                        @submit.prevent="
                            patch('/admin/questions/' + item.id, {
                                answer: answers[item.id] ?? item.answer,
                            })
                        "
                    >
                        <label
                            >{{ t('Your answer')
                            }}<textarea
                                v-model="answers[item.id]"
                                required
                                minlength="2"
                                maxlength="2000"
                                :placeholder="t('Write a helpful answer')"
                            /></label
                        ><button class="button dark" :disabled="busy">
                            {{
                                item.answer
                                    ? t('Update answer')
                                    : t('Publish answer')
                            }}
                        </button>
                    </form>
                </article>
                <nav
                    v-if="questions.links.length > 3"
                    class="pagination"
                    :aria-label="t('Product question pages')"
                >
                    <template v-for="(link, i) in questions.links" :key="i"
                        ><Link
                            v-if="link.url"
                            :href="link.url"
                            :class="{ active: link.active }"
                            preserve-state
                            preserve-scroll
                            ><span v-text="paginationLabel(link.label)" /></Link
                        ><span v-else v-text="paginationLabel(link.label)"
                    /></template>
                </nav>
            </div>
            <div v-if="tab === 'Reviews'">
                <p v-if="!reviews.data.length" class="empty-state">
                    {{ t('No customer reviews yet.') }}
                </p>
                <article
                    v-for="item in reviews.data"
                    :key="item.id"
                    class="admin-card"
                >
                    <div class="section-heading">
                        <div>
                            <h3>{{ t(item.product.name) }}</h3>
                            <small
                                >{{ item.user.name }} ·
                                {{ item.user.email }}</small
                            >
                        </div>
                        <span class="status-pill"
                            >{{ num(item.rating) }} / 5 ★</span
                        >
                    </div>
                    <strong>{{ item.title }}</strong>
                    <p>{{ item.body }}</p>
                    <p v-if="item.seller_reply" class="seller-review-reply">
                        <strong>{{ t('Current shop reply') }}</strong
                        ><br />{{ item.seller_reply }}
                    </p>
                    <form
                        class="order-actions"
                        @submit.prevent="
                            patch('/admin/reviews/' + item.id, {
                                reply:
                                    reviewReplies[item.id] ??
                                    item.seller_reply ??
                                    '',
                            })
                        "
                    >
                        <label
                            >{{ t('Reply publicly as EVE Shoes') }}
                            <textarea
                                v-model="reviewReplies[item.id]"
                                required
                                minlength="2"
                                maxlength="2000"
                                :placeholder="
                                    t(
                                        'Thank the customer and address their feedback…',
                                    )
                                "
                            />
                        </label>
                        <button class="button dark" :disabled="busy">
                            {{
                                item.seller_reply
                                    ? t('Update public reply')
                                    : t('Publish public reply')
                            }}
                        </button>
                    </form>
                    <Link
                        :href="'/products/' + item.product.slug + '#reviews'"
                        class="text-button"
                        >{{ t('View on product page') }}</Link
                    >
                </article>
                <nav
                    v-if="reviews.links.length > 3"
                    class="pagination"
                    :aria-label="t('Customer review pages')"
                >
                    <template v-for="(link, i) in reviews.links" :key="i"
                        ><Link
                            v-if="link.url"
                            :href="link.url"
                            :class="{ active: link.active }"
                            preserve-state
                            preserve-scroll
                            ><span v-text="paginationLabel(link.label)" /></Link
                        ><span v-else v-text="paginationLabel(link.label)"
                    /></template>
                </nav>
            </div>
            <div v-if="tab === 'Messages'">
                <p v-if="!conversations.data.length" class="empty-state">
                    {{ t('No customer messages yet.') }}
                </p>
                <article
                    v-for="item in conversations.data"
                    :key="item.id"
                    class="admin-card"
                >
                    <div class="section-heading">
                        <div>
                            <h3>{{ item.subject }}</h3>
                            <small
                                >{{ item.user.name }} ·
                                {{ item.user.email }}</small
                            >
                        </div>
                        <span class="status-pill">{{
                            t(label(item.status))
                        }}</span>
                    </div>
                    <div class="admin-conversation">
                        <div
                            v-for="message in item.messages.slice().reverse()"
                            :key="message.id"
                            class="admin-message"
                            :class="{ 'from-team': message.is_staff }"
                        >
                            <strong>{{
                                message.is_staff
                                    ? t('EVE Shoes team')
                                    : item.user.name
                            }}</strong>
                            <small>{{ date(message.created_at, true) }}</small>
                            <p>{{ message.body }}</p>
                        </div>
                    </div>
                    <form
                        class="order-actions"
                        @submit.prevent="
                            post('/admin/messages/' + item.id, {
                                body: customerReplies[item.id] ?? '',
                            })
                        "
                    >
                        <label
                            >{{ t('Reply to customer')
                            }}<textarea
                                v-model="customerReplies[item.id]"
                                required
                                minlength="2"
                                maxlength="4000"
                                :placeholder="
                                    t('Write a helpful, personal reply…')
                                "
                            />
                        </label>
                        <button class="button dark" :disabled="busy">
                            {{ t('Send reply') }}
                        </button>
                    </form>
                    <label class="admin-status-control"
                        >{{ t('Conversation status') }}
                        <select
                            :value="item.status"
                            @change="
                                patch(
                                    '/admin/messages/' + item.id + '/status',
                                    {
                                        status: (
                                            $event.target as HTMLSelectElement
                                        ).value,
                                    },
                                )
                            "
                        >
                            <option value="open">{{ t('Open') }}</option>
                            <option value="answered">
                                {{ t('Answered') }}
                            </option>
                            <option value="closed">{{ t('Resolved') }}</option>
                        </select>
                    </label>
                </article>
                <nav
                    v-if="conversations.links.length > 3"
                    class="pagination"
                    :aria-label="t('Customer message pages')"
                >
                    <template v-for="(link, i) in conversations.links" :key="i"
                        ><Link
                            v-if="link.url"
                            :href="link.url"
                            :class="{ active: link.active }"
                            preserve-state
                            preserve-scroll
                            ><span v-text="paginationLabel(link.label)" /></Link
                        ><span v-else v-text="paginationLabel(link.label)"
                    /></template>
                </nav>
            </div></section
    ></AdminLayout>
</template>
