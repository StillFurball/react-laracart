import { Head, Link, router, usePage } from '@inertiajs/react';
import { AlertTriangle, Info, Trash2 } from 'lucide-react';
import CartItemController from '@/actions/App/Http/Controllers/Shop/CartItemController';
import InputError from '@/components/input-error';
import { ProductImage } from '@/components/product-card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { NativeSelect } from '@/components/ui/native-select';
import { formatRupiah } from '@/lib/utils';
import { home } from '@/routes';
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
            <h1 className="mb-4 text-2xl font-semibold">Keranjang</h1>

            {priceChanges.length > 0 && (
                <Alert className="mb-4">
                    <Info />
                    <AlertTitle>Harga berubah</AlertTitle>
                    <AlertDescription>
                        <ul className="list-disc pl-4">
                            {priceChanges.map((message) => (
                                <li key={message}>{message}</li>
                            ))}
                        </ul>
                    </AlertDescription>
                </Alert>
            )}

            {issues.length > 0 && (
                <Alert variant="destructive" className="mb-4">
                    <AlertTriangle />
                    <AlertTitle>Perbaiki keranjang sebelum checkout</AlertTitle>
                    <AlertDescription>
                        <ul className="list-disc pl-4">
                            {issues.map((issue) => (
                                <li key={issue}>{issue}</li>
                            ))}
                        </ul>
                    </AlertDescription>
                </Alert>
            )}

            <InputError className="mb-4" message={errors.quantity} />

            {items.length === 0 ? (
                <div className="rounded-lg border border-dashed p-10 text-center">
                    <p className="mb-4 text-muted-foreground">
                        Keranjang masih kosong.
                    </p>
                    <Button asChild>
                        <Link href={home()}>Mulai belanja</Link>
                    </Button>
                </div>
            ) : (
                <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
                    <ul className="divide-y rounded-lg border">
                        {items.map((item) => {
                            const max = Math.max(
                                Math.min(item.stock, maxPerItem),
                                item.quantity,
                            );

                            return (
                                <li key={item.id} className="flex gap-3 p-3">
                                    <div className="w-20 shrink-0">
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
                                            className="font-medium hover:underline"
                                        >
                                            {item.product_name}
                                        </Link>
                                        <p className="text-xs text-muted-foreground">
                                            {item.variant_label ?? 'Standar'} ·{' '}
                                            {item.sku}
                                        </p>
                                        <p className="text-sm">
                                            {formatRupiah(item.price)}
                                        </p>
                                        {item.issue && (
                                            <p className="text-sm font-medium text-red-600">
                                                {item.issue}
                                            </p>
                                        )}
                                    </div>
                                    <div className="flex flex-col items-end justify-between gap-2">
                                        <p className="font-semibold">
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

                    <aside className="h-fit space-y-3 rounded-lg border p-4">
                        <div className="flex justify-between">
                            <span>Subtotal</span>
                            <span className="font-semibold">
                                {formatRupiah(subtotal)}
                            </span>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            Ongkir dan voucher dihitung saat checkout.
                        </p>
                        <Button
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
