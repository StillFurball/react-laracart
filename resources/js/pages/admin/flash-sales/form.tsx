import { Form, Head, router, usePage } from '@inertiajs/react';
import { Search, Trash2 } from 'lucide-react';
import { useState } from 'react';
import FlashSaleController from '@/actions/App/Http/Controllers/Admin/FlashSaleController';
import FlashSaleItemController from '@/actions/App/Http/Controllers/Admin/FlashSaleItemController';
import { Field } from '@/components/admin/product-fields';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { formatRupiah } from '@/lib/utils';
import { edit, index } from '@/routes/admin/flash-sales';
import { statusBadge } from './index';
import type { FlashSaleFormData } from './index';

type Offer = {
    id: number;
    product_name: string;
    variant_label: string | null;
    sku: string;
    regular_price: number;
    sale_price: number;
    quota: number | null;
    sold_count: number;
    per_user_limit: number | null;
    stock: number;
    is_available: boolean;
    has_orders: boolean;
};

type VariantOption = {
    id: number;
    label: string;
    price: number;
    stock: number;
};

function OfferFields({
    offer,
    errors,
    prefix,
}: {
    offer?: Offer;
    errors: Record<string, string>;
    prefix: string;
}) {
    return (
        <div className="grid gap-3 sm:grid-cols-3">
            <Field
                label="Harga flash sale (Rp)"
                name={`${prefix}sale_price`}
                error={errors.sale_price}
            >
                <Input
                    id={`${prefix}sale_price`}
                    name="sale_price"
                    type="number"
                    min={1}
                    required
                    defaultValue={offer?.sale_price}
                />
            </Field>
            <Field
                label="Kuota (opsional)"
                name={`${prefix}quota`}
                error={errors.quota}
                hint="Kosongkan bila hanya dibatasi stok."
            >
                <Input
                    id={`${prefix}quota`}
                    name="quota"
                    type="number"
                    min={offer ? Math.max(1, offer.sold_count) : 1}
                    defaultValue={offer?.quota ?? ''}
                />
            </Field>
            <Field
                label="Maks. per pelanggan (opsional)"
                name={`${prefix}per_user_limit`}
                error={errors.per_user_limit}
            >
                <Input
                    id={`${prefix}per_user_limit`}
                    name="per_user_limit"
                    type="number"
                    min={1}
                    defaultValue={offer?.per_user_limit ?? ''}
                />
            </Field>
        </div>
    );
}

function OfferRow({ offer }: { offer: Offer }) {
    const [editing, setEditing] = useState(false);
    const percent = Math.round(
        (1 - offer.sale_price / offer.regular_price) * 100,
    );

    return (
        <li className="space-y-3 py-4">
            <div className="flex flex-wrap items-center gap-3">
                <div className="min-w-0 flex-1">
                    <p className="font-medium">
                        {offer.product_name}
                        {offer.variant_label && ` (${offer.variant_label})`}
                    </p>
                    <p className="text-xs text-muted-foreground tabular-nums">
                        {offer.sku} · <s>{formatRupiah(offer.regular_price)}</s>{' '}
                        → {formatRupiah(offer.sale_price)} (−{percent}%)
                        {offer.per_user_limit !== null &&
                            ` · maks. ${offer.per_user_limit}/pelanggan`}
                    </p>
                    {!offer.is_available && (
                        <p className="text-xs font-medium text-warning">
                            Produk tidak aktif atau varian dihapus: tidak tampil
                            di toko.
                        </p>
                    )}
                </div>
                <p className="text-sm font-semibold tabular-nums">
                    Terjual {offer.sold_count}
                    {offer.quota !== null && ` / ${offer.quota}`} · stok{' '}
                    {offer.stock}
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
                    aria-label={`Hapus ${offer.sku} dari flash sale`}
                    disabled={offer.has_orders}
                    onClick={() => {
                        if (
                            window.confirm(
                                `Hapus ${offer.sku} dari flash sale ini?`,
                            )
                        ) {
                            router.delete(
                                FlashSaleItemController.destroy.url(offer.id),
                                { preserveScroll: true },
                            );
                        }
                    }}
                >
                    <Trash2 />
                </Button>
            </div>

            {editing && (
                <Form
                    {...FlashSaleItemController.update.form(offer.id)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setEditing(false)}
                    className="space-y-3 rounded-md bg-muted/40 p-3"
                >
                    {({ processing, errors }) => (
                        <>
                            <OfferFields
                                offer={offer}
                                errors={errors}
                                prefix={`o${offer.id}-`}
                            />
                            <Button size="sm" disabled={processing}>
                                Simpan
                            </Button>
                        </>
                    )}
                </Form>
            )}
        </li>
    );
}

function AddOffer({
    flashSaleId,
    variantOptions,
    search,
}: {
    flashSaleId: number;
    variantOptions: VariantOption[];
    search: string;
}) {
    const [query, setQuery] = useState(search);

    return (
        <section aria-labelledby="add-offer-title" className="space-y-3">
            <h3 id="add-offer-title" className="font-semibold">
                Tambah barang
            </h3>
            <form
                role="search"
                className="flex gap-2"
                onSubmit={(event) => {
                    event.preventDefault();
                    router.get(
                        edit.url(flashSaleId, {
                            query: { variant_q: query.trim() || undefined },
                        }),
                        {},
                        {
                            only: ['variantOptions', 'filters'],
                            preserveState: true,
                            preserveScroll: true,
                            replace: true,
                        },
                    );
                }}
            >
                <Input
                    type="search"
                    aria-label="Cari varian berdasarkan nama produk atau SKU"
                    placeholder="Cari nama produk atau SKU"
                    value={query}
                    onChange={(event) => setQuery(event.target.value)}
                />
                <Button type="submit" variant="secondary">
                    <Search /> Cari
                </Button>
            </form>

            <Form
                {...FlashSaleItemController.store.form(flashSaleId)}
                options={{ preserveScroll: true }}
                resetOnSuccess
                className="space-y-3 rounded-md border p-3"
            >
                {({ processing, errors }) => (
                    <>
                        <Field
                            label="Varian"
                            name="product_variant_id"
                            error={errors.product_variant_id}
                        >
                            <NativeSelect
                                id="product_variant_id"
                                name="product_variant_id"
                                required
                                defaultValue=""
                            >
                                <option value="" disabled>
                                    {variantOptions.length > 0
                                        ? 'Pilih varian'
                                        : 'Tidak ada varian yang cocok'}
                                </option>
                                {variantOptions.map((option) => (
                                    <option key={option.id} value={option.id}>
                                        {option.label} —{' '}
                                        {formatRupiah(option.price)} · stok{' '}
                                        {option.stock}
                                    </option>
                                ))}
                            </NativeSelect>
                        </Field>
                        <OfferFields errors={errors} prefix="new-" />
                        <Button disabled={processing}>Tambahkan</Button>
                    </>
                )}
            </Form>
        </section>
    );
}

export default function AdminFlashSaleForm({
    flashSale,
    items,
    variantOptions,
    filters,
}: {
    flashSale: FlashSaleFormData | null;
    items: Offer[];
    variantOptions: VariantOption[];
    filters: { variant_q: string };
}) {
    const { errors: pageErrors } = usePage().props as {
        errors: Record<string, string>;
    };
    const action = flashSale
        ? FlashSaleController.update.form(flashSale.id)
        : FlashSaleController.store.form();

    return (
        <>
            <Head title={flashSale ? flashSale.name : 'Flash sale baru'} />

            <div className="max-w-4xl space-y-10 p-4">
                <div className="space-y-6">
                    <div className="flex flex-wrap items-start gap-3">
                        <Heading
                            title={
                                flashSale
                                    ? `Ubah ${flashSale.name}`
                                    : 'Flash sale baru'
                            }
                            description="Harga flash sale berlaku otomatis selama jadwal berjalan. Satu varian hanya boleh ada di satu flash sale pada waktu yang sama."
                        />
                        {flashSale && (
                            <Badge variant={statusBadge[flashSale.status]}>
                                {flashSale.status_label}
                            </Badge>
                        )}
                    </div>

                    <Form {...action} className="space-y-4">
                        {({ processing, errors }) => (
                            <>
                                <Field
                                    label="Nama"
                                    name="name"
                                    error={errors.name}
                                >
                                    <Input
                                        id="name"
                                        name="name"
                                        required
                                        placeholder="mis. Payday Sale"
                                        defaultValue={flashSale?.name}
                                    />
                                </Field>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field
                                        label="Mulai"
                                        name="starts_at"
                                        error={errors.starts_at}
                                    >
                                        <Input
                                            id="starts_at"
                                            name="starts_at"
                                            type="datetime-local"
                                            required
                                            defaultValue={flashSale?.starts_at}
                                        />
                                    </Field>
                                    <Field
                                        label="Berakhir"
                                        name="ends_at"
                                        error={errors.ends_at}
                                    >
                                        <Input
                                            id="ends_at"
                                            name="ends_at"
                                            type="datetime-local"
                                            required
                                            defaultValue={flashSale?.ends_at}
                                        />
                                    </Field>
                                </div>
                                <label className="flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        name="is_active"
                                        value="1"
                                        defaultChecked={
                                            flashSale?.is_active ?? true
                                        }
                                        className="accent-primary"
                                    />
                                    Aktif
                                </label>
                                <Button disabled={processing}>
                                    {flashSale
                                        ? 'Simpan jadwal'
                                        : 'Buat & tambah barang'}
                                </Button>
                            </>
                        )}
                    </Form>
                </div>

                {flashSale && (
                    <section
                        aria-labelledby="offers-title"
                        className="space-y-4"
                    >
                        <Heading
                            variant="small"
                            title="Barang di flash sale ini"
                            description="Harga flash sale harus di bawah harga normal. Kuota dihitung dari pesanan yang tidak kedaluwarsa atau dibatalkan."
                        />
                        <span id="offers-title" className="sr-only">
                            Barang di flash sale ini
                        </span>
                        <InputError message={pageErrors.item} />
                        {items.length > 0 ? (
                            <ul className="divide-y rounded-lg border px-4">
                                {items.map((offer) => (
                                    <OfferRow key={offer.id} offer={offer} />
                                ))}
                            </ul>
                        ) : (
                            <p className="rounded-lg border p-6 text-center text-sm text-muted-foreground">
                                Belum ada barang. Cari varian di bawah lalu
                                tentukan harga flash sale-nya.
                            </p>
                        )}

                        <AddOffer
                            flashSaleId={flashSale.id}
                            variantOptions={variantOptions}
                            search={filters.variant_q}
                        />
                    </section>
                )}
            </div>
        </>
    );
}

AdminFlashSaleForm.layout = {
    breadcrumbs: [{ title: 'Flash Sale', href: index() }],
};
