export type MerchantStore = {
    id: number;
    slug: string;
    name: string;
    tagline: string | null;
    description: string | null;
    accent: string;
    active: boolean;
    logo_url: string | null;
    banner_url: string | null;
};

export type MerchantVariant = { id: number; size: string; stock: number };

export type MerchantProduct = {
    id: number;
    slug: string;
    name: string;
    category: 'Shoes' | 'Clothing' | 'Accessories';
    description: string;
    price: number;
    image_url: string | null;
    active: boolean;
    variants: MerchantVariant[];
};

export type MerchantLine = {
    variant_id: number;
    product: string;
    slug: string;
    image_url: string | null;
    size: string;
    stock: number;
    quantity: number;
    unit_price: number;
};

export type MerchantOrder = {
    id: number;
    reference: string;
    name: string;
    email: string;
    phone: string;
    address: string;
    total: number;
    status: string;
    created_at: string;
    items: {
        id: number;
        name: string;
        size: string;
        quantity: number;
        unit_price: number;
    }[];
};

export type MerchantConversation = {
    id: number;
    subject: string;
    last_message_at: string;
    user?: { id: number; name: string; email: string };
    messages: {
        id: number;
        sender_id: number;
        body: string;
        created_at: string;
    }[];
};
