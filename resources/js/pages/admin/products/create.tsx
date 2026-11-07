import { Form, Head } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import ProductFields, { Field } from '@/components/admin/product-fields';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, index } from '@/routes/admin/products';

export default function AdminProductCreate({
    categories,
    statuses,
}: {
    categories: { id: number; name: string }[];
    statuses: string[];
}) {
    return (
        <>
            <Head title="Produk baru" />

            <div className="max-w-3xl space-y-6 p-4">
                <Heading
                    title="Produk baru"
                    description="Produk dibuat bersama varian pertamanya. Varian lain dan gambar ditambahkan setelah disimpan."
                />

                <Form {...ProductController.store.form()} className="space-y-6">
                    {({ processing, errors }) => (
                        <>
                            <ProductFields
                                categories={categories}
                                statuses={statuses}
                                errors={errors}
                            />

                            <fieldset className="rounded-lg border p-4">
                                <legend className="px-1 text-sm font-semibold">
                                    Varian pertama
                                </legend>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field
                                        label="SKU"
                                        name="sku"
                                        error={errors.sku}
                                    >
                                        <Input id="sku" name="sku" required />
                                    </Field>
                                    <Field
                                        label="Stok awal"
                                        name="stock"
                                        error={errors.stock}
                                    >
                                        <Input
                                            id="stock"
                                            name="stock"
                                            type="number"
                                            min={0}
                                            required
                                            defaultValue={0}
                                        />
                                    </Field>
                                    <Field
                                        label="Harga (Rp)"
                                        name="price"
                                        error={errors.price}
                                    >
                                        <Input
                                            id="price"
                                            name="price"
                                            type="number"
                                            min={1}
                                            required
                                        />
                                    </Field>
                                    <Field
                                        label="Harga coret (Rp, opsional)"
                                        name="compare_at_price"
                                        error={errors.compare_at_price}
                                    >
                                        <Input
                                            id="compare_at_price"
                                            name="compare_at_price"
                                            type="number"
                                            min={1}
                                        />
                                    </Field>
                                </div>
                            </fieldset>

                            <Button disabled={processing}>Simpan produk</Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

AdminProductCreate.layout = {
    breadcrumbs: [
        { title: 'Produk', href: index() },
        { title: 'Produk baru', href: create() },
    ],
};
