<script setup lang="ts">
import MerchantConversationPanel from '@/components/MerchantConversationPanel.vue';
import MerchantProductEditor from '@/components/MerchantProductEditor.vue';
import { useLocale } from '@/composables/useLocale';
import ShopLayout from '@/layouts/ShopLayout.vue';
import type {
    MerchantConversation,
    MerchantOrder,
    MerchantProduct,
    MerchantStore,
} from '@/types/merchant';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    store: MerchantStore | null;
    products: MerchantProduct[];
    orders: {
        data: MerchantOrder[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    conversations: {
        data: MerchantConversation[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();
const { t, money, date, paginationLabel } = useLocale();
const create = useForm({ name: '', tagline: '', description: '' });
const settings = useForm({
    name: props.store?.name ?? '',
    tagline: props.store?.tagline ?? '',
    description: props.store?.description ?? '',
    accent: props.store?.accent ?? '#2c4540',
    active: props.store?.active ?? true,
    logo: null as File | null,
    banner: null as File | null,
});
const product = useForm({
    name: '',
    category: 'Shoes',
    description: '',
    price: '0.00',
    size: '',
    stock: 0,
    image: null as File | null,
});
function updateStatus(order: number, status: string) {
    router.patch(
        '/merchant/orders/' + order,
        { status },
        { preserveScroll: true },
    );
}
</script>
<template>
    <Head :title="t('My store workspace')" /><ShopLayout
        ><section class="wrap inner-page merchant-workspace">
            <span class="eyebrow">{{ t('SELLER WORKSPACE') }}</span>
            <h1>{{ t('My store workspace') }}</h1>
            <p>
                {{
                    t(
                        'Build your independent shop, manage stock and orders, and answer customers.',
                    )
                }}
            </p>
            <form
                v-if="!store"
                class="form-card merchant-create"
                @submit.prevent="create.post('/merchant')"
            >
                <h2>{{ t('Create your storefront') }}</h2>
                <label
                    >{{ t('Store name')
                    }}<input
                        v-model="create.name"
                        required
                        minlength="2"
                        maxlength="100"
                    /><span class="field-error">{{
                        create.errors.name
                    }}</span></label
                ><label
                    >{{ t('Tagline')
                    }}<input v-model="create.tagline" maxlength="150" /><span
                        class="field-error"
                        >{{ create.errors.tagline }}</span
                    ></label
                ><label
                    >{{ t('About your store')
                    }}<textarea
                        v-model="create.description"
                        maxlength="2000"
                        rows="4"
                    /></label
                ><button class="button dark" :disabled="create.processing">
                    {{ t('Create store') }}
                </button>
            </form>
            <template v-else
                ><div class="merchant-workspace-header">
                    <div>
                        <h2>{{ store.name }}</h2>
                        <p>
                            {{ t('Your storefront address') }}:
                            <Link :href="'/stores/' + store.slug">{{
                                '/stores/' + store.slug
                            }}</Link>
                        </p>
                    </div>
                    <Link :href="'/stores/' + store.slug" class="button dark">{{
                        t('View my store')
                    }}</Link>
                </div>
                <nav class="merchant-workspace-nav">
                    <a href="#store-settings">{{ t('Store appearance') }}</a
                    ><a href="#store-products">{{ t('Products') }}</a
                    ><a href="#store-orders">{{ t('Orders') }}</a
                    ><a href="#store-messages">{{ t('Messages') }}</a>
                </nav>
                <section id="store-settings" class="form-card">
                    <h2>{{ t('Store appearance') }}</h2>
                    <form
                        @submit.prevent="
                            settings
                                .transform((data) => ({
                                    ...data,
                                    _method: 'patch',
                                }))
                                .post('/merchant', { preserveScroll: true })
                        "
                    >
                        <div class="merchant-two-col">
                            <label
                                >{{ t('Store name')
                                }}<input
                                    v-model="settings.name"
                                    required
                                    maxlength="100" /></label
                            ><label
                                >{{ t('Tagline')
                                }}<input
                                    v-model="settings.tagline"
                                    maxlength="150"
                            /></label>
                        </div>
                        <label
                            >{{ t('About your store')
                            }}<textarea
                                v-model="settings.description"
                                maxlength="2000"
                                rows="4"
                            />
                        </label>
                        <div class="merchant-two-col">
                            <label
                                >{{ t('Brand color')
                                }}<input
                                    v-model="settings.accent"
                                    type="color" /></label
                            ><label
                                >{{ t('Store logo')
                                }}<input
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    @change="
                                        settings.logo =
                                            ($event.target as HTMLInputElement)
                                                .files?.[0] || null
                                    "
                            /></label>
                        </div>
                        <label
                            >{{ t('Store banner')
                            }}<input
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                @change="
                                    settings.banner =
                                        ($event.target as HTMLInputElement)
                                            .files?.[0] || null
                                " /></label
                        ><label class="merchant-check"
                            ><input
                                v-model="settings.active"
                                type="checkbox"
                            />{{ t('Store is public') }}</label
                        >
                        <p
                            v-for="error in settings.errors"
                            :key="error"
                            class="field-error"
                        >
                            {{ error }}
                        </p>
                        <button
                            class="button dark"
                            :disabled="settings.processing"
                        >
                            {{ t('Save storefront') }}
                        </button>
                    </form>
                </section>
                <section id="store-products" class="form-card">
                    <h2>{{ t('Products') }}</h2>
                    <p>
                        {{
                            t(
                                'Add shoes, clothing, or accessories. Each product can have sizes and stock.',
                            )
                        }}
                    </p>
                    <form
                        class="merchant-new-product"
                        @submit.prevent="
                            product.post('/merchant/products', {
                                preserveScroll: true,
                                onSuccess: () => product.reset(),
                            })
                        "
                    >
                        <div class="merchant-two-col">
                            <label
                                >{{ t('Product name')
                                }}<input
                                    v-model="product.name"
                                    required
                                    maxlength="150" /></label
                            ><label
                                >{{ t('Category')
                                }}<select v-model="product.category">
                                    <option value="Shoes">
                                        {{ t('Shoes') }}
                                    </option>
                                    <option value="Clothing">
                                        {{ t('Clothing') }}
                                    </option>
                                    <option value="Accessories">
                                        {{ t('Accessories') }}
                                    </option>
                                </select></label
                            >
                        </div>
                        <label
                            >{{ t('Description')
                            }}<textarea
                                v-model="product.description"
                                required
                                minlength="10"
                                maxlength="5000"
                                rows="4"
                            />
                        </label>
                        <div class="merchant-two-col">
                            <label
                                >{{ t('Price in NPR')
                                }}<input
                                    v-model="product.price"
                                    type="number"
                                    min="0.01"
                                    max="1000000"
                                    step="0.01"
                                    required /></label
                            ><label
                                >{{ t('Product photo')
                                }}<input
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    required
                                    @change="
                                        product.image =
                                            ($event.target as HTMLInputElement)
                                                .files?.[0] || null
                                    " /></label
                            ><label
                                >{{ t('First size')
                                }}<input
                                    v-model="product.size"
                                    required
                                    maxlength="40"
                                    :placeholder="
                                        t('For example: EU 42 or M')
                                    " /></label
                            ><label
                                >{{ t('Stock')
                                }}<input
                                    v-model.number="product.stock"
                                    type="number"
                                    min="0"
                                    max="100000"
                                    required
                            /></label>
                        </div>
                        <p
                            v-for="error in product.errors"
                            :key="error"
                            class="field-error"
                        >
                            {{ error }}
                        </p>
                        <button
                            class="button dark"
                            :disabled="product.processing"
                        >
                            {{ t('Add product') }}
                        </button>
                    </form>
                    <div class="merchant-manage-list">
                        <MerchantProductEditor
                            v-for="item in products"
                            :key="item.id"
                            :product="item"
                        />
                    </div>
                </section>
                <section id="store-orders" class="form-card">
                    <h2>{{ t('Orders') }}</h2>
                    <p v-if="!orders.data.length">{{ t('No orders yet.') }}</p>
                    <article
                        v-for="order in orders.data"
                        :key="order.id"
                        class="merchant-order-row"
                    >
                        <div>
                            <strong>{{ order.name }}</strong
                            ><small
                                >{{ date(order.created_at) }} ·
                                {{ order.reference }}</small
                            >
                            <p>
                                {{ order.phone }} · {{ order.email }}<br />{{
                                    order.address
                                }}
                            </p>
                            <p v-for="item in order.items" :key="item.id">
                                {{ item.quantity }} × {{ item.name }} ·
                                {{ item.size }}
                            </p>
                            <strong>{{ money(order.total, 'NPR') }}</strong>
                        </div>
                        <div>
                            <span class="status-pill">{{
                                t(order.status)
                            }}</span
                            ><select
                                :aria-label="t('Update order status')"
                                :value="order.status"
                                @change="
                                    updateStatus(
                                        order.id,
                                        ($event.target as HTMLSelectElement)
                                            .value,
                                    )
                                "
                            >
                                <option :value="order.status">
                                    {{ t(order.status) }}
                                </option>
                                <option
                                    v-if="order.status === 'pending'"
                                    value="accepted"
                                >
                                    {{ t('accepted') }}
                                </option>
                                <option
                                    v-if="
                                        order.status === 'pending' ||
                                        order.status === 'accepted'
                                    "
                                    value="cancelled"
                                >
                                    {{ t('cancelled') }}
                                </option>
                                <option
                                    v-if="order.status === 'accepted'"
                                    value="shipped"
                                >
                                    {{ t('shipped') }}
                                </option>
                                <option
                                    v-if="order.status === 'shipped'"
                                    value="fulfilled"
                                >
                                    {{ t('fulfilled') }}
                                </option>
                            </select>
                        </div>
                    </article>
                    <nav
                        v-if="orders.links.length > 3"
                        class="pagination"
                        :aria-label="t('Order pages')"
                    >
                        <Link
                            v-for="(link, index) in orders.links"
                            :key="index"
                            :href="link.url || '#'"
                            :class="{
                                active: link.active,
                                disabled: !link.url,
                            }"
                            >{{ paginationLabel(link.label) }}</Link
                        >
                    </nav>
                </section>
                <section id="store-messages" class="form-card">
                    <h2>{{ t('Customer messages') }}</h2>
                    <p v-if="!conversations.data.length">
                        {{ t('No messages yet.') }}
                    </p>
                    <MerchantConversationPanel
                        v-for="thread in conversations.data"
                        :key="thread.id"
                        :thread="thread"
                    />
                    <nav
                        v-if="conversations.links.length > 3"
                        class="pagination"
                        :aria-label="t('Conversation pages')"
                    >
                        <Link
                            v-for="(link, index) in conversations.links"
                            :key="index"
                            :href="link.url || '#'"
                            :class="{
                                active: link.active,
                                disabled: !link.url,
                            }"
                            >{{ paginationLabel(link.label) }}</Link
                        >
                    </nav>
                </section>
            </template>
        </section></ShopLayout
    >
</template>
