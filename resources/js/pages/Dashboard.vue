<script setup lang="ts">
import ShopLayout from '@/layouts/ShopLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
const page = usePage();
</script>
<template>
    <Head title="Your account" /><ShopLayout
        ><section class="wrap inner-page">
            <div class="eyebrow">YOUR ACCOUNT</div>
            <h1>Welcome, {{ (page.props.auth as any).user.name }}.</h1>
            <p class="muted">
                Find your next pair, manage your account, or apply for our
                wholesale program.
            </p>
            <div class="order-actions">
                <Link href="/" class="button dark">Shop the collection ↗</Link
                ><Link href="/wholesale" class="button outline"
                    >Wholesale program</Link
                ><Link href="/settings/profile" class="button outline"
                    >Account settings</Link
                ><Link
                    v-if="(page.props.auth as any).user.is_admin"
                    href="/admin"
                    class="button outline"
                    >Team workspace</Link
                >
            </div>
        </section></ShopLayout
    >
</template>
