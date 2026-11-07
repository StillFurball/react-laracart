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

export type ProductCard = {
    id: number;
    name: string;
    slug: string;
    category: string;
    image: string | null;
    min_price: number;
    total_stock: number;
    has_low_stock: boolean;
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
    price: number;
    line_total: number;
    stock: number;
    issue: string | null;
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
