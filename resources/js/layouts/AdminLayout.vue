<script setup lang="ts">
import type { ShopSharedProps } from '@/types/shop';
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    Boxes,
    Check,
    ChevronRight,
    Footprints,
    LogOut,
    Settings2,
    ShoppingBag,
    UsersRound,
} from 'lucide-vue-next';
const tab = defineModel<string>('tab', { required: true });
const page = usePage<ShopSharedProps>();
const sections = [
    { label: 'Orders', icon: ShoppingBag },
    { label: 'Inventory', icon: Boxes },
    { label: 'Partners', icon: UsersRound },
];
</script>
<template>
    <div class="store admin-shell">
        <a href="#workspace-content" class="skip-link">Skip to workspace</a>
        <aside class="workspace-sidebar">
            <Link href="/admin" class="wordmark"
                ><span class="brand-symbol"><Footprints :size="21" /></span
                >{{ page.props.name }}</Link
            ><span class="workspace-label">TEAM WORKSPACE</span>
            <nav aria-label="Workspace navigation">
                <button
                    v-for="section in sections"
                    :key="section.label"
                    :class="{ active: tab === section.label }"
                    :aria-current="tab === section.label ? 'page' : undefined"
                    @click="tab = section.label"
                >
                    <component :is="section.icon" :size="19" />{{ section.label
                    }}<ChevronRight v-if="tab === section.label" :size="15" />
                </button>
            </nav>
            <div class="sidebar-bottom">
                <Link href="/" class="view-store"
                    >Visit storefront <ArrowUpRight :size="16" /></Link
                ><Link href="/settings/profile"
                    ><Settings2 :size="16" />Account settings</Link
                ><Link href="/logout" method="post" as="button"
                    ><LogOut :size="16" />Sign out</Link
                >
            </div>
        </aside>
        <div class="workspace-main">
            <header class="workspace-topbar">
                <span
                    >Workspace <ChevronRight :size="14" /><strong>{{
                        tab
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
                    <Check :size="17" />{{ page.props.flash.success }}
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
