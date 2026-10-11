export function normalizePhone(phone: string): string {
    return phone
        .trim()
        .replace(/[०-९]/g, (digit) => String('०१२३४५६७८९'.indexOf(digit)));
}

export function validateContact(
    data: { name: string; email: string; phone: string; address: string },
    ne: boolean,
) {
    const errors: Partial<
        Record<'name' | 'email' | 'phone' | 'address', string>
    > = {};
    if (data.name.trim().length < 2)
        errors.name = ne
            ? 'कम्तीमा दुई अक्षरको पूरा नाम लेख्नुहोस्।'
            : 'Enter your full name (at least 2 characters).';
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email))
        errors.email = ne
            ? 'सही इमेल ठेगाना लेख्नुहोस्।'
            : 'Enter a valid email address.';
    const phone = normalizePhone(data.phone);
    const digits = phone.replace(/[^0-9]/g, '');
    if (
        !/^\+?[0-9][0-9 ()-]*$/.test(phone) ||
        digits.length < 7 ||
        digits.length > 15
    )
        errors.phone = ne
            ? '७–१५ अङ्कको सही फोन नम्बर लेख्नुहोस्।'
            : 'Use 7–15 digits, with an optional country code.';
    if (data.address.trim().length < 10)
        errors.address = ne
            ? 'सडक, क्षेत्र र सहरसहितको पूरा ठेगाना लेख्नुहोस्।'
            : 'Enter a full address with your street, area and city.';
    return errors;
}
