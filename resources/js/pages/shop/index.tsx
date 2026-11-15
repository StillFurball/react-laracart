import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import CategoryTree from '@/components/category-tree';
import Pagination from '@/components/pagination';
import ProductCard from '@/components/product-card';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { cn } from '@/lib/utils';
import { index as productsIndex } from '@/routes/products';
import type {
    CategoryNode,
    Paginated,
    ProductCard as ProductCardData,
} from '@/types';

type Filters = { q: string; category: string; sort: string };

const sortOptions = [
    { value: 'newest', label: 'Terbaru' },
    { value: 'bestselling', label: 'Terlaris' },
    { value: 'price_asc', label: 'Harga terendah' },
    { value: 'price_desc', label: 'Harga tertinggi' },
];

function flatten(
    nodes: CategoryNode[],
    depth = 0,
): { node: CategoryNode; depth: number }[] {
    return nodes.flatMap((node) => [
        { node, depth },
        ...flatten(node.children ?? [], depth + 1),
    ]);
}

export default function Catalog({
    products,
    categories,
    filters,
    activeCategory,
}: {
    products: Paginated<ProductCardData>;
    categories: CategoryNode[];
    filters: Filters;
    activeCategory: { name: string; slug: string } | null;
}) {
    const [search, setSearch] = useState(filters.q);
    const tree = flatten(categories);

    const apply = (changes: Partial<Filters>) => {
        const next = { ...filters, ...changes };

        router.get(
            productsIndex({
                query: {
                    q: next.q || undefined,
                    category: next.category || undefined,
                    sort: next.sort !== 'newest' ? next.sort : undefined,
                },
            }).url,
            {},
            { preserveState: true, preserveScroll: true },
        );
    };

    const title = filters.q
        ? `“${filters.q}”`
        : (activeCategory?.name ?? 'Semua barang');

    return (
        <>
            <Head title={activeCategory?.name ?? 'Semua barang'} />

            <div className="grid gap-x-10 gap-y-8 md:grid-cols-[14rem_1fr]">
                <div className="space-y-3 md:col-span-2">
                    <h1 className="text-head font-display break-words">
                        {title}
                    </h1>
                    <p className="text-body">
                        {products.total} barang
                        {activeCategory && filters.q
                            ? ` di ${activeCategory.name}`
                            : ''}
                        . Harga per barang; ongkir dihitung dari total berat
                        saat checkout.
                    </p>
                </div>

                <aside
                    aria-labelledby="category-title"
                    className="hidden md:block"
                >
                    <h2
                        id="category-title"
                        className="mb-3 text-body font-semibold"
                    >
                        Kategori
                    </h2>
                    <button
                        type="button"
                        onClick={() => apply({ category: '' })}
                        aria-current={!filters.category ? 'true' : undefined}
                        className={cn(
                            'w-full px-3 py-1.5 text-left text-body transition-colors hover:bg-accent',
                            !filters.category &&
                                'bg-primary text-primary-foreground hover:bg-primary',
                        )}
                    >
                        Semua barang
                    </button>
                    <CategoryTree
                        categories={categories}
                        activeSlug={filters.category}
                        onSelect={(category) => apply({ category })}
                    />
                </aside>

                <section
                    aria-label="Daftar barang"
                    className="min-w-0 space-y-6"
                >
                    <div className="grid gap-3 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                        <form
                            className="relative"
                            role="search"
                            onSubmit={(event) => {
                                event.preventDefault();
                                apply({ q: search.trim() });
                            }}
                        >
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-soft" />
                            <Input
                                type="search"
                                aria-label="Cari barang atau SKU"
                                placeholder="Cari barang atau SKU"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                className="h-12 pl-10 text-body"
                            />
                        </form>
                        <NativeSelect
                            aria-label="Kategori"
                            value={filters.category}
                            onChange={(event) =>
                                apply({ category: event.target.value })
                            }
                            className="h-12 md:hidden"
                        >
                            <option value="">Semua kategori</option>
                            {tree.map(({ node, depth }) => (
                                <option key={node.id} value={node.slug}>
                                    {'  '.repeat(depth)}
                                    {node.name}
                                </option>
                            ))}
                        </NativeSelect>
                        <NativeSelect
                            aria-label="Urutkan"
                            value={filters.sort}
                            onChange={(event) =>
                                apply({ sort: event.target.value })
                            }
                            className="h-12 sm:w-48"
                        >
                            {sortOptions.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </NativeSelect>
                    </div>

                    {products.data.length === 0 ? (
                        <div className="bg-surface px-6 py-16">
                            <h2 className="text-lead font-semibold">
                                Belum ada barang yang cocok
                            </h2>
                            <p className="mt-2 max-w-[48ch] text-body">
                                Coba kata lain, atau cari dengan SKU varian.
                                Semua barang juga bisa dilihat sekaligus.
                            </p>
                            <Link
                                href={productsIndex()}
                                onClick={() => setSearch('')}
                                className="mt-6 inline-flex h-11 items-center bg-primary px-5 font-medium text-primary-foreground"
                            >
                                Lihat semua barang
                            </Link>
                        </div>
                    ) : (
                        <div className="grid grid-cols-2 gap-x-4 gap-y-8 lg:grid-cols-3">
                            {products.data.map((product) => (
                                <ProductCard
                                    key={product.id}
                                    product={product}
                                />
                            ))}
                        </div>
                    )}

                    <Pagination links={products.links} />
                </section>
            </div>
        </>
    );
}
