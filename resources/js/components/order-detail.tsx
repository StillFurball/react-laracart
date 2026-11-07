import OrderStatusBadge from '@/components/order-status-badge';
import { formatDateTime, formatRupiah } from '@/lib/utils';
import type { OrderDetail as OrderDetailData } from '@/types';

function Card({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    return (
        <section className="rounded-lg border p-4">
            <h2 className="mb-3 font-semibold">{title}</h2>
            {children}
        </section>
    );
}

/**
 * Order sections shared by the customer and admin detail pages.
 */
export default function OrderDetail({ order }: { order: OrderDetailData }) {
    const address = order.shipping_address;

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center gap-3">
                <h1 className="text-xl font-semibold">{order.order_number}</h1>
                <OrderStatusBadge
                    status={order.status}
                    label={order.status_label}
                />
                <span className="text-sm text-muted-foreground">
                    {formatDateTime(order.created_at)}
                </span>
            </div>

            <Card title="Item">
                <ul className="divide-y text-sm">
                    {order.items.map((item) => (
                        <li
                            key={item.id}
                            className="flex justify-between gap-3 py-2"
                        >
                            <div>
                                <p className="font-medium">
                                    {item.product_name}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {item.variant_name ?? 'Standar'} ·{' '}
                                    {item.sku} · {item.quantity} ×{' '}
                                    {formatRupiah(item.unit_price)}
                                </p>
                            </div>
                            <span>{formatRupiah(item.line_total)}</span>
                        </li>
                    ))}
                </ul>
                <dl className="mt-3 space-y-1 border-t pt-3 text-sm">
                    <div className="flex justify-between">
                        <dt>Subtotal</dt>
                        <dd>{formatRupiah(order.subtotal)}</dd>
                    </div>
                    {order.discount_total > 0 && (
                        <div className="flex justify-between text-emerald-600">
                            <dt>Diskon ({order.voucher_code})</dt>
                            <dd>−{formatRupiah(order.discount_total)}</dd>
                        </div>
                    )}
                    <div className="flex justify-between">
                        <dt>
                            Ongkir ({order.courier} {order.courier_service})
                        </dt>
                        <dd>{formatRupiah(order.shipping_cost)}</dd>
                    </div>
                    <div className="flex justify-between border-t pt-2 text-base font-semibold">
                        <dt>Total</dt>
                        <dd>{formatRupiah(order.grand_total)}</dd>
                    </div>
                </dl>
            </Card>

            <div className="grid gap-4 md:grid-cols-2">
                <Card title="Pengiriman">
                    <p className="text-sm font-medium">
                        {address.recipient_name} · {address.phone}
                    </p>
                    <p className="text-sm text-muted-foreground">
                        {address.address_line}, {address.city},{' '}
                        {address.province} {address.postal_code}
                    </p>
                    {order.shipment ? (
                        <p className="mt-2 text-sm">
                            Resi{' '}
                            <span className="font-mono font-medium">
                                {order.shipment.tracking_number}
                            </span>
                        </p>
                    ) : (
                        <p className="mt-2 text-sm text-muted-foreground">
                            Nomor resi belum tersedia.
                        </p>
                    )}
                    {order.customer_note && (
                        <p className="mt-2 text-sm">
                            <span className="text-muted-foreground">
                                Catatan:
                            </span>{' '}
                            {order.customer_note}
                        </p>
                    )}
                </Card>

                <Card title="Pembayaran">
                    <p className="text-sm">{order.payment_method_label}</p>
                    {order.payments.length === 0 ? (
                        <p className="mt-1 text-sm text-muted-foreground">
                            Belum ada percobaan pembayaran.
                        </p>
                    ) : (
                        <ul className="mt-2 space-y-1 text-sm">
                            {order.payments.map((payment) => (
                                <li
                                    key={payment.id}
                                    className="flex justify-between gap-2"
                                >
                                    <span className="font-mono text-xs">
                                        {payment.reference}
                                    </span>
                                    <span
                                        className={
                                            payment.status === 'paid'
                                                ? 'text-emerald-600'
                                                : 'text-red-600'
                                        }
                                    >
                                        {payment.status === 'paid'
                                            ? 'Berhasil'
                                            : 'Gagal'}{' '}
                                        · {formatRupiah(payment.amount)}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>
            </div>

            <Card title="Linimasa status">
                <ol className="space-y-3 border-l pl-4">
                    {order.history.map((entry) => (
                        <li key={entry.id} className="relative text-sm">
                            <span
                                className="absolute top-1.5 -left-[21px] size-2.5 rounded-full bg-primary"
                                aria-hidden
                            />
                            <p className="font-medium">{entry.status_label}</p>
                            <p className="text-muted-foreground">
                                {formatDateTime(entry.created_at)} ·{' '}
                                {entry.actor}
                                {entry.note && ` · ${entry.note}`}
                            </p>
                        </li>
                    ))}
                </ol>
            </Card>
        </div>
    );
}
