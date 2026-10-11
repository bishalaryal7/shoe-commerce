<script setup lang="ts">
import PhoneVerification from '@/components/PhoneVerification.vue';
import { useLocale } from '@/composables/useLocale';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
const { t } = useLocale();
defineProps<{
    available: boolean;
    challenge: { phone: string; expiresAt: string } | null;
}>();
</script>
<template>
    <AuthBase
        :title="t('Sign in with phone')"
        :description="
            t('Use the mobile number you verified in your account settings.')
        "
    >
        <Head :title="t('Sign in with phone')" />
        <PhoneVerification
            v-if="available"
            base-url="/login/phone"
            :challenge="challenge"
        />
        <p v-else class="rounded-xl bg-muted p-4 text-sm">
            {{
                t(
                    'Phone sign-in is not available yet. Please use email and password.',
                )
            }}
        </p>
        <p class="mt-5 text-center text-sm text-muted-foreground">
            {{
                t(
                    'New here? Create an account with email, then add a verified phone number in Sign-in methods.',
                )
            }}
        </p>
        <div class="mt-4 flex justify-center gap-6 text-sm">
            <Link href="/login" class="underline underline-offset-4">{{
                t('Back to log in')
            }}</Link
            ><Link href="/register" class="underline underline-offset-4">{{
                t('Create account')
            }}</Link>
        </div>
    </AuthBase>
</template>
