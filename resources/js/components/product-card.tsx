import { Link } from '@inertiajs/react';
import ProductArt from '@/components/product-art';
import { cn, formatRupiah } from '@/lib/utils';
import { show } from '@/routes/products';
import type { ProductCard as ProductCardData } from '@/types';

/** The product's photograph, or its device glyph when it has none yet. */
export function ProductImage({
    src,
    alt,
    color,
    className = 'aspect-square',
}: {
    src: string | null;
    alt: string;
    color?: string | null;
    className?: string;
}) {
    return src ? (
        <img
            src={src}
            alt={alt}
            loading="lazy"
            className={cn(
                'w-full rounded-2xl bg-surface object-cover',
                className,
            )}
        />
    ) : (
        <ProductArt
            name={alt}
            color={color}
            className={cn('w-full', className)}
        />
    );
}

export function formatWeight(grams: number): string {
    return grams >= 1000
        ? `${(grams / 1000).toLocaleString('id-ID', { maximumFractionDigits: 2 })} kg`
        : `${grams} g`;
}

export default function ProductCard({ product }: { product: ProductCardData }) {
    const soldOut = product.total_stock === 0;
    const colors = product.colors.length;

    return (
        <Link
            href={show(product.slug)}
            prefetch
            className="group flex flex-col gap-3"
        >
            <ProductImage
                src={product.image}
                alt={product.name}
                className={cn(
                    'aspect-square transition-opacity group-hover:opacity-80',
                    soldOut && 'opacity-50',
                )}
            />
            <div className="flex flex-1 flex-col gap-0.5 px-1">
                <p className="text-small text-soft">
                    {product.category}
                    {colors > 1 && ` · ${colors} warna`}
                </p>
                <h3 className="text-body font-semibold group-hover:underline">
                    {product.name}
                </h3>
                <p className="flex flex-wrap items-baseline gap-x-2 text-body">
                    <span className="tabular-nums">
                        {formatRupiah(product.min_price)}
                    </span>
                    {soldOut ? (
                        <span className="text-small text-soft">Habis</span>
                    ) : (
                        product.has_low_stock && (
                            <span className="text-small font-medium text-warning">
                                Sisa sedikit
                            </span>
                        )
                    )}
                </p>
            </div>
        </Link>
    );
}
