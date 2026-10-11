<script setup lang="ts">
import type { Product } from '@/types/shop';
import { useForm } from '@inertiajs/vue3';
import { Upload } from 'lucide-vue-next';
import { onBeforeUnmount, ref, watch } from 'vue';
defineProps<{ product: Product }>();
const form = useForm({ image: null as File | null });
const preview = ref<string | null>(null);
const input = ref<HTMLInputElement | null>(null);
watch(
    () => form.image,
    (image) => {
        if (preview.value) URL.revokeObjectURL(preview.value);
        preview.value = image ? URL.createObjectURL(image) : null;
    },
);
onBeforeUnmount(() => {
    if (preview.value) URL.revokeObjectURL(preview.value);
});
function reset() {
    form.reset();
    if (input.value) input.value.value = '';
}
function file(event: Event) {
    form.clearErrors('image');
    const selected = (event.target as HTMLInputElement).files?.[0] || null;
    if (
        selected &&
        (!['image/jpeg', 'image/png', 'image/webp'].includes(selected.type) ||
            selected.size > 5 * 1024 * 1024)
    ) {
        form.image = null;
        form.setError(
            'image',
            'Choose a JPG, PNG or WebP photo no larger than 5 MB.',
        );
        return;
    }
    form.image = selected;
}
</script>
<template>
    <form
        class="product-photo-form"
        @submit.prevent="
            form.post('/admin/products/' + product.id + '/image', {
                preserveScroll: true,
                onSuccess: reset,
            })
        "
    >
        <img :src="preview || product.image" :alt="product.name" />
        <div>
            <label
                >Replace product photo<input
                    ref="input"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    @change="file"
                    required /></label
            ><small>JPG, PNG, or WebP · Up to 5 MB</small>
            <p v-if="form.errors.image" class="field-error" role="alert">
                {{ form.errors.image }}
            </p>
            <p v-if="form.progress" role="status">
                Uploading {{ form.progress.percentage }}%
            </p>
            <progress
                v-if="form.progress"
                :value="form.progress.percentage"
                max="100"
                aria-label="Photo upload progress"
            />
            <button
                class="button outline"
                :disabled="!form.image || form.processing"
            >
                <Upload :size="14" />{{
                    form.processing ? 'Uploading…' : 'Save replacement photo'
                }}
            </button>
        </div>
    </form>
</template>
