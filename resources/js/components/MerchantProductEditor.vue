<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import type { MerchantProduct } from '@/types/merchant';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{ product: MerchantProduct }>();
const { t, money } = useLocale();
const edit = useForm({
    name: props.product.name,
    category: props.product.category,
    description: props.product.description,
    price: (props.product.price / 100).toFixed(2),
    active: props.product.active,
    image: null as File | null,
});
const size = useForm({ size: '', stock: 0 });
</script>
<template>
    <details class="merchant-manage-product">
        <summary>
            <img
                v-if="product.image_url"
                :src="product.image_url"
                :alt="''"
            /><span
                ><strong>{{ product.name }}</strong
                ><small
                    >{{ money(product.price, 'NPR') }} ·
                    {{ product.active ? t('Active') : t('Hidden') }}</small
                ></span
            >
        </summary>
        <div class="merchant-manage-product-body">
            <form
                @submit.prevent="
                    edit
                        .transform((data) => ({ ...data, _method: 'patch' }))
                        .post('/merchant/products/' + product.id, {
                            preserveScroll: true,
                        })
                "
            >
                <label
                    >{{ t('Product name')
                    }}<input
                        v-model="edit.name"
                        required
                        maxlength="150" /></label
                ><label
                    >{{ t('Category')
                    }}<select v-model="edit.category">
                        <option value="Shoes">{{ t('Shoes') }}</option>
                        <option value="Clothing">{{ t('Clothing') }}</option>
                        <option value="Accessories">
                            {{ t('Accessories') }}
                        </option>
                    </select></label
                ><label
                    >{{ t('Description')
                    }}<textarea
                        v-model="edit.description"
                        required
                        minlength="10"
                        maxlength="5000"
                        rows="4"
                    /></label
                ><label
                    >{{ t('Price in NPR')
                    }}<input
                        v-model="edit.price"
                        type="number"
                        min="0.01"
                        max="1000000"
                        step="0.01"
                        required /></label
                ><label
                    >{{ t('Replace photo')
                    }}<input
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        @change="
                            edit.image =
                                ($event.target as HTMLInputElement)
                                    .files?.[0] || null
                        " /></label
                ><label class="merchant-check"
                    ><input v-model="edit.active" type="checkbox" />{{
                        t('Visible in store')
                    }}</label
                >
                <p
                    v-for="error in edit.errors"
                    :key="error"
                    class="field-error"
                >
                    {{ error }}
                </p>
                <button class="button outline" :disabled="edit.processing">
                    {{ t('Save product') }}
                </button>
            </form>
            <div class="merchant-variant-editor">
                <h4>{{ t('Sizes and stock') }}</h4>
                <p v-for="variant in product.variants" :key="variant.id">
                    {{ variant.size }} ·
                    {{ t(':count available', { count: variant.stock }) }}
                </p>
                <form
                    @submit.prevent="
                        size.post(
                            '/merchant/products/' + product.id + '/variants',
                            {
                                preserveScroll: true,
                                onSuccess: () => size.reset(),
                            },
                        )
                    "
                >
                    <label
                        >{{ t('Size')
                        }}<input
                            v-model="size.size"
                            required
                            maxlength="40"
                            :placeholder="
                                t('For example: EU 42 or M')
                            " /></label
                    ><label
                        >{{ t('Stock')
                        }}<input
                            v-model.number="size.stock"
                            type="number"
                            min="0"
                            max="100000"
                            required
                    /></label>
                    <p
                        v-for="error in size.errors"
                        :key="error"
                        class="field-error"
                    >
                        {{ error }}
                    </p>
                    <button class="button outline" :disabled="size.processing">
                        {{ t('Add or update size') }}
                    </button>
                </form>
            </div>
        </div>
    </details>
</template>
