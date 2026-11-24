import { Head, Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { privacy } from '@/routes';
import { index as flashSaleIndex } from '@/routes/flash-sale';

export function LegalSection({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    return (
        <section className="space-y-3">
            <h2 className="text-xl leading-7 font-bold text-[#222]">{title}</h2>
            <div className="space-y-3 text-base leading-7 text-[#444] [&_li]:ms-5 [&_li]:list-disc [&_li]:ps-1">
                {children}
            </div>
        </section>
    );
}

export default function Terms({
    paymentDueHours,
    autoCompleteDays,
    maxPerItem,
    paymentSimulator,
}: {
    paymentDueHours: number;
    autoCompleteDays: number;
    maxPerItem: number;
    paymentSimulator: boolean;
}) {
    const { store } = usePage().props;

    return (
        <>
            <Head title="Syarat & Ketentuan" />

            <article className="mx-auto max-w-3xl space-y-10 py-6">
                <header className="space-y-2">
                    <h1 className="text-[32px] leading-10 font-bold text-[#222]">
                        Syarat &amp; Ketentuan
                    </h1>
                    <p className="text-sm text-[#666]">
                        Berlaku sejak 24 November 2026. Dengan membuat pesanan
                        di {store.name}, kamu menyetujui ketentuan berikut.
                    </p>
                </header>

                {paymentSimulator && (
                    <p className="rounded-2xl bg-[#F3F9FB] p-4 text-base text-[#222]">
                        <strong>Mode demo aktif.</strong> Pembayaran di toko ini
                        hanya simulasi: tidak ada uang yang ditagih dan pesanan
                        tidak dikirim.
                    </p>
                )}

                <LegalSection title="1. Tentang toko">
                    <p>
                        {store.name} adalah toko daring dengan satu penjual.
                        Hubungi kami di{' '}
                        <a
                            href={`mailto:${store.email}`}
                            className="font-medium text-[#007AB0] underline"
                        >
                            {store.email}
                        </a>{' '}
                        atau {store.phone}. Alamat: {store.address}.
                    </p>
                </LegalSection>

                <LegalSection title="2. Akun">
                    <ul>
                        <li>Isi data akun dan alamat dengan benar.</li>
                        <li>
                            Jaga kerahasiaan kata sandi; aktifkan autentikasi
                            dua faktor di Pengaturan untuk perlindungan
                            tambahan.
                        </li>
                        <li>
                            Kami dapat menangguhkan akun yang dipakai untuk
                            penipuan, spam, atau mengganggu layanan.
                        </li>
                    </ul>
                </LegalSection>

                <LegalSection title="3. Harga dan pemesanan">
                    <ul>
                        <li>
                            Harga dalam Rupiah. Ongkos kirim dihitung terpisah
                            saat checkout.
                        </li>
                        <li>
                            Total pesanan dihitung ulang saat pesanan dibuat.
                            Bila harga atau stok berubah, kamu diminta memeriksa
                            ringkasan lagi sebelum melanjutkan.
                        </li>
                        <li>
                            Maksimal {maxPerItem} unit per barang dalam satu
                            pesanan. Stok dipesankan untukmu begitu pesanan
                            dibuat.
                        </li>
                    </ul>
                </LegalSection>

                <LegalSection title="4. Flash sale">
                    <ul>
                        <li>
                            Harga{' '}
                            <Link
                                href={flashSaleIndex()}
                                className="font-medium text-[#007AB0] underline"
                            >
                                flash sale
                            </Link>{' '}
                            berlaku selama jadwalnya berjalan dan kuotanya masih
                            ada. Harga yang berlaku adalah harga saat pesanan
                            dibuat, bukan saat barang masuk keranjang.
                        </li>
                        <li>
                            Batas per pelanggan dihitung dari semua pesananmu
                            yang tidak kedaluwarsa atau dibatalkan.
                        </li>
                        <li>
                            Bila jumlah di keranjang melebihi sisa kuota atau
                            batasmu, seluruh barang itu dikenakan harga normal;
                            keranjang menjelaskan cara mendapatkan harga flash
                            sale.
                        </li>
                    </ul>
                </LegalSection>

                <LegalSection title="5. Voucher">
                    <p>
                        Satu voucher per pesanan, sesuai minimum belanja, kuota,
                        dan periode berlakunya. Voucher memotong subtotal (tidak
                        termasuk ongkir) dan dapat dipakai bersama harga flash
                        sale. Percobaan kode yang terlalu sering dibatasi
                        sementara.
                    </p>
                </LegalSection>

                <LegalSection title="6. Pembayaran">
                    <p>
                        Selesaikan pembayaran dalam {paymentDueHours} jam
                        setelah pesanan dibuat. Lewat dari itu, pesanan
                        kedaluwarsa dan stok serta kuota flash sale-nya dilepas
                        untuk pembeli lain.
                    </p>
                </LegalSection>

                <LegalSection title="7. Pengiriman">
                    <ul>
                        <li>
                            Ongkir dihitung dari total berat, dibulatkan ke atas
                            per kilogram (minimal 1 kg).
                        </li>
                        <li>
                            Estimasi waktu kirim bukan jaminan. Nomor resi
                            tampil di halaman pesanan setelah barang dikirim.
                        </li>
                        <li>
                            Konfirmasi penerimaan di halaman pesanan. Bila tidak
                            dikonfirmasi, pesanan selesai otomatis{' '}
                            {autoCompleteDays} hari setelah diterima.
                        </li>
                    </ul>
                </LegalSection>

                <LegalSection title="8. Pembatalan dan pengembalian">
                    <p>
                        Pesanan yang belum dibayar dapat kamu batalkan sendiri.
                        Untuk pesanan yang sudah dibayar, barang rusak, atau
                        salah kirim, hubungi kami dengan nomor pesananmu dan
                        kami akan membantu penyelesaiannya.
                    </p>
                </LegalSection>

                <LegalSection title="9. Batas tanggung jawab">
                    <p>
                        Kami berupaya menampilkan informasi barang dan harga
                        dengan akurat. Bila terjadi kesalahan yang jelas
                        (misalnya salah ketik harga), kami dapat membatalkan
                        pesanan terkait dan mengembalikan dana yang sudah
                        dibayar.
                    </p>
                </LegalSection>

                <LegalSection title="10. Hukum yang berlaku dan perubahan">
                    <p>
                        Ketentuan ini tunduk pada hukum Republik Indonesia. Kami
                        dapat memperbaruinya; versi terbaru selalu ada di
                        halaman ini. Cara kami mengelola data pribadimu ada di{' '}
                        <Link
                            href={privacy()}
                            className="font-medium text-[#007AB0] underline"
                        >
                            Kebijakan Privasi
                        </Link>
                        .
                    </p>
                </LegalSection>
            </article>
        </>
    );
}
