<script setup lang="ts">
const { t } = useLocale();
import { useLocale } from '@/composables/useLocale';
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <AuthLayout :title="t(title)" :description="t(description)">
        <slot />
    </AuthLayout>
</template>
