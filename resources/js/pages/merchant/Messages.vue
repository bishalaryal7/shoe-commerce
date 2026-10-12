<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import MerchantLayout from '@/layouts/MerchantLayout.vue';
import type { MerchantConversation, MerchantStore } from '@/types/merchant';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    store: MerchantStore;
    conversations: MerchantConversation[];
}>();
const { t, date } = useLocale();
const page = usePage();
const subject =
    new URL(page.url, 'http://localhost').searchParams.get('subject') ?? '';
const selected = ref<number | null>(props.conversations[0]?.id ?? null);
const newForm = useForm({ subject, body: '' });
const replyForms = new Map<
    number,
    ReturnType<typeof useForm<{ body: string }>>
>();
function replyForm(id: number) {
    if (!replyForms.has(id)) replyForms.set(id, useForm({ body: '' }));
    return replyForms.get(id)!;
}
</script>

<template>
    <Head :title="t('Messages') + ' | ' + store.name" /><MerchantLayout
        :store="store"
        ><section class="wrap inner-page">
            <span class="eyebrow">{{ store.name }}</span>
            <h1>{{ t('Message the seller') }}</h1>
            <p>
                {{
                    t(
                        'Ask about products, sizing, delivery, or an order. The seller will reply here.',
                    )
                }}
            </p>
            <div class="message-center">
                <aside class="message-sidebar">
                    <h2>{{ t('Your conversations') }}</h2>
                    <button
                        v-for="thread in conversations"
                        :key="thread.id"
                        class="message-thread-link"
                        :class="{ selected: selected === thread.id }"
                        @click="selected = thread.id"
                    >
                        <strong>{{ thread.subject }}</strong
                        ><span>{{ date(thread.last_message_at) }}</span>
                    </button>
                    <p v-if="!conversations.length" class="muted">
                        {{ t('No messages yet.') }}
                    </p>
                </aside>
                <div class="message-panel">
                    <form
                        class="form-card support-compose"
                        @submit.prevent="
                            newForm.post(
                                '/stores/' + store.slug + '/messages',
                                {
                                    preserveScroll: true,
                                    onSuccess: () => newForm.reset(),
                                },
                            )
                        "
                    >
                        <h2>{{ t('Start a new message') }}</h2>
                        <label
                            >{{ t('Subject')
                            }}<input
                                v-model="newForm.subject"
                                required
                                minlength="4"
                                maxlength="150"
                            /><span class="field-error">{{
                                newForm.errors.subject
                            }}</span></label
                        ><label
                            >{{ t('Your message')
                            }}<textarea
                                v-model="newForm.body"
                                required
                                minlength="10"
                                maxlength="4000"
                                rows="4"
                            /><span class="field-error">{{
                                newForm.errors.body
                            }}</span></label
                        ><button
                            class="button dark"
                            :disabled="newForm.processing"
                        >
                            {{ t('Send message') }}
                        </button>
                    </form>
                    <template v-for="thread in conversations" :key="thread.id"
                        ><div
                            v-if="selected === thread.id"
                            class="merchant-thread"
                        >
                            <h2>{{ thread.subject }}</h2>
                            <article
                                v-for="message in thread.messages"
                                :key="message.id"
                                class="merchant-message"
                            >
                                <p>{{ message.body }}</p>
                                <small>{{
                                    date(message.created_at, true)
                                }}</small>
                            </article>
                            <form
                                @submit.prevent="
                                    replyForm(thread.id).post(
                                        '/stores/' +
                                            store.slug +
                                            '/messages/' +
                                            thread.id,
                                        {
                                            preserveScroll: true,
                                            onSuccess: () =>
                                                replyForm(thread.id).reset(),
                                        },
                                    )
                                "
                            >
                                <label
                                    >{{ t('Reply')
                                    }}<textarea
                                        v-model="replyForm(thread.id).body"
                                        required
                                        minlength="2"
                                        maxlength="4000"
                                        rows="3"
                                    />
                                </label>
                                <p class="field-error">
                                    {{ replyForm(thread.id).errors.body }}
                                </p>
                                <button
                                    class="button outline"
                                    :disabled="replyForm(thread.id).processing"
                                >
                                    {{ t('Send reply') }}
                                </button>
                            </form>
                        </div></template
                    >
                </div>
            </div>
        </section></MerchantLayout
    >
</template>
