<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import type { ShopSharedProps } from '@/types/shop';
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
const { t } = useLocale();
const page = usePage<ShopSharedProps>();
const busy = ref(false);
function change(event: Event) {
    busy.value = true;
    router.post(
        '/language',
        { locale: (event.target as HTMLSelectElement).value },
        { preserveScroll: true, onFinish: () => (busy.value = false) },
    );
}
</script>
<template>
    <label class="language-switch"
        ><span class="sr-only"> {{ t('Language / भाषा') }} </span
        ><select
            :value="page.props.locale"
            :disabled="busy"
            :aria-label="t('Language / भाषा')"
            @change="change"
        >
            <option value="en">{{ t('English') }}</option>
            <option value="ne">नेपाली</option>
        </select></label
    >
</template>
