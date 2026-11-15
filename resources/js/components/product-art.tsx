import {
    BatteryCharging,
    Cable,
    Camera,
    Gamepad2,
    HardDrive,
    Headphones,
    Keyboard,
    Laptop,
    Monitor,
    Mouse,
    Package,
    Plug,
    Router,
    Smartphone,
    Speaker,
    Tablet,
    Tv,
    Watch,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * A thin-line device glyph on a grey tile, used wherever a product or category has no photograph.
 * First match wins, so "Ponsel & Tablet" is a phone and "Aksesori Komputer" is a cable.
 */
const kinds: [RegExp, LucideIcon][] = [
    [/\baksesori\b/i, Cable],
    [/\b(ponsel|smartphone|phone)\b/i, Smartphone],
    [/\b(tablet|pad)\b/i, Tablet],
    [/\b(laptop|notebook|komputer)\b/i, Laptop],
    [/\bmonitor\b/i, Monitor],
    [/\b(tv|televisi)\b/i, Tv],
    [/\bkeyboard\b/i, Keyboard],
    [/\bmouse\b/i, Mouse],
    [/\b(headphones?|headset|earbuds|tws|audio)\b/i, Headphones],
    [/\bspeaker\b/i, Speaker],
    [/\b(jam|watch|smartband)\b/i, Watch],
    [/\b(kamera|camera|webcam)\b/i, Camera],
    [/\b(power ?bank|baterai)\b/i, BatteryCharging],
    [/\b(charger|adaptor)\b/i, Plug],
    [/\b(kabel|cable|usb)\b/i, Cable],
    [/\b(ssd|hard ?disk|flash ?disk)\b/i, HardDrive],
    [/\b(router|wi-?fi)\b/i, Router],
    [/\b(konsol|gamepad|controller)\b/i, Gamepad2],
];

const swatches: Record<string, string> = {
    hitam: '#1d1d1f',
    putih: '#fafafa',
    silver: '#d1d1d6',
    abu: '#8e8e93',
    biru: '#3b6ea8',
    hijau: '#4f7a62',
    emas: '#d9c6a0',
    merah: '#b3423a',
    ungu: '#7d6aa8',
};

/** The first colour word in a variant label such as "256 GB / Hitam". */
export function colorFromLabel(label: string | null): string | null {
    return (
        label
            ?.split('/')
            .map((part) => part.trim())
            .find((part) => part.toLowerCase() in swatches) ?? null
    );
}

export default function ProductArt({
    name,
    color,
    className,
}: {
    name: string;
    color?: string | null;
    className?: string;
}) {
    const Icon = kinds.find(([pattern]) => pattern.test(name))?.[1] ?? Package;
    const fill = color ? swatches[color.toLowerCase()] : undefined;

    return (
        <div
            role="img"
            aria-label={name}
            className={cn(
                'relative grid place-items-center rounded-2xl bg-surface text-foreground/75',
                className,
            )}
        >
            <Icon aria-hidden strokeWidth={1} className="size-2/5" />
            {fill && (
                <span
                    aria-hidden
                    className="absolute bottom-[7%] size-4 rounded-full ring-1 ring-foreground/20"
                    style={{ backgroundColor: fill }}
                />
            )}
        </div>
    );
}
