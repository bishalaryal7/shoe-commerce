<script setup lang="ts">
import { money, type Product } from '@/types/shop';
import { useForm, usePage } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed } from 'vue';
const props = defineProps<{ product: Product; wholesale: boolean }>();
const page = usePage();
const form = useForm({ variant_id: '', quantity: 1 });
const available = computed(() =>
    props.product.variants.filter((v) => v.stock > 0),
);
function add() {
    form.post('/cart', { preserveScroll: true });
}
</script>
<template>
    <article class="product-card">
        <div class="product-image">
            <span class="category-label">{{ product.category }}</span
            ><img
                :src="product.image"
                :alt="product.name + ' in ' + product.variants[0]?.color"
                loading="lazy"
            /><span class="image-note">{{ product.variants[0]?.color }}</span>
        </div>
        <div class="product-title">
            <h3>{{ product.name }}</h3>
            <span>{{
                money(product.price, page.props.currency as string)
            }}</span>
        </div>
        <p>{{ product.description }}</p>
        <form @submit.prevent="add" class="product-controls">
            <select
                v-model="form.variant_id"
                :aria-label="'Choose size for ' + product.name"
                required
            >
                <option disabled value="">Select EU size</option>
                <option v-for="v in available" :key="v.id" :value="v.id">
                    EU {{ v.size }}
                </option>
            </select>
            <input
                v-if="wholesale"
                v-model="form.quantity"
                type="number"
                min="1"
                max="999"
                aria-label="Pairs"
            />
            <button
                class="add-button"
                :disabled="form.processing || !available.length"
                type="submit"
            >
                <Plus :size="16" />{{ available.length ? 'Add' : 'Sold out' }}
            </button>
        </form>
        <p
            v-if="form.errors.quantity || form.errors.variant_id"
            class="field-error"
            role="alert"
        >
            {{ form.errors.quantity || form.errors.variant_id }}
        </p>
    </article>
</template>
