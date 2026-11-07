import { cn } from '@/lib/utils';
import type { OrderStatus } from '@/types';

const tones: Record<OrderStatus, string> = {
    pending_payment:
        'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
    paid: 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
    processing:
        'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200',
    shipped:
        'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-200',
    delivered:
        'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-200',
    completed:
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
    expired:
        'bg-neutral-200 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
    cancelled:
        'bg-neutral-200 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
    return_requested:
        'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-200',
    refunded:
        'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
};

export default function OrderStatusBadge({
    status,
    label,
}: {
    status: OrderStatus;
    label: string;
}) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap',
                tones[status],
            )}
        >
            {label}
        </span>
    );
}
