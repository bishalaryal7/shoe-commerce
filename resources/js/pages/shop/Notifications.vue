<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Bell, CheckCheck, PackageCheck } from 'lucide-vue-next';
defineProps<{
    notifications: {
        data: {
            id: string;
            read_at: string | null;
            created_at: string;
            data: {
                title: string;
                message: string;
                url: string;
                category: string;
            };
        }[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();
const { t, date, paginationLabel } = useLocale();
function readAll() {
    router.post('/notifications/read-all', {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('Notifications')" />
    <ShopLayout>
        <section class="wrap inner-page notification-page">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">{{ t('YOUR ACCOUNT') }}</span>
                    <h1>{{ t('Your notifications.') }}</h1>
                </div>
                <button
                    v-if="notifications.data.some((item) => !item.read_at)"
                    class="button outline"
                    @click="readAll"
                >
                    <CheckCheck :size="16" /> {{ t('Mark all as read') }}
                </button>
            </div>
            <div v-if="!notifications.data.length" class="empty-state">
                <span class="empty-icon"><Bell :size="27" /></span>
                <h2>{{ t('You’re all caught up.') }}</h2>
                <p>
                    {{
                        t(
                            'Order progress, shop replies, and answers to your questions will appear here.',
                        )
                    }}
                </p>
                <Link href="/orders" class="button dark"
                    ><PackageCheck :size="16" />
                    {{ t('View your orders') }}</Link
                >
            </div>
            <div v-else class="notification-list">
                <Link
                    v-for="item in notifications.data"
                    :key="item.id"
                    :href="'/notifications/' + item.id + '/read'"
                    method="post"
                    as="button"
                    class="notification-item"
                    :class="{ unread: !item.read_at }"
                >
                    <span class="notification-dot" />
                    <span class="notification-copy">
                        <strong>{{ t(item.data.title) }}</strong>
                        <span>{{ t(item.data.message) }}</span>
                        <small>{{ date(item.created_at, true) }}</small>
                    </span>
                </Link>
            </div>
            <nav
                v-if="notifications.links.length > 3"
                class="pagination"
                :aria-label="t('Notification pages')"
            >
                <template
                    v-for="(link, index) in notifications.links"
                    :key="index"
                >
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="{ active: link.active }"
                        ><span v-text="paginationLabel(link.label)"
                    /></Link>
                    <span v-else v-text="paginationLabel(link.label)" />
                </template>
            </nav>
        </section>
    </ShopLayout>
</template>
