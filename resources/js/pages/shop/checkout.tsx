import { Form, Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import CheckoutController from '@/actions/App/Http/Controllers/Shop/CheckoutController';
import InputError from '@/components/input-error';
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
        <section className="bg-surface p-5 md:p-6">
            <div className="mb-3 flex items-center justify-between">
                <h2 className="text-body font-semibold">{title}</h2>
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
            <h1 className="mb-10 text-head font-display">Checkout</h1>

            {priceChanges.length > 0 && (
                <div role="status" className="mb-4 rounded-2xl bg-accent p-5">
                    <h2 className="text-body font-semibold">
                        Harga berubah sejak ditambahkan ke keranjang
                    </h2>
                    <ul className="mt-1 list-disc pl-5 text-body">
                        {priceChanges.map((message) => (
                            <li key={message}>{message}</li>
                        ))}
                    </ul>
                </div>
            )}

            <Form
                {...CheckoutController.store.form()}
                options={{ preserveScroll: true }}
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6 lg:grid-cols-[1fr_380px]">
                        <div className="space-y-2">
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
                                        className="text-body underline"
                                    >
                                        Ubah
                                    </Link>
                                }
                            >
                                <p className="text-body font-semibold">
                                    {address.recipient_name} · {address.phone}
                                </p>
                                <p className="text-body text-soft">
                                    {address.address_line}, {address.city},{' '}
                                    {address.province} {address.postal_code}
                                </p>
                                <InputError message={errors.address} />
                            </Section>

                            <Section title={`Pengiriman — ${courier}`}>
                                <p className="mb-3 text-small text-soft">
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
                                                'p-4 text-left transition-colors',
                                                selection.shipping_service ===
                                                    service.key
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'bg-background hover:bg-accent',
                                            )}
                                        >
                                            <p className="text-body font-semibold">
                                                {service.name}
                                            </p>
                                            <p className="text-small">
                                                Estimasi {service.eta}
                                            </p>
                                            <p className="mt-2 text-lead font-semibold tabular-nums">
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
                                            className="flex items-center gap-3 text-body"
                                        >
                                            <input
                                                type="radio"
                                                name="payment_method"
                                                value={method.value}
                                                defaultChecked={index === 0}
                                                className="size-4 accent-[var(--brand)]"
                                            />
                                            {method.label}
                                        </label>
                                    ))}
                                </div>
                                <p className="mt-3 text-small text-soft">
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
                                        variant="secondary"
                                        className="h-10 bg-background hover:bg-accent"
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
                                    <p className="mt-3 text-body font-semibold text-warning">
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

                        <aside className="h-fit space-y-4 bg-surface p-6 lg:sticky lg:top-24">
                            <h2 className="text-lead font-semibold">
                                Ringkasan
                            </h2>
                            <ul className="space-y-1 text-body">
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
                            <dl className="space-y-1 bg-background p-4 text-body tabular-nums">
                                <div className="flex justify-between">
                                    <dt>Subtotal</dt>
                                    <dd>{formatRupiah(totals.subtotal)}</dd>
                                </div>
                                {totals.discount > 0 && (
                                    <div className="flex justify-between text-warning">
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
                                <div className="flex items-baseline justify-between pt-2 text-lead font-semibold">
                                    <dt>Total</dt>
                                    <dd>{formatRupiah(totals.grand_total)}</dd>
                                </div>
                            </dl>

                            <label className="flex items-start gap-3 text-body">
                                <input
                                    type="checkbox"
                                    name="terms"
                                    value="1"
                                    className="mt-1 size-4 accent-[var(--brand)]"
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
                                size="xl"
                                className="w-full"
                                disabled={processing}
                            >
                                {processing ? 'Memproses…' : 'Buat pesanan'}
                            </Button>
                            <p className="text-small text-soft">
                                Stok direservasi selama {paymentDueHours} jam
                                menunggu pembayaran.
                            </p>
                            <Link
                                href={cartIndex()}
                                className="block text-center text-body underline"
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
