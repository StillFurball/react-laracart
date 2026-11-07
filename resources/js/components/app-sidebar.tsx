import { Link, usePage } from '@inertiajs/react';
import {
    Boxes,
    FolderTree,
    MapPin,
    Package,
    ReceiptText,
    ShoppingBag,
    ShoppingCart,
    Store,
    TicketPercent,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, home } from '@/routes';
import { edit as addressEdit } from '@/routes/address';
import { index as adminCategories } from '@/routes/admin/categories';
import { index as adminOrders } from '@/routes/admin/orders';
import { index as adminProducts } from '@/routes/admin/products';
import { index as adminStock } from '@/routes/admin/stock';
import { index as adminVouchers } from '@/routes/admin/vouchers';
import { index as cartIndex } from '@/routes/cart';
import { index as ordersIndex } from '@/routes/orders';
import type { NavItem } from '@/types';

const adminNavItems: NavItem[] = [
    { title: 'Pesanan', href: adminOrders(), icon: ReceiptText },
    { title: 'Produk', href: adminProducts(), icon: Package },
    { title: 'Kategori', href: adminCategories(), icon: FolderTree },
    { title: 'Stok', href: adminStock(), icon: Boxes },
    { title: 'Voucher', href: adminVouchers(), icon: TicketPercent },
];

const customerNavItems: NavItem[] = [
    { title: 'Belanja', href: home(), icon: Store },
    { title: 'Keranjang', href: cartIndex(), icon: ShoppingCart },
    { title: 'Pesanan Saya', href: ordersIndex(), icon: ShoppingBag },
    { title: 'Alamat', href: addressEdit(), icon: MapPin },
];

export function AppSidebar() {
    const { auth } = usePage().props;
    const isAdmin = auth.user.role === 'admin';

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link
                                href={isAdmin ? dashboard() : home()}
                                prefetch
                            >
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {isAdmin && <NavMain label="Admin" items={adminNavItems} />}
                <NavMain
                    label={isAdmin ? 'Toko' : 'Akun'}
                    items={isAdmin ? [customerNavItems[0]] : customerNavItems}
                />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
