import { Form, Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import { productStatusLabels } from '@/components/admin/product-fields';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { formatRupiah } from '@/lib/utils';
import { create, edit, index } from '@/routes/admin/products';
import type { Paginated } from '@/types';

type Row = {
    id: number;
    name: string;
    slug: string;
    category: string;
    status: string;
    variants_count: number;
    total_stock: number;
    min_price: number;
};

export default function AdminProductsIndex({
    products,
    filters,
}: {
    products: Paginated<Row>;
    filters: { q: string; status: string };
}) {
    return (
        <>
            <Head title="Produk" />

            <div className="space-y-4 p-4">
                <div className="flex flex-wrap items-start justify-between gap-2">
                    <Heading
                        title="Produk"
                        description="Kelola katalog, varian, gambar, dan stok."
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus /> Produk baru
                        </Link>
                    </Button>
                </div>

                <Form
                    {...ProductController.index.form()}
                    className="flex flex-col gap-2 sm:flex-row"
                >
                    <Input
                        name="q"
                        defaultValue={filters.q}
                        placeholder="Nama produk atau SKU"
                        aria-label="Cari produk"
                    />
                    <NativeSelect
                        name="status"
                        defaultValue={filters.status}
                        aria-label="Filter status"
                        className="sm:w-44"
                    >
                        <option value="">Semua status</option>
                        {Object.entries(productStatusLabels).map(
                            ([value, label]) => (
                                <option key={value} value={value}>
                                    {label}
                                </option>
                            ),
                        )}
                    </NativeSelect>
                    <Button type="submit" variant="outline">
                        Terapkan
                    </Button>
                </Form>

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="p-3 font-medium">Produk</th>
                                <th className="p-3 font-medium">Kategori</th>
                                <th className="p-3 font-medium">Status</th>
                                <th className="p-3 text-right font-medium">
                                    Varian
                                </th>
                                <th className="p-3 text-right font-medium">
                                    Stok
                                </th>
                                <th className="p-3 text-right font-medium">
                                    Harga mulai
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {products.data.map((product) => (
                                <tr
                                    key={product.id}
                                    className="hover:bg-accent/50"
                                >
                                    <td className="p-3">
                                        <Link
                                            href={edit(product.id)}
                                            className="font-medium hover:underline"
                                        >
                                            {product.name}
                                        </Link>
                                    </td>
                                    <td className="p-3">{product.category}</td>
                                    <td className="p-3">
                                        <Badge
                                            variant={
                                                product.status === 'active'
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {
                                                productStatusLabels[
                                                    product.status
                                                ]
                                            }
                                        </Badge>
                                    </td>
                                    <td className="p-3 text-right">
                                        {product.variants_count}
                                    </td>
                                    <td className="p-3 text-right">
                                        {product.total_stock}
                                    </td>
                                    <td className="p-3 text-right">
                                        {formatRupiah(product.min_price)}
                                    </td>
                                </tr>
                            ))}
                            {products.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="p-6 text-center text-muted-foreground"
                                    >
                                        Tidak ada produk.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination links={products.links} />
            </div>
        </>
    );
}

AdminProductsIndex.layout = {
    breadcrumbs: [{ title: 'Produk', href: index() }],
};
