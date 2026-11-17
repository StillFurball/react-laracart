import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import ProductArt from '@/components/product-art';
import { ProductImage } from '@/components/product-card';
import { cn, formatRupiah } from '@/lib/utils';
import { index as productsIndex, show } from '@/routes/products';
import type { ProductCard as ProductCardData } from '@/types';

type LandingProduct = ProductCardData & {
    department: string;
    compare_at_price: number | null;
};

type Department = {
    name: string;
    slug: string;
    products: LandingProduct[];
    minPrice: number;
};

const description =
    'Ponsel, laptop, audio dan aksesori dengan harga yang jelas. Ongkir dihitung per kilo.';

/** Hover state the design shows on its highlighted card. */
const cardHover =
    'border border-transparent transition-[border-color,box-shadow] hover:border-[#008ECC] hover:shadow-[0_6px_13px_rgba(0,0,0,0.05),0_24px_24px_rgba(0,0,0,0.04)]';

const brandStyles = [
    { card: 'bg-[#313131] text-white', chip: 'bg-[#494949]', rings: 'dark' },
    { card: 'bg-[#FFF3CC] text-[#222]', chip: 'bg-[#F6DE8D]', rings: 'yellow' },
    { card: 'bg-[#FFECDF] text-[#222]', chip: 'bg-[#FFD1B0]', rings: 'peach' },
];

function discountPercent(product: LandingProduct): number {
    return product.compare_at_price
        ? Math.round((1 - product.min_price / product.compare_at_price) * 100)
        : 0;
}

function SectionHeader({
    id,
    lead,
    accent,
}: {
    id: string;
    lead: string;
    accent: string;
}) {
    return (
        <div className="mb-10 flex items-end justify-between gap-4 border-b border-[#EDEDED]">
            <h2
                id={id}
                className="-mb-px border-b-[3px] border-[#008ECC] pb-4 text-xl leading-[30px] font-bold text-[#666] md:text-2xl"
            >
                {lead} <span className="text-[#008ECC]">{accent}</span>
            </h2>
            <Link
                href={productsIndex()}
                className="flex shrink-0 items-center gap-1.5 pb-4 text-base leading-[18px] font-medium text-[#222] hover:text-[#008ECC]"
            >
                Lihat semua
                <ChevronRight
                    aria-hidden
                    className="size-[18px] text-[#008ECC]"
                />
            </Link>
        </div>
    );
}

function Section({ id, children }: { id: string; children: ReactNode }) {
    return (
        <section aria-labelledby={id} className="mt-16 md:mt-[120px]">
            {children}
        </section>
    );
}

function HeroSlider({ departments }: { departments: Department[] }) {
    const [current, setCurrent] = useState(0);
    const slide = departments[current];
    const go = (step: number) =>
        setCurrent((current + step + departments.length) % departments.length);
    const arrow =
        'absolute top-1/2 z-10 grid size-10 -translate-y-1/2 place-items-center rounded-full border-4 border-white bg-[#F3F9FB] text-[#008ECC] transition-colors hover:bg-white md:size-[86px] md:border-[6px]';

    return (
        <section
            aria-roledescription="carousel"
            aria-label="Penawaran unggulan"
            className="relative"
        >
            <div className="relative h-[220px] overflow-hidden rounded-2xl bg-[#212844] text-white md:h-[316px]">
                <img
                    src="/images/landing/hero-rings.svg"
                    alt=""
                    width={516}
                    height={754}
                    className="pointer-events-none absolute top-[-339px] right-[-31px] max-w-none max-md:right-[-260px]"
                />

                <Link
                    href={productsIndex({ query: { category: slide.slug } })}
                    aria-roledescription="slide"
                    aria-label={`${current + 1} dari ${departments.length}: ${slide.name}`}
                    className="relative flex h-full items-center"
                >
                    <div className="z-10 px-12 md:px-[90px]">
                        <p className="text-base leading-tight font-semibold md:text-[30px] md:leading-[30px]">
                            Penawaran terbaik {slide.name.toLowerCase()}
                        </p>
                        <p className="mt-3 text-[32px] leading-none font-bold uppercase md:mt-5 md:text-[63px] md:leading-[63px]">
                            {slide.name}.
                        </p>
                        <p className="mt-3 text-base leading-tight font-semibold md:mt-5 md:text-[30px] md:leading-[30px]">
                            Mulai {formatRupiah(slide.minPrice)}
                        </p>
                    </div>
                    {/* The design's photo is a watch; other departments get their device glyph. */}
                    {/\b(jam|watch)\b/i.test(slide.name) ? (
                        <img
                            src="/images/landing/hero-watch.png"
                            alt=""
                            width={268}
                            height={270}
                            className="absolute right-[-40px] size-[150px] object-cover md:right-[125px] md:h-[270px] md:w-[268px]"
                        />
                    ) : (
                        <ProductArt
                            name={slide.name}
                            className="absolute right-[-20px] size-[150px] bg-transparent text-white/90 md:right-[125px] md:size-[268px]"
                        />
                    )}
                </Link>

                {departments.length > 1 && (
                    <div className="absolute bottom-5 left-12 flex items-center gap-1.5 md:bottom-10 md:left-[90px]">
                        {departments.map((department, index) => (
                            <button
                                key={department.slug}
                                type="button"
                                aria-label={`Tampilkan ${department.name}`}
                                aria-current={index === current}
                                onClick={() => setCurrent(index)}
                                className={cn(
                                    'h-2 rounded-full transition-[width,background-color]',
                                    index === current
                                        ? 'w-[22px] bg-white'
                                        : 'w-2 bg-white/50 hover:bg-white/80',
                                )}
                            />
                        ))}
                    </div>
                )}
            </div>

            {departments.length > 1 && (
                <>
                    <button
                        type="button"
                        aria-label="Sebelumnya"
                        onClick={() => go(-1)}
                        className={cn(arrow, '-left-3 md:-left-[43px]')}
                    >
                        <ChevronLeft aria-hidden className="size-6" />
                    </button>
                    <button
                        type="button"
                        aria-label="Berikutnya"
                        onClick={() => go(1)}
                        className={cn(arrow, '-right-3 md:-right-[43px]')}
                    >
                        <ChevronRight aria-hidden className="size-6" />
                    </button>
                </>
            )}
        </section>
    );
}

function DealCard({ product }: { product: LandingProduct }) {
    const percent = discountPercent(product);

    return (
        <Link
            href={show(product.slug)}
            prefetch
            className={cn(
                'relative flex flex-col overflow-hidden rounded-2xl bg-[#F5F5F5]',
                cardHover,
            )}
        >
            <ProductImage
                src={product.image}
                alt={product.name}
                className="h-[160px] rounded-none bg-transparent object-contain p-2 md:h-[188px]"
            />
            {percent > 0 && (
                <span className="absolute top-0 right-0 grid h-[53px] w-[51px] place-items-center rounded-tr-2xl rounded-bl-2xl bg-[#008ECC] px-1 text-center text-sm leading-4 font-semibold text-white">
                    {percent}% OFF
                </span>
            )}
            <div className="flex flex-1 flex-col rounded-b-[15px] bg-white px-3 pt-3 pb-2.5">
                <h3 className="truncate text-base leading-[18px] font-semibold text-[#222]">
                    {product.name}
                </h3>
                <p className="mt-2.5 flex flex-wrap items-baseline gap-x-2.5 text-base leading-[18px] text-[#222] tabular-nums">
                    <span className="font-bold">
                        {formatRupiah(product.min_price)}
                    </span>
                    {product.compare_at_price && (
                        <s>{formatRupiah(product.compare_at_price)}</s>
                    )}
                </p>
                <p className="mt-2.5 border-t border-[#EDEDED] pt-2.5 text-base leading-[18px] font-semibold">
                    {product.compare_at_price ? (
                        <span className="text-[#249B3E] tabular-nums">
                            Hemat -{' '}
                            {formatRupiah(
                                product.compare_at_price - product.min_price,
                            )}
                        </span>
                    ) : (
                        <span className="text-[#666]">{product.category}</span>
                    )}
                </p>
            </div>
        </Link>
    );
}

export default function Landing({ products }: { products: LandingProduct[] }) {
    const { menuCategories } = usePage().props;

    const departments: Department[] = menuCategories
        .map((category) => {
            const items = products.filter(
                (product) => product.department === category.name,
            );

            return {
                ...category,
                products: items,
                minPrice: Math.min(
                    ...items.map((product) => product.min_price),
                ),
            };
        })
        .filter((department) => department.products.length > 0);
    // The design opens on its watch photo, so that slide leads.
    const slides = [...departments].sort(
        (a, b) =>
            Number(/\b(jam|watch)\b/i.test(b.name)) -
            Number(/\b(jam|watch)\b/i.test(a.name)),
    );

    const deals = [...products]
        .sort((a, b) => discountPercent(b) - discountPercent(a))
        .slice(0, 5);
    const essentials = products
        .filter((product) => !deals.includes(product))
        .slice(0, 6);

    return (
        <>
            <Head title="Elektronik pilihan">
                <meta
                    name="description"
                    head-key="description"
                    content={description}
                />
            </Head>

            <h1 className="sr-only">
                Elektronik pilihan. Belanja tanpa ribet.
            </h1>

            {slides.length > 0 && <HeroSlider departments={slides} />}

            {deals.length > 0 && (
                <Section id="deals-title">
                    <SectionHeader
                        id="deals-title"
                        lead="Penawaran terbaik untuk"
                        accent="kamu"
                    />
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                        {deals.map((product) => (
                            <DealCard key={product.id} product={product} />
                        ))}
                    </div>
                </Section>
            )}

            {departments.length > 0 && (
                <Section id="categories-title">
                    <SectionHeader
                        id="categories-title"
                        lead="Belanja dari"
                        accent="Kategori Teratas"
                    />
                    <ul className="flex flex-wrap justify-center gap-x-6 gap-y-8 sm:justify-start sm:gap-x-[46px]">
                        {departments.map((department) => (
                            <li
                                key={department.slug}
                                className="w-24 sm:w-[132px]"
                            >
                                <Link
                                    href={productsIndex({
                                        query: { category: department.slug },
                                    })}
                                    className="group flex flex-col items-center gap-5 text-center"
                                >
                                    <span
                                        className={cn(
                                            'grid aspect-square w-full place-items-center overflow-hidden rounded-full bg-[#F5F5F5]',
                                            cardHover,
                                        )}
                                    >
                                        <ProductImage
                                            src={
                                                department.products.find(
                                                    (product) => product.image,
                                                )?.image ?? null
                                            }
                                            alt={department.name}
                                            className="aspect-square w-3/4 rounded-none bg-transparent object-contain"
                                        />
                                    </span>
                                    <span className="text-sm leading-5 font-medium text-[#222] group-hover:text-[#008ECC] sm:text-base">
                                        {department.name}
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </Section>
            )}

            {departments.length > 0 && (
                <Section id="departments-title">
                    <SectionHeader
                        id="departments-title"
                        lead="Departemen"
                        accent="Elektronik Teratas"
                    />
                    <div className="grid gap-4 md:grid-cols-3">
                        {departments.slice(0, 3).map((department, index) => {
                            const style = brandStyles[index];
                            const lead = department.products[0];

                            return (
                                <Link
                                    key={department.slug}
                                    href={productsIndex({
                                        query: { category: department.slug },
                                    })}
                                    className={cn(
                                        'relative flex h-[207px] overflow-hidden rounded-2xl p-5 transition-opacity hover:opacity-90',
                                        style.card,
                                    )}
                                >
                                    <img
                                        src={`/images/landing/brand-rings-${style.rings}.svg`}
                                        alt=""
                                        width={246}
                                        height={246}
                                        className="pointer-events-none absolute top-[-85px] right-[-45px] max-w-none"
                                    />
                                    <div className="relative z-10 flex flex-col items-start">
                                        <span
                                            className={cn(
                                                'rounded-lg px-5 py-[9px] text-sm leading-none tracking-[1.4px] uppercase',
                                                style.chip,
                                            )}
                                        >
                                            {department.name}
                                        </span>
                                        <ProductArt
                                            name={department.name}
                                            className="mt-5 size-[62px] bg-white text-[#222]"
                                        />
                                        <p className="mt-auto text-2xl leading-[30px] font-semibold">
                                            Mulai{' '}
                                            {formatRupiah(department.minPrice)}
                                        </p>
                                    </div>
                                    <ProductImage
                                        src={lead.image}
                                        alt={lead.name}
                                        className={cn(
                                            'absolute top-3 right-6 h-[185px] w-[140px] rounded-none bg-transparent object-contain',
                                            index === 0
                                                ? 'text-white/90'
                                                : 'text-[#222]/80',
                                        )}
                                    />
                                </Link>
                            );
                        })}
                    </div>
                </Section>
            )}

            {essentials.length > 0 && (
                <Section id="essentials-title">
                    <SectionHeader
                        id="essentials-title"
                        lead="Kebutuhan"
                        accent="Harian"
                    />
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                        {essentials.map((product) => (
                            <Link
                                key={product.id}
                                href={show(product.slug)}
                                prefetch
                                className="group flex flex-col items-center gap-5 text-center"
                            >
                                <span
                                    className={cn(
                                        'grid aspect-square w-full place-items-center overflow-hidden rounded-2xl bg-[#F5F5F5]',
                                        cardHover,
                                    )}
                                >
                                    <ProductImage
                                        src={product.image}
                                        alt={product.name}
                                        className="aspect-square w-4/5 rounded-none bg-transparent object-contain"
                                    />
                                </span>
                                <span>
                                    <span className="line-clamp-1 text-base leading-5 font-semibold text-[#666]">
                                        {product.name}
                                    </span>
                                    <span className="mt-1 block text-xl leading-5 font-bold text-[#222] tabular-nums">
                                        {formatRupiah(product.min_price)}
                                    </span>
                                </span>
                            </Link>
                        ))}
                    </div>
                </Section>
            )}
        </>
    );
}
