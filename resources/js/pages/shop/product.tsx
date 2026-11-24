import { Form, Head, Link, router } from '@inertiajs/react';
import { Minus, Plus, ShoppingCart, Zap } from 'lucide-react';
import { useState } from 'react';
import CartItemController from '@/actions/App/Http/Controllers/Shop/CartItemController';
import { Countdown, QuotaMeter } from '@/components/flash-sale';
import InputError from '@/components/input-error';
import { colorFromLabel } from '@/components/product-art';
import ProductCard, {
    formatWeight,
    ProductImage,
} from '@/components/product-card';
import ShareButton from '@/components/share-button';
import { Button } from '@/components/ui/button';
import { cn, formatDateTime, formatRupiah } from '@/lib/utils';
import { index as productsIndex } from '@/routes/products';
import type { ProductCard as ProductCardData } from '@/types';

type VariantFlashSale = {
    name: string;
    price: number;
    discount_percent: number;
    ends_at: string;
    quota: number | null;
    sold: number;
    remaining: number | null;
    per_user_limit: number | null;
};

type Variant = {
    id: number;
    sku: string;
    label: string | null;
    price: number;
    compare_at_price: number | null;
    stock: number;
    is_low_stock: boolean;
    flash_sale: VariantFlashSale | null;
};

type Product = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    weight_gram: number;
    category: { name: string; slug: string };
    images: { id: number; url: string }[];
    variants: Variant[];
    upcoming_flash_sale: {
        name: string;
        starts_at: string;
        price: number;
    } | null;
};

const reloadProduct = () => router.reload({ only: ['product'] });

function FlashSalePanel({
    offer,
    variant,
}: {
    offer: VariantFlashSale;
    variant: Variant;
}) {
    return (
        <div className="space-y-3 rounded-2xl bg-[#F3F9FB] p-4">
            <div className="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                <p className="flex items-center gap-2 font-bold text-[#007AB0]">
                    <Zap aria-hidden className="size-5 fill-current" />
                    {offer.name} · hemat {offer.discount_percent}%
                </p>
                <p className="flex items-center gap-2 text-sm font-medium text-[#666]">
                    Berakhir dalam
                    <Countdown
                        target={offer.ends_at}
                        label="Flash sale berakhir dalam"
                        onElapsed={reloadProduct}
                    />
                </p>
            </div>
            {offer.quota !== null && (
                <QuotaMeter
                    quota={offer.quota}
                    sold={offer.sold}
                    remaining={offer.remaining}
                    stock={variant.stock}
                    saving={variant.price - offer.price}
                />
            )}
            {offer.per_user_limit !== null && (
                <p className="text-sm text-[#666]">
                    Harga flash sale berlaku untuk maks. {offer.per_user_limit}{' '}
                    barang per pelanggan.
                </p>
            )}
        </div>
    );
}

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
    const offer = variant.flash_sale;
    const maxQuantity = Math.min(variant.stock, maxPerItem);
    const hasOptions = product.variants.length > 1 || variant.label !== null;

    return (
        <>
            <Head title={product.name}>
                <meta
                    name="description"
                    head-key="description"
                    content={
                        product.description ??
                        `${product.name}, ${formatRupiah(variant.price)}.`
                    }
                />
            </Head>

            <div className="grid gap-10 md:grid-cols-[1.15fr_1fr] lg:gap-16">
                <div className="space-y-3 md:sticky md:top-24 md:self-start">
                    <ProductImage
                        src={product.images[imageIndex]?.url ?? null}
                        alt={product.name}
                        color={colorFromLabel(variant.label)}
                        className="aspect-square rounded-3xl"
                    />
                    {product.images.length > 1 && (
                        <div className="flex gap-2 overflow-x-auto">
                            {product.images.map((image, index) => (
                                <button
                                    key={image.id}
                                    type="button"
                                    onClick={() => setImageIndex(index)}
                                    aria-label={`Gambar ${index + 1}`}
                                    aria-pressed={index === imageIndex}
                                    className={cn(
                                        'size-20 shrink-0 overflow-hidden',
                                        index === imageIndex
                                            ? 'outline-3 outline-foreground'
                                            : 'opacity-70 hover:opacity-100',
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

                <div className="space-y-8">
                    <div className="space-y-4">
                        <nav
                            aria-label="Breadcrumb"
                            className="text-small text-soft"
                        >
                            <Link
                                href={productsIndex()}
                                className="hover:underline"
                            >
                                Semua barang
                            </Link>
                            {' / '}
                            <Link
                                href={productsIndex({
                                    query: { category: product.category.slug },
                                })}
                                className="hover:underline"
                            >
                                {product.category.name}
                            </Link>
                        </nav>
                        <h1 className="text-head font-display">
                            {product.name}
                        </h1>
                        <p className="flex flex-wrap items-baseline gap-x-4 gap-y-1">
                            <span
                                className={cn(
                                    'text-lead font-semibold tabular-nums',
                                    offer && 'text-[#007AB0]',
                                )}
                            >
                                {formatRupiah(
                                    offer ? offer.price : variant.price,
                                )}
                            </span>
                            {offer ? (
                                <span className="text-body text-soft line-through">
                                    {formatRupiah(variant.price)}
                                </span>
                            ) : (
                                variant.compare_at_price && (
                                    <span className="text-body text-soft line-through">
                                        {formatRupiah(variant.compare_at_price)}
                                    </span>
                                )
                            )}
                            <span className="text-body text-soft">
                                {formatWeight(product.weight_gram)} · SKU{' '}
                                {variant.sku}
                            </span>
                        </p>
                    </div>

                    {offer ? (
                        <FlashSalePanel offer={offer} variant={variant} />
                    ) : (
                        product.upcoming_flash_sale && (
                            <div className="flex flex-wrap items-center gap-x-3 gap-y-2 rounded-2xl bg-[#F3F9FB] p-4 text-sm text-[#222]">
                                <Zap
                                    aria-hidden
                                    className="size-5 text-[#008ECC]"
                                />
                                <p>
                                    <span className="font-semibold">
                                        {product.upcoming_flash_sale.name}
                                    </span>{' '}
                                    mulai{' '}
                                    {formatDateTime(
                                        product.upcoming_flash_sale.starts_at,
                                    )}
                                    : harga mulai{' '}
                                    <span className="font-semibold tabular-nums">
                                        {formatRupiah(
                                            product.upcoming_flash_sale.price,
                                        )}
                                    </span>
                                    .
                                </p>
                                <Countdown
                                    target={
                                        product.upcoming_flash_sale.starts_at
                                    }
                                    label="Flash sale mulai dalam"
                                    tone="brand"
                                    onElapsed={reloadProduct}
                                />
                            </div>
                        )
                    )}

                    {hasOptions && (
                        <fieldset className="space-y-3">
                            <legend className="text-body font-semibold">
                                Varian
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
                                            'h-11 min-w-14 px-4 text-body transition-colors',
                                            item.id === variantId
                                                ? 'bg-primary text-primary-foreground'
                                                : 'bg-surface hover:bg-accent',
                                            item.stock === 0 &&
                                                'text-soft line-through',
                                        )}
                                    >
                                        {item.label ?? 'Standar'}
                                        {item.stock === 0 && (
                                            <span className="sr-only">
                                                {' '}
                                                (habis)
                                            </span>
                                        )}
                                    </button>
                                ))}
                            </div>
                        </fieldset>
                    )}

                    <p className="text-body" aria-live="polite">
                        {variant.stock === 0 ? (
                            <span className="font-semibold">
                                Varian ini habis.
                            </span>
                        ) : variant.is_low_stock ? (
                            <span className="font-semibold text-warning">
                                Sisa sedikit: tinggal {variant.stock}.
                            </span>
                        ) : (
                            <span className="text-soft">
                                Stok tersedia: {variant.stock}. Maksimal{' '}
                                {maxPerItem} per pesanan.
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

                                <div className="flex flex-wrap items-stretch gap-3">
                                    <div className="flex items-center rounded-full bg-surface">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            className="h-13 w-12"
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
                                            className="w-10 text-center text-lead tabular-nums"
                                            aria-live="polite"
                                            aria-label={`Jumlah ${quantity}`}
                                        >
                                            {quantity}
                                        </span>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            className="h-13 w-12"
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
                                        size="xl"
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

                    <ShareButton
                        title={product.name}
                        text={`${product.name} — ${formatRupiah(offer ? offer.price : variant.price)}`}
                    />

                    {product.description && (
                        <section
                            aria-labelledby="description-title"
                            className="space-y-2"
                        >
                            <h2
                                id="description-title"
                                className="text-body font-semibold"
                            >
                                Tentang barang ini
                            </h2>
                            <p className="max-w-[60ch] text-body whitespace-pre-line">
                                {product.description}
                            </p>
                        </section>
                    )}
                </div>
            </div>

            {related.length > 0 && (
                <section
                    aria-labelledby="related-title"
                    className="mt-24 space-y-6"
                >
                    <h2 id="related-title" className="text-head font-display">
                        Barang serupa
                    </h2>
                    <div className="grid grid-cols-2 gap-x-4 gap-y-8 lg:grid-cols-4">
                        {related.map((item) => (
                            <ProductCard key={item.id} product={item} />
                        ))}
                    </div>
                </section>
            )}
        </>
    );
}
