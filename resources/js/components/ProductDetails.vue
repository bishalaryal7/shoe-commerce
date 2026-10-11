<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import { type Product, type ShopSharedProps } from '@/types/shop';
import { useForm, usePage } from '@inertiajs/vue3';
import {
    Check,
    Minus,
    PackageCheck,
    Plus,
    ShoppingBag,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
const { t, num, money } = useLocale();
const props = defineProps<{ product: Product; wholesale: boolean }>();
const page = usePage<ShopSharedProps>();
const dialog = ref<HTMLDialogElement | null>(null);
const color = ref(props.product.variants[0]?.color || '');
const form = useForm({ variant_id: null as number | null, quantity: 1 });
const colors = computed(() => [
    ...new Set(props.product.variants.map((v) => v.color)),
]);
const variants = computed(() =>
    props.product.variants.filter((v) => v.color === color.value),
);
const selected = computed(() =>
    props.product.variants.find((v) => v.id === form.variant_id),
);
const maximum = computed(() => Math.min(selected.value?.stock || 1, 999));
function open() {
    form.clearErrors();
    dialog.value?.showModal();
}
function chooseColor(value: string) {
    color.value = value;
    form.variant_id = null;
    form.quantity = 1;
    form.clearErrors();
}
function chooseSize(id: number) {
    form.variant_id = id;
    form.quantity = 1;
    form.clearErrors();
}
function add() {
    if (!form.variant_id) {
        form.setError('variant_id', t('Choose your size to continue.'));
        return;
    }
    form.post('/cart', {
        preserveScroll: true,
        onSuccess: () => dialog.value?.close(),
    });
}
defineExpose({ open });
</script>
<template>
    <dialog
        ref="dialog"
        class="product-dialog store"
        :aria-labelledby="'product-title-' + product.id"
        @click="
            (event) => {
                if (event.target === dialog) dialog?.close();
            }
        "
    >
        <button
            class="dialog-close"
            type="button"
            @click="dialog?.close()"
            :aria-label="t('Close product details')"
        >
            <X :size="20" />
        </button>
        <div class="product-detail-grid">
            <div class="detail-image" :data-color="color">
                <img
                    :src="product.image"
                    :alt="t('Illustration of :name', { name: t(product.name) })"
                /><span> {{ t('Made for your everyday.') }} </span>
            </div>
            <div class="detail-content">
                <span class="eyebrow"
                    >{{ t(product.category) }} {{ t('COLLECTION') }}
                </span>
                <h2 :id="'product-title-' + product.id">
                    {{ t(product.name) }}
                </h2>
                <div class="detail-price">
                    {{ money(product.price, page.props.currency)
                    }}<span>{{
                        wholesale
                            ? t('Wholesale price / pair')
                            : t('Price / pair')
                    }}</span>
                </div>
                <p class="detail-description">{{ t(product.description) }}</p>
                <form @submit.prevent="add">
                    <div class="option-heading">
                        <strong> {{ t('Color') }} </strong
                        ><span>{{ t(color) }}</span>
                    </div>
                    <div class="color-options">
                        <button
                            v-for="option in colors"
                            :key="option"
                            type="button"
                            :class="{ selected: color === option }"
                            :aria-pressed="color === option"
                            @click="chooseColor(option)"
                        >
                            <span
                                class="color-swatch"
                                :data-color="option"
                            ></span
                            >{{ t(option)
                            }}<Check v-if="color === option" :size="13" />
                        </button>
                    </div>
                    <div class="option-heading">
                        <strong> {{ t('Choose your size') }} </strong
                        ><span> {{ t('EU sizing') }} </span>
                    </div>
                    <div class="size-options">
                        <button
                            v-for="variant in variants"
                            :key="variant.id"
                            type="button"
                            :disabled="variant.stock < 1"
                            :class="{
                                selected: form.variant_id === variant.id,
                            }"
                            :aria-pressed="form.variant_id === variant.id"
                            :aria-label="
                                t('Size :size, :availability', {
                                    size: num(variant.size),
                                    availability:
                                        variant.stock < 1
                                            ? t('Sold out')
                                            : t('Available'),
                                })
                            "
                            @click="chooseSize(variant.id)"
                        >
                            {{ num(variant.size) }}
                        </button>
                    </div>
                    <p
                        v-if="form.errors.variant_id"
                        class="field-error"
                        role="alert"
                    >
                        {{ form.errors.variant_id }}
                    </p>
                    <p v-else class="size-help">
                        {{
                            selected
                                ? t(':count pairs available in this size.', {
                                      count: num(selected.stock),
                                  })
                                : t(
                                      'Select a size. Unavailable sizes are crossed out.',
                                  )
                        }}
                    </p>
                    <div class="purchase-row">
                        <div class="quantity-stepper">
                            <button
                                type="button"
                                :disabled="
                                    form.quantity <= 1 || form.processing
                                "
                                @click="form.quantity--"
                                :aria-label="t('Decrease quantity')"
                            >
                                <Minus :size="15" /></button
                            ><input
                                v-model.number="form.quantity"
                                type="number"
                                min="1"
                                :max="maximum"
                                required
                                :aria-label="t('Number of pairs')"
                            /><button
                                type="button"
                                :disabled="
                                    form.quantity >= maximum || form.processing
                                "
                                @click="form.quantity++"
                                :aria-label="t('Increase quantity')"
                            >
                                <Plus :size="15" />
                            </button>
                        </div>
                        <button
                            class="button dark"
                            :disabled="
                                form.processing ||
                                !variants.some((v) => v.stock > 0)
                            "
                            type="submit"
                        >
                            <ShoppingBag :size="17" />{{
                                form.processing ? t('Adding…') : t('Add to bag')
                            }}
                        </button>
                    </div>
                    <p
                        v-if="form.errors.quantity"
                        class="field-error"
                        role="alert"
                    >
                        {{ form.errors.quantity }}
                    </p>
                </form>
                <div class="detail-note">
                    <PackageCheck :size="18" /><span>{{
                        wholesale
                            ? t(
                                  'Mix styles and sizes. Minimum 12 pairs per wholesale order.',
                              )
                            : t(
                                  'Order online. Our team confirms payment and delivery with you.',
                              )
                    }}</span>
                </div>
            </div>
        </div>
    </dialog>
</template>
