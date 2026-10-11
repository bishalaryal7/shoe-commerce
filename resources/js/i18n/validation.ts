import { number, translate, type Locale } from './locale';

type Field = {
    type: string;
    minLength: number;
    maxLength: number;
    min: string;
    max: string;
};

export function validationMessage(
    locale: Locale,
    validity: ValidityState,
    field: Field,
): string {
    const t = (key: string, count?: number | string) =>
        translate(
            locale,
            key,
            count === undefined ? {} : { count: number(locale, count) },
        );
    if (validity.valueMissing) return t('required field');
    if (validity.typeMismatch)
        return t(
            field.type === 'email'
                ? 'Enter a valid email address.'
                : 'Enter a valid web address.',
        );
    if (validity.badInput) return t('Enter a valid number.');
    if (validity.tooShort)
        return t('Use at least :count characters.', field.minLength);
    if (validity.tooLong)
        return t('Use no more than :count characters.', field.maxLength);
    if (validity.rangeUnderflow)
        return t('Enter a value of at least :count.', field.min);
    if (validity.rangeOverflow)
        return t('Enter a value no greater than :count.', field.max);
    if (validity.stepMismatch) return t('Choose an allowed value.');
    return t('Use the requested format.');
}

export function installValidationMessages(): void {
    document.addEventListener(
        'invalid',
        (event) => {
            const target = event.target;
            if (
                document.documentElement.lang !== 'ne' ||
                !(
                    target instanceof HTMLInputElement ||
                    target instanceof HTMLTextAreaElement ||
                    target instanceof HTMLSelectElement
                )
            )
                return;
            target.setCustomValidity('');
            target.setCustomValidity(
                validationMessage('ne', target.validity, {
                    type: target.type,
                    minLength: 'minLength' in target ? target.minLength : 0,
                    maxLength: 'maxLength' in target ? target.maxLength : 0,
                    min: 'min' in target ? target.min : '',
                    max: 'max' in target ? target.max : '',
                }),
            );
        },
        true,
    );
    for (const event of ['input', 'change'])
        document.addEventListener(
            event,
            (event) => {
                const target = event.target;
                if (
                    target instanceof HTMLInputElement ||
                    target instanceof HTMLTextAreaElement ||
                    target instanceof HTMLSelectElement
                )
                    target.setCustomValidity('');
            },
            true,
        );
}
