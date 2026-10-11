<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
const { t } = useLocale();
defineProps<{
    options?: { google: boolean; facebook: boolean; phone: boolean };
}>();
const page = usePage();
const busy = ref(false);
function signIn(provider: string) {
    router.post(
        '/auth/' + provider,
        {},
        {
            onStart: () => (busy.value = true),
            onFinish: () => (busy.value = false),
        },
    );
}
</script>
<template>
    <div class="space-y-3">
        <p
            v-if="page.props.errors.social"
            role="alert"
            class="text-sm text-red-600"
        >
            {{ page.props.errors.social }}
        </p>
        <div
            v-if="options?.google || options?.facebook"
            class="grid gap-3 sm:grid-cols-2"
        >
            <button
                v-if="options?.google"
                type="button"
                :disabled="busy"
                class="rounded-xl border border-input px-4 py-3 text-sm font-semibold transition hover:bg-muted disabled:opacity-50"
                @click="signIn('google')"
            >
                {{ t('Continue with Google') }}
            </button>
            <button
                v-if="options?.facebook"
                type="button"
                :disabled="busy"
                class="rounded-xl border border-input px-4 py-3 text-sm font-semibold transition hover:bg-muted disabled:opacity-50"
                @click="signIn('facebook')"
            >
                {{ t('Continue with Facebook') }}
            </button>
        </div>
        <Link
            v-if="options?.phone"
            href="/login/phone"
            class="block rounded-xl border border-input px-4 py-3 text-center text-sm font-semibold transition hover:bg-muted"
            >{{ t('Sign in with phone') }}</Link
        >
        <div
            v-if="options?.google || options?.facebook || options?.phone"
            class="flex items-center gap-3 py-2 text-xs text-muted-foreground"
        >
            <span class="h-px flex-1 bg-border" />{{ t('Or use your email')
            }}<span class="h-px flex-1 bg-border" />
        </div>
    </div>
</template>
