import { Link, usePage } from '@inertiajs/react';
import { LayoutDashboard, Package, ShoppingCart, User } from 'lucide-react';
import type { ReactNode } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { dashboard, home, login, register } from '@/routes';
import { index as cartIndex } from '@/routes/cart';
import { index as ordersIndex } from '@/routes/orders';
import { edit as profileEdit } from '@/routes/profile';
import type { User as AuthUser } from '@/types';

export default function ShopLayout({ children }: { children: ReactNode }) {
    const { store, cartCount } = usePage().props;
    const user = usePage().props.auth.user as AuthUser | null;

    return (
        <div className="flex min-h-screen flex-col bg-background">
            <div className="bg-amber-100 px-4 py-1.5 text-center text-xs text-amber-900 dark:bg-amber-900/40 dark:text-amber-100">
                Mode demo — tidak ada transaksi atau pembayaran sungguhan.
            </div>

            <header className="sticky top-0 z-20 border-b bg-background/95 backdrop-blur">
                <div className="mx-auto flex h-16 max-w-6xl items-center gap-3 px-4">
                    <Link
                        href={home()}
                        className="flex items-center gap-2 font-semibold"
                    >
                        <span className="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                            <AppLogoIcon className="size-5 fill-current" />
                        </span>
                        <span className="hidden sm:inline">{store.name}</span>
                    </Link>

                    <nav className="ml-auto flex items-center gap-1">
                        {user ? (
                            <>
                                {user.role === 'admin' && (
                                    <Button variant="ghost" size="sm" asChild>
                                        <Link href={dashboard()}>
                                            <LayoutDashboard />
                                            <span className="hidden sm:inline">
                                                Admin
                                            </span>
                                        </Link>
                                    </Button>
                                )}
                                <Button variant="ghost" size="sm" asChild>
                                    <Link href={ordersIndex()}>
                                        <Package />
                                        <span className="hidden sm:inline">
                                            Pesanan
                                        </span>
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    asChild
                                    className="relative"
                                >
                                    <Link
                                        href={cartIndex()}
                                        aria-label={`Keranjang (${cartCount} item)`}
                                    >
                                        <ShoppingCart />
                                        <span className="hidden sm:inline">
                                            Keranjang
                                        </span>
                                        {cartCount > 0 && (
                                            <span className="absolute -top-1 -right-1 flex min-w-5 items-center justify-center rounded-full bg-primary px-1 text-[11px] text-primary-foreground">
                                                {cartCount}
                                            </span>
                                        )}
                                    </Link>
                                </Button>
                                <Button variant="ghost" size="sm" asChild>
                                    <Link href={profileEdit()}>
                                        <User />
                                        <span className="hidden max-w-28 truncate md:inline">
                                            {user.name}
                                        </span>
                                    </Link>
                                </Button>
                            </>
                        ) : (
                            <>
                                <Button variant="ghost" size="sm" asChild>
                                    <Link href={login()}>Masuk</Link>
                                </Button>
                                <Button size="sm" asChild>
                                    <Link href={register()}>Daftar</Link>
                                </Button>
                            </>
                        )}
                    </nav>
                </div>
            </header>

            <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-6">
                {children}
            </main>

            <footer className="border-t">
                <div className="mx-auto grid max-w-6xl gap-2 px-4 py-6 text-sm text-muted-foreground sm:grid-cols-2">
                    <div>
                        <p className="font-medium text-foreground">
                            {store.name}
                        </p>
                        <p>{store.address}</p>
                    </div>
                    <div className="sm:text-right">
                        <p>{store.email}</p>
                        <p>{store.phone}</p>
                        <p className="mt-1 text-xs">
                            Proyek portofolio · data contoh
                        </p>
                    </div>
                </div>
            </footer>
        </div>
    );
}
