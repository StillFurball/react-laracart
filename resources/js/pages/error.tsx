import { Head, Link } from '@inertiajs/react';
import { home } from '@/routes';
import { index as flashSaleIndex } from '@/routes/flash-sale';
import { index as productsIndex } from '@/routes/products';

const messages: Record<number, { title: string; description: string }> = {
    403: {
        title: 'Akses ditolak',
        description:
            'Halaman ini hanya untuk akun dengan izin tertentu. Masuk dengan akun yang benar atau kembali ke toko.',
    },
    404: {
        title: 'Halaman tidak ditemukan',
        description:
            'Tautannya mungkin salah, atau barangnya sudah tidak dijual. Coba cari dari katalog.',
    },
    429: {
        title: 'Terlalu banyak permintaan',
        description:
            'Kamu mencoba terlalu sering dalam waktu singkat. Tunggu sebentar, lalu coba lagi.',
    },
    500: {
        title: 'Terjadi kesalahan',
        description:
            'Ada masalah di server kami dan sudah tercatat. Coba muat ulang halaman ini sebentar lagi.',
    },
};

export default function ErrorPage({ status }: { status: number }) {
    const message = messages[status] ?? messages[500];

    return (
        <>
            <Head title={message.title} />

            <section className="mx-auto max-w-xl py-16 text-center md:py-24">
                <p className="text-sm font-semibold tracking-[1.4px] text-[#007AB0] uppercase">
                    Kode {status}
                </p>
                <h1 className="mt-3 text-[32px] leading-10 font-bold text-[#222]">
                    {message.title}
                </h1>
                <p className="mt-4 text-base text-[#666]">
                    {message.description}
                </p>
                <div className="mt-8 flex flex-wrap justify-center gap-3">
                    <Link
                        href={home()}
                        className="inline-flex h-11 items-center rounded-[10px] bg-[#008ECC] px-5 font-semibold text-white hover:bg-[#0077ad]"
                    >
                        Ke beranda
                    </Link>
                    <Link
                        href={productsIndex()}
                        className="inline-flex h-11 items-center rounded-[10px] bg-[#F3F9FB] px-5 font-semibold text-[#007AB0] hover:bg-[#e6f4fa]"
                    >
                        Lihat semua barang
                    </Link>
                    <Link
                        href={flashSaleIndex()}
                        className="inline-flex h-11 items-center rounded-[10px] bg-[#F3F9FB] px-5 font-semibold text-[#007AB0] hover:bg-[#e6f4fa]"
                    >
                        Flash sale
                    </Link>
                </div>
            </section>
        </>
    );
}
