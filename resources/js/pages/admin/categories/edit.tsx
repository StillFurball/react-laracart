import { Form, Head } from '@inertiajs/react';
import CategoryController from '@/actions/App/Http/Controllers/Admin/CategoryController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/admin/categories';
import { CategoryFormFields } from './index';

export default function AdminCategoryEdit({
    category,
    parentOptions,
}: {
    category: {
        id: number;
        name: string;
        slug: string;
        parent_id: number | null;
        image: string | null;
    };
    parentOptions: { id: number; name: string }[];
}) {
    return (
        <>
            <Head title={`Kategori ${category.name}`} />

            <div className="max-w-3xl space-y-6 p-4">
                <Heading title={`Ubah kategori: ${category.name}`} />

                {category.image && (
                    <img
                        src={category.image}
                        alt={category.name}
                        className="size-24 rounded-md object-cover"
                    />
                )}

                <Form
                    {...CategoryController.update.form(category.id)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <CategoryFormFields
                                errors={errors}
                                parentOptions={parentOptions}
                                values={category}
                            />
                            <Button disabled={processing}>
                                Simpan perubahan
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

AdminCategoryEdit.layout = {
    breadcrumbs: [{ title: 'Kategori', href: index() }],
};
