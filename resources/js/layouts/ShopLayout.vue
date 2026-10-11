<script setup lang="ts">
import LanguageSwitch from '@/components/LanguageSwitch.vue';
import { useLocale } from '@/composables/useLocale';
import type { ShopSharedProps } from '@/types/shop';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeftRight,
    ArrowRight,
    ArrowUpRight,
    Check,
    Heart,
    Menu,
    Mountain,
    Search,
    ShoppingBag,
    UserRound,
    X,
} from 'lucide-vue-next';
import { computed, onUnmounted, ref, watch } from 'vue';
const { t, num } = useLocale();
const page = usePage<ShopSharedProps>();
const open = ref(false);
const user = computed(() => page.props.auth.user);
const notice = ref('');
const savedNotice = computed(() =>
    [t('Shoe saved for later.'), t('Shoe removed from saved list.')].includes(
        notice.value,
    ),
);
const savedError = computed(() => page.props.errors?.saved || '');
const compareError = computed(() => page.props.errors?.compare || '');
const compareNotice = computed(() =>
    [
        t('Shoe added to comparison.'),
        t('Shoe removed from comparison.'),
        t('Comparison list cleared.'),
    ].includes(notice.value),
);
const reviewNotice = computed(
    () => notice.value === t('Thank you for sharing your review.'),
);
const query = ref(
    new URL(page.url, 'http://localhost').searchParams.get('q') || '',
);
function searchShop() {
    open.value = false;
    router.get('/', { q: query.value || undefined }, { preserveState: false });
}
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
        <a class="skip-link" href="#main-content">{{ t('Skip to content') }}</a>
        <div class="announcement">
            <span>{{ t('Reach higher. Walk further.') }}</span
            ><Link href="/wholesale"
                >{{ t('Explore wholesale') }} <ArrowUpRight :size="12"
            /></Link>
        </div>
        <header class="header-shell">
            <div class="shop-header wrap">
                <Link href="/" class="wordmark"
                    ><span class="brand-symbol"><Mountain :size="22" /></span
                    >{{ page.props.name }}</Link
                >
                <LanguageSwitch />
                <form class="header-search" @submit.prevent="searchShop()">
                    <Search :size="17" /><input
                        v-model="query"
                        type="search"
                        maxlength="100"
                        :placeholder="
                            t('Search shoes, styles, your next favorite…')
                        "
                        :aria-label="t('Search the store')"
                    /><button type="submit" :aria-label="t('Search')">
                        <ArrowRight :size="17" />
                    </button>
                </form>

                <div class="header-actions">
                    <Link
                        v-if="user?.is_admin"
                        href="/admin"
                        class="admin-shortcut"
                    >
                        {{ t('Workspace') }}
                    </Link>
                    <details v-if="user" class="account-menu">
                        <summary :aria-label="t('Account menu')">
                            <UserRound :size="19" /><span>
                                {{ t('Account') }}
                            </span>
                        </summary>
                        <div class="account-dropdown">
                            <strong>{{ user.name }}</strong
                            ><Link href="/orders">{{ t('Your orders') }}</Link
                            ><Link href="/settings/addresses">{{
                                t('Address book')
                            }}</Link
                            ><Link href="/saved">{{ t('Saved shoes') }}</Link
                            ><Link href="/dashboard">{{ t('My account') }}</Link
                            ><Link href="/settings/profile">
                                {{ t('Account settings') }} </Link
                            ><Link v-if="user.is_admin" href="/admin">{{
                                t('Team workspace')
                            }}</Link
                            ><Link href="/logout" method="post" as="button">{{
                                t('Sign out')
                            }}</Link>
                        </div>
                    </details>
                    <Link v-else href="/login" class="account-link"
                        ><UserRound :size="19" /><span>{{
                            t('Sign in')
                        }}</span></Link
                    >
                    <Link
                        href="/cart"
                        class="bag"
                        :aria-label="
                            t('Shopping bag, :count pairs', {
                                count: num(page.props.cartCount),
                            })
                        "
                        ><ShoppingBag :size="20" /><span>{{
                            num(page.props.cartCount)
                        }}</span></Link
                    >
                    <button
                        class="mobile-menu"
                        @click="open = !open"
                        :aria-expanded="open"
                        aria-controls="shop-navigation"
                        :aria-label="t('Toggle navigation')"
                    >
                        <X v-if="open" :size="21" /><Menu v-else :size="21" />
                    </button>
                </div>
            </div>
            <nav
                id="shop-navigation"
                :class="['shop-nav', 'wrap', { open }]"
                :aria-label="t('Main navigation')"
            >
                <Link
                    href="/"
                    :class="{ selected: current === '/' }"
                    @click="open = false"
                >
                    {{ t('Shop all') }}
                </Link>
                <Link
                    href="/size-guide"
                    :class="{ selected: current === '/size-guide' }"
                    @click="open = false"
                    >{{ t('Size guide') }}</Link
                >
                <Link
                    href="/?category=Everyday#collection"
                    @click="open = false"
                    >{{ t('Everyday') }}</Link
                >
                <Link
                    href="/?category=Running#collection"
                    @click="open = false"
                    >{{ t('Running') }}</Link
                >
                <Link
                    href="/saved"
                    :class="{ selected: current === '/saved' }"
                    @click="open = false"
                    ><Heart :size="15" /> {{ t('Saved shoes') }}</Link
                >
                <Link
                    href="/compare"
                    :class="{ selected: current === '/compare' }"
                    @click="open = false"
                    ><ArrowLeftRight :size="15" /> {{ t('Compare')
                    }}<span v-if="page.props.compareIds?.length"
                        >({{ num(page.props.compareIds.length) }})</span
                    ></Link
                >
                <Link
                    href="/wholesale"
                    :class="{ selected: current === '/wholesale' }"
                    @click="open = false"
                >
                    {{ t('For businesses') }} <ArrowUpRight :size="13"
                /></Link>
            </nav>
        </header>
        <Transition name="toast"
            ><div
                v-if="notice || savedError || compareError"
                class="shop-toast"
                role="status"
            >
                <span class="toast-check"><Check :size="17" /></span>
                <div>
                    <strong>{{
                        t(compareError || savedError || notice)
                    }}</strong
                    ><Link v-if="compareNotice || compareError" href="/compare">
                        {{ t('View comparison →') }} </Link
                    ><Link v-else-if="savedNotice || savedError" href="/saved">
                        {{ t('View saved shoes →') }} </Link
                    ><Link v-else-if="!reviewNotice" href="/cart">
                        {{ t('View your bag →') }}
                    </Link>
                </div>
                <button
                    @click="notice = ''"
                    :aria-label="t('Dismiss notification')"
                >
                    <X :size="17" />
                </button></div
        ></Transition>
        <main id="main-content" tabindex="-1"><slot /></main>
        <footer class="shop-footer wrap">
            <div>
                <Link href="/" class="wordmark"
                    ><span class="brand-symbol"><Mountain :size="22" /></span
                    >{{ page.props.name }}</Link
                >
                <p>
                    {{ t('A little more comfort.') }} <br />
                    {{ t('A lot more possibility.') }}
                </p>
            </div>
            <div class="footer-links">
                <strong> {{ t('Find your next step') }} </strong
                ><Link href="/"> {{ t('Shop all shoes') }} </Link
                ><Link href="/size-guide">{{ t('Size guide') }}</Link
                ><Link href="/saved"> {{ t('Saved shoes') }} </Link
                ><Link href="/compare"> {{ t('Compare shoes') }} </Link
                ><Link href="/wholesale"> {{ t('Wholesale partners') }} </Link
                ><Link :href="user ? '/dashboard' : '/login'">
                    {{ t('Your account') }}
                </Link>
            </div>
            <div class="footer-note">
                <span class="eyebrow"> {{ t('FOR EVERY WAY YOU MOVE') }} </span>
                <p>
                    {{ t('From one perfect pair') }} <br />
                    {{ t('to your next bulk order.') }}
                </p>
            </div>
            <div class="footer-bottom">
                <span
                    >© {{ num(new Date().getFullYear(), false) }}
                    {{ page.props.name }}</span
                ><span> {{ t('Retail & wholesale · All in one place') }} </span>
            </div>
        </footer>
    </div>
</template>
