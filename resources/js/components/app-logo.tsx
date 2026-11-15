import { usePage } from '@inertiajs/react';

/** The MyShop wordmark: a square in the brand colour with the initial (kept when the sidebar collapses), then the name. */
export default function AppLogo() {
    const { name } = usePage().props;

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center bg-sidebar-primary text-xl font-display text-sidebar-primary-foreground">
                M
            </div>
            <div className="ml-1 grid flex-1 text-left">
                <span className="truncate text-xl font-display">{name}</span>
            </div>
        </>
    );
}
