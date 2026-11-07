import { Form, Head, Link } from '@inertiajs/react';
import OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
import Heading from '@/components/heading';
import OrderStatusBadge from '@/components/order-status-badge';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { formatDateTime, formatRupiah } from '@/lib/utils';
import { index, show } from '@/routes/admin/orders';
import type { Option, OrderSummary, Paginated } from '@/types';

type Row = OrderSummary & { customer: { name: string; email: string } };

export default function AdminOrdersIndex({
    orders,
    filters,
    statuses,
}: {
    orders: Paginated<Row>;
    filters: { q: string; status: string };
    statuses: Option[];
}) {
    return (
        <>
            <Head title="Pesanan" />

            <div className="space-y-4 p-4">
                <Heading
                    title="Pesanan"
                    description="Cari berdasarkan nomor pesanan atau email pelanggan."
                />

                <Form
                    {...OrderController.index.form()}
                    className="flex flex-col gap-2 sm:flex-row"
                >
                    <Input
                        name="q"
                        defaultValue={filters.q}
                        placeholder="ORD-… atau email"
                        aria-label="Cari pesanan"
                    />
                    <NativeSelect
                        name="status"
                        defaultValue={filters.status}
                        aria-label="Filter status"
                        className="sm:w-56"
                    >
                        <option value="">Semua status</option>
                        {statuses.map((status) => (
                            <option key={status.value} value={status.value}>
                                {status.label}
                            </option>
                        ))}
                    </NativeSelect>
                    <Button type="submit" variant="outline">
                        Terapkan
                    </Button>
                </Form>

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="p-3 font-medium">Nomor</th>
                                <th className="p-3 font-medium">Pelanggan</th>
                                <th className="p-3 font-medium">Tanggal</th>
                                <th className="p-3 font-medium">Status</th>
                                <th className="p-3 text-right font-medium">
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {orders.data.map((order) => (
                                <tr
                                    key={order.order_number}
                                    className="hover:bg-accent/50"
                                >
                                    <td className="p-3">
                                        <Link
                                            href={show(order.order_number)}
                                            className="font-medium underline-offset-4 hover:underline"
                                        >
                                            {order.order_number}
                                        </Link>
                                    </td>
                                    <td className="p-3">
                                        <p>{order.customer.name}</p>
                                        <p className="text-xs text-muted-foreground">
                                            {order.customer.email}
                                        </p>
                                    </td>
                                    <td className="p-3 whitespace-nowrap">
                                        {formatDateTime(order.created_at)}
                                    </td>
                                    <td className="p-3">
                                        <OrderStatusBadge
                                            status={order.status}
                                            label={order.status_label}
                                        />
                                    </td>
                                    <td className="p-3 text-right font-medium">
                                        {formatRupiah(order.grand_total)}
                                    </td>
                                </tr>
                            ))}
                            {orders.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="p-6 text-center text-muted-foreground"
                                    >
                                        Tidak ada pesanan.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination links={orders.links} />
            </div>
        </>
    );
}

AdminOrdersIndex.layout = {
    breadcrumbs: [{ title: 'Pesanan', href: index() }],
};
