import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { PaginationLink } from '@/types';

function cleanLabel(label: string): string {
    return label
        .replace('&laquo;', '‹')
        .replace('&raquo;', '›')
        .replace('Previous', '')
        .replace('Next', '')
        .trim();
}

export default function Pagination({ links }: { links: PaginationLink[] }) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <nav
            aria-label="Halaman"
            className="mt-6 flex flex-wrap items-center justify-center gap-1"
        >
            {links.map((link, index) =>
                link.url ? (
                    <Link
                        key={index}
                        href={link.url}
                        preserveScroll
                        aria-current={link.active ? 'page' : undefined}
                        className={cn(
                            'min-w-9 rounded-md border px-3 py-1.5 text-center text-sm',
                            link.active
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'hover:bg-accent',
                        )}
                    >
                        {cleanLabel(link.label)}
                    </Link>
                ) : (
                    <span
                        key={index}
                        className="min-w-9 px-3 py-1.5 text-center text-sm text-muted-foreground"
                    >
                        {cleanLabel(link.label)}
                    </span>
                ),
            )}
        </nav>
    );
}
