<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLocale } from '@/composables/useLocale';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
const { t } = useLocale();
const props = defineProps<{
    baseUrl: string;
    challenge?: { phone: string; expiresAt: string } | null;
    initialPhone?: string | null;
}>();
const send = useForm({ phone: props.initialPhone ?? '' });
const verify = useForm({ code: '', phone: '' });
const changing = ref(false);
function sendCode() {
    send.post(props.baseUrl + '/send', {
        preserveScroll: true,
        onSuccess: () => {
            changing.value = false;
            verify.reset();
            nextTick(() => document.getElementById('phone-code')?.focus());
        },
    });
}
function checkCode() {
    verify.post(props.baseUrl + '/verify', {
        preserveScroll: true,
        onSuccess: () => verify.reset(),
        onFinish: () => verify.reset('code'),
    });
}
</script>
<template>
    <div class="space-y-5">
        <form
            v-if="!challenge || changing"
            class="space-y-4"
            @submit.prevent="sendCode"
        >
            <div class="grid gap-2">
                <Label for="sign-in-phone">{{ t('Mobile number') }}</Label>
                <Input
                    id="sign-in-phone"
                    v-model="send.phone"
                    type="tel"
                    autocomplete="tel"
                    maxlength="25"
                    required
                    :placeholder="t('+977 9800000000')"
                    aria-describedby="phone-help phone-error"
                    :aria-invalid="!!send.errors.phone"
                />
                <p id="phone-help" class="text-sm text-muted-foreground">
                    {{
                        t(
                            'Use your country code. Nepali mobile numbers also work without +977.',
                        )
                    }}
                </p>
                <InputError id="phone-error" :message="send.errors.phone" />
            </div>
            <Button :disabled="send.processing" class="w-full">{{
                send.processing
                    ? t('Sending code…')
                    : t('Send verification code')
            }}</Button>
        </form>
        <form v-else class="space-y-4" @submit.prevent="checkCode">
            <p class="text-sm text-muted-foreground" role="status">
                {{
                    t(
                        'Enter the code sent to :phone. It expires after 10 minutes.',
                        { phone: challenge.phone },
                    )
                }}
            </p>
            <div class="grid gap-2">
                <Label for="phone-code">{{ t('Verification code') }}</Label>
                <Input
                    id="phone-code"
                    v-model="verify.code"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    minlength="4"
                    maxlength="10"
                    required
                    :aria-invalid="!!verify.errors.code"
                    aria-describedby="code-error"
                />
                <InputError id="code-error" :message="verify.errors.code" />
                <InputError :message="verify.errors.phone" />
            </div>
            <Button :disabled="verify.processing" class="w-full">{{
                verify.processing ? t('Verifying…') : t('Verify code')
            }}</Button>
            <button
                type="button"
                class="text-sm underline underline-offset-4"
                @click="changing = true"
            >
                {{ t('Change number or request a new code') }}
            </button>
        </form>
    </div>
</template>
