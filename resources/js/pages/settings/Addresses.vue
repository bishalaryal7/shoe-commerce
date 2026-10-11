<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import { useLocale } from '@/composables/useLocale';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const { t } = useLocale();
type Address = {
    id: number;
    label: string;
    recipient: string;
    phone: string;
    address: string;
    is_default: boolean;
};
defineProps<{ addresses: Address[] }>();
const editing = ref<number | null>(null);
const form = useForm({
    label: '',
    recipient: '',
    phone: '',
    address: '',
    is_default: false,
});
function edit(address: Address) {
    editing.value = address.id;
    form.label = address.label;
    form.recipient = address.recipient;
    form.phone = address.phone;
    form.address = address.address;
    form.is_default = address.is_default;
}
function submit() {
    if (editing.value)
        form.patch('/settings/addresses/' + editing.value, {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    else
        form.post('/settings/addresses', {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
}
function reset() {
    editing.value = null;
    form.reset();
}
</script>
<template>
    <AppLayout
        ><Head :title="t('Address book')" /><SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall
                    :title="t('Address book')"
                    :description="
                        t('Save delivery details for a faster checkout.')
                    "
                />
                <article
                    v-for="address in addresses"
                    :key="address.id"
                    class="admin-card"
                >
                    <span v-if="address.is_default" class="status-pill">{{
                        t('Default address')
                    }}</span>
                    <h3>{{ t(address.label) }} · {{ address.recipient }}</h3>
                    <p>{{ address.phone }}</p>
                    <p>{{ address.address }}</p>
                    <div class="flex gap-3">
                        <button
                            class="text-button"
                            type="button"
                            @click="edit(address)"
                        >
                            {{ t('Edit') }}</button
                        ><button
                            class="text-button"
                            type="button"
                            @click="
                                router.delete(
                                    '/settings/addresses/' + address.id,
                                    { preserveScroll: true },
                                )
                            "
                        >
                            {{ t('Remove') }}
                        </button>
                    </div>
                </article>
                <form class="form-card" @submit.prevent="submit">
                    <h2>
                        {{
                            editing
                                ? t('Edit delivery address')
                                : t('Add a delivery address')
                        }}
                    </h2>
                    <label
                        >{{ t('Address label')
                        }}<input
                            v-model="form.label"
                            required
                            minlength="2"
                            maxlength="60"
                            :placeholder="t('Home or work')"
                    /></label>
                    <label
                        >{{ t('Recipient name')
                        }}<input
                            v-model="form.recipient"
                            required
                            minlength="2"
                            maxlength="150"
                            autocomplete="name"
                    /></label>
                    <label
                        >{{ t('Phone number')
                        }}<input
                            v-model="form.phone"
                            required
                            maxlength="40"
                            autocomplete="tel"
                    /></label>
                    <label
                        >{{ t('Delivery address')
                        }}<textarea
                            v-model="form.address"
                            required
                            minlength="10"
                            maxlength="1000"
                            rows="3"
                            autocomplete="street-address"
                        />
                    </label>
                    <label class="check-line"
                        ><input
                            v-model="form.is_default"
                            type="checkbox"
                        /><span>{{
                            t('Make this my default address')
                        }}</span></label
                    >
                    <button class="button dark" :disabled="form.processing">
                        {{ form.processing ? t('Saving…') : t('Save address') }}
                    </button>
                    <button
                        v-if="editing"
                        class="text-button"
                        type="button"
                        @click="reset"
                    >
                        {{ t('Cancel') }}
                    </button>
                    <p
                        v-for="(error, key) in form.errors"
                        :key="key"
                        class="field-error"
                    >
                        {{ t(error) }}
                    </p>
                </form>
            </div>
        </SettingsLayout></AppLayout
    >
</template>
