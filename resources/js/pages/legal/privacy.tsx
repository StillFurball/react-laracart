import { Head, Link, usePage } from '@inertiajs/react';
import { terms } from '@/routes';
import { edit as profileEdit } from '@/routes/profile';
import { LegalSection } from './terms';

export default function Privacy() {
    const { store } = usePage().props;

    return (
        <>
            <Head title="Kebijakan Privasi" />

            <article className="mx-auto max-w-3xl space-y-10 py-6">
                <header className="space-y-2">
                    <h1 className="text-[32px] leading-10 font-bold text-[#222]">
                        Kebijakan Privasi
                    </h1>
                    <p className="text-sm text-[#666]">
                        Berlaku sejak 24 November 2026. Kebijakan ini
                        menjelaskan data pribadi apa yang disimpan {store.name},
                        untuk apa, dan bagaimana kamu mengendalikannya, sesuai
                        UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi.
                    </p>
                </header>

                <LegalSection title="1. Data yang kami simpan">
                    <ul>
                        <li>
                            <strong>Akun:</strong> nama, email, nomor telepon
                            (opsional), dan kata sandi yang hanya disimpan dalam
                            bentuk hash.
                        </li>
                        <li>
                            <strong>Alamat pengiriman:</strong> nama penerima,
                            telepon, dan alamat.
                        </li>
                        <li>
                            <strong>Pesanan:</strong> barang, harga, voucher,
                            catatan untuk penjual, serta salinan alamat
                            pengiriman pada saat pesanan dibuat.
                        </li>
                        <li>
                            <strong>Data teknis:</strong> alamat IP dan
                            informasi peramban, dipakai untuk keamanan (misalnya
                            membatasi percobaan login) dan catatan server.
                        </li>
                    </ul>
                </LegalSection>

                <LegalSection title="2. Untuk apa data dipakai">
                    <ul>
                        <li>Memproses, mengirim, dan melacak pesananmu.</li>
                        <li>
                            Mengirim email penting: verifikasi akun, status
                            pesanan, dan pengingat pembayaran. Kami tidak
                            mengirim email promosi.
                        </li>
                        <li>
                            Melindungi akun dan toko dari penyalahgunaan, serta
                            memenuhi kewajiban pencatatan transaksi.
                        </li>
                    </ul>
                </LegalSection>

                <LegalSection title="3. Dengan siapa data dibagikan">
                    <p>
                        Kami tidak menjual data pribadimu. Data hanya dibuka
                        kepada penyedia yang membantu menjalankan toko, yaitu
                        layanan hosting, layanan pengiriman email, dan kurir
                        (nama, telepon, dan alamat untuk pengiriman), atau bila
                        diwajibkan oleh hukum.
                    </p>
                </LegalSection>

                <LegalSection title="4. Cookie">
                    <p>
                        Kami hanya memakai cookie yang diperlukan agar toko
                        berjalan: sesi login, token keamanan formulir, dan
                        preferensi tampilan. Tidak ada cookie iklan atau
                        pelacakan pihak ketiga. Huruf situs dimuat dari Google
                        Fonts, sehingga alamat IP-mu terlihat oleh Google saat
                        memuat huruf tersebut.
                    </p>
                </LegalSection>

                <LegalSection title="5. Keamanan dan penyimpanan">
                    <ul>
                        <li>
                            Koneksi dienkripsi (HTTPS), kata sandi di-hash, dan
                            kamu dapat mengaktifkan autentikasi dua faktor.
                            Mengganti kata sandi mengeluarkan akunmu dari
                            perangkat lain.
                        </li>
                        <li>
                            Data akun disimpan selama akunmu aktif. Catatan
                            pesanan disimpan untuk keperluan pembukuan toko.
                        </li>
                    </ul>
                </LegalSection>

                <LegalSection title="6. Hak kamu">
                    <ul>
                        <li>
                            <strong>Melihat dan memperbaiki</strong> data akun
                            dan alamat di{' '}
                            <Link
                                href={profileEdit()}
                                className="font-medium text-[#007AB0] underline"
                            >
                                Pengaturan
                            </Link>
                            .
                        </li>
                        <li>
                            <strong>Menghapus akun</strong> dari Pengaturan.
                            Pesanan yang masih berjalan perlu diselesaikan atau
                            dibatalkan dulu. Bila akunmu punya riwayat pesanan,
                            nama, email, telepon, alamat tersimpan, dan
                            keranjangmu dihapus; catatan pesanan lama tetap
                            disimpan sebagai bukti transaksi.
                        </li>
                        <li>
                            Untuk permintaan lain terkait data pribadimu,
                            hubungi{' '}
                            <a
                                href={`mailto:${store.email}`}
                                className="font-medium text-[#007AB0] underline"
                            >
                                {store.email}
                            </a>
                            .
                        </li>
                    </ul>
                </LegalSection>

                <LegalSection title="7. Perubahan kebijakan">
                    <p>
                        Bila kebijakan ini berubah, versi terbaru ditampilkan di
                        halaman ini beserta tanggal berlakunya. Lihat juga{' '}
                        <Link
                            href={terms()}
                            className="font-medium text-[#007AB0] underline"
                        >
                            Syarat &amp; Ketentuan
                        </Link>
                        .
                    </p>
                </LegalSection>
            </article>
        </>
    );
}
