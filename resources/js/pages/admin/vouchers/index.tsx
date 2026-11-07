import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import VoucherController from '@/actions/App/Http/Controllers/Admin/VoucherController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDateTime, formatRupiah } from '@/lib/utils';
import { create, edit, index } from '@/routes/admin/vouchers';
import type { Paginated } from '@/types';

export type VoucherData = {
    id: number;
    code: string;
    type: 'percent' | 'fixed';
    value: number;
    max_discount: number | null;
    min_purchase: number;
    quota: number | null;
    per_user_limit: number | null;
    starts_at: string | null;
    ends_at: string | null;
    is_active: boolean;
};

export default function AdminVouchersIndex({
    vouchers,
}: {
    vouchers: Paginated<VoucherData & { used_count: number }>;
}) {
    const { errors } = usePage().props as { errors: Record<string, string> };

    return (
        <>
            <Head title="Voucher" />

            <div className="space-y-4 p-4">
                <div className="flex flex-wrap items-start justify-between gap-2">
                    <Heading
                        title="Voucher"
                        description="Pemakaian dihitung dari pesanan yang tidak kedaluwarsa/dibatalkan."
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus /> Voucher baru
                        </Link>
                    </Button>
                </div>

                <InputError message={errors.voucher} />

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="p-3 font-medium">Kode</th>
                                <th className="p-3 font-medium">Potongan</th>
                                <th className="p-3 font-medium">
                                    Min. belanja
                                </th>
                                <th className="p-3 font-medium">Pemakaian</th>
                                <th className="p-3 font-medium">Berakhir</th>
                                <th className="p-3 font-medium">Status</th>
                                <th className="p-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {vouchers.data.map((voucher) => (
                                <tr key={voucher.id}>
                                    <td className="p-3">
                                        <Link
                                            href={edit(voucher.id)}
                                            className="font-mono font-medium hover:underline"
                                        >
                                            {voucher.code}
                                        </Link>
                                    </td>
                                    <td className="p-3">
                                        {voucher.type === 'percent'
                                            ? `${voucher.value}%`
                                            : formatRupiah(voucher.value)}
                                        {voucher.max_discount && (
                                            <span className="text-muted-foreground">
                                                {' '}
                                                (maks{' '}
                                                {formatRupiah(
                                                    voucher.max_discount,
                                                )}
                                                )
                                            </span>
                                        )}
                                    </td>
                                    <td className="p-3">
                                        {formatRupiah(voucher.min_purchase)}
                                    </td>
                                    <td className="p-3">
                                        {voucher.used_count}
                                        {voucher.quota !== null &&
                                            ` / ${voucher.quota}`}
                                    </td>
                                    <td className="p-3 whitespace-nowrap">
                                        {voucher.ends_at
                                            ? formatDateTime(voucher.ends_at)
                                            : '—'}
                                    </td>
                                    <td className="p-3">
                                        <Badge
                                            variant={
                                                voucher.is_active
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {voucher.is_active
                                                ? 'Aktif'
                                                : 'Nonaktif'}
                                        </Badge>
                                    </td>
                                    <td className="p-3 text-right">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            aria-label={`Hapus ${voucher.code}`}
                                            disabled={voucher.used_count > 0}
                                            onClick={() => {
                                                if (
                                                    window.confirm(
                                                        `Hapus voucher ${voucher.code}?`,
                                                    )
                                                ) {
                                                    router.delete(
                                                        VoucherController.destroy.url(
                                                            voucher.id,
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
                            {vouchers.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="p-6 text-center text-muted-foreground"
                                    >
                                        Belum ada voucher.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination links={vouchers.links} />
            </div>
        </>
    );
}

AdminVouchersIndex.layout = {
    breadcrumbs: [{ title: 'Voucher', href: index() }],
};
