<script setup lang="ts">
import ProductCreate from '@/components/ProductCreate.vue';
import ProductEditor from '@/components/ProductEditor.vue';
import ProductPhoto from '@/components/ProductPhoto.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { money, type Product } from '@/types/shop';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
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
        khalti: boolean;
        paypay: boolean;
        mail: string;
        pendingEmails: number;
    };
    stats: {
        orders: number;
        pending: number;
        stock: number;
        applications: number;
    };
}>();
const page = usePage();
const tab = ref('Orders');
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
function stock(id: number, event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.checkValidity())
        patch('/admin/variants/' + id, { stock: Number(input.value) });
}
</script>
<template>
    <Head title="Team dashboard" /><AdminLayout v-model:tab="tab"
        ><section class="wrap inner-page admin-page">
            <div class="eyebrow">TEAM WORKSPACE</div>
            <h1>
                {{
                    tab === 'Orders'
                        ? 'Orders, at a glance.'
                        : tab === 'Inventory'
                          ? 'Keep your shelves moving.'
                          : tab === 'Payments'
                            ? 'Every payment, accounted for.'
                            : tab === 'Team'
                              ? 'Your people. Your workspace.'
                              : 'Good partners. Better business.'
                }}
            </h1>
            <p class="workspace-description">
                {{
                    tab === 'Orders'
                        ? 'Review requests and keep every next step on track.'
                        : tab === 'Inventory'
                          ? 'Manage your collection, pricing, and available sizes.'
                          : tab === 'Payments'
                            ? 'Check provider status and monitor your integrations.'
                            : tab === 'Team'
                              ? 'Manage access for registered team members.'
                              : 'Review applications and manage your wholesale relationships.'
                }}
            </p>
            <div class="stats-grid">
                <div
                    v-for="(value, key) in stats"
                    :key="key"
                    class="summary-card"
                >
                    <span>{{ key }}</span
                    ><strong>{{ value }}</strong>
                </div>
            </div>
            <p
                v-for="(error, key) in page.props.errors"
                :key="key"
                class="field-error"
                role="alert"
            >
                {{ error }}
            </p>
            <div v-if="tab === 'Orders'">
                <form class="order-actions" @submit.prevent="filterOrders">
                    <label
                        >Find an order<input
                            v-model="filters.search"
                            maxlength="150"
                            placeholder="Reference, customer, email or phone"
                    /></label>
                    <label
                        >Order status<select v-model="filters.status">
                            <option value="">All statuses</option>
                            <option
                                v-for="status in [
                                    'pending_confirmation',
                                    'confirmed',
                                    'fulfilled',
                                    'cancelled',
                                ]"
                                :key="status"
                                :value="status"
                            >
                                {{ status.replaceAll('_', ' ') }}
                            </option>
                        </select></label
                    >
                    <label
                        >Payment<select v-model="filters.payment">
                            <option value="">All payments</option>
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
                                {{ status }}
                            </option>
                        </select></label
                    >
                    <button class="button dark">Search</button>
                    <button
                        type="button"
                        class="text-button"
                        @click="
                            filters = { search: '', status: '', payment: '' };
                            filterOrders();
                        "
                    >
                        Reset
                    </button>
                </form>
                <p class="muted">
                    {{ orders.total }} matching orders. Fulfillment requires
                    verified online payment or a recorded offline receipt.
                </p>
                <div v-if="!orders.data.length" class="empty-state">
                    No order requests yet.
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
                            order.status.replaceAll('_', ' ')
                        }}</span>
                    </div>
                    <p>{{ order.address }}</p>
                    <small>{{ order.reference }} · {{ order.channel }}</small>
                    <div
                        v-for="item in order.items"
                        :key="item.id"
                        class="summary-line"
                    >
                        <span>{{ item.name }} · EU {{ item.size }}</span
                        ><span>{{ item.quantity }} pairs · {{ item.sku }}</span>
                    </div>
                    <details class="order-audit">
                        <summary>Order history</summary>
                        <div
                            v-for="event in order.events"
                            :key="event.id"
                            class="summary-line"
                        >
                            <span
                                >{{ event.event.replaceAll('_', ' ')
                                }}<small
                                    >{{
                                        event.actor?.name ?? 'System / customer'
                                    }}
                                    ·
                                    {{
                                        new Date(
                                            event.created_at,
                                        ).toLocaleString()
                                    }}</small
                                ><small v-if="event.details">{{
                                    Object.entries(event.details)
                                        .map(
                                            ([key, value]) =>
                                                key.replaceAll('_', ' ') +
                                                ': ' +
                                                value,
                                        )
                                        .join(' · ')
                                }}</small></span
                            >
                        </div>
                        <p v-if="!order.events.length" class="muted">
                            No recorded events for this older order.
                        </p>
                    </details>
                    <p class="muted">
                        Payment: {{ order.payment_status }} ·
                        {{ order.payment_method }}
                    </p>
                    <form
                        v-if="
                            order.payment_method === 'request' &&
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
                        <label
                            >Receipt or bank reference<input
                                v-model="receipts[order.id]"
                                required
                                maxlength="150"
                                placeholder="Verified receipt reference"
                        /></label>
                        <button class="button outline" :disabled="busy">
                            Record received payment
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
                            Confirm</button
                        ><button
                            v-if="
                                order.status === 'confirmed' &&
                                order.payment_status === 'paid'
                            "
                            class="button dark"
                            :disabled="busy"
                            @click="
                                patch('/admin/orders/' + order.id, {
                                    status: 'fulfilled',
                                })
                            "
                        >
                            Mark fulfilled</button
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
                            Cancel & restore stock
                        </button>
                    </div>
                </article>
            </div>
            <nav
                v-if="tab === 'Orders' && orders.links.length > 3"
                class="pagination"
                aria-label="Staff order pages"
            >
                <template v-for="(link, i) in orders.links" :key="i"
                    ><Link
                        v-if="link.url"
                        :href="link.url"
                        :class="{ active: link.active }"
                        preserve-state
                        preserve-scroll
                        ><span v-html="link.label" /></Link
                    ><span v-else v-html="link.label"
                /></template>
            </nav>
            <div v-if="tab === 'Payments'">
                <div class="admin-card">
                    <h3>Integrations</h3>
                    <p>
                        Khalti:
                        {{
                            integrations.khalti
                                ? 'configured'
                                : 'needs credentials'
                        }}
                        · PayPay:
                        {{
                            integrations.paypay
                                ? 'configured'
                                : 'needs credentials'
                        }}
                    </p>
                    <p>
                        Email transport: {{ integrations.mail }} ·
                        {{ integrations.pendingEmails }} unsent notifications
                    </p>
                </div>
                <p class="muted">
                    {{ payments.total }} attempts. Verification asks the
                    provider for its authoritative status. Sandbox attempts do
                    not represent live payments.
                </p>
                <article
                    v-for="payment in payments.data"
                    :key="payment.id"
                    class="admin-card"
                >
                    <h3>{{ payment.provider }} · {{ payment.status }}</h3>
                    <p>{{ payment.reference }}</p>
                    <p>
                        {{ money(payment.amount, payment.currency) }} ·
                        {{ payment.sandbox ? 'Sandbox' : 'Live' }}
                    </p>
                    <button
                        v-if="payment.provider_id"
                        class="button outline"
                        :disabled="busy"
                        @click="
                            post('/admin/payments/' + payment.id + '/verify')
                        "
                    >
                        Verify with provider
                    </button>
                    <p v-else class="muted">
                        No provider session ID. Check the merchant dashboard
                        before retrying or releasing stock.
                    </p>
                </article>
            </div>
            <nav
                v-if="tab === 'Payments' && payments.links.length > 3"
                class="pagination"
                aria-label="Payment attempt pages"
            >
                <template v-for="(link, i) in payments.links" :key="i"
                    ><Link
                        v-if="link.url"
                        :href="link.url"
                        :class="{ active: link.active }"
                        preserve-state
                        preserve-scroll
                        ><span v-html="link.label" /></Link
                    ><span v-else v-html="link.label"
                /></template>
            </nav>
            <div v-if="tab === 'Inventory'" class="admin-products">
                <ProductCreate />
                <article
                    v-for="product in products"
                    :key="product.id"
                    class="admin-card"
                >
                    <h3>{{ product.name }}</h3>
                    <ProductEditor :product="product" /><ProductPhoto
                        :product="product"
                    />
                    <p class="muted">
                        Available pairs by size. Stock changes save when you
                        leave a field.
                    </p>
                    <div class="variant-grid">
                        <label
                            v-for="variant in product.variants"
                            :key="variant.id"
                            >EU {{ variant.size
                            }}<input
                                :value="variant.stock"
                                type="number"
                                min="0"
                                max="1000000"
                                :disabled="busy"
                                :aria-label="
                                    product.name +
                                    ' size ' +
                                    variant.size +
                                    ' stock'
                                "
                                @change="stock(variant.id, $event)"
                        /></label>
                    </div>
                </article>
            </div>
            <div v-if="tab === 'Team'">
                <div class="admin-card">
                    <h2>Give your team access</h2>
                    <p>
                        Ask the person to register an account first, then enter
                        their email. Staff can manage orders, products, payments
                        and team access.
                    </p>
                    <form
                        class="order-actions"
                        @submit.prevent="
                            post('/admin/team', { email: teamEmail })
                        "
                    >
                        <label
                            >Registered email<input
                                v-model="teamEmail"
                                type="email"
                                required
                                maxlength="254"
                                autocomplete="off" /></label
                        ><button class="button dark" :disabled="busy">
                            Grant staff access
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
                            Remove this person's staff access?
                        </p>
                        <button
                            v-if="removingMember === member.id"
                            class="button outline"
                            @click="removeMember(member.id)"
                        >
                            Confirm removal</button
                        ><button
                            v-else
                            class="text-button"
                            @click="removingMember = member.id"
                        >
                            Remove staff access</button
                        ><button
                            v-if="removingMember === member.id"
                            class="text-button"
                            @click="removingMember = null"
                        >
                            Keep access
                        </button></template
                    >
                    <p v-else class="muted">Your account · Staff access</p>
                </article>
            </div>
            <div v-if="tab === 'Partners'">
                <div v-if="!applications.length" class="empty-state">
                    No wholesale applications yet.
                </div>
                <article
                    v-for="application in applications"
                    :key="application.id"
                    class="admin-card"
                >
                    <div class="section-heading">
                        <h3>{{ application.company }}</h3>
                        <span class="status-pill">{{
                            application.status
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
                            Approve partner</button
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
                                    ? 'Revoke approval'
                                    : 'Reject'
                            }}
                        </button>
                    </div>
                </article>
            </div>
        </section></AdminLayout
    >
</template>
