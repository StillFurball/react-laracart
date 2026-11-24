import { Link, usePage } from '@inertiajs/react';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

/** Sign-in pages: the store name, one heading and the form, centred on the plain ground. */
export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { name } = usePage().props;

    return (
        // bg/text set here so the pinned light tokens of .theme-shop apply even when <body> is dark.
        <div className="theme-shop flex min-h-svh flex-col items-center justify-center bg-background px-4 py-12 text-foreground">
            <div className="w-full max-w-sm space-y-8">
                <Link
                    href={home()}
                    className="text-body font-semibold tracking-tight"
                >
                    {name}
                </Link>
                <div className="space-y-2">
                    <h1 className="text-head font-display">{title}</h1>
                    <p className="text-body text-soft">{description}</p>
                </div>
                {children}
            </div>
        </div>
    );
}
