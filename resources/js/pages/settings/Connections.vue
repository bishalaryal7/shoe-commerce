<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import PhoneVerification from '@/components/PhoneVerification.vue';
import { useLocale } from '@/composables/useLocale';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
const { t } = useLocale();
defineProps<{
    signInOptions: { google: boolean; facebook: boolean; phone: boolean };
    connected: string[];
    phone: string | null;
    challenge: { phone: string; expiresAt: string } | null;
}>();
const page = usePage();
const busy = ref(false);
const providers = [
    { id: 'google' as const, name: 'Google' },
    { id: 'facebook' as const, name: 'Facebook' },
];
function connect(provider: string) {
    router.post(
        '/settings/connections/' + provider,
        {},
        {
            onStart: () => (busy.value = true),
            onFinish: () => (busy.value = false),
        },
    );
}
function disconnect(provider: string) {
    router.delete('/settings/connections/' + provider, {
        preserveScroll: true,
        onStart: () => (busy.value = true),
        onFinish: () => (busy.value = false),
    });
}
</script>
<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Sign-in methods', href: '/settings/connections' },
        ]"
    >
        <Head :title="t('Sign-in methods')" />
        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    :title="t('Sign-in methods')"
                    :description="
                        t(
                            'Connect the accounts you use and keep a verified mobile number for easy sign-in.',
                        )
                    "
                />
                <p
                    v-if="(page.props.flash as any)?.success"
                    role="status"
                    class="rounded-lg bg-green-50 p-3 text-sm text-green-800"
                >
                    {{ (page.props.flash as any).success }}
                </p>
                <p
                    v-if="page.props.errors.social"
                    role="alert"
                    class="text-sm text-red-600"
                >
                    {{ page.props.errors.social }}
                </p>
                <div
                    v-for="provider in providers"
                    :key="provider.id"
                    class="flex items-center justify-between gap-4 rounded-xl border border-border p-4"
                >
                    <div>
                        <h3 class="font-semibold">{{ t(provider.name) }}</h3>
                        <p class="text-sm text-muted-foreground">
                            {{
                                connected.includes(provider.id)
                                    ? t('Connected')
                                    : signInOptions[provider.id]
                                      ? t('Ready to connect')
                                      : t('Not available yet')
                            }}
                        </p>
                    </div>
                    <button
                        v-if="connected.includes(provider.id)"
                        type="button"
                        :disabled="busy"
                        class="text-sm underline underline-offset-4"
                        @click="disconnect(provider.id)"
                    >
                        {{ t('Disconnect') }}
                    </button>
                    <button
                        v-else-if="signInOptions[provider.id]"
                        type="button"
                        :disabled="busy"
                        class="rounded-lg bg-primary px-4 py-2 text-sm text-primary-foreground disabled:opacity-50"
                        @click="connect(provider.id)"
                    >
                        {{ t('Connect') }}
                    </button>
                </div>
                <section class="space-y-4 rounded-xl border border-border p-5">
                    <h3 class="font-semibold">{{ t('Phone sign-in') }}</h3>
                    <div
                        v-if="phone"
                        class="flex items-center justify-between gap-4 text-sm"
                    >
                        <p>{{ phone }} · {{ t('Verified') }}</p>
                        <button
                            type="button"
                            :disabled="busy"
                            class="underline underline-offset-4"
                            @click="disconnect('phone')"
                        >
                            {{ t('Disconnect') }}
                        </button>
                    </div>
                    <PhoneVerification
                        v-if="signInOptions.phone"
                        base-url="/settings/connections/phone"
                        :challenge="challenge"
                        :initial-phone="phone"
                    />
                    <p v-else class="text-sm text-muted-foreground">
                        {{
                            t(
                                'Phone sign-in is not available yet. Please use email and password.',
                            )
                        }}
                    </p>
                </section>
                <p class="text-sm text-muted-foreground">
                    {{
                        t(
                            'For your security, connecting or removing a sign-in method may ask for your password.',
                        )
                    }}
                    {{
                        t(
                            'Joined with a social account? Use the password reset page to set your account password first.',
                        )
                    }}
                    <button
                        type="button"
                        :disabled="busy"
                        class="underline underline-offset-4"
                        @click="
                            router.post(
                                '/settings/connections/password-reset',
                                {},
                                {
                                    onStart: () => (busy = true),
                                    onFinish: () => (busy = false),
                                },
                            )
                        "
                    >
                        {{ t('Email a reset link and sign out') }}
                    </button>
                    <span
                        v-if="page.props.errors.password_reset"
                        role="alert"
                        class="block text-red-600"
                        >{{ page.props.errors.password_reset }}</span
                    >
                </p>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
