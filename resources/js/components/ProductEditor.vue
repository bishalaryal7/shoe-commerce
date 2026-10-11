<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import type { Product, ShopSharedProps } from '@/types/shop';
import { useForm, usePage } from '@inertiajs/vue3';
import { Check } from 'lucide-vue-next';
const { t, num } = useLocale();
const props = defineProps<{ product: Product }>();
const page = usePage<ShopSharedProps>();
const form = useForm({
    name: props.product.name,
    category: props.product.category,
    description: props.product.description,
    name_ne: props.product.name_ne || '',
    description_ne: props.product.description_ne || '',
    retail_price: (props.product.retail_price / 100).toFixed(2),
    wholesale_price: ((props.product.wholesale_price || 0) / 100).toFixed(2),
    active: props.product.active,
});
function save() {
    form.transform((data) => ({
        ...data,
        retail_price: Math.round(Number(data.retail_price) * 100),
        wholesale_price: Math.round(Number(data.wholesale_price) * 100),
    })).patch('/admin/products/' + props.product.id, { preserveScroll: true });
}
</script>
<template>
    <form class="price-form" @submit.prevent="save">
        <label>
            {{ t('Product name (English)') }}
            <input v-model="form.name" required maxlength="150" /><span
                v-if="form.errors.name"
                class="field-error"
                >{{ form.errors.name }}</span
            ></label
        ><label>
            {{ t('Retail price (') }} {{ t(page.props.currency) }})<input
                v-model="form.retail_price"
                type="number"
                step="0.01"
                min="0.01"
                max="1000000"
                required
            /><span v-if="form.errors.retail_price" class="field-error">{{
                form.errors.retail_price
            }}</span></label
        ><label>
            {{ t('Wholesale price (') }} {{ t(page.props.currency) }})<input
                v-model="form.wholesale_price"
                type="number"
                step="0.01"
                min="0.01"
                max="1000000"
                required
            /><span v-if="form.errors.wholesale_price" class="field-error">{{
                form.errors.wholesale_price
            }}</span></label
        ><label>
            {{ t('Category') }}
            <select v-model="form.category">
                <option
                    v-for="category in ['Everyday', 'Running', 'Outdoor']"
                    :key="category"
                    :value="category"
                >
                    {{ t(category) }}
                </option></select
            ><span v-if="form.errors.category" class="field-error">{{
                form.errors.category
            }}</span></label
        >
        <label class="product-description-field">
            {{ t('Customer description (English)') }}
            <textarea
                v-model="form.description"
                required
                rows="5"
                maxlength="5000"
                :placeholder="t('Describe fit, materials, comfort and care.')"
            /><small>
                {{ t('This text appears on the product page.') }}
                {{
                    t(':count / :limit characters', {
                        count: num(form.description.length),
                        limit: num(5000),
                    })
                }} </small
            ><span v-if="form.errors.description" class="field-error">{{
                form.errors.description
            }}</span></label
        >
        <label>
            {{ t('Nepali product name (optional)') }}
            <input
                v-model="form.name_ne"
                maxlength="150"
                placeholder="नेपाली नाम"
            /><span v-if="form.errors.name_ne" class="field-error">{{
                form.errors.name_ne
            }}</span></label
        >
        <label class="product-description-field">
            {{ t('Nepali description (optional)') }}
            <textarea
                v-model="form.description_ne"
                rows="4"
                maxlength="5000"
                placeholder="नेपालीमा जुत्ताको विवरण"
            /><small>
                {{
                    t(
                        'Used when customers choose नेपाली. Leave blank to show the English description.',
                    )
                }} </small
            ><span v-if="form.errors.description_ne" class="field-error">{{
                form.errors.description_ne
            }}</span></label
        >
        <label class="checkbox-label"
            ><input v-model="form.active" type="checkbox" />
            {{ t('Visible in the shop') }} </label
        ><button class="button dark" :disabled="form.processing">
            {{ form.processing ? t('Saving…') : t('Save changes') }}</button
        ><span
            v-if="form.recentlySuccessful"
            class="saved-indicator"
            role="status"
            ><Check :size="14" /> {{ t('Changes saved') }}
        </span>
    </form>
</template>
