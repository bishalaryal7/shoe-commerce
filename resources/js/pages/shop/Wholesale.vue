<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
const { t } = useLocale();
defineProps<{ account: { company: string; status: string } | null }>();
const page = usePage();
const form = useForm({ company: '', phone: '', message: '' });
</script>
<template>
    <Head title="Wholesale partners" /><ShopLayout
        ><section class="wrap inner-page">
            <div class="eyebrow">GOOD COMPANY. GREAT PARTNERS.</div>
            <h1>Let’s grow<br /><em>together.</em></h1>
            <div class="checkout-grid">
                <div class="wholesale-copy">
                    <p>
                        Bring the collection to your customers. Our wholesale
                        program gives approved businesses access to partner
                        pricing and bulk ordering.
                    </p>
                    <ol>
                        <li>
                            <strong>Tell us about your business.</strong>
                            <p>
                                Create an account and submit your application.
                            </p>
                        </li>
                        <li>
                            <strong>We review your application.</strong>
                            <p>
                                Your team’s wholesale prices stay private until
                                approval.
                            </p>
                        </li>
                        <li>
                            <strong>Build your next order.</strong>
                            <p>
                                Mix sizes and styles with a minimum of 12 pairs
                                per order.
                            </p>
                        </li>
                    </ol>
                </div>
                <div class="form-card">
                    <template v-if="account"
                        ><h2>{{ account.company }}</h2>
                        <span class="status-pill">{{ account.status }}</span>
                        <p v-if="account.status === 'approved'">
                            Your partner pricing is ready. Prices in the shop
                            and your bag now reflect your wholesale account.
                        </p>
                        <p v-else-if="account.status === 'pending'">
                            Your application is with our team. You can continue
                            shopping at retail prices while we review it.
                        </p>
                        <p v-else>
                            Your application was not approved. Contact the
                            company team to discuss your business.
                        </p>
                        <Link href="/" class="button dark"
                            >Explore the collection ↗</Link
                        ></template
                    ><template v-else-if="!(page.props.auth as any)?.user"
                        ><h2>{{ t('Join the partner program') }}</h2>
                        <p>
                            {{
                                t(
                                    'Sign in or register to apply for a wholesale account.',
                                )
                            }}
                        </p>
                        <Link href="/register" class="button dark"
                            >Create an account ↗</Link
                        >
                        <p>
                            {{ t('Already registered?') }}
                            <Link href="/login" class="text-button">{{
                                t('Sign in')
                            }}</Link>
                        </p></template
                    >
                    <form v-else @submit.prevent="form.post('/wholesale')">
                        <h2>{{ t('Apply for wholesale') }}</h2>
                        <label
                            >{{ t('Company name')
                            }}<input
                                v-model="form.company"
                                required
                                maxlength="150"
                                autocomplete="organization"
                            /><span class="field-error">{{
                                form.errors.company
                            }}</span></label
                        ><label
                            >{{ t('Business phone')
                            }}<input
                                v-model="form.phone"
                                type="tel"
                                required
                                maxlength="40"
                                autocomplete="tel"
                            /><span class="field-error">{{
                                form.errors.phone
                            }}</span></label
                        ><label
                            >{{ t('Tell us about your business')
                            }}<textarea
                                v-model="form.message"
                                rows="4"
                                maxlength="2000"
                                :placeholder="
                                    t(
                                        'Your shop, location, and expected order sizes',
                                    )
                                "
                            /><span class="field-error">{{
                                form.errors.message
                            }}</span></label
                        ><button
                            class="button dark"
                            :disabled="form.processing"
                        >
                            Submit application ↗
                        </button>
                    </form>
                </div>
            </div>
        </section></ShopLayout
    >
</template>
