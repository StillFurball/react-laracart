import { Link } from '@inertiajs/react';
import { Zap } from 'lucide-react';
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
    const offer = product.flash_sale;
    // The offer leads only when it is the cheapest way to buy the product.
    const offerLeads = offer !== null && offer.price < product.min_price;

    return (
        <Link
            href={show(product.slug)}
            prefetch
            className="group flex flex-col gap-3"
        >
            <div className="relative">
                <ProductImage
                    src={product.image}
                    alt={product.name}
                    className={cn(
                        'aspect-square transition-opacity group-hover:opacity-80',
                        soldOut && 'opacity-50',
                    )}
                />
                {offer && (
                    <span className="absolute top-2 left-2 inline-flex items-center gap-1 rounded-full bg-[#008ECC] px-2.5 py-1 text-xs font-semibold text-white">
                        <Zap aria-hidden className="size-3.5 fill-current" />
                        Flash sale −{offer.discount_percent}%
                    </span>
                )}
            </div>
            <div className="flex flex-1 flex-col gap-0.5 px-1">
                <p className="text-small text-soft">
                    {product.category}
                    {colors > 1 && ` · ${colors} warna`}
                </p>
                <h3 className="text-body font-semibold group-hover:underline">
                    {product.name}
                </h3>
                <p className="flex flex-wrap items-baseline gap-x-2 text-body">
                    <span
                        className={cn(
                            'tabular-nums',
                            offerLeads && 'font-semibold text-[#007AB0]',
                        )}
                    >
                        {formatRupiah(
                            offerLeads ? offer.price : product.min_price,
                        )}
                    </span>
                    {offerLeads && (
                        <s className="text-small text-soft tabular-nums">
                            {formatRupiah(offer.regular_price)}
                        </s>
                    )}
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
