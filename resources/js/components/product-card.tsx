import { Link } from '@inertiajs/react';
import { ImageOff } from 'lucide-react';
import { formatRupiah } from '@/lib/utils';
import { show } from '@/routes/products';
import type { ProductCard as ProductCardData } from '@/types';

export function ProductImage({
    src,
    alt,
    className = 'aspect-square',
}: {
    src: string | null;
    alt: string;
    className?: string;
}) {
    return src ? (
        <img
            src={src}
            alt={alt}
            loading="lazy"
            className={`${className} w-full rounded-md object-cover`}
        />
    ) : (
        <div
            className={`${className} flex w-full items-center justify-center rounded-md bg-muted text-muted-foreground`}
            aria-hidden
        >
            <ImageOff className="size-8" />
        </div>
    );
}

export default function ProductCard({ product }: { product: ProductCardData }) {
    const soldOut = product.total_stock === 0;

    return (
        <Link
            href={show(product.slug)}
            prefetch
            className="group flex flex-col gap-2 rounded-lg border p-3 transition-shadow hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        >
            <div className="relative">
                <ProductImage src={product.image} alt={product.name} />
                {soldOut && (
                    <span className="absolute top-2 left-2 rounded bg-neutral-900/80 px-2 py-0.5 text-xs font-medium text-white">
                        Habis
                    </span>
                )}
                {!soldOut && product.has_low_stock && (
                    <span className="absolute top-2 left-2 rounded bg-amber-500 px-2 py-0.5 text-xs font-medium text-white">
                        Sisa sedikit
                    </span>
                )}
            </div>
            <p className="text-xs text-muted-foreground">{product.category}</p>
            <p className="line-clamp-2 text-sm font-medium group-hover:underline">
                {product.name}
            </p>
            <p className="mt-auto font-semibold">
                {formatRupiah(product.min_price)}
            </p>
        </Link>
    );
}
