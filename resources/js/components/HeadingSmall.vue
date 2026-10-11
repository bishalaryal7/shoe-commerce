<script setup lang="ts">
const { t } = useLocale();
import { useLocale } from '@/composables/useLocale';
interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <header>
        <h3 class="mb-0.5 text-base font-medium">{{ t(title) }}</h3>
        <p v-if="description" class="text-sm text-muted-foreground">
            {{ t(description) }}
        </p>
    </header>
</template>
