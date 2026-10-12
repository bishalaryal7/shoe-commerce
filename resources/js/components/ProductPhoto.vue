<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import type { Product } from '@/types/shop';
import { router, useForm } from '@inertiajs/vue3';
import { Upload } from 'lucide-vue-next';
import { onBeforeUnmount, ref, watch } from 'vue';
const { t, num } = useLocale();
defineProps<{ product: Product }>();
const form = useForm({ image: null as File | null });
const galleryForm = useForm({ image: null as File | null });
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
            t('Choose a JPG, PNG or WebP photo no larger than 5 MB.'),
        );
        return;
    }
    form.image = selected;
}
function removePhoto(productId: number, photoId: number) {
    if (!window.confirm(t('Remove this photo?'))) return;
    router.delete('/admin/products/' + productId + '/photos/' + photoId, {
        preserveScroll: true,
    });
}
</script>
<template>
    <div class="admin-gallery" v-if="product.photos?.length">
        <h4>{{ t('Product photos') }}</h4>
        <div class="admin-gallery-list">
            <div
                v-for="photo in product.photos"
                :key="photo.id"
                class="admin-gallery-item"
            >
                <img
                    :src="photo.url || '/storage/' + photo.path"
                    :alt="t(product.name)"
                />
                <button
                    type="button"
                    class="text-button"
                    @click="
                        router.patch(
                            '/admin/products/' +
                                product.id +
                                '/photos/' +
                                photo.id +
                                '/primary',
                            {},
                            { preserveScroll: true },
                        )
                    "
                >
                    {{
                        product.image ===
                        (photo.url || '/storage/' + photo.path)
                            ? t('Primary photo')
                            : t('Make primary')
                    }}
                </button>
                <div class="admin-gallery-move">
                    <button
                        type="button"
                        :disabled="product.photos?.[0]?.id === photo.id"
                        @click="
                            router.patch(
                                '/admin/products/' +
                                    product.id +
                                    '/photos/' +
                                    photo.id +
                                    '/move',
                                { direction: 'up' },
                                { preserveScroll: true },
                            )
                        "
                    >
                        {{ t('Move earlier') }}</button
                    ><button
                        type="button"
                        :disabled="
                            product.photos?.[product.photos.length - 1]?.id ===
                            photo.id
                        "
                        @click="
                            router.patch(
                                '/admin/products/' +
                                    product.id +
                                    '/photos/' +
                                    photo.id +
                                    '/move',
                                { direction: 'down' },
                                { preserveScroll: true },
                            )
                        "
                    >
                        {{ t('Move later') }}
                    </button>
                </div>
                <button
                    type="button"
                    class="text-button"
                    @click="removePhoto(product.id, photo.id)"
                >
                    {{ t('Remove photo') }}
                </button>
            </div>
        </div>
    </div>
    <form
        class="product-photo-form"
        @submit.prevent="
            galleryForm.post('/admin/products/' + product.id + '/photos', {
                preserveScroll: true,
                onSuccess: () => galleryForm.reset(),
            })
        "
    >
        <div>
            <strong>{{ t('Add gallery photo') }}</strong>
            <p>{{ t('Show more angles, details, and materials.') }}</p>
            <input
                type="file"
                accept="image/jpeg,image/png,image/webp"
                required
                @change="
                    galleryForm.image =
                        ($event.target as HTMLInputElement).files?.[0] || null
                "
            />
            <p v-if="galleryForm.errors.image" class="field-error">
                {{ galleryForm.errors.image }}
            </p>
            <button
                class="button outline"
                :disabled="!galleryForm.image || galleryForm.processing"
            >
                {{ t('Add photo') }}
            </button>
        </div>
    </form>
    <form
        class="product-photo-form"
        @submit.prevent="
            form.post('/admin/products/' + product.id + '/image', {
                preserveScroll: true,
                onSuccess: reset,
            })
        "
    >
        <img :src="preview || product.image" :alt="t(product.name)" />
        <div>
            <label>
                {{ t('Replace product photo') }}
                <input
                    ref="input"
                    type="file"
                    class="sr-only"
                    accept="image/jpeg,image/png,image/webp"
                    @change="file"
                    required
                /><span class="button outline">{{ t('Choose a photo') }}</span
                ><span>{{
                    form.image?.name || t('No photo selected')
                }}</span></label
            ><small> {{ t('JPG, PNG, or WebP · Up to 5 MB') }} </small>
            <p v-if="form.errors.image" class="field-error" role="alert">
                {{ form.errors.image }}
            </p>
            <p v-if="form.progress" role="status">
                {{ t('Uploading') }} {{ num(form.progress.percentage ?? 0) }}%
            </p>
            <progress
                v-if="form.progress"
                :value="form.progress.percentage"
                max="100"
                :aria-label="t('Photo upload progress')"
            />
            <button
                class="button outline"
                :disabled="!form.image || form.processing"
            >
                <Upload :size="14" />{{
                    form.processing
                        ? t('Uploading…')
                        : t('Save replacement photo')
                }}
            </button>
        </div>
    </form>
</template>
