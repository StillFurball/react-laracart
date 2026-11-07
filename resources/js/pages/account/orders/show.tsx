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
        <section className="rounded-lg border-2 border-dashed border-amber-400 bg-amber-50 p-4 dark:bg-amber-950/30">
            <div className="mb-2 flex items-center gap-2">
                <FlaskConical className="size-5 text-amber-600" />
                <h2 className="font-semibold">
                    Pembayaran simulasi (mode demo)
                </h2>
            </div>
            <p className="text-sm">
                Bayar <strong>{formatRupiah(order.grand_total)}</strong> sebelum{' '}
                <strong>{formatDateTime(order.payment_due_at)}</strong> via{' '}
                {order.payment_method_label}.
            </p>
            <p className="mb-3 text-xs text-muted-foreground">
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

export default function OrderShow({ order }: { order: OrderDetailData }) {
    return (
        <>
            <Head title={`Pesanan ${order.order_number}`} />

            <div className="max-w-4xl space-y-4 p-4">
                {order.status === 'pending_payment' && (
                    <PaymentSimulator order={order} />
                )}

                <OrderDetail order={order} />

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
                                <Button disabled={processing}>
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
