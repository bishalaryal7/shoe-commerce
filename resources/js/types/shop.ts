import type { AppPageProps } from '@/types';

export interface Variant {
    id: number;
    sku: string;
    size: string;
    color: string;
    stock: number;
}
export interface Product {
    id: number;
    name: string;
    slug: string;
    category: string;
    description: string;
    image: string;
    price: number;
    retail_price: number;
    wholesale_price?: number;
    active: boolean;
    variants: Variant[];
}
export interface CartLine {
    id: number;
    name: string;
    image: string;
    sku: string;
    size: string;
    color: string;
    stock: number;
    quantity: number;
    unit_price: number;
}
export function money(amount: number, currency = 'USD') {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
    }).format(amount / (currency === 'JPY' ? 1 : 100));
}

export type ShopSharedProps = AppPageProps<{
    currency: string;
    cartCount: number;
    flash: { success?: string };
}>;

export interface PaymentOption {
    id: 'khalti' | 'paypay';
    name: string;
    sandbox: boolean;
}
export interface CustomerOrder {
    reference: string;
    name: string;
    email: string;
    phone: string;
    address: string;
    currency: string;
    subtotal: number;
    shipping_amount: number;
    tax_amount: number;
    total: number;
    status: string;
    payment_status: string;
    payment_method: string;
    paid_at: string | null;
    created_at: string;
    items: {
        id: number;
        name: string;
        size: string;
        color: string;
        quantity: number;
        unit_price: number;
        sku: string;
    }[];
}
