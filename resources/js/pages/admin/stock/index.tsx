import { Form, Head, Link } from '@inertiajs/react';
import StockController from '@/actions/App/Http/Controllers/Admin/StockController';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDateTime } from '@/lib/utils';
import { show as orderShow } from '@/routes/admin/orders';
import { edit as productEdit } from '@/routes/admin/products';
import { index } from '@/routes/admin/stock';
import type { Paginated } from '@/types';

type LowStock = {
    id: number;
    product_id: number;
    product_name: string;
    label: string | null;
    sku: string;
    stock: number;
    low_stock_threshold: number;
};

type Movement = {
    id: number;
    sku: string;
    product_name: string;
    type: 'in' | 'reserve' | 'release' | 'adjustment';
    quantity: number;
    stock_after: number;
    order_number: string | null;
    user: string;
    note: string | null;
    created_at: string;
};

const typeLabels: Record<Movement['type'], string> = {
    in: 'Masuk',
    reserve: 'Reservasi pesanan',
    release: 'Pelepasan (batal/kedaluwarsa)',
    adjustment: 'Penyesuaian manual',
};

export default function AdminStockIndex({
    lowStock,
    movements,
    filters,
}: {
    lowStock: LowStock[];
    movements: Paginated<Movement>;
    filters: { sku: string };
}) {
    return (
        <>
            <Head title="Stok" />

            <div className="space-y-6 p-4">
                <Heading
                    title="Stok"
                    description="Varian dengan stok menipis dan riwayat seluruh pergerakan stok."
                />

                <section className="space-y-3">
                    <h2 className="font-semibold">
                        Stok menipis ({lowStock.length})
                    </h2>
                    {lowStock.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Semua stok di atas ambang batas.
                        </p>
                    ) : (
                        <ul className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            {lowStock.map((variant) => (
                                <li key={variant.id}>
                                    <Link
                                        href={productEdit(variant.product_id)}
                                        className="flex items-center justify-between gap-2 rounded-lg border p-3 hover:bg-accent"
                                    >
                                        <div>
                                            <p className="text-sm font-medium">
                                                {variant.product_name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {variant.label ?? 'Standar'} ·{' '}
                                                {variant.sku}
                                            </p>
                                        </div>
                                        <span
                                            className={
                                                variant.stock === 0
                                                    ? 'font-semibold text-red-600'
                                                    : 'font-semibold text-amber-600'
                                            }
                                        >
                                            {variant.stock} /{' '}
                                            {variant.low_stock_threshold}
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section className="space-y-3">
                    <h2 className="font-semibold">Riwayat pergerakan stok</h2>
                    <Form
                        {...StockController.index.form()}
                        className="flex gap-2"
                    >
                        <Input
                            name="sku"
                            defaultValue={filters.sku}
                            placeholder="Filter SKU"
                            aria-label="Filter SKU"
                            className="max-w-xs"
                        />
                        <Button variant="outline">Terapkan</Button>
                    </Form>

                    <div className="overflow-x-auto rounded-lg border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="p-3 font-medium">Waktu</th>
                                    <th className="p-3 font-medium">SKU</th>
                                    <th className="p-3 font-medium">Jenis</th>
                                    <th className="p-3 text-right font-medium">
                                        Jumlah
                                    </th>
                                    <th className="p-3 text-right font-medium">
                                        Stok akhir
                                    </th>
                                    <th className="p-3 font-medium">
                                        Referensi
                                    </th>
                                    <th className="p-3 font-medium">Oleh</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {movements.data.map((movement) => (
                                    <tr key={movement.id}>
                                        <td className="p-3 whitespace-nowrap">
                                            {formatDateTime(
                                                movement.created_at,
                                            )}
                                        </td>
                                        <td className="p-3">
                                            <p className="font-mono text-xs">
                                                {movement.sku}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {movement.product_name}
                                            </p>
                                        </td>
                                        <td className="p-3">
                                            {typeLabels[movement.type]}
                                        </td>
                                        <td
                                            className={`p-3 text-right font-medium ${movement.quantity < 0 ? 'text-red-600' : 'text-emerald-600'}`}
                                        >
                                            {movement.quantity > 0
                                                ? `+${movement.quantity}`
                                                : movement.quantity}
                                        </td>
                                        <td className="p-3 text-right">
                                            {movement.stock_after}
                                        </td>
                                        <td className="p-3">
                                            {movement.order_number ? (
                                                <Link
                                                    href={orderShow(
                                                        movement.order_number,
                                                    )}
                                                    className="underline"
                                                >
                                                    {movement.order_number}
                                                </Link>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    {movement.note ?? '—'}
                                                </span>
                                            )}
                                        </td>
                                        <td className="p-3">{movement.user}</td>
                                    </tr>
                                ))}
                                {movements.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="p-6 text-center text-muted-foreground"
                                        >
                                            Belum ada pergerakan stok.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    <Pagination links={movements.links} />
                </section>
            </div>
        </>
    );
}

AdminStockIndex.layout = {
    breadcrumbs: [{ title: 'Stok', href: index() }],
};
