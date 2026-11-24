export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type StoreProfile = {
    name: string;
    email: string;
    phone: string;
    address: string;
};

export type OrderStatus =
    | 'pending_payment'
    | 'paid'
    | 'processing'
    | 'shipped'
    | 'delivered'
    | 'completed'
    | 'expired'
    | 'cancelled'
    | 'return_requested'
    | 'refunded';

export type Option = { value: string; label: string };

/** The cheapest running flash sale offer among a product's variants. */
export type CardFlashSale = {
    price: number;
    regular_price: number;
    discount_percent: number;
    ends_at: string;
};

export type ProductCard = {
    id: number;
    name: string;
    slug: string;
    category: string;
    image: string | null;
    min_price: number;
    total_stock: number;
    has_low_stock: boolean;
    weight_gram: number;
    colors: string[];
    flash_sale: CardFlashSale | null;
};

/** One variant offered in a flash sale, as shown on the landing and flash sale pages. */
export type FlashSaleOffer = {
    id: number;
    product_name: string;
    product_slug: string;
    category: string;
    image: string | null;
    variant_label: string | null;
    price: number;
    regular_price: number;
    discount_percent: number;
    quota: number | null;
    sold: number;
    remaining: number | null;
    stock: number;
};

export type FlashSaleSummary = {
    id: number;
    name: string;
    status: 'running' | 'scheduled' | 'ended' | 'inactive';
    starts_at: string;
    ends_at: string;
    items: FlashSaleOffer[];
};

export type CategoryNode = {
    id: number;
    name: string;
    slug: string;
    children?: CategoryNode[];
};

export type CartLine = {
    id: number;
    quantity: number;
    product_name: string;
    product_slug: string;
    variant_label: string | null;
    sku: string;
    /** What the customer pays per unit: the flash sale price when it applies. */
    price: number;
    regular_price: number;
    line_total: number;
    stock: number;
    issue: string | null;
    /** Why the flash sale price does not apply to this quantity. */
    sale_notice: string | null;
    flash_sale: { ends_at: string; discount_percent: number } | null;
    image: string | null;
};

export type ShippingAddress = {
    recipient_name: string;
    phone: string;
    address_line: string;
    city: string;
    province: string;
    postal_code: string;
};

export type OrderDetail = {
    order_number: string;
    status: OrderStatus;
    status_label: string;
    subtotal: number;
    discount_total: number;
    shipping_cost: number;
    tax_total: number;
    grand_total: number;
    voucher_code: string | null;
    shipping_address: ShippingAddress;
    courier: string;
    courier_service: string;
    payment_method: string;
    payment_method_label: string;
    payment_due_at: string;
    customer_note: string | null;
    created_at: string;
    items: {
        id: number;
        product_name: string;
        variant_name: string | null;
        sku: string;
        unit_price: number;
        /** Normal price when the item was bought at a flash sale price. */
        regular_unit_price: number | null;
        quantity: number;
        line_total: number;
    }[];
    payments: {
        id: number;
        reference: string;
        amount: number;
        status: 'paid' | 'failed';
        created_at: string;
    }[];
    shipment: {
        courier: string;
        service: string;
        tracking_number: string;
        shipped_at: string;
        delivered_at: string | null;
    } | null;
    history: {
        id: number;
        status_label: string;
        note: string | null;
        actor: string;
        created_at: string;
    }[];
};

export type OrderSummary = {
    order_number: string;
    status: OrderStatus;
    status_label: string;
    grand_total: number;
    created_at: string;
};
