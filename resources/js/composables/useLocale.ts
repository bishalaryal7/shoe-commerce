import {
    formatMoney,
    labels,
    number,
    translate,
    type Locale,
    type Replacements,
} from '@/i18n/locale';
import type { ShopSharedProps } from '@/types/shop';
import { usePage } from '@inertiajs/vue3';

export function useLocale() {
    const page = usePage<ShopSharedProps>();
    const locale = (): Locale => (page.props.locale === 'ne' ? 'ne' : 'en');
    const t = (text: string | null | undefined, values: Replacements = {}) =>
        translate(locale(), text ?? '', values);
    return {
        t,
        label: (value: string) => t(labels[value] ?? value),
        num: (value: number | string, grouping = true) =>
            number(locale(), value, grouping),
        money: (amount: number, currency = page.props.currency || 'NPR') =>
            formatMoney(locale(), amount, currency),
        date: (value: string, time = false) =>
            new Intl.DateTimeFormat(
                locale() === 'ne' ? 'ne-NP-u-nu-deva' : 'en-US',
                {
                    dateStyle: 'medium',
                    ...(time ? { timeStyle: 'short' as const } : {}),
                },
            ).format(new Date(value)),
        paginationLabel: (value: string) => {
            const decoded = value
                .replaceAll('&laquo;', '«')
                .replaceAll('&raquo;', '»');
            return /^\d+$/.test(decoded)
                ? number(locale(), decoded)
                : t(decoded);
        },
    };
}
