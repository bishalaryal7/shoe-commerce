<script setup lang="ts">
import type { Product } from '@/types/shop';
import { useForm } from '@inertiajs/vue3';
import { Upload } from 'lucide-vue-next';
defineProps<{ product: Product }>();
const form = useForm({ image: null as File | null });
function file(event: Event) {
    form.image = (event.target as HTMLInputElement).files?.[0] || null;
}
</script>
<template>
    <form
        class="product-photo-form"
        @submit.prevent="
            form.post('/admin/products/' + product.id + '/image', {
                preserveScroll: true,
                onSuccess: () => form.reset(),
            })
        "
    >
        <img :src="product.image" :alt="product.name" />
        <div>
            <label
                >Product photo<input
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    @change="file"
                    required /></label
            ><small>JPG, PNG, or WebP · Up to 5 MB</small>
            <p v-if="form.errors.image" class="field-error" role="alert">
                {{ form.errors.image }}
            </p>
            <button
                class="button outline"
                :disabled="!form.image || form.processing"
            >
                <Upload :size="14" />{{
                    form.processing ? 'Uploading…' : 'Upload photo'
                }}
            </button>
        </div>
    </form>
</template>
