<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import type { ShopSharedProps } from '@/types/shop';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { MessageCircle, Send } from 'lucide-vue-next';
const props = withDefaults(
    defineProps<{
        productSlug: string;
        reviewId: number;
        comments?: {
            id: number;
            body: string;
            is_staff: boolean;
            created_at: string;
        }[];
    }>(),
    { comments: () => [] },
);
const { t, num, date } = useLocale();
const page = usePage<ShopSharedProps>();
const form = useForm({ body: '' });
function postComment() {
    form.post(
        '/products/' +
            props.productSlug +
            '/reviews/' +
            props.reviewId +
            '/comments',
        {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        },
    );
}
</script>

<template>
    <details class="review-comments">
        <summary>
            <MessageCircle :size="15" /> {{ t('Comments') }} ·
            {{ num(comments.length) }}
        </summary>
        <div class="review-comment-list">
            <div
                v-for="comment in comments"
                :key="comment.id"
                class="review-comment"
            >
                <strong>{{
                    t(comment.is_staff ? 'EVE Shoes team' : 'Shopper')
                }}</strong>
                <small>{{ date(comment.created_at) }}</small>
                <p>{{ comment.body }}</p>
            </div>
            <p v-if="!comments.length" class="muted">
                {{ t('No comments yet. Add a helpful note or question.') }}
            </p>
        </div>
        <form
            v-if="page.props.auth.user"
            class="review-comment-form"
            @submit.prevent="postComment"
        >
            <label class="sr-only" :for="'review-comment-' + reviewId">{{
                t('Add a comment')
            }}</label>
            <textarea
                :id="'review-comment-' + reviewId"
                v-model="form.body"
                rows="2"
                minlength="4"
                maxlength="1000"
                required
                :placeholder="t('Share a helpful comment about this review')"
            />
            <p v-if="form.errors.body" class="field-error" role="alert">
                {{ t(form.errors.body) }}
            </p>
            <button class="button outline" :disabled="form.processing">
                <Send :size="14" /> {{ t('Post comment') }}
            </button>
        </form>
        <p v-else class="muted">
            <Link href="/login">{{ t('Sign in to comment') }}</Link>
        </p>
    </details>
</template>
