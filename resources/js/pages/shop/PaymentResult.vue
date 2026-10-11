<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
const { t } = useLocale();
defineProps<{ status: string; sandbox: boolean }>();
</script>
<template>
    <Head :title="t('Payment status')" /><ShopLayout
        ><section class="wrap inner-page received">
            <span class="eyebrow">{{
                sandbox ? t('SANDBOX PAYMENT') : t('PAYMENT STATUS')
            }}</span>
            <h1>
                {{
                    status === 'paid'
                        ? t('Payment verified.')
                        : status === 'failed'
                          ? t('Payment wasn’t completed.')
                          : t('Payment is being checked.')
                }}
            </h1>
            <p>
                {{
                    status === 'paid'
                        ? t('The payment provider confirmed this transaction.')
                        : t(
                              'A successful payment has not been confirmed. Check your order before attempting another payment.',
                          )
                }}
            </p>
            <p v-if="sandbox">
                {{
                    t('This was a sandbox test, not a real customer purchase.')
                }}
            </p>
            <Link href="/orders" class="button dark">
                {{ t('View your orders →') }} </Link
            ><Link href="/" class="text-button">
                {{ t('Back to the store') }}
            </Link>
        </section></ShopLayout
    >
</template>
