import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
import InputError from '@/components/input-error';
import OrderDetail from '@/components/order-detail';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import { index } from '@/routes/admin/orders';
import type { OrderDetail as OrderDetailData, Option } from '@/types';

type AdminOrder = OrderDetailData & {
    internal_note: string | null;
    customer: { name: string; email: string; phone: string | null };
};

export default function AdminOrderShow({
    order,
    transitions,
}: {
    order: AdminOrder;
    transitions: Option[];
}) {
    const [nextStatus, setNextStatus] = useState(transitions[0]?.value ?? '');
    const [statusShown, setStatusShown] = useState(order.status);

    // Reset the picker when the order moves on, otherwise it keeps a now-invalid option.
    if (statusShown !== order.status) {
        setStatusShown(order.status);
        setNextStatus(transitions[0]?.value ?? '');
    }

    return (
        <>
            <Head title={`Pesanan ${order.order_number}`} />

            <div className="grid gap-4 p-4 xl:grid-cols-[1fr_320px]">
                <OrderDetail order={order} />

                <aside className="space-y-4">
                    <section className="rounded-lg border p-4 text-sm">
                        <h2 className="mb-2 font-semibold">Pelanggan</h2>
                        <p>{order.customer.name}</p>
                        <p className="text-muted-foreground">
                            {order.customer.email}
                        </p>
                        {order.customer.phone && (
                            <p className="text-muted-foreground">
                                {order.customer.phone}
                            </p>
                        )}
                    </section>

                    <section className="rounded-lg border p-4">
                        <h2 className="mb-3 font-semibold">Ubah status</h2>
                        {transitions.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                {order.status === 'pending_payment'
                                    ? 'Menunggu pembayaran pelanggan.'
                                    : 'Tidak ada aksi lanjutan untuk status ini.'}
                            </p>
                        ) : (
                            <Form
                                {...OrderController.updateStatus.form(
                                    order.order_number,
                                )}
                                options={{ preserveScroll: true }}
                                resetOnSuccess
                                className="space-y-3"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="status">
                                                Status berikutnya
                                            </Label>
                                            <NativeSelect
                                                id="status"
                                                name="status"
                                                value={nextStatus}
                                                onChange={(event) =>
                                                    setNextStatus(
                                                        event.target.value,
                                                    )
                                                }
                                            >
                                                {transitions.map(
                                                    (transition) => (
                                                        <option
                                                            key={
                                                                transition.value
                                                            }
                                                            value={
                                                                transition.value
                                                            }
                                                        >
                                                            {transition.label}
                                                        </option>
                                                    ),
                                                )}
                                            </NativeSelect>
                                            <InputError
                                                message={errors.status}
                                            />
                                        </div>

                                        {nextStatus === 'shipped' && (
                                            <div className="grid gap-2">
                                                <Label htmlFor="tracking_number">
                                                    Nomor resi
                                                </Label>
                                                <Input
                                                    id="tracking_number"
                                                    name="tracking_number"
                                                    required
                                                />
                                                <InputError
                                                    message={
                                                        errors.tracking_number
                                                    }
                                                />
                                            </div>
                                        )}

                                        <div className="grid gap-2">
                                            <Label htmlFor="note">
                                                Catatan riwayat (opsional)
                                            </Label>
                                            <Input id="note" name="note" />
                                        </div>

                                        <Button
                                            disabled={processing}
                                            variant={
                                                nextStatus === 'cancelled'
                                                    ? 'destructive'
                                                    : 'default'
                                            }
                                            className="w-full"
                                        >
                                            Simpan status
                                        </Button>
                                    </>
                                )}
                            </Form>
                        )}
                    </section>

                    <section className="rounded-lg border p-4">
                        <h2 className="mb-3 font-semibold">Catatan internal</h2>
                        <Form
                            {...OrderController.updateNote.form(
                                order.order_number,
                            )}
                            options={{ preserveScroll: true }}
                            className="space-y-2"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <Textarea
                                        name="internal_note"
                                        defaultValue={order.internal_note ?? ''}
                                        aria-label="Catatan internal"
                                        placeholder="Hanya terlihat oleh admin"
                                    />
                                    <InputError
                                        message={errors.internal_note}
                                    />
                                    <Button
                                        variant="outline"
                                        disabled={processing}
                                        className="w-full"
                                    >
                                        Simpan catatan
                                    </Button>
                                </>
                            )}
                        </Form>
                    </section>
                </aside>
            </div>
        </>
    );
}

AdminOrderShow.layout = {
    breadcrumbs: [{ title: 'Pesanan', href: index() }],
};
