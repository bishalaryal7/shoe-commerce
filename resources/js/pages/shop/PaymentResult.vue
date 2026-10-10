<script setup lang="ts">
import ShopLayout from '@/layouts/ShopLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
defineProps<{ status: string; sandbox: boolean }>();
</script>
<template>
    <Head title="Payment status" /><ShopLayout
        ><section class="wrap inner-page received">
            <span class="eyebrow">{{
                sandbox ? 'SANDBOX PAYMENT' : 'PAYMENT STATUS'
            }}</span>
            <h1>
                {{
                    status === 'paid'
                        ? 'Payment verified.'
                        : status === 'failed'
                          ? 'Payment wasn’t completed.'
                          : 'Payment is being checked.'
                }}
            </h1>
            <p>
                {{
                    status === 'paid'
                        ? 'The payment provider confirmed this transaction.'
                        : 'A successful payment has not been confirmed. Check your order before attempting another payment.'
                }}
            </p>
            <p v-if="sandbox">
                This was a sandbox test, not a real customer purchase.
            </p>
            <Link href="/orders" class="button dark">View your orders →</Link
            ><Link href="/" class="text-button">Back to the store</Link>
        </section></ShopLayout
    >
</template>
