import { Form, Head } from '@inertiajs/react';
import AddressController from '@/actions/App/Http/Controllers/Shop/AddressController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { edit } from '@/routes/address';
import type { ShippingAddress } from '@/types';

const fields: {
    name: keyof ShippingAddress;
    label: string;
    autoComplete: string;
    inputMode?: 'tel' | 'numeric';
}[] = [
    { name: 'recipient_name', label: 'Nama penerima', autoComplete: 'name' },
    { name: 'phone', label: 'Nomor HP', autoComplete: 'tel', inputMode: 'tel' },
    { name: 'city', label: 'Kota/Kabupaten', autoComplete: 'address-level2' },
    { name: 'province', label: 'Provinsi', autoComplete: 'address-level1' },
    {
        name: 'postal_code',
        label: 'Kode pos',
        autoComplete: 'postal-code',
        inputMode: 'numeric',
    },
];

export default function Address({
    address,
}: {
    address: ShippingAddress | null;
}) {
    return (
        <>
            <Head title="Alamat pengiriman" />

            <div className="grid gap-10 md:grid-cols-2 lg:gap-16">
                <div className="space-y-4">
                    <h1 className="text-head font-display">
                        Alamat pengiriman
                    </h1>
                    <p className="max-w-[44ch] text-body">
                        Satu alamat dipakai untuk setiap checkout. Tarif kirim
                        tidak bergantung pada kota tujuan. Gunakan data contoh,
                        bukan data pribadi asli.
                    </p>
                </div>

                <Form
                    {...AddressController.update.form()}
                    className="space-y-5 bg-surface p-6 md:p-8"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="address_line">
                                    Alamat lengkap
                                </Label>
                                <Textarea
                                    id="address_line"
                                    name="address_line"
                                    required
                                    autoComplete="street-address"
                                    defaultValue={address?.address_line}
                                />
                                <InputError message={errors.address_line} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                {fields.map((field) => (
                                    <div
                                        key={field.name}
                                        className="grid gap-2"
                                    >
                                        <Label htmlFor={field.name}>
                                            {field.label}
                                        </Label>
                                        <Input
                                            id={field.name}
                                            name={field.name}
                                            required
                                            autoComplete={field.autoComplete}
                                            inputMode={field.inputMode}
                                            defaultValue={address?.[field.name]}
                                        />
                                        <InputError
                                            message={errors[field.name]}
                                        />
                                    </div>
                                ))}
                            </div>

                            <Button size="xl" disabled={processing}>
                                Simpan alamat
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

Address.layout = {
    breadcrumbs: [{ title: 'Alamat pengiriman', href: edit() }],
};
