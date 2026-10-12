<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import type { MerchantConversation } from '@/types/merchant';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{ thread: MerchantConversation }>();
const { t, date } = useLocale();
const reply = useForm({ body: '' });
function sendReply() {
    reply.post('/merchant/conversations/' + props.thread.id, {
        preserveScroll: true,
        onSuccess: () => reply.reset(),
    });
}
</script>
<template>
    <details class="merchant-manage-product">
        <summary>
            <strong>{{ thread.subject }}</strong> ·
            {{ thread.user?.name || t('Customer') }}
        </summary>
        <div class="merchant-thread">
            <article
                v-for="message in thread.messages"
                :key="message.id"
                class="merchant-message"
            >
                <p>{{ message.body }}</p>
                <small>{{ date(message.created_at, true) }}</small>
            </article>
            <form @submit.prevent="sendReply">
                <label
                    >{{ t('Reply to customer')
                    }}<textarea
                        v-model="reply.body"
                        required
                        minlength="2"
                        maxlength="4000"
                        rows="3"
                    />
                </label>
                <p v-if="reply.errors.body" class="field-error">
                    {{ reply.errors.body }}
                </p>
                <button class="button dark" :disabled="reply.processing">
                    {{ t('Send reply') }}
                </button>
            </form>
        </div>
    </details>
</template>
