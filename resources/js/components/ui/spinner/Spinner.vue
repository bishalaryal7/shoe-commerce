<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
const { t } = useLocale();
import type { HTMLAttributes } from "vue"
import { Loader2Icon } from "lucide-vue-next"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <Loader2Icon
    role="status"
    :aria-label="t('Loading')"
    :class="cn('size-4 animate-spin', props.class)"
  />
</template>
