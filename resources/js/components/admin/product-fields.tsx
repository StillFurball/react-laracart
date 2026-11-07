import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';

export type ProductFieldValues = {
    name?: string;
    slug?: string;
    category_id?: number;
    description?: string | null;
    status?: string;
    weight_gram?: number;
};

export const productStatusLabels: Record<string, string> = {
    draft: 'Draft',
    active: 'Aktif',
    archived: 'Arsip',
};

export function Field({
    label,
    name,
    error,
    children,
    hint,
}: {
    label: string;
    name: string;
    error?: string;
    children: React.ReactNode;
    hint?: string;
}) {
    return (
        <div className="grid gap-1.5">
            <Label htmlFor={name}>{label}</Label>
            {children}
            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}

/**
 * Product-level inputs shared by the create and edit forms.
 */
export default function ProductFields({
    values = {},
    categories,
    statuses,
    errors,
}: {
    values?: ProductFieldValues;
    categories: { id: number; name: string }[];
    statuses: string[];
    errors: Record<string, string>;
}) {
    return (
        <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Nama produk" name="name" error={errors.name}>
                <Input
                    id="name"
                    name="name"
                    required
                    defaultValue={values.name}
                />
            </Field>
            <Field
                label="Slug"
                name="slug"
                error={errors.slug}
                hint="Kosongkan untuk dibuat otomatis dari nama."
            >
                <Input id="slug" name="slug" defaultValue={values.slug} />
            </Field>
            <Field
                label="Kategori"
                name="category_id"
                error={errors.category_id}
            >
                <NativeSelect
                    id="category_id"
                    name="category_id"
                    required
                    defaultValue={values.category_id ?? ''}
                >
                    <option value="" disabled>
                        Pilih kategori
                    </option>
                    {categories.map((category) => (
                        <option key={category.id} value={category.id}>
                            {category.name}
                        </option>
                    ))}
                </NativeSelect>
            </Field>
            <Field label="Status" name="status" error={errors.status}>
                <NativeSelect
                    id="status"
                    name="status"
                    defaultValue={values.status ?? 'draft'}
                >
                    {statuses.map((status) => (
                        <option key={status} value={status}>
                            {productStatusLabels[status] ?? status}
                        </option>
                    ))}
                </NativeSelect>
            </Field>
            <Field
                label="Berat default (gram)"
                name="weight_gram"
                error={errors.weight_gram}
            >
                <Input
                    id="weight_gram"
                    name="weight_gram"
                    type="number"
                    min={1}
                    required
                    defaultValue={values.weight_gram}
                />
            </Field>
            <div className="sm:col-span-2">
                <Field
                    label="Deskripsi"
                    name="description"
                    error={errors.description}
                >
                    <Textarea
                        id="description"
                        name="description"
                        rows={5}
                        defaultValue={values.description ?? ''}
                    />
                </Field>
            </div>
        </div>
    );
}
