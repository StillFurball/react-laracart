import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import OrderStatusBadge from '@/components/order-status-badge';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { formatDateTime, formatRupiah } from '@/lib/utils';
import { home } from '@/routes';
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

            <div className="space-y-6 p-4">
                <Heading
                    title="Pesanan saya"
                    description="Riwayat pesanan beserta statusnya."
                />

                {orders.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed p-10 text-center">
                        <p className="mb-4 text-muted-foreground">
                            Belum ada pesanan.
                        </p>
                        <Button asChild>
                            <Link href={home()}>Mulai belanja</Link>
                        </Button>
                    </div>
                ) : (
                    <ul className="divide-y rounded-lg border">
                        {orders.data.map((order) => (
                            <li key={order.order_number}>
                                <Link
                                    href={show(order.order_number)}
                                    className="flex flex-wrap items-center gap-3 p-4 hover:bg-accent"
                                >
                                    <div className="flex-1">
                                        <p className="font-medium">
                                            {order.order_number}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {formatDateTime(order.created_at)} ·{' '}
                                            {order.items_count} item
                                        </p>
                                    </div>
                                    <OrderStatusBadge
                                        status={order.status}
                                        label={order.status_label}
                                    />
                                    <p className="w-28 text-right font-semibold">
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
