<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import { router, usePage } from '@inertiajs/vue3';
import { Facebook, Smartphone } from 'lucide-vue-next';
import { ref } from 'vue';
const { t } = useLocale();
const props = defineProps<{
    options?: { google: boolean; facebook: boolean; phone: boolean };
}>();
const page = usePage();
const busy = ref(false);
const unavailable = ref(false);
function signIn(provider: 'google' | 'facebook' | 'phone') {
    unavailable.value = !props.options?.[provider];
    if (unavailable.value) return;
    if (provider === 'phone') {
        router.visit('/login/phone');
        return;
    }
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
        <div class="grid gap-3">
            <button
                type="button"
                :disabled="busy"
                class="flex min-h-12 items-center justify-center gap-2 rounded-xl border border-input px-4 py-3 text-sm font-semibold transition hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50"
                @click="signIn('google')"
            >
                <svg
                    aria-hidden="true"
                    viewBox="0 0 24 24"
                    class="size-5 shrink-0"
                >
                    <path
                        fill="#4285F4"
                        d="M21.6 12.23c0-.71-.06-1.39-.18-2.05H12v3.88h5.38a4.6 4.6 0 0 1-2 3.01v2.5h3.24c1.9-1.75 2.98-4.33 2.98-7.34Z"
                    />
                    <path
                        fill="#34A853"
                        d="M12 22c2.7 0 4.96-.9 6.62-2.43l-3.24-2.5c-.9.6-2.05.97-3.38.97-2.61 0-4.82-1.77-5.61-4.15H3.05v2.59A10 10 0 0 0 12 22Z"
                    />
                    <path
                        fill="#FBBC05"
                        d="M6.39 13.89a6 6 0 0 1 0-3.78V7.52H3.05a10 10 0 0 0 0 8.96l3.34-2.59Z"
                    />
                    <path
                        fill="#EA4335"
                        d="M12 5.96c1.47 0 2.79.51 3.82 1.51l2.87-2.87A9.6 9.6 0 0 0 12 2a10 10 0 0 0-8.95 5.52l3.34 2.59C7.18 7.73 9.39 5.96 12 5.96Z"
                    />
                </svg>
                {{ t('Continue with Google') }}
            </button>
            <button
                type="button"
                :disabled="busy"
                class="flex min-h-12 items-center justify-center gap-2 rounded-xl border border-input px-4 py-3 text-sm font-semibold transition hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50"
                @click="signIn('facebook')"
            >
                <Facebook
                    aria-hidden="true"
                    class="size-5 shrink-0 text-blue-600"
                />
                {{ t('Continue with Facebook') }}
            </button>
        </div>
        <button
            type="button"
            :disabled="busy"
            class="flex min-h-12 w-full items-center justify-center gap-2 rounded-xl border border-input px-4 py-3 text-sm font-semibold transition hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50"
            @click="signIn('phone')"
        >
            <Smartphone aria-hidden="true" class="size-5 shrink-0" />
            {{ t('Sign in with phone') }}
        </button>
        <div role="status" aria-live="polite">
            <p
                v-if="unavailable"
                class="rounded-lg bg-muted p-3 text-sm text-muted-foreground"
            >
                {{
                    t(
                        'This sign-in method is not available yet. Please use email and password.',
                    )
                }}
            </p>
        </div>
        <p
            v-if="page.props.errors.social"
            role="alert"
            class="text-sm text-red-600"
        >
            {{ page.props.errors.social }}
        </p>
        <div class="flex items-center gap-3 py-2 text-xs text-muted-foreground">
            <span class="h-px flex-1 bg-border" />{{ t('Or use your email')
            }}<span class="h-px flex-1 bg-border" />
        </div>
    </div>
</template>
