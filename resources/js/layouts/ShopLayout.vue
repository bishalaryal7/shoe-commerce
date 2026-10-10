<script setup lang="ts">
import type { ShopSharedProps } from '@/types/shop';
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    Check,
    Footprints,
    Menu,
    ShoppingBag,
    UserRound,
    X,
} from 'lucide-vue-next';
import { computed, onUnmounted, ref, watch } from 'vue';
const page = usePage<ShopSharedProps>();
const open = ref(false);
const user = computed(() => page.props.auth.user);
const notice = ref('');
let timer: ReturnType<typeof setTimeout>;
watch(
    () => page.props.flash,
    (flash) => {
        const message = flash.success;
        clearTimeout(timer);
        notice.value = message || '';
        if (message) timer = setTimeout(() => (notice.value = ''), 6500);
    },
    { immediate: true },
);
onUnmounted(() => clearTimeout(timer));
const current = computed(() => page.url.split('?')[0]);
</script>
<template>
    <div class="store">
        <a class="skip-link" href="#main-content">Skip to content</a>
        <div class="announcement">
            <span>Everyday pairs. Business possibilities.</span
            ><Link href="/wholesale"
                >Explore wholesale <ArrowUpRight :size="12"
            /></Link>
        </div>
        <header class="header-shell">
            <div class="shop-header wrap">
                <Link href="/" class="wordmark"
                    ><span class="brand-symbol"><Footprints :size="22" /></span
                    >{{ page.props.name }}</Link
                >
                <nav
                    id="shop-navigation"
                    :class="['shop-nav', { open }]"
                    aria-label="Main navigation"
                >
                    <Link
                        href="/"
                        :class="{ selected: current === '/' }"
                        @click="open = false"
                        >Shop all</Link
                    >
                    <Link
                        href="/?category=Everyday#collection"
                        @click="open = false"
                        >Everyday</Link
                    >
                    <Link
                        href="/?category=Running#collection"
                        @click="open = false"
                        >Running</Link
                    >
                    <Link
                        href="/wholesale"
                        :class="{ selected: current === '/wholesale' }"
                        @click="open = false"
                        >For businesses <ArrowUpRight :size="13"
                    /></Link>
                </nav>
                <div class="header-actions">
                    <Link
                        v-if="user?.is_admin"
                        href="/admin"
                        class="admin-shortcut"
                        >Workspace</Link
                    >
                    <details v-if="user" class="account-menu">
                        <summary aria-label="Account menu">
                            <UserRound :size="19" /><span>Account</span>
                        </summary>
                        <div class="account-dropdown">
                            <strong>{{ user.name }}</strong
                            ><Link href="/dashboard">My account</Link
                            ><Link href="/settings/profile"
                                >Account settings</Link
                            ><Link v-if="user.is_admin" href="/admin"
                                >Team workspace</Link
                            ><Link href="/logout" method="post" as="button"
                                >Sign out</Link
                            >
                        </div>
                    </details>
                    <Link v-else href="/login" class="account-link"
                        ><UserRound :size="19" /><span>Sign in</span></Link
                    >
                    <Link
                        href="/cart"
                        class="bag"
                        :aria-label="
                            'Shopping bag, ' + page.props.cartCount + ' pairs'
                        "
                        ><ShoppingBag :size="20" /><span>{{
                            page.props.cartCount
                        }}</span></Link
                    >
                    <button
                        class="mobile-menu"
                        @click="open = !open"
                        :aria-expanded="open"
                        aria-controls="shop-navigation"
                        aria-label="Toggle navigation"
                    >
                        <X v-if="open" :size="21" /><Menu v-else :size="21" />
                    </button>
                </div>
            </div>
        </header>
        <Transition name="toast"
            ><div v-if="notice" class="shop-toast" role="status">
                <span class="toast-check"><Check :size="17" /></span>
                <div>
                    <strong>{{ notice }}</strong
                    ><Link href="/cart">View your bag →</Link>
                </div>
                <button @click="notice = ''" aria-label="Dismiss notification">
                    <X :size="17" />
                </button></div
        ></Transition>
        <main id="main-content" tabindex="-1"><slot /></main>
        <footer class="shop-footer wrap">
            <div>
                <Link href="/" class="wordmark"
                    ><span class="brand-symbol"><Footprints :size="22" /></span
                    >{{ page.props.name }}</Link
                >
                <p>A little more comfort.<br />A lot more possibility.</p>
            </div>
            <div class="footer-links">
                <strong>Find your next step</strong
                ><Link href="/">Shop all shoes</Link
                ><Link href="/wholesale">Wholesale partners</Link
                ><Link :href="user ? '/dashboard' : '/login'"
                    >Your account</Link
                >
            </div>
            <div class="footer-note">
                <span class="eyebrow">FOR EVERY WAY YOU MOVE</span>
                <p>From one perfect pair<br />to your next bulk order.</p>
            </div>
            <div class="footer-bottom">
                <span
                    >© {{ new Date().getFullYear() }}
                    {{ page.props.name }}</span
                ><span>Retail & wholesale · All in one place</span>
            </div>
        </footer>
    </div>
</template>
