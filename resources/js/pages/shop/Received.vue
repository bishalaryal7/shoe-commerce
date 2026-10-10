<script setup lang="ts">
import ShopLayout from '@/layouts/ShopLayout.vue';
import { money } from '@/types/shop';
import { Head, Link, usePage } from '@inertiajs/vue3';
defineProps<{ reference: string; subtotal: number }>();
const page = usePage();
</script>
<template>
    <Head title="Request received" /><ShopLayout
        ><section class="wrap inner-page received">
            <div class="checkout-steps" style="justify-content: center">
                <span>01 · Your bag</span><span>02 · Your details</span
                ><span class="active">03 · Request received</span>
            </div>
            <h1>Your next step<br />is on its way.</h1>
            <p>
                We received your order request. No payment has been
                collected.<br />Our team will contact you to confirm the final
                total and delivery.
            </p>
            <div class="summary-card">
                <h3>Keep your reference</h3>
                <code>{{ reference }}</code>
                <p>
                    Product subtotal:
                    {{ money(subtotal, page.props.currency as string) }}
                </p>
            </div>
            <Link href="/" class="button dark">Back to the collection ↗</Link>
        </section></ShopLayout
    >
</template>
