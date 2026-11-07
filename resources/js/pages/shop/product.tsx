import { Form, Head, Link } from '@inertiajs/react';
import { Minus, Plus, ShoppingCart } from 'lucide-react';
import { useState } from 'react';
import CartItemController from '@/actions/App/Http/Controllers/Shop/CartItemController';
import InputError from '@/components/input-error';
import ProductCard, { ProductImage } from '@/components/product-card';
import { Button } from '@/components/ui/button';
import { cn, formatRupiah } from '@/lib/utils';
import { home } from '@/routes';
import type { ProductCard as ProductCardData } from '@/types';

type Variant = {
    id: number;
    sku: string;
    label: string | null;
    price: number;
    compare_at_price: number | null;
    stock: number;
    is_low_stock: boolean;
};

type Product = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    category: { name: string; slug: string };
    images: { id: number; url: string }[];
    variants: Variant[];
};

export default function ProductShow({
    product,
    related,
    maxPerItem,
}: {
    product: Product;
    related: ProductCardData[];
    maxPerItem: number;
}) {
    const firstAvailable =
        product.variants.find((variant) => variant.stock > 0) ??
        product.variants[0];
    const [variantId, setVariantId] = useState(firstAvailable.id);
    const [quantity, setQuantity] = useState(1);
    const [imageIndex, setImageIndex] = useState(0);

    const variant =
        product.variants.find((item) => item.id === variantId) ??
        firstAvailable;
    const maxQuantity = Math.min(variant.stock, maxPerItem);
    const hasOptions = product.variants.length > 1 || variant.label !== null;

    return (
        <>
            <Head title={product.name} />

            <nav
                aria-label="Breadcrumb"
                className="mb-4 text-sm text-muted-foreground"
            >
                <Link href={home()} className="hover:underline">
                    Katalog
                </Link>
                {' / '}
                <Link
                    href={home({ query: { category: product.category.slug } })}
                    className="hover:underline"
                >
                    {product.category.name}
                </Link>
            </nav>

            <div className="grid gap-8 md:grid-cols-2">
                <div className="space-y-3">
                    <ProductImage
                        src={product.images[imageIndex]?.url ?? null}
                        alt={product.name}
                    />
                    {product.images.length > 1 && (
                        <div className="flex gap-2 overflow-x-auto">
                            {product.images.map((image, index) => (
                                <button
                                    key={image.id}
                                    type="button"
                                    onClick={() => setImageIndex(index)}
                                    aria-label={`Gambar ${index + 1}`}
                                    className={cn(
                                        'size-16 shrink-0 overflow-hidden rounded-md border-2',
                                        index === imageIndex
                                            ? 'border-primary'
                                            : 'border-transparent',
                                    )}
                                >
                                    <img
                                        src={image.url}
                                        alt=""
                                        className="size-full object-cover"
                                    />
                                </button>
                            ))}
                        </div>
                    )}
                </div>

                <div className="space-y-5">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {product.name}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            SKU {variant.sku}
                        </p>
                    </div>

                    <div className="flex items-baseline gap-3">
                        <span className="text-2xl font-bold">
                            {formatRupiah(variant.price)}
                        </span>
                        {variant.compare_at_price && (
                            <span className="text-muted-foreground line-through">
                                {formatRupiah(variant.compare_at_price)}
                            </span>
                        )}
                    </div>

                    {hasOptions && (
                        <fieldset>
                            <legend className="mb-2 text-sm font-medium">
                                Pilih varian
                            </legend>
                            <div className="flex flex-wrap gap-2">
                                {product.variants.map((item) => (
                                    <button
                                        key={item.id}
                                        type="button"
                                        onClick={() => {
                                            setVariantId(item.id);
                                            setQuantity(1);
                                        }}
                                        aria-pressed={item.id === variantId}
                                        className={cn(
                                            'rounded-md border px-3 py-1.5 text-sm',
                                            item.id === variantId
                                                ? 'border-primary bg-primary text-primary-foreground'
                                                : 'hover:bg-accent',
                                            item.stock === 0 &&
                                                'line-through opacity-50',
                                        )}
                                    >
                                        {item.label ?? 'Standar'}
                                    </button>
                                ))}
                            </div>
                        </fieldset>
                    )}

                    <p className="text-sm">
                        {variant.stock === 0 ? (
                            <span className="font-medium text-red-600">
                                Habis
                            </span>
                        ) : variant.is_low_stock ? (
                            <span className="font-medium text-amber-600">
                                Sisa sedikit — tinggal {variant.stock}
                            </span>
                        ) : (
                            <span className="text-muted-foreground">
                                Stok tersedia: {variant.stock}
                            </span>
                        )}
                    </p>

                    <Form
                        {...CartItemController.store.form()}
                        options={{ preserveScroll: true }}
                        className="space-y-3"
                    >
                        {({ processing, errors }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="product_variant_id"
                                    value={variant.id}
                                />
                                <input
                                    type="hidden"
                                    name="quantity"
                                    value={quantity}
                                />

                                <div className="flex items-center gap-3">
                                    <div className="flex items-center rounded-md border">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            aria-label="Kurangi jumlah"
                                            disabled={quantity <= 1}
                                            onClick={() =>
                                                setQuantity(
                                                    (value) => value - 1,
                                                )
                                            }
                                        >
                                            <Minus />
                                        </Button>
                                        <span
                                            className="w-10 text-center"
                                            aria-live="polite"
                                        >
                                            {quantity}
                                        </span>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            aria-label="Tambah jumlah"
                                            disabled={quantity >= maxQuantity}
                                            onClick={() =>
                                                setQuantity(
                                                    (value) => value + 1,
                                                )
                                            }
                                        >
                                            <Plus />
                                        </Button>
                                    </div>

                                    <Button
                                        type="submit"
                                        className="flex-1"
                                        disabled={
                                            processing || variant.stock === 0
                                        }
                                    >
                                        <ShoppingCart />
                                        {variant.stock === 0
                                            ? 'Stok habis'
                                            : 'Tambah ke keranjang'}
                                    </Button>
                                </div>
                                <InputError
                                    message={
                                        errors.quantity ??
                                        errors.product_variant_id
                                    }
                                />
                            </>
                        )}
                    </Form>

                    {product.description && (
                        <div>
                            <h2 className="mb-1 text-sm font-semibold">
                                Deskripsi
                            </h2>
                            <p className="text-sm leading-relaxed whitespace-pre-line text-muted-foreground">
                                {product.description}
                            </p>
                        </div>
                    )}
                </div>
            </div>

            {related.length > 0 && (
                <section className="mt-12">
                    <h2 className="mb-4 text-lg font-semibold">
                        Produk terkait
                    </h2>
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        {related.map((item) => (
                            <ProductCard key={item.id} product={item} />
                        ))}
                    </div>
                </section>
            )}
        </>
    );
}
