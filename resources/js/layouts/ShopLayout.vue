<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Menu, ShoppingBag, X } from 'lucide-vue-next';
import { ref } from 'vue';
const page = usePage();
const open = ref(false);
</script>
<template>
    <div class="store">
        <div class="announcement">
            GOOD SHOES. EVERY DAY.
            <span>Retail & wholesale, all in one place.</span>
        </div>
        <header class="shop-header wrap">
            <Link href="/" class="wordmark"
                >{{ page.props.name }}<span class="brand-dot">®</span></Link
            >
            <nav :class="['shop-nav', { open }]" aria-label="Main navigation">
                <Link href="/" @click="open = false">Shop all</Link>
                <Link href="/?category=Everyday" @click="open = false"
                    >Everyday</Link
                >
                <Link href="/?category=Running" @click="open = false"
                    >Running</Link
                >
                <Link href="/wholesale" @click="open = false"
                    >Wholesale <ArrowUpRight :size="14"
                /></Link>
            </nav>
            <div class="header-actions">
                <Link
                    v-if="(page.props.auth as any)?.user?.is_admin"
                    href="/admin"
                    >Admin</Link
                >
                <Link v-if="!(page.props.auth as any)?.user" href="/login"
                    >Sign in</Link
                >
                <Link v-else href="/logout" method="post" as="button"
                    >Sign out</Link
                >
                <Link href="/cart" class="bag" aria-label="Shopping bag"
                    ><ShoppingBag :size="20" /><span>{{
                        page.props.cartCount || 0
                    }}</span></Link
                >
                <button
                    class="mobile-menu"
                    @click="open = !open"
                    :aria-expanded="open"
                    aria-label="Toggle navigation"
                >
                    <X v-if="open" :size="22" /><Menu v-else :size="22" />
                </button>
            </div>
        </header>
        <div
            v-if="(page.props.flash as any)?.success"
            role="status"
            class="flash wrap"
        >
            {{ (page.props.flash as any).success }}
        </div>
        <main><slot /></main>
        <footer class="shop-footer wrap">
            <div>
                <Link href="/" class="wordmark"
                    >{{ page.props.name }}<span class="brand-dot">®</span></Link
                >
                <p>For your next step. And every step after.</p>
            </div>
            <div>
                <Link href="/">Shop the collection</Link
                ><Link href="/wholesale">Become a wholesale partner ↗</Link
                ><Link href="/login">Your account</Link>
            </div>
            <small
                >© {{ new Date().getFullYear() }} {{ page.props.name }}. All
                rights reserved.</small
            >
        </footer>
    </div>
</template>
