import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { ExternalLink, Trash2 } from 'lucide-react';
import { useState } from 'react';
import ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import ProductImageController from '@/actions/App/Http/Controllers/Admin/ProductImageController';
import ProductVariantController from '@/actions/App/Http/Controllers/Admin/ProductVariantController';
import StockAdjustmentController from '@/actions/App/Http/Controllers/Admin/StockAdjustmentController';
import ProductFields, { Field } from '@/components/admin/product-fields';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatRupiah } from '@/lib/utils';
import { index } from '@/routes/admin/products';
import { show as productShow } from '@/routes/products';

type Variant = {
    id: number;
    sku: string;
    price: number;
    compare_at_price: number | null;
    stock: number;
    low_stock_threshold: number;
    weight_gram: number;
    attributes: Record<string, string>;
    label: string | null;
};

type Product = {
    id: number;
    category_id: number;
    name: string;
    slug: string;
    description: string | null;
    weight_gram: number;
    status: string;
    images: { id: number; url: string }[];
    variants: Variant[];
};

function Section({
    title,
    description,
    children,
}: {
    title: string;
    description?: string;
    children: React.ReactNode;
}) {
    return (
        <section className="space-y-4 rounded-lg border p-4">
            <div>
                <h2 className="font-semibold">{title}</h2>
                {description && (
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            {children}
        </section>
    );
}

function VariantFields({
    variant,
    errors,
    isNew,
}: {
    variant?: Variant;
    errors: Record<string, string>;
    isNew: boolean;
}) {
    const options = Object.entries(variant?.attributes ?? {});
    const prefix = variant ? `v${variant.id}-` : 'new-';

    return (
        <div className="grid gap-3 sm:grid-cols-4">
            <Field label="SKU" name={`${prefix}sku`} error={errors.sku}>
                <Input
                    id={`${prefix}sku`}
                    name="sku"
                    required
                    defaultValue={variant?.sku}
                />
            </Field>
            {[1, 2].map((number) => (
                <div
                    key={number}
                    className="grid grid-cols-2 gap-2 sm:col-span-1"
                >
                    <Field
                        label={`Opsi ${number}`}
                        name={`${prefix}option${number}_name`}
                        error={errors[`option${number}_name`]}
                    >
                        <Input
                            id={`${prefix}option${number}_name`}
                            name={`option${number}_name`}
                            placeholder={number === 1 ? 'Ukuran' : 'Warna'}
                            defaultValue={options[number - 1]?.[0]}
                        />
                    </Field>
                    <Field
                        label="Nilai"
                        name={`${prefix}option${number}_value`}
                        error={errors[`option${number}_value`]}
                    >
                        <Input
                            id={`${prefix}option${number}_value`}
                            name={`option${number}_value`}
                            placeholder={number === 1 ? 'L' : 'Hitam'}
                            defaultValue={options[number - 1]?.[1]}
                        />
                    </Field>
                </div>
            ))}
            <Field
                label="Harga (Rp)"
                name={`${prefix}price`}
                error={errors.price}
            >
                <Input
                    id={`${prefix}price`}
                    name="price"
                    type="number"
                    min={1}
                    required
                    defaultValue={variant?.price}
                />
            </Field>
            <Field
                label="Harga coret"
                name={`${prefix}compare_at_price`}
                error={errors.compare_at_price}
            >
                <Input
                    id={`${prefix}compare_at_price`}
                    name="compare_at_price"
                    type="number"
                    min={1}
                    defaultValue={variant?.compare_at_price ?? ''}
                />
            </Field>
            <Field
                label="Berat (gram)"
                name={`${prefix}weight_gram`}
                error={errors.weight_gram}
            >
                <Input
                    id={`${prefix}weight_gram`}
                    name="weight_gram"
                    type="number"
                    min={1}
                    required
                    defaultValue={variant?.weight_gram}
                />
            </Field>
            <Field
                label="Ambang stok menipis"
                name={`${prefix}low_stock_threshold`}
                error={errors.low_stock_threshold}
            >
                <Input
                    id={`${prefix}low_stock_threshold`}
                    name="low_stock_threshold"
                    type="number"
                    min={0}
                    required
                    defaultValue={variant?.low_stock_threshold ?? 5}
                />
            </Field>
            {isNew && (
                <Field
                    label="Stok awal"
                    name={`${prefix}stock`}
                    error={errors.stock}
                >
                    <Input
                        id={`${prefix}stock`}
                        name="stock"
                        type="number"
                        min={0}
                        required
                        defaultValue={0}
                    />
                </Field>
            )}
        </div>
    );
}

function VariantRow({ variant }: { variant: Variant }) {
    const [editing, setEditing] = useState(false);

    return (
        <li className="space-y-3 py-4">
            <div className="flex flex-wrap items-center gap-3">
                <div className="flex-1">
                    <p className="font-medium">
                        {variant.label ?? 'Varian standar'}
                    </p>
                    <p className="text-xs text-muted-foreground">
                        {variant.sku} · {formatRupiah(variant.price)} · ambang{' '}
                        {variant.low_stock_threshold}
                    </p>
                </div>
                <p
                    className={
                        variant.stock <= variant.low_stock_threshold
                            ? 'font-semibold text-amber-600'
                            : 'font-semibold'
                    }
                >
                    Stok {variant.stock}
                </p>
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => setEditing((value) => !value)}
                >
                    {editing ? 'Tutup' : 'Ubah'}
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label={`Hapus varian ${variant.sku}`}
                    onClick={() => {
                        if (window.confirm(`Hapus varian ${variant.sku}?`)) {
                            router.delete(
                                ProductVariantController.destroy.url(
                                    variant.id,
                                ),
                                { preserveScroll: true },
                            );
                        }
                    }}
                >
                    <Trash2 />
                </Button>
            </div>

            <Form
                {...StockAdjustmentController.store.form(variant.id)}
                options={{ preserveScroll: true }}
                resetOnSuccess
                className="flex flex-col gap-2 sm:flex-row sm:items-start"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="sm:w-32">
                            <Input
                                name="quantity"
                                type="number"
                                required
                                placeholder="+5 / -2"
                                aria-label={`Penyesuaian stok ${variant.sku}`}
                            />
                            <InputError
                                message={errors.quantity ?? errors.stock}
                            />
                        </div>
                        <div className="flex-1">
                            <Input
                                name="note"
                                required
                                placeholder="Alasan (wajib), mis. stok opname"
                                aria-label="Alasan penyesuaian"
                            />
                            <InputError message={errors.note} />
                        </div>
                        <Button variant="secondary" disabled={processing}>
                            Sesuaikan stok
                        </Button>
                    </>
                )}
            </Form>

            {editing && (
                <Form
                    {...ProductVariantController.update.form(variant.id)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setEditing(false)}
                    className="space-y-3 rounded-md bg-muted/40 p-3"
                >
                    {({ processing, errors }) => (
                        <>
                            <VariantFields
                                variant={variant}
                                errors={errors}
                                isNew={false}
                            />
                            <Button size="sm" disabled={processing}>
                                Simpan varian
                            </Button>
                        </>
                    )}
                </Form>
            )}
        </li>
    );
}

export default function AdminProductEdit({
    product,
    categories,
    statuses,
}: {
    product: Product;
    categories: { id: number; name: string }[];
    statuses: string[];
}) {
    const { errors } = usePage().props as { errors: Record<string, string> };

    return (
        <>
            <Head title={product.name} />

            <div className="max-w-5xl space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-2">
                    <Heading
                        title={product.name}
                        description="Ubah data produk, gambar, varian, dan stok."
                    />
                    {product.status === 'active' && (
                        <Button variant="outline" asChild>
                            <Link href={productShow(product.slug)}>
                                <ExternalLink /> Lihat di toko
                            </Link>
                        </Button>
                    )}
                </div>

                <Section title="Data produk">
                    <Form
                        {...ProductController.update.form(product.id)}
                        options={{ preserveScroll: true }}
                        className="space-y-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <ProductFields
                                    values={product}
                                    categories={categories}
                                    statuses={statuses}
                                    errors={errors}
                                />
                                <Button disabled={processing}>
                                    Simpan produk
                                </Button>
                            </>
                        )}
                    </Form>
                </Section>

                <Section
                    title="Gambar"
                    description="JPG, PNG, atau WebP, maks 2 MB per file. Urutan mengikuti urutan unggah."
                >
                    {product.images.length > 0 && (
                        <ul className="grid grid-cols-3 gap-3 sm:grid-cols-6">
                            {product.images.map((image, position) => (
                                <li key={image.id} className="relative">
                                    <img
                                        src={image.url}
                                        alt={`Gambar ${position + 1}`}
                                        className="aspect-square w-full rounded-md object-cover"
                                    />
                                    <Button
                                        variant="destructive"
                                        size="icon"
                                        className="absolute top-1 right-1 size-7"
                                        aria-label={`Hapus gambar ${position + 1}`}
                                        onClick={() =>
                                            router.delete(
                                                ProductImageController.destroy.url(
                                                    image.id,
                                                ),
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <Trash2 />
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    )}
                    <Form
                        {...ProductImageController.store.form(product.id)}
                        options={{ preserveScroll: true }}
                        resetOnSuccess
                        className="flex flex-col gap-2 sm:flex-row sm:items-center"
                    >
                        {({ processing, errors, progress }) => (
                            <>
                                <Input
                                    type="file"
                                    name="images[]"
                                    multiple
                                    accept="image/jpeg,image/png,image/webp"
                                    required
                                    aria-label="Pilih gambar"
                                />
                                <Button
                                    variant="secondary"
                                    disabled={processing}
                                >
                                    {progress
                                        ? `Mengunggah ${progress.percentage}%`
                                        : 'Unggah'}
                                </Button>
                                <InputError
                                    message={
                                        errors.images ??
                                        Object.entries(errors).find(([key]) =>
                                            key.startsWith('images.'),
                                        )?.[1]
                                    }
                                />
                            </>
                        )}
                    </Form>
                </Section>

                <Section
                    title="Varian & stok"
                    description="Stok hanya berubah lewat penyesuaian (dengan alasan) atau pesanan, sehingga riwayatnya lengkap."
                >
                    <ul className="divide-y">
                        {product.variants.map((variant) => (
                            <VariantRow key={variant.id} variant={variant} />
                        ))}
                    </ul>
                    <InputError message={errors.variant} />
                </Section>

                <Section title="Tambah varian">
                    <Form
                        {...ProductVariantController.store.form(product.id)}
                        options={{ preserveScroll: true }}
                        resetOnSuccess
                        className="space-y-3"
                    >
                        {({ processing, errors }) => (
                            <>
                                <VariantFields errors={errors} isNew />
                                <Button disabled={processing}>
                                    Tambah varian
                                </Button>
                            </>
                        )}
                    </Form>
                </Section>

                <Section
                    title="Hapus produk"
                    description="Produk disembunyikan dari toko dan admin; riwayat pesanan tetap utuh."
                >
                    <Button
                        variant="destructive"
                        onClick={() => {
                            if (
                                window.confirm(`Hapus produk ${product.name}?`)
                            ) {
                                router.delete(
                                    ProductController.destroy.url(product.id),
                                );
                            }
                        }}
                    >
                        Hapus produk
                    </Button>
                </Section>
            </div>
        </>
    );
}

AdminProductEdit.layout = {
    breadcrumbs: [{ title: 'Produk', href: index() }],
};
