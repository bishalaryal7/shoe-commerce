<script setup lang="ts">
import ProductEditor from '@/components/ProductEditor.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { money, type Product } from '@/types/shop';
import { Head, router, usePage } from '@inertiajs/vue3';
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
defineProps<{
    products: Product[];
    orders: Order[];
    applications: Application[];
    stats: {
        orders: number;
        pending: number;
        stock: number;
        applications: number;
    };
}>();
const page = usePage();
const tab = ref('Orders');
const busy = ref(false);
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
                          : 'Good partners. Better business.'
                }}
            </h1>
            <p class="workspace-description">
                {{
                    tab === 'Orders'
                        ? 'Review requests and keep every next step on track.'
                        : tab === 'Inventory'
                          ? 'Manage your collection, pricing, and available sizes.'
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
                <p class="muted">
                    Latest 50 requests. Payment is arranged separately;
                    confirming an order does not record a payment.
                </p>
                <div v-if="!orders.length" class="empty-state">
                    No order requests yet.
                </div>
                <article
                    v-for="order in orders"
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
                    <div class="order-actions">
                        <strong>{{
                            money(order.subtotal, order.currency)
                        }}</strong
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
                            v-if="order.status === 'confirmed'"
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
            <div v-if="tab === 'Inventory'" class="admin-products">
                <article
                    v-for="product in products"
                    :key="product.id"
                    class="admin-card"
                >
                    <h3>{{ product.name }}</h3>
                    <ProductEditor :product="product" />
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
