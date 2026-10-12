<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CheckCheck, MessageCircle, Send } from 'lucide-vue-next';
import { ref } from 'vue';

type Message = {
    id: number;
    body: string;
    is_staff: boolean;
    created_at: string;
};
type Conversation = {
    id: number;
    subject: string;
    status: string;
    last_message_at: string | null;
    messages_count?: number;
    messages?: Message[];
};
const props = defineProps<{
    conversations: {
        data: Conversation[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    activeConversation: Conversation | null;
}>();
const { t, date, label, paginationLabel } = useLocale();
const composeOpen = ref(!props.conversations.data.length);
const newForm = useForm({ subject: '', body: '' });
const replyForm = useForm({ body: '' });
function startConversation() {
    newForm.post('/messages', {
        preserveScroll: true,
        onSuccess: () => newForm.reset(),
    });
}
function sendReply() {
    if (!props.activeConversation) return;
    replyForm.post('/messages/' + props.activeConversation.id, {
        preserveScroll: true,
        onSuccess: () => replyForm.reset(),
    });
}
</script>

<template>
    <Head :title="t('Messages')" />
    <ShopLayout>
        <section class="wrap inner-page">
            <div class="eyebrow">{{ t('CUSTOMER CARE') }}</div>
            <h1>{{ t('Messages with EVE Shoes') }}</h1>
            <p class="workspace-description">
                {{
                    t(
                        'Ask about an order, delivery, returns, or choosing the right pair. Our team will reply here.',
                    )
                }}
            </p>
            <div class="message-center">
                <aside class="message-sidebar">
                    <button
                        class="button dark full"
                        type="button"
                        @click="composeOpen = !composeOpen"
                    >
                        <MessageCircle :size="16" />
                        {{
                            composeOpen
                                ? t('Close new message')
                                : t('Start a new message')
                        }}
                    </button>
                    <h2>{{ t('Your conversations') }}</h2>
                    <Link
                        v-for="conversation in conversations.data"
                        :key="conversation.id"
                        :href="'/messages?conversation=' + conversation.id"
                        class="message-thread-link"
                        :class="{
                            selected:
                                activeConversation?.id === conversation.id,
                        }"
                    >
                        <strong>{{ conversation.subject }}</strong>
                        <span
                            >{{ t(label(conversation.status)) }} ·
                            {{ date(conversation.last_message_at || '') }}</span
                        >
                    </Link>
                    <p v-if="!conversations.data.length" class="muted">
                        {{
                            t(
                                'No messages yet. Start a conversation and our team will help.',
                            )
                        }}
                    </p>
                    <nav
                        v-if="conversations.links.length > 3"
                        class="pagination"
                        :aria-label="t('Conversation pages')"
                    >
                        <template
                            v-for="(link, index) in conversations.links"
                            :key="index"
                        >
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="{ active: link.active }"
                                ><span v-text="paginationLabel(link.label)"
                            /></Link>
                            <span v-else v-text="paginationLabel(link.label)" />
                        </template>
                    </nav>
                </aside>
                <section class="message-panel">
                    <form
                        v-if="composeOpen"
                        class="form-card support-compose"
                        @submit.prevent="startConversation"
                    >
                        <h2>{{ t('How can we help?') }}</h2>
                        <label
                            >{{ t('Subject') }}
                            <input
                                v-model="newForm.subject"
                                required
                                minlength="4"
                                maxlength="150"
                                :placeholder="
                                    t('For example: help choosing a size')
                                "
                            />
                            <span
                                v-if="newForm.errors.subject"
                                class="field-error"
                                >{{ t(newForm.errors.subject) }}</span
                            >
                        </label>
                        <label
                            >{{ t('Your message') }}
                            <textarea
                                v-model="newForm.body"
                                rows="5"
                                required
                                minlength="10"
                                maxlength="4000"
                                :placeholder="
                                    t(
                                        'Tell us what you need help with. For an order, include its reference number.',
                                    )
                                "
                            />
                            <span
                                v-if="newForm.errors.body"
                                class="field-error"
                                >{{ t(newForm.errors.body) }}</span
                            >
                        </label>
                        <button
                            class="button dark"
                            :disabled="newForm.processing"
                        >
                            <Send :size="15" /> {{ t('Send message') }}
                        </button>
                    </form>
                    <template v-else-if="activeConversation">
                        <header class="message-panel-heading">
                            <div>
                                <span class="eyebrow"
                                    >{{ t('CONVERSATION') }} ·
                                    {{
                                        t(label(activeConversation.status))
                                    }}</span
                                >
                                <h2>{{ activeConversation.subject }}</h2>
                            </div>
                            <Link href="/messages" class="text-button"
                                ><ArrowLeft :size="15" />
                                {{ t('All messages') }}</Link
                            >
                        </header>
                        <div class="message-history" aria-live="polite">
                            <article
                                v-for="message in activeConversation.messages"
                                :key="message.id"
                                class="support-message"
                                :class="{ 'from-team': message.is_staff }"
                            >
                                <div class="support-message-meta">
                                    <strong>{{
                                        t(
                                            message.is_staff
                                                ? 'EVE Shoes team'
                                                : 'You',
                                        )
                                    }}</strong>
                                    <time>{{
                                        date(message.created_at, true)
                                    }}</time>
                                </div>
                                <p>{{ message.body }}</p>
                            </article>
                        </div>
                        <form
                            v-if="activeConversation.status !== 'closed'"
                            class="support-reply"
                            @submit.prevent="sendReply"
                        >
                            <label class="sr-only" for="support-reply">{{
                                t('Your reply')
                            }}</label>
                            <textarea
                                id="support-reply"
                                v-model="replyForm.body"
                                rows="3"
                                required
                                minlength="2"
                                maxlength="4000"
                                :placeholder="t('Write a reply…')"
                            />
                            <p v-if="replyForm.errors.body" class="field-error">
                                {{ t(replyForm.errors.body) }}
                            </p>
                            <div class="order-actions">
                                <button
                                    class="button dark"
                                    :disabled="replyForm.processing"
                                >
                                    <Send :size="15" /> {{ t('Send reply') }}
                                </button>
                                <button
                                    class="text-button"
                                    type="button"
                                    @click="
                                        router.patch(
                                            '/messages/' +
                                                activeConversation.id +
                                                '/close',
                                            {},
                                            { preserveScroll: true },
                                        )
                                    "
                                >
                                    {{ t('Mark resolved') }}
                                </button>
                            </div>
                        </form>
                        <div v-else class="support-resolved">
                            <CheckCheck :size="18" /><span>{{
                                t('This conversation is closed.')
                            }}</span>
                            <Link href="/messages" class="text-button">{{
                                t('Start a new message')
                            }}</Link>
                        </div>
                    </template>
                    <div v-else class="message-welcome">
                        <span class="empty-icon"
                            ><MessageCircle :size="28"
                        /></span>
                        <h2>{{ t('Here when you need us.') }}</h2>
                        <p>
                            {{
                                t(
                                    'Choose a conversation or send us a message. Replies and order updates also show in your notifications.',
                                )
                            }}
                        </p>
                    </div>
                </section>
            </div>
        </section>
    </ShopLayout>
</template>
