import OrderStatusBadge from '@/components/order-status-badge';
import { cn, formatDateTime, formatRupiah } from '@/lib/utils';
import type { OrderDetail as OrderDetailData } from '@/types';

function Card({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    return (
        <section className="bg-muted p-5">
            <h2 className="mb-3 font-semibold">{title}</h2>
            {children}
        </section>
    );
}

/**
 * Order sections shared by the customer and admin detail pages.
 */
export default function OrderDetail({
    order,
    titleClassName = 'text-2xl font-semibold',
}: {
    order: OrderDetailData;
    titleClassName?: string;
}) {
    const address = order.shipping_address;

    return (
        <div className="space-y-2">
            <div className="mb-6 flex flex-wrap items-center gap-x-5 gap-y-3">
                <h1 className={cn('break-all', titleClassName)}>
                    {order.order_number}
                </h1>
                <OrderStatusBadge
                    status={order.status}
                    label={order.status_label}
                />
                <span className="text-sm text-muted-foreground">
                    {formatDateTime(order.created_at)}
                </span>
            </div>

            <Card title="Item">
                <ul className="space-y-3 text-sm">
                    {order.items.map((item) => (
                        <li
                            key={item.id}
                            className="flex justify-between gap-3"
                        >
                            <div>
                                <p className="font-medium">
                                    {item.product_name}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {item.variant_name ?? 'Standar'} ·{' '}
                                    {item.sku} · {item.quantity} ×{' '}
                                    {item.regular_unit_price !== null && (
                                        <>
                                            <s>
                                                {formatRupiah(
                                                    item.regular_unit_price,
                                                )}
                                            </s>{' '}
                                        </>
                                    )}
                                    {formatRupiah(item.unit_price)}
                                    {item.regular_unit_price !== null &&
                                        ' · harga flash sale'}
                                </p>
                            </div>
                            <span>{formatRupiah(item.line_total)}</span>
                        </li>
                    ))}
                </ul>
                <dl className="mt-4 space-y-1 bg-background p-4 text-sm tabular-nums">
                    <div className="flex justify-between">
                        <dt>Subtotal</dt>
                        <dd>{formatRupiah(order.subtotal)}</dd>
                    </div>
                    {order.discount_total > 0 && (
                        <div className="flex justify-between text-warning">
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
                    <div className="flex justify-between pt-2 text-base font-semibold">
                        <dt>Total</dt>
                        <dd>{formatRupiah(order.grand_total)}</dd>
                    </div>
                </dl>
            </Card>

            <div className="grid gap-2 md:grid-cols-2">
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
                            <span className="font-semibold tracking-wide tabular-nums">
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
                                    <span className="text-xs tabular-nums">
                                        {payment.reference}
                                    </span>
                                    <span
                                        className={
                                            payment.status === 'paid'
                                                ? 'font-medium text-success'
                                                : 'font-medium text-destructive'
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
                <ol className="space-y-3">
                    {order.history.map((entry) => (
                        <li key={entry.id} className="relative pl-6 text-sm">
                            <span
                                className="absolute top-1.5 left-0 size-2.5 rounded-full bg-primary"
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
