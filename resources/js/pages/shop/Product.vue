<script setup lang="ts">
import ProductCard from '@/components/ProductCard.vue';
import ProductDetails from '@/components/ProductDetails.vue';
import ProductReviews from '@/components/ProductReviews.vue';
import { useLocale } from '@/composables/useLocale';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { type Product, type ShopSharedProps } from '@/types/shop';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeftRight,
    ArrowRight,
    ChevronRight,
    Footprints,
    Heart,
    Layers3,
    PackageCheck,
    ShoppingBag,
    Star,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
const { t, num, money } = useLocale();
const props = defineProps<{
    product: Product;
    wholesale: boolean;
    related: Product[];
    savedIds: number[];
    recentlyViewed: Product[];
    reviewSummary: {
        count: number;
        average: number | null;
        fit: { small: number; true: number; large: number };
    };
    reviews: {
        data: {
            id: number;
            rating: number;
            fit: 'small' | 'true' | 'large' | null;
            title: string;
            body: string;
            created_at: string;
            helpful_count: number;
            voted_helpful: boolean;
        }[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    ownReview: {
        id: number;
        rating: number;
        fit: 'small' | 'true' | 'large' | null;
        title: string;
        body: string;
    } | null;
    canReview: boolean;
}>();
const page = usePage<ShopSharedProps>();
const details = ref<InstanceType<typeof ProductDetails> | null>(null);
const saving = ref(false);
const comparing = ref(false);
const isSaved = computed(() => props.savedIds.includes(props.product.id));
const isCompared = computed(
    () => page.props.compareIds?.includes(props.product.id) ?? false,
);
function toggleSaved() {
    router.post(
        '/saved/' + props.product.id,
        {},
        {
            preserveScroll: true,
            onStart: () => (saving.value = true),
            onFinish: () => (saving.value = false),
        },
    );
}
function toggleCompare() {
    router.post(
        '/compare/' + props.product.id,
        {},
        {
            preserveScroll: true,
            onStart: () => (comparing.value = true),
            onFinish: () => (comparing.value = false),
        },
    );
}
const available = computed(() =>
    props.product.variants.filter((v) => v.stock > 0),
);
</script>
<template>
    <Head :title="t(product.name)"
        ><meta name="description" :content="product.description" /><meta
            property="og:title"
            :content="product.name + ' | ' + page.props.name" /><meta
            property="og:description"
            :content="product.description" /></Head
    ><ShopLayout
        ><section class="wrap inner-page product-page">
            <nav class="breadcrumbs" :aria-label="t('Breadcrumb')">
                <Link href="/">{{ t('Shop') }}</Link
                ><ChevronRight :size="12" /><Link
                    :href="'/?category=' + product.category + '#collection'"
                    >{{ t(product.category) }}</Link
                ><ChevronRight :size="12" /><span>{{ t(product.name) }}</span>
            </nav>
            <div class="product-page-grid">
                <div
                    class="product-page-image"
                    :data-color="product.variants[0]?.color"
                >
                    <span class="category-label">{{ t(product.category) }}</span
                    ><img
                        :src="product.image"
                        :alt="t(product.name)"
                        fetchpriority="high"
                    />
                </div>
                <div class="product-page-copy">
                    <span class="eyebrow"
                        >{{ t(product.category) }} {{ t('COLLECTION') }}
                    </span>
                    <h1>{{ t(product.name) }}</h1>
                    <a
                        v-if="reviewSummary.count"
                        href="#reviews"
                        class="product-review-link"
                        ><Star :size="15" fill="currentColor" />{{
                            num(reviewSummary.average ?? 0)
                        }}
                        ·
                        {{
                            t(':count reviews', {
                                count: num(reviewSummary.count),
                            })
                        }}</a
                    >
                    <div class="product-page-price">
                        {{ money(product.price, page.props.currency)
                        }}<span>{{
                            wholesale
                                ? t('Your wholesale price / pair')
                                : t('Per pair')
                        }}</span>
                    </div>
                    <p class="product-page-description">
                        {{ t(product.description) }}
                    </p>
                    <div class="product-page-availability">
                        <span
                            class="availability-dot"
                            :class="{ empty: !available.length }"
                        ></span
                        >{{
                            available.length
                                ? t(':count sizes available', {
                                      count: num(available.length),
                                  })
                                : t('Currently sold out')
                        }}
                    </div>
                    <button
                        class="button dark full"
                        :disabled="!available.length"
                        @click="details?.open()"
                    >
                        <ShoppingBag :size="18" />{{
                            available.length
                                ? t('Choose size & add to bag')
                                : t('Currently unavailable')
                        }}
                    </button>
                    <button
                        type="button"
                        class="product-save-action"
                        :aria-pressed="isSaved"
                        :disabled="saving"
                        @click="toggleSaved"
                    >
                        <Heart
                            :size="18"
                            :fill="isSaved ? 'currentColor' : 'none'"
                        />{{
                            t(isSaved ? 'Saved for later' : 'Save for later')
                        }}
                    </button>
                    <button
                        type="button"
                        class="product-save-action compare-action"
                        :aria-pressed="isCompared"
                        :disabled="comparing || saving"
                        @click="toggleCompare"
                    >
                        <ArrowLeftRight :size="18" />{{
                            t(
                                isCompared
                                    ? 'Remove from comparison'
                                    : 'Add to comparison',
                            )
                        }}
                    </button>
                    <div class="product-page-benefits">
                        <div>
                            <Footprints :size="19" /><span>
                                {{ t('Choose your EU size') }}
                            </span>
                        </div>
                        <div>
                            <Layers3 :size="19" /><span>{{
                                wholesale
                                    ? t(
                                          '12 pair minimum · Mix sizes and styles',
                                      )
                                    : t('Individual pairs & wholesale orders')
                            }}</span>
                        </div>
                        <div>
                            <PackageCheck :size="19" /><span>
                                {{ t('Your order is confirmed by our team') }}
                            </span>
                        </div>
                    </div>
                    <details class="product-faq">
                        <summary>{{ t('How does ordering work?') }}</summary>
                        <p>
                            {{
                                t(
                                    'Choose a size and quantity, add the shoes to your bag, then enter your delivery details. You can request confirmation or use an available online payment method at checkout.',
                                )
                            }}
                        </p>
                    </details>
                    <details class="product-faq">
                        <summary>{{ t('Can I buy in wholesale?') }}</summary>
                        <p>
                            {{
                                t(
                                    'Apply for a wholesale account. Once approved, sign in to see partner prices and order at least 12 pairs across styles and sizes.',
                                )
                            }}
                        </p>
                        <Link href="/wholesale"
                            >{{ t('Explore wholesale') }}
                            <ArrowRight :size="12"
                        /></Link>
                    </details>
                    <details class="product-faq">
                        <summary>
                            {{ t('Which size should I choose?') }}
                        </summary>
                        <p>
                            {{
                                t(
                                    'Stand on paper and mark your heel and longest toe. Measure both feet in centimeters and use the longer length. Sizes here use EU labels; ask our team for the measurement of a specific style before ordering if you are unsure.',
                                )
                            }}
                        </p>
                    </details>
                </div>
            </div>
            <ProductDetails
                ref="details"
                :product="product"
                :wholesale="wholesale"
            />
        </section>
        <section v-if="related.length" class="collection wrap">
            <div class="section-heading">
                <div>
                    <span class="eyebrow"> {{ t('MORE GOOD FINDS') }} </span>
                    <h2>{{ t('Keep your options open.') }}</h2>
                </div>
                <Link href="/" class="back-shopping">
                    {{ t('Shop all shoes') }} <ArrowRight :size="14"
                /></Link>
            </div>
            <div class="product-grid">
                <ProductCard
                    v-for="item in related"
                    :key="item.id"
                    :product="item"
                    :wholesale="wholesale"
                    :saved="savedIds.includes(item.id)"
                />
            </div>
        </section>
        <section
            v-if="recentlyViewed.length"
            class="collection wrap discovery-section"
        >
            <div class="section-heading">
                <div>
                    <span class="eyebrow">{{
                        t('PICK UP WHERE YOU LEFT OFF')
                    }}</span>
                    <h2>{{ t('Recently viewed shoes') }}</h2>
                </div>
                <button
                    type="button"
                    class="back-shopping"
                    @click="
                        router.delete('/recently-viewed', {
                            preserveScroll: true,
                        })
                    "
                >
                    {{ t('Clear history') }} <ArrowRight :size="14" />
                </button>
            </div>
            <div class="product-grid">
                <ProductCard
                    v-for="item in recentlyViewed"
                    :key="item.id"
                    :product="item"
                    :wholesale="wholesale"
                    :saved="savedIds.includes(item.id)"
                />
            </div>
        </section>
        <ProductReviews
            :product-slug="product.slug"
            :summary="reviewSummary"
            :reviews="reviews"
            :own-review="ownReview"
            :can-review="canReview"
        />
        ></ShopLayout
    >
</template>
