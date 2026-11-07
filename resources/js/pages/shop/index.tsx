import { Head, Link, router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { useState } from 'react';
import Pagination from '@/components/pagination';
import ProductCard from '@/components/product-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
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

function CategoryLinks({
    nodes,
    active,
    onSelect,
    depth = 0,
}: {
    nodes: CategoryNode[];
    active: string;
    onSelect: (slug: string) => void;
    depth?: number;
}) {
    return (
        <ul className={cn('space-y-0.5', depth > 0 && 'ml-3 border-l pl-2')}>
            {nodes.map((node) => (
                <li key={node.id}>
                    <button
                        type="button"
                        onClick={() => onSelect(node.slug)}
                        aria-current={active === node.slug ? 'true' : undefined}
                        className={cn(
                            'w-full rounded px-2 py-1 text-left text-sm hover:bg-accent',
                            active === node.slug && 'bg-accent font-medium',
                        )}
                    >
                        {node.name}
                    </button>
                    {node.children && node.children.length > 0 && (
                        <CategoryLinks
                            nodes={node.children}
                            active={active}
                            onSelect={onSelect}
                            depth={depth + 1}
                        />
                    )}
                </li>
            ))}
        </ul>
    );
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

    const apply = (changes: Partial<Filters>) => {
        const next = { ...filters, ...changes };

        router.get(
            home({
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

    return (
        <>
            <Head title="Katalog" />

            <div className="flex flex-col gap-6 md:flex-row">
                <aside className="md:w-56 md:shrink-0">
                    <h2 className="mb-2 text-sm font-semibold">Kategori</h2>
                    <button
                        type="button"
                        onClick={() => apply({ category: '' })}
                        className={cn(
                            'mb-1 w-full rounded px-2 py-1 text-left text-sm hover:bg-accent',
                            !filters.category && 'bg-accent font-medium',
                        )}
                    >
                        Semua produk
                    </button>
                    <CategoryLinks
                        nodes={categories}
                        active={filters.category}
                        onSelect={(slug) => apply({ category: slug })}
                    />
                </aside>

                <section className="flex-1">
                    <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                        <form
                            className="relative flex-1"
                            role="search"
                            onSubmit={(event) => {
                                event.preventDefault();
                                apply({ q: search.trim() });
                            }}
                        >
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                type="search"
                                aria-label="Cari produk atau SKU"
                                placeholder="Cari produk atau SKU…"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                className="pl-9"
                            />
                        </form>
                        <div className="flex items-center gap-2">
                            <label
                                htmlFor="sort"
                                className="text-sm whitespace-nowrap text-muted-foreground"
                            >
                                Urutkan
                            </label>
                            <NativeSelect
                                id="sort"
                                value={filters.sort}
                                onChange={(event) =>
                                    apply({ sort: event.target.value })
                                }
                                className="w-44"
                            >
                                {sortOptions.map((option) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ))}
                            </NativeSelect>
                        </div>
                    </div>

                    {(filters.q || activeCategory) && (
                        <div className="mb-4 flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                            <span>{products.total} produk</span>
                            {activeCategory && (
                                <span>· {activeCategory.name}</span>
                            )}
                            {filters.q && <span>· “{filters.q}”</span>}
                            <Button variant="ghost" size="sm" asChild>
                                <Link
                                    href={home()}
                                    onClick={() => setSearch('')}
                                >
                                    <X /> Reset
                                </Link>
                            </Button>
                        </div>
                    )}

                    {products.data.length === 0 ? (
                        <div className="rounded-lg border border-dashed p-10 text-center text-muted-foreground">
                            Tidak ada produk yang cocok.
                        </div>
                    ) : (
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
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
