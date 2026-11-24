import { Link, router, usePage } from '@inertiajs/react';
import {
    ChevronDown,
    LayoutDashboard,
    List,
    MapPin,
    Menu,
    MessageCircle,
    Package,
    Phone,
    Search,
    ShoppingCart,
    Truck,
    UserRound,
    Zap,
} from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { dashboard, home, login, privacy, terms } from '@/routes';
import { index as cartIndex } from '@/routes/cart';
import { index as flashSaleIndex } from '@/routes/flash-sale';
import { index as ordersIndex } from '@/routes/orders';
import { index as productsIndex } from '@/routes/products';
import { edit as profileEdit } from '@/routes/profile';
import type { User as AuthUser } from '@/types';

const accountLink =
    'flex h-11 items-center gap-1.5 px-2 text-base font-bold text-[#666] transition-colors hover:text-[#008ECC]';

const footerHeading =
    'border-b-[3px] border-white pb-3 text-xl leading-5 font-semibold text-white';

function submitSearch(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const q = (
        event.currentTarget.elements.namedItem('q') as HTMLInputElement
    ).value.trim();
    router.get(productsIndex({ query: { q: q || undefined } }).url);
}

function SearchBox({ className }: { className?: string }) {
    return (
        <form
            role="search"
            onSubmit={submitSearch}
            className={cn(
                'flex h-12 items-center gap-2.5 rounded-[10px] bg-[#F3F9FB] px-4',
                className,
            )}
        >
            <Search
                aria-hidden
                className="size-[18px] shrink-0 text-[#008ECC]"
            />
            <input
                type="search"
                name="q"
                aria-label="Cari barang"
                placeholder="Cari ponsel, laptop, audio dan lainnya..."
                className="h-full min-w-0 flex-1 bg-transparent text-sm text-[#222] outline-none placeholder:text-[#666]"
            />
            <Link
                href={productsIndex()}
                aria-label="Semua barang"
                className="text-[#008ECC]"
            >
                <List aria-hidden className="size-6" />
            </Link>
        </form>
    );
}

export default function ShopLayout({ children }: { children: ReactNode }) {
    const { store, cartCount, menuCategories, demoMode } = usePage().props;
    const user = usePage().props.auth.user as AuthUser | null;
    const activeCategory = new URLSearchParams(usePage().url.split('?')[1]).get(
        'category',
    );

    return (
        <div className="theme-shop flex min-h-svh flex-col bg-white text-[#222]">
            <div className="hidden bg-[#F5F5F5] md:block">
                <div className="mx-auto flex h-[42px] max-w-[1248px] items-center gap-4 px-6 text-sm text-[#666]">
                    <p className="leading-[14px]">
                        Selamat datang di {store.name}!
                    </p>
                    <p className="ml-auto flex items-center gap-1.5">
                        <MapPin
                            aria-hidden
                            className="size-[18px] text-[#008ECC]"
                        />
                        Dikirim dari{' '}
                        <span className="font-semibold">
                            {store.address.split(',').at(-2)?.trim()}
                        </span>
                    </p>
                    <span aria-hidden className="h-[18px] w-px bg-[#D9D9D9]" />
                    <Link
                        href={ordersIndex()}
                        className="flex items-center gap-1.5 hover:text-[#008ECC]"
                    >
                        <Truck
                            aria-hidden
                            className="size-[18px] text-[#008ECC]"
                        />
                        Lacak pesanan
                    </Link>
                    <span aria-hidden className="h-[18px] w-px bg-[#D9D9D9]" />
                    <Link
                        href={flashSaleIndex()}
                        className="flex items-center gap-1.5 hover:text-[#008ECC]"
                    >
                        <Zap
                            aria-hidden
                            className="size-[18px] text-[#008ECC]"
                        />
                        Flash sale
                    </Link>
                </div>
            </div>

            <header className="sticky top-0 z-20 bg-white">
                <div className="border-y border-[#EDEDED]">
                    <div className="mx-auto flex max-w-[1248px] flex-wrap items-center gap-x-4 gap-y-3 px-4 py-3 md:px-6 lg:h-[90px] lg:flex-nowrap lg:py-0">
                        <Link
                            href={productsIndex()}
                            aria-label="Semua barang"
                            className="grid size-12 shrink-0 place-items-center rounded-[10px] bg-[#F3F9FB] text-[#008ECC]"
                        >
                            <Menu aria-hidden className="size-6" />
                        </Link>
                        <Link
                            href={home()}
                            className="text-[28px] leading-7 font-bold tracking-tight text-[#008ECC]"
                        >
                            {store.name}
                        </Link>

                        <SearchBox className="order-last w-full lg:order-none lg:mx-auto lg:w-[507px]" />

                        <nav
                            aria-label="Akun"
                            className="ml-auto flex items-center lg:ml-0"
                        >
                            {user ? (
                                <>
                                    {user.role === 'admin' && (
                                        <Link
                                            href={dashboard()}
                                            className={accountLink}
                                            aria-label="Admin"
                                        >
                                            <LayoutDashboard
                                                aria-hidden
                                                className="size-6 text-[#008ECC]"
                                            />
                                        </Link>
                                    )}
                                    <Link
                                        href={ordersIndex()}
                                        className={accountLink}
                                        aria-label="Pesanan saya"
                                    >
                                        <Package
                                            aria-hidden
                                            className="size-6 text-[#008ECC]"
                                        />
                                    </Link>
                                    <Link
                                        href={profileEdit()}
                                        className={accountLink}
                                        aria-label="Profil"
                                    >
                                        <UserRound
                                            aria-hidden
                                            className="size-6 text-[#008ECC]"
                                        />
                                        <span className="hidden max-w-28 truncate xl:inline">
                                            {user.name}
                                        </span>
                                    </Link>
                                </>
                            ) : (
                                <Link href={login()} className={accountLink}>
                                    <UserRound
                                        aria-hidden
                                        className="size-6 text-[#008ECC]"
                                    />
                                    <span className="hidden sm:inline">
                                        Masuk/Daftar
                                    </span>
                                </Link>
                            )}
                            <span
                                aria-hidden
                                className="mx-2 h-6 w-px bg-[#D9D9D9]"
                            />
                            <Link
                                href={cartIndex()}
                                aria-label={`Keranjang, ${cartCount} barang`}
                                className={accountLink}
                            >
                                <span className="relative">
                                    <ShoppingCart
                                        aria-hidden
                                        className="size-6 text-[#008ECC]"
                                    />
                                    {cartCount > 0 && (
                                        <span className="absolute -top-2 -right-2 grid min-w-4 place-items-center rounded-full bg-[#008ECC] px-1 text-[10px] leading-4 text-white tabular-nums">
                                            {cartCount}
                                        </span>
                                    )}
                                </span>
                                <span className="hidden sm:inline">
                                    Keranjang
                                </span>
                            </Link>
                        </nav>
                    </div>
                </div>
            </header>

            {menuCategories.length > 0 && (
                <nav
                    aria-label="Kategori"
                    className="border-b border-[#EDEDED]"
                >
                    <ul className="mx-auto flex h-[68px] max-w-[1248px] [scrollbar-width:none] items-center gap-3.5 overflow-x-auto px-4 md:px-6">
                        {menuCategories.map((category) => {
                            const isActive = category.slug === activeCategory;

                            return (
                                <li key={category.slug} className="shrink-0">
                                    <Link
                                        href={productsIndex({
                                            query: {
                                                category: category.slug,
                                            },
                                        })}
                                        aria-current={
                                            isActive ? 'page' : undefined
                                        }
                                        className={cn(
                                            'flex items-center gap-1.5 rounded-[18px] px-3.5 py-[9px] text-sm leading-[18px] font-medium transition-colors',
                                            isActive
                                                ? 'bg-[#008ECC] text-white'
                                                : 'bg-[#F3F9FB] text-[#222] hover:bg-[#008ECC] hover:text-white',
                                        )}
                                    >
                                        {category.name}
                                        <ChevronDown
                                            aria-hidden
                                            className="size-[18px]"
                                        />
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                </nav>
            )}

            <main className="mx-auto w-full max-w-[1248px] flex-1 px-4 py-5 md:px-6">
                {children}
            </main>

            <footer className="relative mt-20 overflow-hidden bg-[#008ECC] text-white">
                <img
                    src="/images/landing/footer-rings.svg"
                    alt=""
                    width={467}
                    height={467}
                    className="pointer-events-none absolute -top-[118px] right-[-162px] hidden lg:block"
                />
                <div className="relative mx-auto grid max-w-[1248px] gap-10 px-4 pt-20 pb-10 md:grid-cols-[1fr_auto_auto] md:gap-20 md:px-6">
                    <div>
                        <Link
                            href={home()}
                            className="text-[44px] leading-[44px] font-bold tracking-tight"
                        >
                            {store.name}
                        </Link>
                        <h2 className="mt-8 text-xl leading-5 font-bold">
                            Hubungi kami
                        </h2>
                        <ul className="mt-5 space-y-5 text-base">
                            <li className="flex gap-3">
                                <MessageCircle
                                    aria-hidden
                                    className="size-6 shrink-0"
                                />
                                <p className="leading-5">
                                    WhatsApp
                                    <br />
                                    {store.phone}
                                </p>
                            </li>
                            <li className="flex gap-3">
                                <Phone
                                    aria-hidden
                                    className="size-6 shrink-0"
                                />
                                <p className="leading-5">
                                    Telepon
                                    <br />
                                    {store.phone}
                                </p>
                            </li>
                        </ul>
                    </div>

                    <nav aria-label="Kategori populer">
                        <h2 className={footerHeading}>Kategori populer</h2>
                        <ul className="mt-5 list-disc ps-6 text-base leading-[37px] font-medium">
                            {menuCategories.map((category) => (
                                <li key={category.slug}>
                                    <Link
                                        href={productsIndex({
                                            query: { category: category.slug },
                                        })}
                                        className="hover:underline"
                                    >
                                        {category.name}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </nav>

                    <nav aria-label="Layanan pelanggan">
                        <h2 className={footerHeading}>Layanan pelanggan</h2>
                        <ul className="mt-5 list-disc ps-6 text-base leading-[37px] font-medium">
                            <li>
                                <Link
                                    href={productsIndex()}
                                    className="hover:underline"
                                >
                                    Semua barang
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href={flashSaleIndex()}
                                    className="hover:underline"
                                >
                                    Flash sale
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href={ordersIndex()}
                                    className="hover:underline"
                                >
                                    Lacak pesanan
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href={cartIndex()}
                                    className="hover:underline"
                                >
                                    Keranjang
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href={terms()}
                                    className="hover:underline"
                                >
                                    Syarat &amp; Ketentuan
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href={privacy()}
                                    className="hover:underline"
                                >
                                    Kebijakan Privasi
                                </Link>
                            </li>
                            <li>
                                <a
                                    href={`mailto:${store.email}`}
                                    className="hover:underline"
                                >
                                    {store.email}
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <p className="relative mx-auto max-w-[1200px] border-t border-white/30 px-4 py-7 text-center text-base md:text-xl md:leading-5">
                    © {new Date().getFullYear()} {store.name}.
                    {demoMode && ' Toko demo: tidak ada transaksi sungguhan.'}
                </p>
            </footer>
        </div>
    );
}
