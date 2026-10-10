<script setup lang="ts">
import ShopLayout from '@/layouts/ShopLayout.vue';
import { money, type CartLine } from '@/types/shop';
import { Head, useForm, usePage } from '@inertiajs/vue3';
const props = defineProps<{
    lines: CartLine[];
    wholesale: boolean;
    token: string;
}>();
const page = usePage();
const user = (page.props.auth as any)?.user;
const form = useForm({
    name: user?.name || '',
    email: user?.email || '',
    phone: '',
    address: '',
    token: props.token,
});
const subtotal = props.lines.reduce(
    (sum, l) => sum + l.unit_price * l.quantity,
    0,
);
</script>
<template>
    <Head title="Order request" /><ShopLayout
        ><section class="wrap inner-page">
            <div class="checkout-steps">
                <span>01 · Your bag</span
                ><span class="active">02 · Your details</span
                ><span>03 · Request received</span>
            </div>
            <h1>Let’s make it yours.</h1>
            <div class="checkout-grid">
                <form
                    class="form-card"
                    @submit.prevent="form.post('/checkout')"
                >
                    <h2>Contact & delivery details</h2>
                    <label
                        v-for="field in ['name', 'email', 'phone'] as const"
                        :key="field"
                        >{{ field
                        }}<input
                            v-model="form[field]"
                            :type="
                                field === 'email'
                                    ? 'email'
                                    : field === 'phone'
                                      ? 'tel'
                                      : 'text'
                            "
                            :autocomplete="field === 'phone' ? 'tel' : field"
                            required
                            :maxlength="
                                field === 'name'
                                    ? 150
                                    : field === 'email'
                                      ? 254
                                      : 40
                            "
                        /><span class="field-error">{{
                            form.errors[field]
                        }}</span></label
                    ><label
                        >Delivery address<textarea
                            v-model="form.address"
                            required
                            maxlength="1000"
                            autocomplete="street-address"
                            rows="4"
                        /><span class="field-error">{{
                            form.errors.address
                        }}</span></label
                    >
                    <p
                        v-if="(form.errors as any).cart"
                        class="field-error"
                        role="alert"
                    >
                        {{ (form.errors as any).cart }}
                    </p>
                    <p class="muted">
                        This submits an unpaid order request and reserves the
                        selected stock. Our team will confirm availability,
                        shipping, taxes, and payment arrangements.
                    </p>
                    <button class="button dark" :disabled="form.processing">
                        {{
                            form.processing
                                ? 'Submitting…'
                                : 'Submit order request ↗'
                        }}
                    </button>
                </form>
                <aside class="summary-card">
                    <h2>{{ wholesale ? 'Wholesale' : 'Retail' }} order</h2>
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
                                page.props.currency as string,
                            )
                        }}</strong>
                    </div>
                    <div class="summary-line total">
                        <span>Product subtotal</span
                        ><strong>{{
                            money(subtotal, page.props.currency as string)
                        }}</strong>
                    </div>
                    <p>
                        Final shipping and tax amounts are subject to
                        confirmation.
                    </p>
                </aside>
            </div>
        </section></ShopLayout
    >
</template>
