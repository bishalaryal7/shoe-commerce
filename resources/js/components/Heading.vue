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
    <div class="mb-8 space-y-0.5">
        <h2 class="text-xl font-semibold tracking-tight">{{ t(title) }}</h2>
        <p v-if="description" class="text-sm text-muted-foreground">
            {{ t(description) }}
        </p>
    </div>
</template>
