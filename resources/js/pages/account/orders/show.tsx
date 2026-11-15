import { Form, Head } from '@inertiajs/react';
import { FlaskConical } from 'lucide-react';
import OrderController from '@/actions/App/Http/Controllers/Shop/OrderController';
import OrderPaymentController from '@/actions/App/Http/Controllers/Shop/OrderPaymentController';
import InputError from '@/components/input-error';
import OrderDetail from '@/components/order-detail';
import { Button } from '@/components/ui/button';
import { formatDateTime, formatRupiah } from '@/lib/utils';
import { index } from '@/routes/orders';
import type { OrderDetail as OrderDetailData } from '@/types';

const outcomes = [
    { value: 'success', label: 'Bayar sukses', variant: 'default' },
    { value: 'failed', label: 'Bayar gagal', variant: 'outline' },
    { value: 'expired', label: 'Simulasikan kedaluwarsa', variant: 'ghost' },
] as const;

function PaymentSimulator({ order }: { order: OrderDetailData }) {
    return (
        <section className="bg-surface p-5 md:p-6">
            <div className="mb-2 flex items-center gap-2">
                <FlaskConical className="size-5" />
                <h2 className="text-lead font-semibold">
                    Pembayaran simulasi (mode demo)
                </h2>
            </div>
            <p className="text-sm">
                Bayar <strong>{formatRupiah(order.grand_total)}</strong> sebelum{' '}
                <strong>{formatDateTime(order.payment_due_at)}</strong> via{' '}
                {order.payment_method_label}.
            </p>
            <p className="mb-4 text-small">
                Tidak ada uang sungguhan. Pilih hasil yang ingin disimulasikan.
            </p>
            <div className="flex flex-wrap gap-2">
                {outcomes.map((outcome) => (
                    <Form
                        key={outcome.value}
                        {...OrderPaymentController.store.form(
                            order.order_number,
                        )}
                        options={{ preserveScroll: true }}
                    >
                        {({ processing, errors }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="outcome"
                                    value={outcome.value}
                                />
                                <Button
                                    variant={outcome.variant}
                                    disabled={processing}
                                >
                                    {outcome.label}
                                </Button>
                                <InputError
                                    className="mt-2"
                                    message={errors.outcome}
                                />
                            </>
                        )}
                    </Form>
                ))}
            </div>
        </section>
    );
}

function TransferInstructions({
    order,
    bankAccount,
}: {
    order: OrderDetailData;
    bankAccount: string | null;
}) {
    return (
        <section className="bg-surface p-5 md:p-6">
            <h2 className="mb-2 text-lead font-semibold">Cara membayar</h2>
            <p className="text-sm">
                Transfer <strong>{formatRupiah(order.grand_total)}</strong>{' '}
                sebelum <strong>{formatDateTime(order.payment_due_at)}</strong>{' '}
                ke <strong>{bankAccount ?? 'rekening toko'}</strong>, dengan
                berita transfer <strong>{order.order_number}</strong>.
            </p>
            <p className="text-small">
                Pesanan diproses setelah pembayaran kami verifikasi.
            </p>
        </section>
    );
}

export default function OrderShow({
    order,
    paymentSimulator,
    bankAccount,
}: {
    order: OrderDetailData;
    paymentSimulator: boolean;
    bankAccount: string | null;
}) {
    return (
        <>
            <Head title={`Pesanan ${order.order_number}`} />

            <div className="max-w-5xl space-y-4">
                {order.status === 'pending_payment' &&
                    (paymentSimulator ? (
                        <PaymentSimulator order={order} />
                    ) : (
                        <TransferInstructions
                            order={order}
                            bankAccount={bankAccount}
                        />
                    ))}

                <OrderDetail
                    order={order}
                    titleClassName="font-display text-head"
                />

                <Form
                    {...(order.status === 'delivered'
                        ? OrderController.complete.form(order.order_number)
                        : OrderController.cancel.form(order.order_number))}
                >
                    {({ processing, errors }) => (
                        <>
                            {order.status === 'pending_payment' && (
                                <Button
                                    variant="destructive"
                                    disabled={processing}
                                    onClick={(event) => {
                                        if (
                                            !window.confirm(
                                                'Batalkan pesanan ini? Stok akan dikembalikan.',
                                            )
                                        ) {
                                            event.preventDefault();
                                        }
                                    }}
                                >
                                    Batalkan pesanan
                                </Button>
                            )}
                            {order.status === 'delivered' && (
                                <Button size="xl" disabled={processing}>
                                    Pesanan sudah saya terima
                                </Button>
                            )}
                            <InputError
                                className="mt-2"
                                message={errors.status}
                            />
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

OrderShow.layout = {
    breadcrumbs: [{ title: 'Pesanan saya', href: index() }],
};
