import { Form, Head, Link, router } from '@inertiajs/react';
import { Info } from 'lucide-react';
import { useState } from 'react';
import CheckoutController from '@/actions/App/Http/Controllers/Shop/CheckoutController';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { cn, formatRupiah } from '@/lib/utils';
import { edit as addressEdit } from '@/routes/address';
import { index as cartIndex } from '@/routes/cart';
import { show as checkoutShow } from '@/routes/checkout';
import type { CartLine, Option, ShippingAddress } from '@/types';

type Service = { key: string; name: string; eta: string; cost: number };

type Totals = {
    subtotal: number;
    discount: number;
    shipping_cost: number;
    tax_total: number;
    grand_total: number;
};

function Section({
    title,
    children,
    action,
}: {
    title: string;
    children: React.ReactNode;
    action?: React.ReactNode;
}) {
    return (
        <section className="rounded-lg border p-4">
            <div className="mb-3 flex items-center justify-between">
                <h2 className="font-semibold">{title}</h2>
                {action}
            </div>
            {children}
        </section>
    );
}

export default function Checkout({
    items,
    address,
    services,
    courier,
    paymentMethods,
    selection,
    voucherError,
    appliedVoucher,
    totals,
    weightGram,
    priceChanges,
    idempotencyKey,
    paymentDueHours,
}: {
    items: CartLine[];
    address: ShippingAddress;
    services: Service[];
    courier: string;
    paymentMethods: Option[];
    selection: { shipping_service: string; voucher_code: string };
    voucherError: string | null;
    appliedVoucher: string | null;
    totals: Totals;
    weightGram: number;
    priceChanges: string[];
    idempotencyKey: string;
    paymentDueHours: number;
}) {
    const [voucherInput, setVoucherInput] = useState(selection.voucher_code);

    const preview = (changes: Partial<typeof selection>) => {
        const next = { ...selection, ...changes };

        router.get(
            checkoutShow({
                query: {
                    shipping_service: next.shipping_service,
                    voucher_code: next.voucher_code || undefined,
                },
            }).url,
            {},
            { preserveScroll: true, preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Checkout" />
            <h1 className="mb-4 text-2xl font-semibold">Checkout</h1>

            {priceChanges.length > 0 && (
                <Alert className="mb-4">
                    <Info />
                    <AlertTitle>
                        Harga berubah sejak ditambahkan ke keranjang
                    </AlertTitle>
                    <AlertDescription>
                        <ul className="list-disc pl-4">
                            {priceChanges.map((message) => (
                                <li key={message}>{message}</li>
                            ))}
                        </ul>
                    </AlertDescription>
                </Alert>
            )}

            <Form
                {...CheckoutController.store.form()}
                options={{ preserveScroll: true }}
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6 lg:grid-cols-[1fr_340px]">
                        <div className="space-y-4">
                            <input
                                type="hidden"
                                name="idempotency_key"
                                value={idempotencyKey}
                            />
                            <input
                                type="hidden"
                                name="expected_total"
                                value={totals.grand_total}
                            />
                            <input
                                type="hidden"
                                name="shipping_service"
                                value={selection.shipping_service}
                            />
                            <input
                                type="hidden"
                                name="voucher_code"
                                value={appliedVoucher ?? ''}
                            />

                            <Section
                                title="Alamat pengiriman"
                                action={
                                    <Link
                                        href={addressEdit()}
                                        className="text-sm underline"
                                    >
                                        Ubah
                                    </Link>
                                }
                            >
                                <p className="font-medium">
                                    {address.recipient_name} · {address.phone}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {address.address_line}, {address.city},{' '}
                                    {address.province} {address.postal_code}
                                </p>
                                <InputError message={errors.address} />
                            </Section>

                            <Section title={`Pengiriman — ${courier}`}>
                                <p className="mb-2 text-xs text-muted-foreground">
                                    Berat total{' '}
                                    {(weightGram / 1000).toLocaleString(
                                        'id-ID',
                                    )}{' '}
                                    kg (dibulatkan ke atas per kg).
                                </p>
                                <div
                                    className="grid gap-2 sm:grid-cols-2"
                                    role="radiogroup"
                                    aria-label="Layanan pengiriman"
                                >
                                    {services.map((service) => (
                                        <button
                                            key={service.key}
                                            type="button"
                                            role="radio"
                                            aria-checked={
                                                selection.shipping_service ===
                                                service.key
                                            }
                                            onClick={() =>
                                                preview({
                                                    shipping_service:
                                                        service.key,
                                                })
                                            }
                                            className={cn(
                                                'rounded-md border p-3 text-left',
                                                selection.shipping_service ===
                                                    service.key
                                                    ? 'border-primary ring-2 ring-primary/30'
                                                    : 'hover:bg-accent',
                                            )}
                                        >
                                            <p className="font-medium">
                                                {service.name}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                Estimasi {service.eta}
                                            </p>
                                            <p className="mt-1 text-sm font-semibold">
                                                {formatRupiah(service.cost)}
                                            </p>
                                        </button>
                                    ))}
                                </div>
                                <InputError message={errors.shipping_service} />
                            </Section>

                            <Section title="Metode pembayaran (simulasi)">
                                <div className="space-y-2">
                                    {paymentMethods.map((method, index) => (
                                        <label
                                            key={method.value}
                                            className="flex items-center gap-2 text-sm"
                                        >
                                            <input
                                                type="radio"
                                                name="payment_method"
                                                value={method.value}
                                                defaultChecked={index === 0}
                                                className="accent-primary"
                                            />
                                            {method.label}
                                        </label>
                                    ))}
                                </div>
                                <p className="mt-2 text-xs text-muted-foreground">
                                    Tidak ada pembayaran sungguhan. Data kartu
                                    tidak pernah diminta.
                                </p>
                                <InputError message={errors.payment_method} />
                            </Section>

                            <Section title="Voucher">
                                <div className="flex gap-2">
                                    <Input
                                        aria-label="Kode voucher"
                                        placeholder="Masukkan kode voucher"
                                        value={voucherInput}
                                        onChange={(event) =>
                                            setVoucherInput(
                                                event.target.value.toUpperCase(),
                                            )
                                        }
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() =>
                                            preview({
                                                voucher_code:
                                                    voucherInput.trim(),
                                            })
                                        }
                                    >
                                        Terapkan
                                    </Button>
                                    {appliedVoucher && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            onClick={() => {
                                                setVoucherInput('');
                                                preview({ voucher_code: '' });
                                            }}
                                        >
                                            Hapus
                                        </Button>
                                    )}
                                </div>
                                {appliedVoucher && (
                                    <p className="mt-2 text-sm text-emerald-600">
                                        Voucher {appliedVoucher} dipakai: hemat{' '}
                                        {formatRupiah(totals.discount)}.
                                    </p>
                                )}
                                <InputError
                                    className="mt-2"
                                    message={
                                        voucherError ?? errors.voucher_code
                                    }
                                />
                            </Section>

                            <Section title="Catatan untuk penjual (opsional)">
                                <Textarea
                                    name="customer_note"
                                    maxLength={500}
                                    aria-label="Catatan untuk penjual"
                                />
                                <InputError message={errors.customer_note} />
                            </Section>
                        </div>

                        <aside className="h-fit space-y-3 rounded-lg border p-4 lg:sticky lg:top-24">
                            <h2 className="font-semibold">Ringkasan</h2>
                            <ul className="space-y-1 text-sm">
                                {items.map((item) => (
                                    <li
                                        key={item.id}
                                        className="flex justify-between gap-2"
                                    >
                                        <span className="truncate">
                                            {item.quantity}× {item.product_name}
                                            {item.variant_label &&
                                                ` (${item.variant_label})`}
                                        </span>
                                        <span>
                                            {formatRupiah(item.line_total)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                            <dl className="space-y-1 border-t pt-3 text-sm">
                                <div className="flex justify-between">
                                    <dt>Subtotal</dt>
                                    <dd>{formatRupiah(totals.subtotal)}</dd>
                                </div>
                                {totals.discount > 0 && (
                                    <div className="flex justify-between text-emerald-600">
                                        <dt>Diskon</dt>
                                        <dd>
                                            −{formatRupiah(totals.discount)}
                                        </dd>
                                    </div>
                                )}
                                <div className="flex justify-between">
                                    <dt>Ongkir</dt>
                                    <dd>
                                        {formatRupiah(totals.shipping_cost)}
                                    </dd>
                                </div>
                                <div className="flex justify-between border-t pt-2 text-base font-semibold">
                                    <dt>Total</dt>
                                    <dd>{formatRupiah(totals.grand_total)}</dd>
                                </div>
                            </dl>

                            <label className="flex items-start gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    name="terms"
                                    value="1"
                                    className="mt-1 accent-primary"
                                />
                                <span>
                                    Saya memahami ini adalah toko demo dan
                                    menyetujui Syarat &amp; Ketentuan demo.
                                </span>
                            </label>
                            <InputError message={errors.terms} />
                            <InputError
                                message={
                                    errors.expected_total ??
                                    errors.cart ??
                                    errors.stock
                                }
                            />

                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                            >
                                {processing ? 'Memproses…' : 'Buat pesanan'}
                            </Button>
                            <p className="text-xs text-muted-foreground">
                                Stok direservasi selama {paymentDueHours} jam
                                menunggu pembayaran.
                            </p>
                            <Link
                                href={cartIndex()}
                                className="block text-center text-sm underline"
                            >
                                Kembali ke keranjang
                            </Link>
                        </aside>
                    </div>
                )}
            </Form>
        </>
    );
}
