import { Head, Link, router } from '@inertiajs/react';
import { Zap } from 'lucide-react';
import { Countdown, FlashSaleOfferCard } from '@/components/flash-sale';
import { formatDateTime } from '@/lib/utils';
import { index as productsIndex } from '@/routes/products';
import type { FlashSaleSummary } from '@/types';

const description =
    'Flash sale: harga khusus untuk waktu terbatas, selama kuota masih ada.';

function SaleSection({
    sale,
    upcoming = false,
}: {
    sale: FlashSaleSummary;
    upcoming?: boolean;
}) {
    const titleId = `sale-${sale.id}`;

    return (
        <section aria-labelledby={titleId} className="mt-12 first:mt-0">
            <div className="mb-6 flex flex-wrap items-end justify-between gap-x-6 gap-y-3 border-b border-[#EDEDED]">
                <h2
                    id={titleId}
                    className="-mb-px border-b-[3px] border-[#008ECC] pb-4 text-xl leading-[30px] font-bold text-[#222] md:text-2xl"
                >
                    {sale.name}
                </h2>
                <div className="flex flex-wrap items-center gap-2 pb-4 text-sm font-medium text-[#666]">
                    {upcoming ? (
                        <>
                            <span>
                                Mulai {formatDateTime(sale.starts_at)} ·
                            </span>
                            <Countdown
                                target={sale.starts_at}
                                label="Mulai dalam"
                                tone="brand"
                                onElapsed={() => router.reload()}
                            />
                        </>
                    ) : (
                        <>
                            <span>Berakhir dalam</span>
                            <Countdown
                                target={sale.ends_at}
                                label="Berakhir dalam"
                                onElapsed={() => router.reload()}
                            />
                        </>
                    )}
                </div>
            </div>
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                {sale.items.map((offer) => (
                    <FlashSaleOfferCard
                        key={offer.id}
                        offer={offer}
                        upcoming={upcoming}
                    />
                ))}
            </div>
        </section>
    );
}

export default function FlashSale({
    running,
    upcoming,
}: {
    running: FlashSaleSummary[];
    upcoming: FlashSaleSummary[];
}) {
    return (
        <>
            <Head title="Flash Sale">
                <meta
                    name="description"
                    head-key="description"
                    content={description}
                />
            </Head>

            <div className="mb-10 flex items-center gap-3">
                <span className="grid size-12 place-items-center rounded-[10px] bg-[#008ECC] text-white">
                    <Zap aria-hidden className="size-6" />
                </span>
                <div>
                    <h1 className="text-[28px] leading-8 font-bold text-[#222]">
                        Flash Sale
                    </h1>
                    <p className="text-sm text-[#666]">
                        Harga berlaku sampai waktunya habis atau kuotanya
                        terjual. Harga akhir dihitung ulang saat checkout.
                    </p>
                </div>
            </div>

            {running.map((sale) => (
                <SaleSection key={sale.id} sale={sale} />
            ))}

            {running.length === 0 && (
                <div className="rounded-2xl bg-[#F5F5F5] px-6 py-14">
                    <h2 className="text-xl font-bold text-[#222]">
                        Belum ada flash sale yang berjalan
                    </h2>
                    <p className="mt-2 max-w-[52ch] text-base text-[#666]">
                        {upcoming.length > 0
                            ? 'Flash sale berikutnya sudah dijadwalkan di bawah ini. Simpan halaman ini dan kembali saat hitung mundurnya selesai.'
                            : 'Pantau halaman ini untuk penawaran berikutnya. Sementara itu, lihat semua barang di katalog.'}
                    </p>
                    <Link
                        href={productsIndex()}
                        className="mt-6 inline-flex h-11 items-center rounded-[10px] bg-[#008ECC] px-5 font-semibold text-white hover:bg-[#0077ad]"
                    >
                        Lihat semua barang
                    </Link>
                </div>
            )}

            {upcoming.length > 0 && (
                <div className="mt-16">
                    <h2 className="mb-6 text-sm font-semibold tracking-[1.4px] text-[#666] uppercase">
                        Segera hadir
                    </h2>
                    {upcoming.map((sale) => (
                        <SaleSection key={sale.id} sale={sale} upcoming />
                    ))}
                </div>
            )}
        </>
    );
}
