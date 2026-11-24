import { Link } from '@inertiajs/react';
import { useEffect, useEffectEvent, useId, useState } from 'react';
import { ProductImage } from '@/components/product-card';
import { cn, formatRupiah } from '@/lib/utils';
import { show } from '@/routes/products';
import type { FlashSaleOffer } from '@/types';

type TimeLeft = {
    total: number;
    days: number;
    hours: number;
    minutes: number;
    seconds: number;
};

function breakdown(milliseconds: number): TimeLeft {
    const totalSeconds = Math.floor(Math.max(0, milliseconds) / 1000);

    return {
        total: Math.max(0, milliseconds),
        days: Math.floor(totalSeconds / 86400),
        hours: Math.floor((totalSeconds % 86400) / 3600),
        minutes: Math.floor((totalSeconds % 3600) / 60),
        seconds: totalSeconds % 60,
    };
}

/**
 * Time left until an ISO moment, refreshed every second. onElapsed fires once when the moment
 * passes while the page is open; it does not fire for a moment already past on mount, so a
 * reload triggered by it can never loop when the device clock runs ahead of the server.
 */
export function useCountdown(
    targetIso: string,
    onElapsed?: () => void,
): TimeLeft {
    const target = Date.parse(targetIso);
    const [now, setNow] = useState(() => Date.now());
    const notifyElapsed = useEffectEvent(() => onElapsed?.());

    useEffect(() => {
        if (Date.now() >= target) {
            return;
        }

        const timer = window.setInterval(() => {
            const current = Date.now();
            setNow(current);

            if (current >= target) {
                window.clearInterval(timer);
                notifyElapsed();
            }
        }, 1000);

        return () => window.clearInterval(timer);
    }, [target]);

    return breakdown(target - now);
}

const pad = (value: number) => String(value).padStart(2, '0');

/**
 * hh:mm:ss in small tiles (with days when more than a day is left). role="timer" is not announced
 * on every tick; the label states the time left at minute precision.
 */
export function Countdown({
    target,
    label,
    onElapsed,
    tone = 'dark',
    className,
}: {
    target: string;
    label: string;
    onElapsed?: () => void;
    tone?: 'dark' | 'brand' | 'light';
    className?: string;
}) {
    const { days, hours, minutes, seconds } = useCountdown(target, onElapsed);
    const tile = cn(
        'inline-grid min-w-8 place-items-center rounded-md px-1.5 py-1 text-sm leading-none font-bold tabular-nums',
        tone === 'dark' && 'bg-[#222] text-white',
        tone === 'brand' && 'bg-[#008ECC] text-white',
        tone === 'light' && 'bg-white text-[#222]',
    );
    const spoken = `${label} ${days > 0 ? `${days} hari ` : ''}${hours} jam ${minutes} menit`;

    return (
        <span
            role="timer"
            aria-label={spoken}
            className={cn('inline-flex items-center gap-1', className)}
        >
            {days > 0 && (
                <>
                    <span className={tile}>{days}</span>
                    <span aria-hidden className="text-sm font-semibold">
                        hari
                    </span>
                </>
            )}
            <span aria-hidden className={tile}>
                {pad(hours)}
            </span>
            <span aria-hidden className="font-bold">
                :
            </span>
            <span aria-hidden className={tile}>
                {pad(minutes)}
            </span>
            <span aria-hidden className="font-bold">
                :
            </span>
            <span aria-hidden className={tile}>
                {pad(seconds)}
            </span>
        </span>
    );
}

/**
 * How much of a quota is gone, on a native <progress>; without a quota it shows the saving instead.
 */
export function QuotaMeter({
    quota,
    sold,
    remaining,
    stock,
    saving,
}: {
    quota: number | null;
    sold: number;
    remaining: number | null;
    stock: number;
    saving: number;
}) {
    const id = useId();

    if (remaining === 0 || stock === 0) {
        return (
            <p className="text-sm font-semibold text-[#666]">Habis terjual</p>
        );
    }

    if (quota === null) {
        return (
            <p className="text-sm font-semibold text-[#249B3E] tabular-nums">
                Hemat {formatRupiah(saving)}
            </p>
        );
    }

    return (
        <div>
            <progress
                className="quota-progress"
                max={quota}
                value={sold}
                aria-labelledby={id}
            />
            <p id={id} className="mt-1.5 text-xs text-[#666] tabular-nums">
                Terjual {sold} · sisa {Math.min(remaining ?? stock, stock)}
            </p>
        </div>
    );
}

const cardHover =
    'border border-transparent transition-[border-color,box-shadow] hover:border-[#008ECC] hover:shadow-[0_6px_13px_rgba(0,0,0,0.05),0_24px_24px_rgba(0,0,0,0.04)]';

export function FlashSaleOfferCard({
    offer,
    upcoming = false,
}: {
    offer: FlashSaleOffer;
    upcoming?: boolean;
}) {
    const soldOut = !upcoming && (offer.remaining === 0 || offer.stock === 0);

    return (
        <Link
            href={show(offer.product_slug)}
            prefetch
            className={cn(
                'relative flex flex-col overflow-hidden rounded-2xl bg-[#F5F5F5]',
                cardHover,
            )}
        >
            <ProductImage
                src={offer.image}
                alt={offer.product_name}
                className={cn(
                    'h-[160px] rounded-none bg-transparent object-contain p-2 md:h-[188px]',
                    soldOut && 'opacity-50',
                )}
            />
            <span className="absolute top-0 right-0 grid h-[53px] w-[51px] place-items-center rounded-tr-2xl rounded-bl-2xl bg-[#008ECC] px-1 text-center text-sm leading-4 font-semibold text-white">
                {offer.discount_percent}% OFF
            </span>
            <div className="flex flex-1 flex-col gap-2.5 rounded-b-[15px] bg-white px-3 pt-3 pb-3">
                <div className="min-w-0">
                    <h3 className="truncate text-base leading-[18px] font-semibold text-[#222]">
                        {offer.product_name}
                    </h3>
                    {offer.variant_label && (
                        <p className="mt-1 truncate text-sm text-[#666]">
                            {offer.variant_label}
                        </p>
                    )}
                </div>
                <p className="flex flex-wrap items-baseline gap-x-2.5 text-base leading-[18px] text-[#222] tabular-nums">
                    <span className="font-bold">
                        {formatRupiah(offer.price)}
                    </span>
                    <s className="text-sm text-[#666]">
                        {formatRupiah(offer.regular_price)}
                    </s>
                </p>
                <div className="mt-auto border-t border-[#EDEDED] pt-2.5">
                    {upcoming ? (
                        <p className="text-sm font-semibold text-[#007AB0]">
                            Segera dimulai
                        </p>
                    ) : (
                        <QuotaMeter
                            quota={offer.quota}
                            sold={offer.sold}
                            remaining={offer.remaining}
                            stock={offer.stock}
                            saving={offer.regular_price - offer.price}
                        />
                    )}
                </div>
            </div>
        </Link>
    );
}
