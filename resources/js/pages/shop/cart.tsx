import { Head, Link, router, usePage } from '@inertiajs/react';
import { Trash2, Zap } from 'lucide-react';
import CartItemController from '@/actions/App/Http/Controllers/Shop/CartItemController';
import InputError from '@/components/input-error';
import { ProductImage } from '@/components/product-card';
import { Button } from '@/components/ui/button';
import { NativeSelect } from '@/components/ui/native-select';
import { formatRupiah } from '@/lib/utils';
import { index as productsIndex } from '@/routes/products';
import { show as checkoutShow } from '@/routes/checkout';
import { show as productShow } from '@/routes/products';
import type { CartLine } from '@/types';

export default function Cart({
    items,
    subtotal,
    issues,
    priceChanges,
    maxPerItem,
}: {
    items: CartLine[];
    subtotal: number;
    issues: string[];
    priceChanges: string[];
    maxPerItem: number;
}) {
    const { errors } = usePage().props as { errors: Record<string, string> };

    const updateQuantity = (item: CartLine, quantity: number) => {
        router.patch(
            CartItemController.update.url(item.id),
            { quantity },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Keranjang" />
            <h1 className="mb-10 text-head font-display">Keranjang</h1>

            {priceChanges.length > 0 && (
                <div role="status" className="mb-4 bg-surface p-5">
                    <h2 className="text-body font-semibold">Harga berubah</h2>
                    <ul className="mt-1 list-disc pl-5 text-body">
                        {priceChanges.map((message) => (
                            <li key={message}>{message}</li>
                        ))}
                    </ul>
                </div>
            )}

            {issues.length > 0 && (
                <div role="alert" className="mb-4 rounded-2xl bg-accent p-5">
                    <h2 className="text-body font-semibold">
                        Perbaiki keranjang sebelum checkout
                    </h2>
                    <ul className="mt-1 list-disc pl-5 text-body">
                        {issues.map((issue) => (
                            <li key={issue}>{issue}</li>
                        ))}
                    </ul>
                </div>
            )}

            <InputError className="mb-4" message={errors.quantity} />

            {items.length === 0 ? (
                <div className="bg-surface px-6 py-16">
                    <h2 className="text-lead font-semibold">
                        Keranjang masih kosong
                    </h2>
                    <p className="mt-2 mb-6 max-w-[48ch] text-body">
                        Pilih barang di katalog. Ongkirnya nanti dihitung dari
                        total berat, per kilo.
                    </p>
                    <Button asChild>
                        <Link href={productsIndex()}>Mulai belanja</Link>
                    </Button>
                </div>
            ) : (
                <div className="grid gap-6 lg:grid-cols-[1fr_360px]">
                    <ul className="divide-y divide-border border-y border-border">
                        {items.map((item) => {
                            const max = Math.max(
                                Math.min(item.stock, maxPerItem),
                                item.quantity,
                            );

                            return (
                                <li key={item.id} className="flex gap-4 py-5">
                                    <div className="w-20 shrink-0 md:w-28">
                                        <ProductImage
                                            src={item.image}
                                            alt={item.product_name}
                                        />
                                    </div>
                                    <div className="flex flex-1 flex-col gap-1">
                                        <Link
                                            href={productShow(
                                                item.product_slug,
                                            )}
                                            className="text-body font-semibold hover:underline"
                                        >
                                            {item.product_name}
                                        </Link>
                                        <p className="text-small text-soft">
                                            {item.variant_label ?? 'Standar'} ·{' '}
                                            {item.sku}
                                        </p>
                                        <p className="flex flex-wrap items-baseline gap-x-2 text-body tabular-nums">
                                            <span
                                                className={
                                                    item.flash_sale
                                                        ? 'font-semibold text-[#007AB0]'
                                                        : undefined
                                                }
                                            >
                                                {formatRupiah(item.price)}
                                            </span>
                                            {item.flash_sale && (
                                                <>
                                                    <s className="text-small text-soft">
                                                        {formatRupiah(
                                                            item.regular_price,
                                                        )}
                                                    </s>
                                                    <span className="inline-flex items-center gap-1 text-small font-semibold text-[#007AB0]">
                                                        <Zap
                                                            aria-hidden
                                                            className="size-3.5 fill-current"
                                                        />
                                                        Flash sale
                                                    </span>
                                                </>
                                            )}
                                        </p>
                                        {item.sale_notice && (
                                            <p className="text-small font-medium text-[#007AB0]">
                                                {item.sale_notice}
                                            </p>
                                        )}
                                        {item.issue && (
                                            <p className="text-body font-semibold text-warning">
                                                {item.issue}
                                            </p>
                                        )}
                                    </div>
                                    <div className="flex flex-col items-end justify-between gap-2">
                                        <p className="text-body font-semibold tabular-nums">
                                            {formatRupiah(item.line_total)}
                                        </p>
                                        <div className="flex items-center gap-1">
                                            <label
                                                htmlFor={`qty-${item.id}`}
                                                className="sr-only"
                                            >
                                                Jumlah {item.product_name}
                                            </label>
                                            <NativeSelect
                                                id={`qty-${item.id}`}
                                                value={item.quantity}
                                                onChange={(event) =>
                                                    updateQuantity(
                                                        item,
                                                        Number(
                                                            event.target.value,
                                                        ),
                                                    )
                                                }
                                                className="w-20"
                                            >
                                                {Array.from(
                                                    {
                                                        length: Math.max(
                                                            max,
                                                            1,
                                                        ),
                                                    },
                                                    (_, index) => index + 1,
                                                ).map((value) => (
                                                    <option
                                                        key={value}
                                                        value={value}
                                                    >
                                                        {value}
                                                    </option>
                                                ))}
                                            </NativeSelect>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Hapus ${item.product_name}`}
                                                onClick={() =>
                                                    router.delete(
                                                        CartItemController.destroy.url(
                                                            item.id,
                                                        ),
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                <Trash2 />
                                            </Button>
                                        </div>
                                    </div>
                                </li>
                            );
                        })}
                    </ul>

                    <aside className="h-fit space-y-4 bg-surface p-6 lg:sticky lg:top-24">
                        <div className="flex items-baseline justify-between text-body">
                            <span>Subtotal</span>
                            <span className="text-lead font-semibold tabular-nums">
                                {formatRupiah(subtotal)}
                            </span>
                        </div>
                        <p className="text-small text-soft">
                            Ongkir (per kilo, dibulatkan ke atas) dan voucher
                            dihitung saat checkout.
                        </p>
                        <Button
                            size="xl"
                            className="w-full"
                            disabled={issues.length > 0}
                            asChild={issues.length === 0}
                        >
                            {issues.length === 0 ? (
                                <Link href={checkoutShow()}>
                                    Lanjut ke checkout
                                </Link>
                            ) : (
                                <span>Lanjut ke checkout</span>
                            )}
                        </Button>
                    </aside>
                </div>
            )}
        </>
    );
}
