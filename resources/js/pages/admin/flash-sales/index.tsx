import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import FlashSaleController from '@/actions/App/Http/Controllers/Admin/FlashSaleController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDateTime, formatRupiah } from '@/lib/utils';
import { create, edit, index } from '@/routes/admin/flash-sales';
import type { Paginated } from '@/types';

export type FlashSaleStatus = 'running' | 'scheduled' | 'ended' | 'inactive';

export type FlashSaleFormData = {
    id: number;
    name: string;
    starts_at: string;
    ends_at: string;
    is_active: boolean;
    status: FlashSaleStatus;
    status_label: string;
};

type FlashSaleRow = FlashSaleFormData & {
    items_count: number;
    sold_total: number;
    revenue: number;
};

export const statusBadge: Record<
    FlashSaleStatus,
    'default' | 'secondary' | 'outline'
> = {
    running: 'default',
    scheduled: 'secondary',
    ended: 'outline',
    inactive: 'outline',
};

export default function AdminFlashSalesIndex({
    flashSales,
}: {
    flashSales: Paginated<FlashSaleRow>;
}) {
    const { errors } = usePage().props as { errors: Record<string, string> };

    return (
        <>
            <Head title="Flash Sale" />

            <div className="space-y-4 p-4">
                <div className="flex flex-wrap items-start justify-between gap-2">
                    <Heading
                        title="Flash Sale"
                        description="Terjual mencakup pesanan yang masih menunggu pembayaran; omzet hanya dari pesanan yang sudah dibayar."
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus /> Flash sale baru
                        </Link>
                    </Button>
                </div>

                <InputError message={errors.flash_sale} />

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="p-3 font-medium">Nama</th>
                                <th className="p-3 font-medium">Jadwal</th>
                                <th className="p-3 font-medium">Status</th>
                                <th className="p-3 font-medium">Barang</th>
                                <th className="p-3 font-medium">Terjual</th>
                                <th className="p-3 font-medium">Omzet</th>
                                <th className="p-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {flashSales.data.map((sale) => (
                                <tr key={sale.id}>
                                    <td className="p-3">
                                        <Link
                                            href={edit(sale.id)}
                                            className="font-medium hover:underline"
                                        >
                                            {sale.name}
                                        </Link>
                                    </td>
                                    <td className="p-3 whitespace-nowrap">
                                        {formatDateTime(sale.starts_at)} –{' '}
                                        {formatDateTime(sale.ends_at)}
                                    </td>
                                    <td className="p-3">
                                        <Badge
                                            variant={statusBadge[sale.status]}
                                        >
                                            {sale.status_label}
                                        </Badge>
                                    </td>
                                    <td className="p-3 tabular-nums">
                                        {sale.items_count}
                                    </td>
                                    <td className="p-3 tabular-nums">
                                        {sale.sold_total}
                                    </td>
                                    <td className="p-3 tabular-nums">
                                        {formatRupiah(sale.revenue)}
                                    </td>
                                    <td className="p-3 text-right">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            aria-label={`Hapus ${sale.name}`}
                                            disabled={sale.sold_total > 0}
                                            onClick={() => {
                                                if (
                                                    window.confirm(
                                                        `Hapus flash sale ${sale.name}?`,
                                                    )
                                                ) {
                                                    router.delete(
                                                        FlashSaleController.destroy.url(
                                                            sale.id,
                                                        ),
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    );
                                                }
                                            }}
                                        >
                                            <Trash2 />
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                            {flashSales.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="p-6 text-center text-muted-foreground"
                                    >
                                        Belum ada flash sale. Buat satu untuk
                                        menampilkan hitung mundur di beranda.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination links={flashSales.links} />
            </div>
        </>
    );
}

AdminFlashSalesIndex.layout = {
    breadcrumbs: [{ title: 'Flash Sale', href: index() }],
};
