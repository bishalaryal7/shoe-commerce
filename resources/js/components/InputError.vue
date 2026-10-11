<script setup lang="ts">
const { t } = useLocale();
import { useLocale } from '@/composables/useLocale';
defineProps<{
    message?: string;
}>();
</script>

<template>
    <div v-show="message">
        <p class="text-sm text-red-600 dark:text-red-500">
            {{ t(message) }}
        </p>
    </div>
</template>
