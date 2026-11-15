import { cn } from '@/lib/utils';
import type { OrderStatus } from '@/types';

/** One marker colour per phase: waiting for payment, in progress, done, stopped. */
const markers: Record<OrderStatus, string> = {
    pending_payment: 'bg-highlight',
    paid: 'bg-foreground',
    processing: 'bg-foreground',
    shipped: 'bg-foreground',
    delivered: 'bg-foreground',
    completed: 'bg-success',
    expired: 'bg-muted-foreground',
    cancelled: 'bg-muted-foreground',
    return_requested: 'bg-muted-foreground',
    refunded: 'bg-muted-foreground',
};

export default function OrderStatusBadge({
    status,
    label,
}: {
    status: OrderStatus;
    label: string;
}) {
    return (
        <span className="inline-flex items-center gap-2 text-sm font-medium whitespace-nowrap">
            <span
                aria-hidden
                className={cn(
                    'size-2.5 shrink-0 rounded-full',
                    markers[status],
                )}
            />
            {label}
        </span>
    );
}
