<script setup lang="ts">
import ShopLayout from '@/layouts/ShopLayout.vue';
import type { ShopSharedProps } from '@/types/shop';
import { Head, Link, usePage } from '@inertiajs/vue3';
const page = usePage<ShopSharedProps>();
</script>
<template>
    <Head title="Staff access required" /><ShopLayout
        ><section class="wrap inner-page">
            <span class="eyebrow">TEAM WORKSPACE</span>
            <h1>Staff access required.</h1>
            <p>
                You’re signed in as
                <strong>{{ page.props.auth.user?.email }}</strong
                >. This account has customer access.
            </p>
            <p>
                Ask the store owner to grant staff access to this email, or sign
                in with your staff account.
            </p>
            <div class="order-actions">
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="button dark"
                    >Sign out to switch account</Link
                ><Link href="/" class="button outline">Back to the shop</Link>
            </div>
        </section></ShopLayout
    >
</template>
