<script setup lang="ts">
import type { Product, ShopSharedProps } from '@/types/shop';
import { useForm, usePage } from '@inertiajs/vue3';
import { Check } from 'lucide-vue-next';
const props = defineProps<{ product: Product }>();
const page = usePage<ShopSharedProps>();
const form = useForm({
    name: props.product.name,
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
        <label
            >Product name<input
                v-model="form.name"
                required
                maxlength="150"
            /><span v-if="form.errors.name" class="field-error">{{
                form.errors.name
            }}</span></label
        ><label
            >Retail price ({{ page.props.currency }})<input
                v-model="form.retail_price"
                type="number"
                step="0.01"
                min="0.01"
                max="1000000"
                required
            /><span v-if="form.errors.retail_price" class="field-error">{{
                form.errors.retail_price
            }}</span></label
        ><label
            >Wholesale price ({{ page.props.currency }})<input
                v-model="form.wholesale_price"
                type="number"
                step="0.01"
                min="0.01"
                max="1000000"
                required
            /><span v-if="form.errors.wholesale_price" class="field-error">{{
                form.errors.wholesale_price
            }}</span></label
        ><label class="checkbox-label"
            ><input v-model="form.active" type="checkbox" />Visible in the
            shop</label
        ><button class="button dark" :disabled="form.processing">
            {{ form.processing ? 'Saving…' : 'Save changes' }}</button
        ><span
            v-if="form.recentlySuccessful"
            class="saved-indicator"
            role="status"
            ><Check :size="14" />Changes saved</span
        >
    </form>
</template>
