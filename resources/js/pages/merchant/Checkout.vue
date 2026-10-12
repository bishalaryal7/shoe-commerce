<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import MerchantLayout from '@/layouts/MerchantLayout.vue';
import type { MerchantLine, MerchantStore } from '@/types/merchant';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    store: MerchantStore;
    lines: MerchantLine[];
    checkoutToken: string;
}>();
const page = usePage();
const { t, money } = useLocale();
const form = useForm({
    name: (page.props.auth?.user as { name?: string } | undefined)?.name ?? '',
    email:
        (page.props.auth?.user as { email?: string } | undefined)?.email ?? '',
    phone: '',
    address: '',
    cart: '',
    checkout_token: props.checkoutToken,
});
const total = computed(() =>
    props.lines.reduce((sum, line) => sum + line.unit_price * line.quantity, 0),
);
</script>

<template>
    <Head :title="t('Checkout') + ' | ' + store.name" /><MerchantLayout
        :store="store"
        ><section class="wrap inner-page">
            <span class="eyebrow">{{ store.name }}</span>
            <h1>{{ t('Checkout') }}</h1>
            <div class="checkout-grid">
                <form
                    class="form-card"
                    @submit.prevent="
                        form.post('/stores/' + store.slug + '/checkout')
                    "
                >
                    <h2>{{ t('Delivery details') }}</h2>
                    <label
                        >{{ t('Full name')
                        }}<input
                            v-model="form.name"
                            required
                            minlength="2"
                            maxlength="150"
                            autocomplete="name"
                        /><span class="field-error">{{
                            form.errors.name
                        }}</span></label
                    ><label
                        >{{ t('Email address')
                        }}<input
                            v-model="form.email"
                            type="email"
                            required
                            maxlength="254"
                            autocomplete="email"
                        /><span class="field-error">{{
                            form.errors.email
                        }}</span></label
                    ><label
                        >{{ t('Phone number')
                        }}<input
                            v-model="form.phone"
                            type="tel"
                            required
                            minlength="7"
                            maxlength="40"
                            autocomplete="tel"
                        /><span class="field-error">{{
                            form.errors.phone
                        }}</span></label
                    ><label
                        >{{ t('Delivery address')
                        }}<textarea
                            v-model="form.address"
                            required
                            minlength="10"
                            maxlength="1000"
                            rows="4"
                            autocomplete="street-address"
                        /><span class="field-error">{{
                            form.errors.address
                        }}</span></label
                    >
                    <p v-if="form.errors.cart" class="field-error">
                        {{ form.errors.cart }}
                    </p>
                    <p class="muted">
                        {{
                            t(
                                'You are sending an order request. The seller will contact you about delivery and payment. No online payment is collected here.',
                            )
                        }}
                    </p>
                    <button
                        class="button dark full"
                        :disabled="form.processing"
                    >
                        {{ t('Send order request') }}
                    </button>
                </form>
                <aside class="form-card">
                    <h2>{{ t('Your order') }}</h2>
                    <p v-for="line in lines" :key="line.variant_id">
                        {{ line.quantity }} × {{ line.product }} ({{
                            line.size
                        }}) ·
                        {{ money(line.unit_price * line.quantity, 'NPR') }}
                    </p>
                    <strong>{{ t('Total') }}: {{ money(total, 'NPR') }}</strong>
                    <p>
                        <Link :href="'/stores/' + store.slug + '/cart'">{{
                            t('Back to your bag')
                        }}</Link>
                    </p>
                </aside>
            </div>
        </section></MerchantLayout
    >
</template>
