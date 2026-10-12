<script setup lang="ts">
import LanguageSwitch from '@/components/LanguageSwitch.vue';
import { useLocale } from '@/composables/useLocale';
import type { ShopSharedProps } from '@/types/shop';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    Bell,
    Boxes,
    Check,
    ChevronRight,
    CreditCard,
    LogOut,
    Mountain,
    Settings2,
    ShoppingBag,
    UsersRound,
} from 'lucide-vue-next';
import { onMounted, onUnmounted } from 'vue';
const { t, num } = useLocale();
const tab = defineModel<string>('tab', { required: true });
const page = usePage<ShopSharedProps>();
const sections = [
    { label: 'Orders', icon: ShoppingBag },
    { label: 'Returns', icon: ShoppingBag },
    { label: 'Inventory', icon: Boxes },
    { label: 'Payments', icon: CreditCard },
    { label: 'Partners', icon: UsersRound },
    { label: 'Promotions', icon: CreditCard },
    { label: 'Reviews', icon: UsersRound },
    { label: 'Questions', icon: UsersRound },
    { label: 'Messages', icon: UsersRound },
    { label: 'Team', icon: UsersRound },
];
let notificationPoll: ReturnType<typeof setInterval> | undefined;
onMounted(() => {
    if (page.props.auth.user) {
        notificationPoll = setInterval(
            () =>
                router.reload({
                    only: ['auth'],
                }),
            60_000,
        );
    }
});
onUnmounted(() => {
    if (notificationPoll) clearInterval(notificationPoll);
});
</script>
<template>
    <div class="store admin-shell">
        <a href="#workspace-content" class="skip-link">
            {{ t('Skip to workspace') }}
        </a>
        <aside class="workspace-sidebar">
            <Link href="/admin" class="wordmark"
                ><span class="brand-symbol"><Mountain :size="21" /></span
                >{{ page.props.name }}</Link
            ><span class="workspace-label"> {{ t('TEAM WORKSPACE') }} </span>
            <nav :aria-label="t('Workspace navigation')">
                <button
                    v-for="section in sections"
                    :key="section.label"
                    :class="{ active: tab === section.label }"
                    :aria-current="tab === section.label ? 'page' : undefined"
                    @click="tab = section.label"
                >
                    <component :is="section.icon" :size="19" />{{
                        t(section.label)
                    }}<ChevronRight v-if="tab === section.label" :size="15" />
                </button>
            </nav>
            <div class="sidebar-bottom">
                <Link href="/" class="view-store">
                    {{ t('Visit storefront') }}
                    <ArrowUpRight :size="16" /></Link
                ><Link href="/notifications">
                    <Bell :size="16" /> {{ t('Notifications') }}
                    <span v-if="page.props.auth.unreadNotifications"
                        >({{ num(page.props.auth.unreadNotifications) }})</span
                    > </Link
                ><Link href="/settings/profile"
                    ><Settings2 :size="16" /> {{ t('Account settings') }} </Link
                ><Link href="/logout" method="post" as="button"
                    ><LogOut :size="16" /> {{ t('Sign out') }}
                </Link>
            </div>
        </aside>
        <div class="workspace-main">
            <header class="workspace-topbar">
                <LanguageSwitch />
                <span>
                    {{ t('Workspace') }} <ChevronRight :size="14" /><strong>{{
                        t(tab)
                    }}</strong></span
                ><span class="workspace-user"
                    ><span class="avatar">{{
                        page.props.auth.user?.name.charAt(0).toUpperCase()
                    }}</span
                    >{{ page.props.auth.user?.name }}</span
                >
            </header>
            <main id="workspace-content" tabindex="-1">
                <div
                    v-if="page.props.flash.success"
                    class="workspace-notice"
                    role="status"
                >
                    <Check :size="17" />{{ t(page.props.flash.success) }}
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
