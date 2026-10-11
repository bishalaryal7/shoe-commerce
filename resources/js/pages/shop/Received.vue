<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import ShopLayout from '@/layouts/ShopLayout.vue';
const { t, money } = useLocale();

import { Head, Link, usePage } from '@inertiajs/vue3';
defineProps<{ reference: string; subtotal: number }>();
const page = usePage();
</script>
<template>
    <Head :title="t('Request received')" /><ShopLayout
        ><section class="wrap inner-page received">
            <div class="checkout-steps" style="justify-content: center">
                <span> {{ t('01 · Your bag') }} </span
                ><span> {{ t('02 · Your details') }} </span
                ><span class="active"> {{ t('03 · Request received') }} </span>
            </div>
            <h1>
                {{ t('Your next step') }} <br />
                {{ t('is on its way.') }}
            </h1>
            <p>
                {{
                    t(
                        'We received your order request. No payment has been collected.',
                    )
                }}
                <br />
                {{
                    t(
                        'Our team will contact you to confirm the final total and delivery.',
                    )
                }}
            </p>
            <div class="summary-card">
                <h3>{{ t('Keep your reference') }}</h3>
                <code>{{ reference }}</code>
                <p>
                    {{ t('Product subtotal:') }}
                    {{ money(subtotal, page.props.currency as string) }}
                </p>
            </div>
            <Link href="/" class="button dark">
                {{ t('Back to the collection ↗') }}
            </Link>
        </section></ShopLayout
    >
</template>
