<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { recommendEuropeanSize } from '@/lib/shoeSize';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Footprints, Ruler, Sparkles } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const { t, num } = useLocale();
const sizes = Array.from({ length: 10 }, (_, index) => index + 36);
const footLength = ref<number | null>(null);
const recommendation = computed(() => {
    return footLength.value === null
        ? null
        : recommendEuropeanSize(footLength.value);
});
</script>

<template>
    <Head :title="t('Shoe size guide')" />
    <ShopLayout>
        <section class="wrap inner-page size-guide-page">
            <span class="eyebrow">{{ t('SIZE & FIT') }}</span>
            <h1>{{ t('Find your fit with confidence.') }}</h1>
            <p class="size-guide-intro">
                {{
                    t(
                        'A quick measurement at home can make choosing your next pair easier. Our shoes use European size labels.',
                    )
                }}
            </p>

            <div class="size-guide-grid">
                <article class="size-guide-card size-finder-card">
                    <span class="size-guide-icon"><Sparkles :size="21" /></span>
                    <span class="eyebrow">{{ t('PERSONAL SIZE FINDER') }}</span>
                    <h2>{{ t('Get a starting size') }}</h2>
                    <p>
                        {{
                            t(
                                'Measure your longer foot, then enter the length in centimeters.',
                            )
                        }}
                    </p>
                    <label class="size-finder-label" for="foot-length">{{
                        t('Foot length in centimeters')
                    }}</label>
                    <div class="size-finder-input">
                        <input
                            id="foot-length"
                            v-model.number="footLength"
                            type="number"
                            min="22.5"
                            max="28.5"
                            step="0.1"
                            inputmode="decimal"
                            :placeholder="t('For example, 25.4')"
                        />
                        <span>{{ t('cm') }}</span>
                    </div>
                    <div
                        v-if="recommendation"
                        class="size-finder-result"
                        role="status"
                        aria-live="polite"
                    >
                        <span>{{ t('Suggested EVE size') }}</span>
                        <strong>{{
                            t('EU :size', { size: num(recommendation) })
                        }}</strong>
                        <small>{{
                            t('Starting estimate based on a :length cm foot.', {
                                length: num(footLength!, false),
                            })
                        }}</small>
                    </div>
                    <p
                        v-else-if="footLength !== null"
                        class="size-guide-note"
                        role="status"
                    >
                        {{
                            t(
                                'For this measurement, ask our team to confirm the closest size.',
                            )
                        }}
                    </p>
                    <p class="size-guide-note">
                        {{
                            t(
                                'This guide is an estimate. Shoe shape and personal fit can vary; check verified fit reviews before ordering.',
                            )
                        }}
                    </p>
                </article>
                <article class="size-guide-card">
                    <span class="size-guide-icon"><Ruler :size="21" /></span>
                    <span class="eyebrow">{{ t('MEASURE AT HOME') }}</span>
                    <h2>{{ t('Three steps to measure your feet') }}</h2>
                    <ol class="size-guide-steps">
                        <li>
                            {{
                                t(
                                    'Place a sheet of paper on a hard floor with one edge against a wall.',
                                )
                            }}
                        </li>
                        <li>
                            {{
                                t(
                                    'Stand on the paper with your heel lightly touching the wall, wearing the socks you plan to use.',
                                )
                            }}
                        </li>
                        <li>
                            {{
                                t(
                                    'Mark the tip of your longest toe, measure from the wall in centimeters, and repeat for both feet.',
                                )
                            }}
                        </li>
                    </ol>
                    <p class="size-guide-note">
                        {{
                            t(
                                'Measure later in the day and use the longer foot as your starting point.',
                            )
                        }}
                    </p>
                </article>

                <article class="size-guide-card size-guide-range">
                    <span class="size-guide-icon"
                        ><Footprints :size="21"
                    /></span>
                    <span class="eyebrow">{{ t('OUR RANGE') }}</span>
                    <h2>
                        {{
                            t('European sizes :first–:last', {
                                first: 36,
                                last: 45,
                            })
                        }}
                    </h2>
                    <p>
                        {{
                            t(
                                'Choose the EU size shown on the product. Availability can vary by shoe and color.',
                            )
                        }}
                    </p>
                    <div class="size-guide-sizes" :aria-label="t('EU sizes')">
                        <span v-for="size in sizes" :key="size">
                            {{ num(size) }}
                        </span>
                    </div>
                    <p class="size-guide-note">
                        {{
                            t(
                                'Sizing and shape can vary between styles. Check the fit feedback on a product page if you are unsure.',
                            )
                        }}
                    </p>
                </article>
            </div>

            <div class="size-guide-next">
                <div>
                    <h2>{{ t('Still deciding?') }}</h2>
                    <p>
                        {{
                            t(
                                'Read verified fit reviews or browse the collection to check each shoe’s available sizes.',
                            )
                        }}
                    </p>
                </div>
                <Link href="/" class="button dark">
                    {{ t('Explore the collection') }} <ArrowRight :size="16" />
                </Link>
            </div>
        </section>
    </ShopLayout>
</template>
