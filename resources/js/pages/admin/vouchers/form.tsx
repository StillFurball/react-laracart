import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import VoucherController from '@/actions/App/Http/Controllers/Admin/VoucherController';
import { Field } from '@/components/admin/product-fields';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { index } from '@/routes/admin/vouchers';
import type { VoucherData } from './index';

const typeLabels: Record<string, string> = {
    percent: 'Persen (%)',
    fixed: 'Nominal (Rp)',
};

export default function AdminVoucherForm({
    voucher,
    types,
}: {
    voucher: VoucherData | null;
    types: string[];
}) {
    const [type, setType] = useState(voucher?.type ?? 'percent');
    const action = voucher
        ? VoucherController.update.form(voucher.id)
        : VoucherController.store.form();

    return (
        <>
            <Head
                title={voucher ? `Voucher ${voucher.code}` : 'Voucher baru'}
            />

            <div className="max-w-3xl space-y-6 p-4">
                <Heading
                    title={
                        voucher
                            ? `Ubah voucher ${voucher.code}`
                            : 'Voucher baru'
                    }
                    description="Diskon dihitung dari subtotal (tidak termasuk ongkir). Satu voucher per pesanan."
                />

                <Form {...action} className="space-y-4">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field
                                    label="Kode"
                                    name="code"
                                    error={errors.code}
                                >
                                    <Input
                                        id="code"
                                        name="code"
                                        required
                                        defaultValue={voucher?.code}
                                        className="uppercase"
                                    />
                                </Field>
                                <Field
                                    label="Tipe"
                                    name="type"
                                    error={errors.type}
                                >
                                    <NativeSelect
                                        id="type"
                                        name="type"
                                        value={type}
                                        onChange={(event) =>
                                            setType(
                                                event.target
                                                    .value as VoucherData['type'],
                                            )
                                        }
                                    >
                                        {types.map((value) => (
                                            <option key={value} value={value}>
                                                {typeLabels[value] ?? value}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </Field>
                                <Field
                                    label={
                                        type === 'percent'
                                            ? 'Nilai (%)'
                                            : 'Nilai (Rp)'
                                    }
                                    name="value"
                                    error={errors.value}
                                >
                                    <Input
                                        id="value"
                                        name="value"
                                        type="number"
                                        min={1}
                                        max={
                                            type === 'percent' ? 100 : undefined
                                        }
                                        required
                                        defaultValue={voucher?.value}
                                    />
                                </Field>
                                <Field
                                    label="Maksimal potongan (Rp, opsional)"
                                    name="max_discount"
                                    error={errors.max_discount}
                                >
                                    <Input
                                        id="max_discount"
                                        name="max_discount"
                                        type="number"
                                        min={1}
                                        defaultValue={
                                            voucher?.max_discount ?? ''
                                        }
                                    />
                                </Field>
                                <Field
                                    label="Minimum belanja (Rp)"
                                    name="min_purchase"
                                    error={errors.min_purchase}
                                >
                                    <Input
                                        id="min_purchase"
                                        name="min_purchase"
                                        type="number"
                                        min={0}
                                        required
                                        defaultValue={
                                            voucher?.min_purchase ?? 0
                                        }
                                    />
                                </Field>
                                <Field
                                    label="Kuota total (opsional)"
                                    name="quota"
                                    error={errors.quota}
                                >
                                    <Input
                                        id="quota"
                                        name="quota"
                                        type="number"
                                        min={1}
                                        defaultValue={voucher?.quota ?? ''}
                                    />
                                </Field>
                                <Field
                                    label="Kuota per pelanggan (opsional)"
                                    name="per_user_limit"
                                    error={errors.per_user_limit}
                                >
                                    <Input
                                        id="per_user_limit"
                                        name="per_user_limit"
                                        type="number"
                                        min={1}
                                        defaultValue={
                                            voucher?.per_user_limit ?? ''
                                        }
                                    />
                                </Field>
                                <div />
                                <Field
                                    label="Mulai berlaku (opsional)"
                                    name="starts_at"
                                    error={errors.starts_at}
                                >
                                    <Input
                                        id="starts_at"
                                        name="starts_at"
                                        type="datetime-local"
                                        defaultValue={voucher?.starts_at ?? ''}
                                    />
                                </Field>
                                <Field
                                    label="Berakhir (opsional)"
                                    name="ends_at"
                                    error={errors.ends_at}
                                >
                                    <Input
                                        id="ends_at"
                                        name="ends_at"
                                        type="datetime-local"
                                        defaultValue={voucher?.ends_at ?? ''}
                                    />
                                </Field>
                            </div>

                            <label className="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    defaultChecked={voucher?.is_active ?? true}
                                    className="accent-primary"
                                />
                                Aktif
                            </label>

                            <Button disabled={processing}>
                                Simpan voucher
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

AdminVoucherForm.layout = {
    breadcrumbs: [{ title: 'Voucher', href: index() }],
};
