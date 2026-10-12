<script setup lang="ts">
import LanguageSwitch from '@/components/LanguageSwitch.vue';
import { useLocale } from '@/composables/useLocale';
import type { MerchantStore } from '@/types/merchant';
import type { ShopSharedProps } from '@/types/shop';
import { Link, usePage } from '@inertiajs/vue3';
import { MessageCircle, ShoppingBag } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{ store: MerchantStore; cartCount?: number }>();
const { t, num } = useLocale();
const page = usePage<ShopSharedProps>();
const base = computed(() => '/stores/' + props.store.slug);
</script>

<template>
    <div class="merchant-site" :style="{ '--merchant-accent': store.accent }">
        <a class="skip-link" href="#merchant-content">{{
            t('Skip to content')
        }}</a>
        <div class="merchant-announcement">
            {{ t('An independent shop on EVE Shoes') }}
        </div>
        <header class="merchant-header wrap">
            <Link :href="base" class="merchant-wordmark"
                ><img v-if="store.logo_url" :src="store.logo_url" :alt="''" />{{
                    store.name
                }}</Link
            >
            <nav :aria-label="t('Main navigation')">
                <Link :href="base">{{ t('Shop all') }}</Link>
                <Link :href="base + '/messages'"
                    ><MessageCircle :size="17" />{{
                        t('Message the seller')
                    }}</Link
                >
                <Link :href="base + '/cart'"
                    ><ShoppingBag :size="17" />{{ t('Bag')
                    }}<span v-if="cartCount">({{ num(cartCount) }})</span></Link
                >
                <LanguageSwitch />
            </nav>
        </header>
        <main id="merchant-content"><slot /></main>
        <footer class="merchant-footer wrap">
            <strong>{{ store.name }}</strong>
            <p>{{ store.tagline }}</p>
            <div>
                <Link :href="base + '/messages'">{{
                    t('Contact the seller')
                }}</Link
                ><Link href="/">EVE Shoes</Link
                ><Link
                    v-if="page.props.auth.merchantEligible"
                    href="/merchant"
                    >{{ t('My store workspace') }}</Link
                >
            </div>
        </footer>
    </div>
</template>
