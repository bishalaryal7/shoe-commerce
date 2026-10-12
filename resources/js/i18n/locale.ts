import nepali from '../../../lang/ne.json' with { type: 'json' };

export type Locale = 'en' | 'ne';
export type Replacements = Record<string, string | number>;
const translations: Record<string, string> = nepali;
export const slogan = 'Reach higher. Walk further.';

export function translate(
    locale: Locale,
    text: string,
    values: Replacements = {},
): string {
    const template =
        locale === 'ne' && text !== slogan
            ? (translations[text] ?? text)
            : text;
    return template.replace(/:([a-zA-Z_]+)/g, (placeholder, key) =>
        Object.hasOwn(values, key) ? String(values[key]) : placeholder,
    );
}
export function number(
    locale: Locale,
    value: number | string,
    grouping = true,
): string {
    return new Intl.NumberFormat(
        locale === 'ne' ? 'ne-NP-u-nu-deva' : 'en-US',
        { useGrouping: grouping },
    ).format(Number(value));
}
export function formatMoney(
    locale: Locale,
    amount: number,
    currency: string,
): string {
    return new Intl.NumberFormat(
        locale === 'ne' ? 'ne-NP-u-nu-deva' : 'en-US',
        {
            style: 'currency',
            currency,
        },
    ).format(amount / (currency === 'JPY' ? 1 : 100));
}
export const labels: Record<string, string> = {
    pending_confirmation: 'Awaiting confirmation',
    confirmed: 'Confirmed',
    shipped: 'Shipped',
    fulfilled: 'Fulfilled',
    cancelled: 'Cancelled',
    unpaid: 'Unpaid',
    pending: 'Pending',
    paid: 'Paid',
    review: 'Under review',
    refunded: 'Refunded',
    failed: 'Failed',
    initiated: 'Started',
    approved: 'Approved',
    rejected: 'Rejected',
    cod: 'Cash on delivery',
    request: 'Order request',
    offline: 'Offline payment',
    retail: 'Retail',
    wholesale: 'Wholesale',
    khalti: 'Khalti',
    paypay: 'PayPay',
    open: 'Open',
    answered: 'Answered',
    closed: 'Resolved',
    requested: 'Requested',
    declined: 'Declined',
    completed: 'Completed',
    orders: 'Orders',
    stock: 'Available stock',
    applications: 'Applications',
    low_stock: 'Low stock sizes',
    return_requests: 'Return requests',
    placed: 'Order placed',
    created: 'Order created',
    payment_received: 'Payment received',
    offline_payment: 'Offline payment',
    reference: 'Reference',
    reason: 'Reason',
    quantity: 'Quantity',
    provider: 'Provider',
    transaction_id: 'Transaction ID',
    payment_reference: 'Payment reference',
    receipt: 'Receipt',
    payment_method: 'Payment method',
    payment_status: 'Payment status',
    payment_review_required: 'Payment review required',
    online_payment_verified: 'Online payment verified',
    offline_payment_received: 'Offline payment received',
    attempt_reference: 'Payment attempt reference',
    amount: 'Amount',
    currency: 'Currency',
    NPR: 'NPR',
    USD: 'USD',
    JPY: 'JPY',
    smtp: 'SMTP mail',
    log: 'Mail log',
    array: 'Test mail',
    sendmail: 'Server mail',
    ses: 'Amazon SES',
    postmark: 'Postmark',
    resend: 'Resend',
};
