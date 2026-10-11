<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
const { t } = useLocale();
const page = usePage();
</script>
<template>
    <Head :title="t('Your account')" /><ShopLayout
        ><section class="wrap inner-page">
            <div class="eyebrow">{{ t('YOUR ACCOUNT') }}</div>
            <h1>
                {{ t('Welcome,') }} {{ (page.props.auth as any).user.name }}.
            </h1>
            <p class="muted">
                {{
                    t(
                        'Find your next pair, manage your account, or apply for our wholesale program.',
                    )
                }}
            </p>
            <div class="order-actions">
                <Link href="/settings/connections" class="button outline">{{
                    t('Sign-in methods')
                }}</Link>
                <Link href="/orders" class="button outline">
                    {{ t('Your orders') }}
                </Link>
                <Link href="/" class="button dark">
                    {{ t('Shop the collection ↗') }} </Link
                ><Link href="/wholesale" class="button outline">
                    {{ t('Wholesale program') }} </Link
                ><Link href="/settings/profile" class="button outline">
                    {{ t('Account settings') }} </Link
                ><Link
                    v-if="(page.props.auth as any).user.is_admin"
                    href="/admin"
                    class="button outline"
                >
                    {{ t('Team workspace') }}
                </Link>
            </div>
        </section></ShopLayout
    >
</template>
