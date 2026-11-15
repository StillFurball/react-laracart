import { Head, Link } from '@inertiajs/react';
import OrderStatusBadge from '@/components/order-status-badge';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { formatDateTime, formatRupiah } from '@/lib/utils';
import { index as productsIndex } from '@/routes/products';
import { index, show } from '@/routes/orders';
import type { OrderSummary, Paginated } from '@/types';

export default function OrdersIndex({
    orders,
}: {
    orders: Paginated<OrderSummary & { items_count: number }>;
}) {
    return (
        <>
            <Head title="Pesanan saya" />

            <div className="space-y-8">
                <div className="space-y-3">
                    <h1 className="text-head font-display">Pesanan saya</h1>
                    <p className="text-body">
                        Riwayat pesanan beserta statusnya.
                    </p>
                </div>

                {orders.data.length === 0 ? (
                    <div className="bg-surface px-6 py-16">
                        <h2 className="text-lead font-semibold">
                            Belum ada pesanan
                        </h2>
                        <p className="mt-2 mb-6 max-w-[48ch] text-body">
                            Pesanan yang kamu buat akan muncul di sini, lengkap
                            dengan status pembayaran dan nomor resi.
                        </p>
                        <Button asChild>
                            <Link href={productsIndex()}>Mulai belanja</Link>
                        </Button>
                    </div>
                ) : (
                    <ul className="space-y-2">
                        {orders.data.map((order) => (
                            <li key={order.order_number}>
                                <Link
                                    href={show(order.order_number)}
                                    className="flex flex-wrap items-center gap-x-6 gap-y-2 bg-surface p-5 transition-colors hover:bg-accent"
                                >
                                    <div className="flex-1">
                                        <p className="text-body font-semibold">
                                            {order.order_number}
                                        </p>
                                        <p className="text-small text-soft">
                                            {formatDateTime(order.created_at)} ·{' '}
                                            {order.items_count} item
                                        </p>
                                    </div>
                                    <OrderStatusBadge
                                        status={order.status}
                                        label={order.status_label}
                                    />
                                    <p className="w-32 text-right text-body font-semibold tabular-nums">
                                        {formatRupiah(order.grand_total)}
                                    </p>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination links={orders.links} />
            </div>
        </>
    );
}

OrdersIndex.layout = {
    breadcrumbs: [{ title: 'Pesanan saya', href: index() }],
};
