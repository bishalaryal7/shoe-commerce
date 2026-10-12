<script setup lang="ts">
import ReviewCommentForm from '@/components/ReviewCommentForm.vue';
import { useLocale } from '@/composables/useLocale';
import type { ShopSharedProps } from '@/types/shop';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Search, Star, ThumbsUp, X } from 'lucide-vue-next';
import { ref } from 'vue';

interface Review {
    id: number;
    rating: number;
    fit: 'small' | 'true' | 'large' | null;
    title: string;
    body: string;
    created_at: string;
    helpful_count: number;
    voted_helpful: boolean;
    seller_reply: string | null;
    seller_replied_at: string | null;
    comments: {
        id: number;
        body: string;
        is_staff: boolean;
        created_at: string;
    }[];
}
const props = defineProps<{
    productSlug: string;
    summary: {
        count: number;
        average: number | null;
        fit: { small: number; true: number; large: number };
    };
    reviews: {
        data: Review[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    ownReview: Pick<Review, 'id' | 'rating' | 'fit' | 'title' | 'body'> | null;
    canReview: boolean;
    reviewSort: 'recent' | 'helpful' | 'rating';
    reviewQuery: string;
}>();
const { t, num, date, paginationLabel } = useLocale();
const page = usePage<ShopSharedProps>();
const votingReview = ref<number | null>(null);
const reviewQuery = ref(props.reviewQuery);
const form = useForm({
    rating: props.ownReview?.rating ?? 0,
    fit: props.ownReview?.fit ?? '',
    title: props.ownReview?.title ?? '',
    body: props.ownReview?.body ?? '',
});
function submit() {
    form.post('/products/' + props.productSlug + '/reviews', {
        preserveScroll: true,
        onSuccess: () => form.clearErrors(),
    });
}
function toggleHelpful(review: Review) {
    router.post(
        `/products/${props.productSlug}/reviews/${review.id}/helpful`,
        {},
        {
            preserveScroll: true,
            onStart: () => (votingReview.value = review.id),
            onFinish: () => (votingReview.value = null),
        },
    );
}
function sortReviews(event: Event) {
    const sort = (event.target as HTMLSelectElement).value;
    router.get(
        `/products/${props.productSlug}`,
        { reviews_sort: sort, review_q: props.reviewQuery || undefined },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['reviews', 'reviewSort'],
        },
    );
}
function searchReviews() {
    router.get(
        `/products/${props.productSlug}`,
        {
            reviews_sort: props.reviewSort,
            review_q: reviewQuery.value.trim() || undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['reviews', 'reviewQuery'],
        },
    );
}
function clearReviewSearch() {
    reviewQuery.value = '';
    searchReviews();
}
const fitLabels = {
    small: 'Runs small',
    true: 'True to size',
    large: 'Runs large',
} as const;
const fitOptions = ['small', 'true', 'large'] as const;
</script>

<template>
    <section id="reviews" class="collection wrap review-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">{{ t('REAL CUSTOMER FEEDBACK') }}</span>
                <h2>{{ t('Reviews from verified buyers') }}</h2>
            </div>
            <div v-if="summary.count" class="review-score">
                <Star :size="19" fill="currentColor" />
                <strong>{{ num(summary.average ?? 0) }}</strong>
                <span>{{
                    t(':count reviews', { count: num(summary.count) })
                }}</span>
            </div>
        </div>
        <p class="review-intro">
            {{
                t(
                    'Only signed-in customers with a paid, fulfilled order can review this shoe.',
                )
            }}
        </p>
        <label class="review-sort">
            {{ t('Sort reviews') }}
            <select :value="reviewSort" @change="sortReviews">
                <option value="recent">{{ t('Most recent') }}</option>
                <option value="helpful">{{ t('Most helpful') }}</option>
                <option value="rating">{{ t('Highest rated') }}</option>
            </select>
        </label>
        <form class="review-search" @submit.prevent="searchReviews">
            <label class="sr-only" :for="'review-search-' + productSlug">{{
                t('Search customer reviews')
            }}</label>
            <Search :size="16" aria-hidden="true" />
            <input
                :id="'review-search-' + productSlug"
                v-model="reviewQuery"
                type="search"
                maxlength="100"
                :placeholder="t('Search review text…')"
            />
            <button class="button outline" type="submit">
                {{ t('Search') }}
            </button>
            <button
                v-if="reviewQuery"
                class="review-search-clear"
                type="button"
                :aria-label="t('Clear review search')"
                @click="clearReviewSearch"
            >
                <X :size="16" />
            </button>
        </form>
        <div v-if="summary.count" class="review-fit-summary">
            <strong>{{ t('How did it fit?') }}</strong>
            <span v-for="fit in fitOptions" :key="fit">
                {{ t(fitLabels[fit]) }}: {{ num(summary.fit[fit]) }}
            </span>
        </div>
        <div class="review-layout">
            <div class="review-list">
                <article
                    v-for="review in reviews.data"
                    :key="review.id"
                    class="review-card"
                >
                    <div class="review-card-top">
                        <span
                            class="review-stars"
                            :aria-label="
                                t(':rating out of 5 stars', {
                                    rating: num(review.rating),
                                })
                            "
                        >
                            <Star
                                v-for="star in 5"
                                :key="star"
                                :size="15"
                                :fill="
                                    star <= review.rating
                                        ? 'currentColor'
                                        : 'none'
                                "
                                aria-hidden="true"
                            />
                        </span>
                        <span>{{ date(review.created_at) }}</span>
                    </div>
                    <h3>{{ review.title }}</h3>
                    <div class="review-tags">
                        <span>{{ t('Verified purchase') }}</span>
                        <span v-if="review.fit">{{
                            t(fitLabels[review.fit])
                        }}</span>
                    </div>
                    <p>{{ review.body }}</p>
                    <aside
                        v-if="review.seller_reply"
                        class="seller-review-reply"
                    >
                        <strong>{{ t('A reply from EVE Shoes') }}</strong>
                        <p>{{ review.seller_reply }}</p>
                        <small v-if="review.seller_replied_at">{{
                            date(review.seller_replied_at)
                        }}</small>
                    </aside>
                    <div class="review-helpful">
                        <button
                            v-if="
                                page.props.auth.user &&
                                review.id !== ownReview?.id
                            "
                            type="button"
                            :aria-pressed="review.voted_helpful"
                            :disabled="votingReview === review.id"
                            @click="toggleHelpful(review)"
                        >
                            <ThumbsUp :size="14" />
                            {{
                                t(
                                    review.voted_helpful
                                        ? 'Marked helpful · :count'
                                        : 'Helpful · :count',
                                    { count: num(review.helpful_count) },
                                )
                            }}
                        </button>
                        <Link v-else-if="!page.props.auth.user" href="/login">
                            {{
                                t('Sign in to mark helpful · :count', {
                                    count: num(review.helpful_count),
                                })
                            }}
                        </Link>
                        <span v-else-if="review.helpful_count">
                            {{
                                t('Helpful · :count', {
                                    count: num(review.helpful_count),
                                })
                            }}
                        </span>
                    </div>
                    <ReviewCommentForm
                        :product-slug="productSlug"
                        :review-id="review.id"
                        :comments="review.comments"
                    />
                </article>
                <div v-if="!reviews.data.length" class="review-empty">
                    <Star :size="22" />
                    <p>
                        {{
                            reviewQuery
                                ? t(
                                      'No reviews match your search. Try another word.',
                                  )
                                : t(
                                      'No reviews yet. Verified buyers can share the first one.',
                                  )
                        }}
                    </p>
                </div>
                <nav
                    v-if="reviews.links.length > 3"
                    class="pagination"
                    :aria-label="t('Review pages')"
                >
                    <template v-for="(link, i) in reviews.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url + '#reviews'"
                            :class="{ active: link.active }"
                            :aria-current="link.active ? 'page' : undefined"
                            ><span v-text="paginationLabel(link.label)"
                        /></Link>
                        <span v-else v-text="paginationLabel(link.label)" />
                    </template>
                </nav>
            </div>
            <div class="review-write">
                <h3>
                    {{
                        t(
                            ownReview
                                ? 'Update your review'
                                : 'Share your experience',
                        )
                    }}
                </h3>
                <form v-if="canReview" @submit.prevent="submit">
                    <label class="review-label">{{ t('Your rating') }}</label>
                    <div
                        class="review-rating-input"
                        role="group"
                        :aria-label="t('Your rating')"
                    >
                        <button
                            v-for="star in 5"
                            :key="star"
                            type="button"
                            :aria-label="
                                t('Rate :count stars', { count: num(star) })
                            "
                            :aria-pressed="form.rating === star"
                            @click="form.rating = star"
                        >
                            <Star
                                :size="23"
                                :fill="
                                    star <= form.rating
                                        ? 'currentColor'
                                        : 'none'
                                "
                            />
                        </button>
                    </div>
                    <p v-if="form.errors.rating" class="form-error">
                        {{ form.errors.rating }}
                    </p>
                    <label class="review-label" for="review-fit">{{
                        t('Fit (optional)')
                    }}</label>
                    <select id="review-fit" v-model="form.fit">
                        <option value="">{{ t('Choose fit') }}</option>
                        <option value="small">{{ t('Runs small') }}</option>
                        <option value="true">{{ t('True to size') }}</option>
                        <option value="large">{{ t('Runs large') }}</option>
                    </select>
                    <p v-if="form.errors.fit" class="form-error">
                        {{ form.errors.fit }}
                    </p>
                    <label class="review-label" for="review-title">{{
                        t('Review title')
                    }}</label>
                    <input
                        id="review-title"
                        v-model="form.title"
                        type="text"
                        minlength="3"
                        maxlength="100"
                        required
                        :placeholder="t('What stood out?')"
                    />
                    <p v-if="form.errors.title" class="form-error">
                        {{ form.errors.title }}
                    </p>
                    <label class="review-label" for="review-body">{{
                        t('Your review')
                    }}</label>
                    <textarea
                        id="review-body"
                        v-model="form.body"
                        rows="5"
                        minlength="20"
                        maxlength="2000"
                        required
                        :placeholder="
                            t(
                                'Tell other shoppers about comfort, fit, and everyday use.',
                            )
                        "
                    />
                    <p v-if="form.errors.body" class="form-error">
                        {{ form.errors.body }}
                    </p>
                    <p v-if="page.props.errors?.review" class="form-error">
                        {{ page.props.errors.review }}
                    </p>
                    <button
                        type="submit"
                        class="button dark"
                        :disabled="form.processing"
                    >
                        {{
                            t(ownReview ? 'Save updated review' : 'Post review')
                        }}
                    </button>
                </form>
                <p v-else-if="page.props.auth.user">
                    {{
                        t(
                            'You can review this shoe after your paid order is fulfilled.',
                        )
                    }}
                </p>
                <p v-else>
                    {{ t('Sign in to review a completed purchase.') }}
                    <Link href="/login">{{ t('Sign in') }}</Link>
                </p>
            </div>
        </div>
    </section>
</template>
