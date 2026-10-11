<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import { Head } from '@inertiajs/vue3';
const { t } = useLocale();

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/appearance';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Appearance settings',
        href: edit().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('Appearance settings')" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    :title="t('Appearance settings')"
                    :description="
                        t('Update your account\'s appearance settings')
                    "
                />
                <AppearanceTabs />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
