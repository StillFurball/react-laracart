import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import CategoryController from '@/actions/App/Http/Controllers/Admin/CategoryController';
import { Field } from '@/components/admin/product-fields';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { edit, index } from '@/routes/admin/categories';

type Row = {
    id: number;
    name: string;
    slug: string;
    products_count: number;
    children_count: number;
    image: string | null;
};

export function CategoryFormFields({
    errors,
    parentOptions,
    values,
}: {
    errors: Record<string, string>;
    parentOptions: { id: number; name: string }[];
    values?: { name: string; slug: string; parent_id: number | null };
}) {
    return (
        <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Nama" name="name" error={errors.name}>
                <Input
                    id="name"
                    name="name"
                    required
                    defaultValue={values?.name}
                />
            </Field>
            <Field
                label="Slug"
                name="slug"
                error={errors.slug}
                hint="Kosongkan untuk dibuat otomatis."
            >
                <Input id="slug" name="slug" defaultValue={values?.slug} />
            </Field>
            <Field
                label="Induk kategori"
                name="parent_id"
                error={errors.parent_id}
                hint="Maksimal 3 level."
            >
                <NativeSelect
                    id="parent_id"
                    name="parent_id"
                    defaultValue={values?.parent_id ?? ''}
                >
                    <option value="">— Kategori utama —</option>
                    {parentOptions.map((option) => (
                        <option key={option.id} value={option.id}>
                            {option.name}
                        </option>
                    ))}
                </NativeSelect>
            </Field>
            <Field label="Gambar (opsional)" name="image" error={errors.image}>
                <Input
                    id="image"
                    name="image"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                />
            </Field>
        </div>
    );
}

export default function AdminCategoriesIndex({
    categories,
    parentOptions,
}: {
    categories: Row[];
    parentOptions: { id: number; name: string }[];
}) {
    const { errors } = usePage().props as { errors: Record<string, string> };

    return (
        <>
            <Head title="Kategori" />

            <div className="max-w-4xl space-y-6 p-4">
                <Heading
                    title="Kategori"
                    description="Kategori bertingkat hingga 3 level."
                />

                <section className="rounded-lg border p-4">
                    <h2 className="mb-3 font-semibold">Kategori baru</h2>
                    <Form
                        {...CategoryController.store.form()}
                        resetOnSuccess
                        className="space-y-4"
                    >
                        {({ processing, errors: formErrors }) => (
                            <>
                                <CategoryFormFields
                                    errors={formErrors}
                                    parentOptions={parentOptions}
                                />
                                <Button disabled={processing}>
                                    Simpan kategori
                                </Button>
                            </>
                        )}
                    </Form>
                </section>

                <InputError message={errors.category} />

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="p-3 font-medium">Kategori</th>
                                <th className="p-3 font-medium">Slug</th>
                                <th className="p-3 text-right font-medium">
                                    Produk
                                </th>
                                <th className="p-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {categories.map((category) => (
                                <tr key={category.id}>
                                    <td className="p-3">
                                        <Link
                                            href={edit(category.id)}
                                            className="font-medium hover:underline"
                                        >
                                            {category.name}
                                        </Link>
                                    </td>
                                    <td className="p-3 text-muted-foreground">
                                        {category.slug}
                                    </td>
                                    <td className="p-3 text-right">
                                        {category.products_count}
                                    </td>
                                    <td className="p-3 text-right">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            aria-label={`Hapus ${category.name}`}
                                            disabled={
                                                category.products_count > 0 ||
                                                category.children_count > 0
                                            }
                                            onClick={() => {
                                                if (
                                                    window.confirm(
                                                        `Hapus kategori ${category.name}?`,
                                                    )
                                                ) {
                                                    router.delete(
                                                        CategoryController.destroy.url(
                                                            category.id,
                                                        ),
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    );
                                                }
                                            }}
                                        >
                                            <Trash2 />
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

AdminCategoriesIndex.layout = {
    breadcrumbs: [{ title: 'Kategori', href: index() }],
};
