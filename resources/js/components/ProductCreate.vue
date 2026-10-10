<script setup lang="ts">
import type { ShopSharedProps } from '@/types/shop';
import { useForm, usePage } from '@inertiajs/vue3';
import { Plus, X } from 'lucide-vue-next';
import { ref } from 'vue';
const page = usePage<ShopSharedProps>();
const open = ref(false);
const form = useForm({
    name: '',
    category: 'Everyday',
    description: '',
    retail_price: '',
    wholesale_price: '',
    color: 'cream',
    sizes: ['38', '39', '40', '41', '42', '43'],
    stock: 0,
});
function save() {
    form.transform((data) => ({
        ...data,
        retail_price: Math.round(Number(data.retail_price) * 100),
        wholesale_price: Math.round(Number(data.wholesale_price) * 100),
    })).post('/admin/products', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            open.value = false;
        },
    });
}
</script>
<template>
    <div class="product-create">
        <button v-if="!open" class="button dark" @click="open = true">
            <Plus :size="16" />Add a product
        </button>
        <form v-else class="form-card" @submit.prevent="save()">
            <div class="section-heading">
                <h2>A new addition to your collection.</h2>
                <button
                    type="button"
                    @click="open = false"
                    aria-label="Close product form"
                >
                    <X :size="18" />
                </button>
            </div>
            <div class="checkout-field-grid">
                <label
                    >Product name<input
                        v-model="form.name"
                        required
                        maxlength="150" /></label
                ><label
                    >Category<select v-model="form.category">
                        <option>Everyday</option>
                        <option>Running</option>
                        <option>Outdoor</option>
                    </select></label
                ><label
                    >Retail price ({{ page.props.currency }})<input
                        v-model="form.retail_price"
                        type="number"
                        min="0.01"
                        max="1000000"
                        step="0.01"
                        required /></label
                ><label
                    >Wholesale price ({{ page.props.currency }})<input
                        v-model="form.wholesale_price"
                        type="number"
                        min="0.01"
                        max="1000000"
                        step="0.01"
                        required /></label
                ><label
                    >Color<select v-model="form.color">
                        <option
                            v-for="color in [
                                'cream',
                                'orange',
                                'green',
                                'white',
                                'blue',
                                'black',
                            ]"
                            :key="color"
                        >
                            {{ color }}
                        </option>
                    </select></label
                ><label
                    >Starting stock per size<input
                        v-model.number="form.stock"
                        type="number"
                        min="0"
                        max="1000000"
                        required
                /></label>
            </div>
            <label
                >Description<textarea
                    v-model="form.description"
                    required
                    maxlength="5000"
                    rows="3"
                />
            </label>
            <fieldset class="product-size-checks">
                <legend>Sizes to include (EU)</legend>
                <label v-for="n in 10" :key="n"
                    ><input
                        v-model="form.sizes"
                        type="checkbox"
                        :value="String(n + 35)"
                    />{{ n + 35 }}</label
                >
            </fieldset>
            <p
                v-for="(error, key) in form.errors"
                :key="key"
                class="field-error"
                role="alert"
            >
                {{ error }}
            </p>
            <button class="button dark" :disabled="form.processing">
                {{ form.processing ? 'Creating…' : 'Create product'
                }}<Plus :size="15" />
            </button>
        </form>
    </div>
</template>
