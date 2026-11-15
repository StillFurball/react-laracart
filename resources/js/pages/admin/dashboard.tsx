import { Form, Head, Link } from '@inertiajs/react';
import DashboardController from '@/actions/App/Http/Controllers/Admin/DashboardController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatRupiah } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { index as ordersIndex } from '@/routes/admin/orders';
import { edit as productEdit } from '@/routes/admin/products';
import { index as stockIndex } from '@/routes/admin/stock';

type Report = {
    orders: number;
    gross: number;
    discount: number;
    shipping: number;
    net: number;
    daily: { day: string; orders: number; net: number }[];
};

type Props = {
    sales: { today: number; week: number; month: number };
    report: Report;
    statusCounts: { value: string; label: string; total: number }[];
    topProducts: { name: string; quantity: number; revenue: number }[];
    lowStock: {
        id: number;
        product_id: number;
        product_name: string;
        sku: string;
        stock: number;
    }[];
    filters: { from: string; to: string };
};

function formatDay(day: string): string {
    return new Date(`${day}T00:00:00`).toLocaleDateString('id-ID', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });
}

function SalesChart({
    daily,
    from,
    to,
}: {
    daily: Report['daily'];
    from: string;
    to: string;
}) {
    const byDay = new Map(daily.map((row) => [row.day, row]));
    const filled: Report['daily'] = [];
    const end = new Date(`${to}T00:00:00Z`);
    const date = new Date(`${from}T00:00:00Z`);

    // shortcut: ranges over a year plot only days with sales (no zero-filled gaps); aggregate per week/month if long ranges matter.
    while (date <= end && filled.length <= 366) {
        const day = date.toISOString().slice(0, 10);
        filled.push(byDay.get(day) ?? { day, orders: 0, net: 0 });
        date.setUTCDate(date.getUTCDate() + 1);
    }

    const days = filled.length > 366 ? daily : filled;
    const max = Math.max(...days.map((row) => row.net), 1);

    return (
        <figure className="rounded-lg border p-4">
            <figcaption className="flex justify-between gap-2 text-sm text-muted-foreground">
                <span>Pendapatan bersih per hari</span>
                <span className="tabular-nums">
                    Tertinggi {formatRupiah(max)}
                </span>
            </figcaption>
            <div
                role="img"
                aria-label="Grafik batang pendapatan bersih per hari. Rincian angka ada di tabel di bawah."
                className="mt-3 flex h-48 items-end gap-px border-b"
            >
                {days.map((row) => (
                    <div
                        key={row.day}
                        title={`${formatDay(row.day)}: ${formatRupiah(row.net)} · ${row.orders} pesanan`}
                        className="min-w-0 flex-1 rounded-t-sm bg-chart-1 hover:bg-chart-2"
                        style={{
                            height: `${(row.net / max) * 100}%`,
                            minHeight: row.net > 0 ? 2 : 0,
                        }}
                    />
                ))}
            </div>
            <div className="mt-1 flex justify-between text-xs text-muted-foreground">
                <span>{formatDay(days[0].day)}</span>
                <span>{formatDay(days[days.length - 1].day)}</span>
            </div>
        </figure>
    );
}

export default function AdminDashboard({
    sales,
    report,
    statusCounts,
    topProducts,
    lowStock,
    filters,
}: Props) {
    const reportRows: [string, number][] = [
        ['Omzet kotor', report.gross],
        ['Diskon', -report.discount || 0], // avoid -0, which formats as "Rp-0"
        ['Pendapatan bersih', report.net],
        ['Ongkir (diteruskan ke kurir)', report.shipping],
    ];

    return (
        <>
            <Head title="Dashboard" />

            <div className="space-y-6 p-4">
                <Heading
                    title="Dashboard"
                    description="Penjualan dihitung dari pesanan yang sudah dibayar, berdasarkan tanggal pesanan dibuat."
                />

                <section className="grid gap-4 sm:grid-cols-3">
                    {(
                        [
                            ['Penjualan hari ini', sales.today],
                            ['Minggu ini', sales.week],
                            ['Bulan ini', sales.month],
                        ] as const
                    ).map(([label, amount]) => (
                        <Card key={label} className="gap-2 py-4">
                            <CardHeader className="px-4">
                                <CardTitle className="text-sm font-normal text-muted-foreground">
                                    {label}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="px-4 text-2xl font-semibold tabular-nums">
                                {formatRupiah(amount)}
                            </CardContent>
                        </Card>
                    ))}
                </section>

                <section className="space-y-2">
                    <h2 className="font-semibold">Pesanan per status</h2>
                    <ul className="flex flex-wrap gap-2">
                        {statusCounts.map((status) => (
                            <li key={status.value}>
                                <Link
                                    href={ordersIndex({
                                        query: { status: status.value },
                                    })}
                                    className="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm hover:bg-accent"
                                >
                                    {status.label}
                                    <span className="font-semibold tabular-nums">
                                        {status.total}
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </section>

                <section className="space-y-3">
                    <h2 className="font-semibold">Laporan penjualan</h2>
                    <Form
                        {...DashboardController.index.form()}
                        className="flex flex-wrap items-end gap-2"
                    >
                        {({ errors }) => (
                            <>
                                <div className="grid gap-1">
                                    <Label htmlFor="from">Dari</Label>
                                    <Input
                                        id="from"
                                        name="from"
                                        type="date"
                                        defaultValue={filters.from}
                                    />
                                </div>
                                <div className="grid gap-1">
                                    <Label htmlFor="to">Sampai</Label>
                                    <Input
                                        id="to"
                                        name="to"
                                        type="date"
                                        defaultValue={filters.to}
                                    />
                                </div>
                                <Button variant="outline">Tampilkan</Button>
                                {errors.to && (
                                    <p className="w-full text-sm text-destructive">
                                        {errors.to}
                                    </p>
                                )}
                            </>
                        )}
                    </Form>

                    {report.daily.length > 0 && (
                        <SalesChart
                            daily={report.daily}
                            from={filters.from}
                            to={filters.to}
                        />
                    )}

                    <div className="grid gap-4 lg:grid-cols-2">
                        <div className="rounded-lg border">
                            <dl className="divide-y text-sm">
                                <div className="flex justify-between p-3">
                                    <dt>Jumlah pesanan</dt>
                                    <dd className="font-medium tabular-nums">
                                        {report.orders}
                                    </dd>
                                </div>
                                {reportRows.map(([label, amount]) => (
                                    <div
                                        key={label}
                                        className="flex justify-between p-3"
                                    >
                                        <dt>{label}</dt>
                                        <dd className="font-medium tabular-nums">
                                            {amount < 0
                                                ? `−${formatRupiah(-amount)}`
                                                : formatRupiah(amount)}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        </div>

                        <div className="max-h-80 overflow-auto rounded-lg border">
                            <table className="w-full text-sm">
                                <thead className="sticky top-0 bg-muted text-left">
                                    <tr>
                                        <th className="p-3 font-medium">
                                            Tanggal
                                        </th>
                                        <th className="p-3 text-right font-medium">
                                            Pesanan
                                        </th>
                                        <th className="p-3 text-right font-medium">
                                            Pendapatan bersih
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {report.daily.map((row) => (
                                        <tr key={row.day}>
                                            <td className="p-3">
                                                {formatDay(row.day)}
                                            </td>
                                            <td className="p-3 text-right tabular-nums">
                                                {row.orders}
                                            </td>
                                            <td className="p-3 text-right tabular-nums">
                                                {formatRupiah(row.net)}
                                            </td>
                                        </tr>
                                    ))}
                                    {report.daily.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={3}
                                                className="p-6 text-center text-muted-foreground"
                                            >
                                                Belum ada penjualan pada periode
                                                ini.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <div className="grid gap-6 lg:grid-cols-2">
                    <section className="space-y-3">
                        <h2 className="font-semibold">
                            Produk terlaris (periode laporan)
                        </h2>
                        {topProducts.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Belum ada produk terjual.
                            </p>
                        ) : (
                            <ol className="divide-y rounded-lg border text-sm">
                                {topProducts.map((product, index) => (
                                    <li
                                        key={product.name}
                                        className="flex items-center justify-between gap-2 p-3"
                                    >
                                        <span>
                                            {index + 1}. {product.name}
                                        </span>
                                        <span className="text-right text-muted-foreground tabular-nums">
                                            {product.quantity} terjual ·{' '}
                                            {formatRupiah(product.revenue)}
                                        </span>
                                    </li>
                                ))}
                            </ol>
                        )}
                    </section>

                    <section className="space-y-3">
                        <div className="flex items-baseline justify-between">
                            <h2 className="font-semibold">Stok menipis</h2>
                            <Link
                                href={stockIndex()}
                                className="text-sm underline"
                            >
                                Lihat semua
                            </Link>
                        </div>
                        {lowStock.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Semua stok di atas ambang batas.
                            </p>
                        ) : (
                            <ul className="divide-y rounded-lg border text-sm">
                                {lowStock.map((variant) => (
                                    <li key={variant.id}>
                                        <Link
                                            href={productEdit(
                                                variant.product_id,
                                            )}
                                            className="flex justify-between gap-2 p-3 hover:bg-accent"
                                        >
                                            <span>
                                                {variant.product_name}{' '}
                                                <span className="font-mono text-xs text-muted-foreground">
                                                    {variant.sku}
                                                </span>
                                            </span>
                                            <span
                                                className={
                                                    variant.stock === 0
                                                        ? 'font-semibold text-destructive'
                                                        : 'font-semibold text-warning'
                                                }
                                            >
                                                {variant.stock}
                                            </span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
