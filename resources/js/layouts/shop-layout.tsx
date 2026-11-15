import { Link, usePage } from '@inertiajs/react';
import { LayoutDashboard, Package, ShoppingBag, User } from 'lucide-react';
import type { ReactNode } from 'react';
import { dashboard, home, login, register } from '@/routes';
import { index as cartIndex } from '@/routes/cart';
import { index as ordersIndex } from '@/routes/orders';
import { index as productsIndex } from '@/routes/products';
import { edit as profileEdit } from '@/routes/profile';
import type { User as AuthUser } from '@/types';

const navLink =
    'flex h-11 items-center gap-2 px-3 text-small transition-colors hover:bg-accent';

export default function ShopLayout({ children }: { children: ReactNode }) {
    const { store, cartCount } = usePage().props;
    const user = usePage().props.auth.user as AuthUser | null;

    return (
        <div className="theme-shop flex min-h-svh flex-col">
            <header className="sticky top-0 z-20 border-b border-border bar-glass">
                <div className="mx-auto flex h-14 max-w-6xl items-center gap-1 px-4 md:px-6">
                    <Link
                        href={home()}
                        className="mr-2 text-body font-semibold tracking-tight"
                    >
                        {store.name}
                    </Link>

                    <nav aria-label="Toko">
                        <Link href={productsIndex()} className={navLink}>
                            Semua barang
                        </Link>
                    </nav>

                    <nav
                        aria-label="Akun"
                        className="ml-auto flex items-center"
                    >
                        {user ? (
                            <>
                                {user.role === 'admin' && (
                                    <Link
                                        href={dashboard()}
                                        className={navLink}
                                        aria-label="Admin"
                                    >
                                        <LayoutDashboard className="size-5" />
                                        <span className="hidden lg:inline">
                                            Admin
                                        </span>
                                    </Link>
                                )}
                                <Link
                                    href={ordersIndex()}
                                    className={navLink}
                                    aria-label="Pesanan saya"
                                >
                                    <Package className="size-5" />
                                    <span className="hidden lg:inline">
                                        Pesanan
                                    </span>
                                </Link>
                                <Link
                                    href={profileEdit()}
                                    className={navLink}
                                    aria-label="Profil"
                                >
                                    <User className="size-5" />
                                    <span className="hidden max-w-28 truncate lg:inline">
                                        {user.name}
                                    </span>
                                </Link>
                                <Link
                                    href={cartIndex()}
                                    aria-label={`Keranjang, ${cartCount} barang`}
                                    className={navLink}
                                >
                                    <ShoppingBag className="size-5" />
                                    <span className="tabular-nums">
                                        {cartCount}
                                    </span>
                                </Link>
                            </>
                        ) : (
                            <>
                                <Link href={login()} className={navLink}>
                                    Masuk
                                </Link>
                                <Link
                                    href={register()}
                                    className={`${navLink} font-semibold`}
                                >
                                    Daftar
                                </Link>
                            </>
                        )}
                    </nav>
                </div>
            </header>

            <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-10 md:px-6 md:py-14">
                {children}
            </main>

            <footer className="mt-20 border-t border-border text-small text-soft">
                <div className="mx-auto grid max-w-6xl gap-6 px-4 py-10 md:grid-cols-3 md:px-6">
                    <p className="max-w-[36ch]">
                        Elektronik pilihan, dikirim dari {store.address}.
                    </p>
                    <p>
                        {store.email}
                        <br />
                        {store.phone}
                    </p>
                    <nav
                        aria-label="Kaki"
                        className="flex flex-col items-start gap-1"
                    >
                        <Link
                            href={productsIndex()}
                            className="hover:underline"
                        >
                            Semua barang
                        </Link>
                        {user && (
                            <Link
                                href={ordersIndex()}
                                className="hover:underline"
                            >
                                Pesanan saya
                            </Link>
                        )}
                    </nav>
                    <p className="md:col-span-3">
                        Toko demo: tidak ada transaksi atau pembayaran
                        sungguhan. © {new Date().getFullYear()} {store.name}
                    </p>
                </div>
            </footer>
        </div>
    );
}
