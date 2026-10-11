<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import ShopLayout from '@/layouts/ShopLayout.vue';
import type { ShopSharedProps } from '@/types/shop';
import { Head, Link, usePage } from '@inertiajs/vue3';
const { t } = useLocale();
const page = usePage<ShopSharedProps>();
</script>
<template>
    <Head :title="t('Staff access required')" /><ShopLayout
        ><section class="wrap inner-page">
            <span class="eyebrow"> {{ t('TEAM WORKSPACE') }} </span>
            <h1>{{ t('Staff access required.') }}</h1>
            <p>
                {{ t('You’re signed in as') }}
                <strong>{{ page.props.auth.user?.email }}</strong>
                {{ t('. This account has customer access.') }}
            </p>
            <p>
                {{
                    t(
                        'Ask the store owner to grant staff access to this email, or sign in with your staff account.',
                    )
                }}
            </p>
            <div class="order-actions">
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="button dark"
                >
                    {{ t('Sign out to switch account') }} </Link
                ><Link href="/" class="button outline">
                    {{ t('Back to the shop') }}
                </Link>
            </div>
        </section></ShopLayout
    >
</template>
