import { Head, Link } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import ProductArt from '@/components/product-art';
import ProductCard from '@/components/product-card';
import { Button } from '@/components/ui/button';
import { formatRupiah } from '@/lib/utils';
import { index as productsIndex } from '@/routes/products';
import type { ProductCard as ProductCardData } from '@/types';

type Service = { key: string; name: string; rate_per_kg: number; eta: string };

type Props = {
    products: ProductCardData[];
    categories: { name: string; slug: string }[];
    shipping: { origin: string; services: Service[] };
    rules: {
        payment_due_hours: number;
        payment_reminder_hours: number;
        auto_complete_days: number;
        max_quantity_per_item: number;
    };
    vouchers: string[];
};

const description =
    'Ponsel, laptop, audio dan aksesori dengan harga yang jelas. Ongkir dihitung per kilo.';

function Faq({
    shipping,
    rules,
    vouchers,
}: Pick<Props, 'shipping' | 'rules' | 'vouchers'>) {
    const rates = shipping.services
        .map(
            (service) =>
                `${service.name} ${formatRupiah(service.rate_per_kg)}/kg`,
        )
        .join(', ');
    const pairs: [string, string][] = [
        [
            'Bagaimana ongkir dihitung?',
            `Berat semua barang dijumlah, lalu dibulatkan ke atas per kilo, minimal 1 kg. Tarifnya ${rates}. Kota tujuan tidak mengubah tarif.`,
        ],
        [
            'Berapa lama waktu untuk membayar?',
            `${rules.payment_due_hours} jam sejak pesanan dibuat. Kami kirim email pengingat ${rules.payment_reminder_hours} jam sebelum batas; setelah itu pesanan kedaluwarsa dan stok dilepas.`,
        ],
        [
            'Bisakah pesanan dibatalkan?',
            'Bisa, selama belum dibayar, langsung dari halaman pesanan. Stoknya otomatis kembali. Pesanan yang sudah dibayar belum bisa dibatalkan.',
        ],
        [
            'Ada kode voucher?',
            vouchers.length > 0
                ? `Satu voucher per pesanan, dimasukkan saat checkout. Yang berlaku sekarang: ${vouchers.join(', ')}.`
                : 'Satu voucher per pesanan, dimasukkan saat checkout. Saat ini belum ada voucher yang berlaku.',
        ],
        [
            'Kapan pesanan dianggap selesai?',
            `Saat kamu menekan "Pesanan sudah saya terima", atau otomatis ${rules.auto_complete_days} hari setelah paket tercatat diterima.`,
        ],
        ['Apakah ini toko sungguhan?', 'Still under development'],
    ];

    return (
        <section aria-labelledby="faq-title" className="mx-auto max-w-3xl">
            <h2 id="faq-title" className="mb-6 text-head font-display">
                Pertanyaan umum
            </h2>
            <div className="divide-y divide-border border-y border-border">
                {pairs.map(([question, answer]) => (
                    <details key={question} className="group">
                        <summary className="flex min-h-14 cursor-pointer list-none items-center justify-between gap-4 py-3 text-body font-semibold [&::-webkit-details-marker]:hidden">
                            {question}
                            <ChevronDown
                                aria-hidden
                                className="size-5 shrink-0 text-soft transition-transform group-open:rotate-180 motion-reduce:transition-none"
                            />
                        </summary>
                        <p className="max-w-[60ch] pb-5 text-body text-soft">
                            {answer}
                        </p>
                    </details>
                ))}
            </div>
        </section>
    );
}

export default function Landing({
    products,
    categories,
    shipping,
    rules,
    vouchers,
}: Props) {
    // Products arrive lightest first; sampling evenly keeps the selection varied.
    const picks =
        products.length <= 8
            ? products
            : Array.from(
                  { length: 8 },
                  (_, step) =>
                      products[Math.round((step * (products.length - 1)) / 7)],
              );

    return (
        <div className="space-y-20 md:space-y-28">
            <Head title="Elektronik pilihan">
                <meta
                    name="description"
                    head-key="description"
                    content={description}
                />
            </Head>

            <section className="py-8 text-center md:py-16">
                <h1 className="text-display font-display text-balance">
                    Elektronik pilihan. Belanja tanpa ribet.
                </h1>
                <p className="mx-auto mt-5 max-w-[44ch] text-lead text-soft">
                    {description}
                </p>
                <Button asChild size="xl" className="mt-8">
                    <Link href={productsIndex()}>Lihat semua barang</Link>
                </Button>
            </section>

            {categories.length > 0 && (
                <section aria-labelledby="categories-title">
                    <h2
                        id="categories-title"
                        className="mb-6 text-head font-display"
                    >
                        Kategori
                    </h2>
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                        {categories.map((category) => (
                            <Link
                                key={category.slug}
                                href={productsIndex({
                                    query: { category: category.slug },
                                })}
                                className="group space-y-2"
                            >
                                <ProductArt
                                    name={category.name}
                                    className="aspect-[4/3] transition-opacity group-hover:opacity-80"
                                />
                                <p className="px-1 text-body font-semibold group-hover:underline">
                                    {category.name}
                                </p>
                            </Link>
                        ))}
                    </div>
                </section>
            )}

            <section aria-labelledby="products-title">
                <h2 id="products-title" className="mb-6 text-head font-display">
                    Barang pilihan
                </h2>
                <div className="grid grid-cols-2 gap-x-4 gap-y-8 lg:grid-cols-4">
                    {picks.map((product) => (
                        <ProductCard key={product.id} product={product} />
                    ))}
                </div>
            </section>

            <Faq shipping={shipping} rules={rules} vouchers={vouchers} />
        </div>
    );
}
