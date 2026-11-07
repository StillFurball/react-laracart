<?php

namespace Database\Seeders;

use App\Actions\Shop\AdjustStock;
use App\Enums\ProductStatus;
use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Enums\VoucherType;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo accounts, a three-level category tree, products with variants and vouchers.
 */
class DemoStoreSeeder extends Seeder
{
    public function run(AdjustStock $adjustStock): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin Demo',
            'email' => 'admin@myshop.test',
            'role' => UserRole::Admin,
        ]);

        $customer = User::factory()->create([
            'name' => 'Pelanggan Demo',
            'email' => 'pelanggan@myshop.test',
            'phone' => '081234567890',
        ]);

        $customer->address()->create([
            'recipient_name' => 'Pelanggan Demo',
            'phone' => '081234567890',
            'address_line' => 'Jl. Merdeka No. 10',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'postal_code' => '40111',
        ]);

        $fashion = $this->category('Fashion');
        $men = $this->category('Pria', $fashion);
        $women = $this->category('Wanita', $fashion);
        $menShirts = $this->category('Kaos Pria', $men);
        $menJackets = $this->category('Jaket Pria', $men);
        $dresses = $this->category('Dress', $women);
        $home = $this->category('Rumah Tangga');
        $kitchen = $this->category('Dapur', $home);
        $accessories = $this->category('Aksesoris');

        $sizes = ['S', 'M', 'L', 'XL'];

        $catalog = [
            [$menShirts, 'Kaos Polos Katun Combed', 200, 79000, ['Ukuran' => $sizes, 'Warna' => ['Hitam', 'Putih']]],
            [$menShirts, 'Kaos Oversize Grafis', 230, 99000, ['Ukuran' => $sizes]],
            [$menShirts, 'Polo Shirt Pique', 250, 129000, ['Ukuran' => ['M', 'L', 'XL'], 'Warna' => ['Navy', 'Abu']]],
            [$menJackets, 'Jaket Denim Klasik', 800, 349000, ['Ukuran' => ['M', 'L', 'XL']]],
            [$menJackets, 'Hoodie Fleece', 600, 219000, ['Ukuran' => $sizes, 'Warna' => ['Hitam', 'Maroon']]],
            [$dresses, 'Dress Midi Floral', 350, 259000, ['Ukuran' => ['S', 'M', 'L']]],
            [$dresses, 'Dress Linen Polos', 320, 239000, ['Ukuran' => ['S', 'M', 'L'], 'Warna' => ['Krem', 'Sage']]],
            [$kitchen, 'Set Pisau Dapur 5 in 1', 900, 189000, []],
            [$kitchen, 'Teflon Anti Lengket 24 cm', 1100, 145000, []],
            [$kitchen, 'Botol Minum Stainless 750 ml', 400, 89000, ['Warna' => ['Hitam', 'Hijau', 'Biru']]],
            [$accessories, 'Topi Baseball Bordir', 150, 69000, ['Warna' => ['Hitam', 'Krem']]],
            [$accessories, 'Tote Bag Kanvas', 250, 59000, []],
            [$accessories, 'Kaus Kaki Sport (3 pasang)', 120, 45000, []],
        ];

        foreach ($catalog as $index => [$category, $name, $weight, $price, $options]) {
            $product = Product::create([
                'category_id' => $category->id,
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => "{$name} — produk contoh untuk demo MyShop. Data ini sintetis dan tidak mewakili produk nyata.",
                'status' => ProductStatus::Active,
                'weight_gram' => $weight,
            ]);

            $skuBase = 'MS-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);

            foreach ($this->combinations($options) as $variantIndex => $attributes) {
                $variant = $product->variants()->create([
                    'sku' => $skuBase.($attributes === [] ? '' : '-'.Str::upper(implode('-', array_map(fn ($value) => Str::substr(Str::slug($value), 0, 3), $attributes)))),
                    'attributes' => $attributes === [] ? null : $attributes,
                    'price' => $price + (($attributes['Ukuran'] ?? null) === 'XL' ? 10000 : 0),
                    'compare_at_price' => $index % 3 === 0 ? $price + 30000 : null,
                    'weight_gram' => $weight,
                    'low_stock_threshold' => (int) config('shop.low_stock_threshold'),
                ]);

                // A few variants start low or out of stock to show the stock labels.
                $stock = match (true) {
                    $index === 1 && $variantIndex === 0 => 0,
                    $index === 3 => 3,
                    default => 15 + (($index + $variantIndex) % 4) * 5,
                };

                if ($stock > 0) {
                    $adjustStock->handle($variant, $stock, StockMovementType::In, $admin, note: 'Stok awal (seeder)');
                }
            }
        }

        Product::create([
            'category_id' => $accessories->id,
            'name' => 'Dompet Kulit (Draft)',
            'slug' => 'dompet-kulit-draft',
            'description' => 'Produk draft: tidak tampil di toko.',
            'status' => ProductStatus::Draft,
            'weight_gram' => 200,
        ])->variants()->create(['sku' => 'MS-DRAFT', 'price' => 150000, 'weight_gram' => 200]);

        Voucher::create([
            'code' => 'HEMAT10',
            'type' => VoucherType::Percent,
            'value' => 10,
            'max_discount' => 20000,
            'min_purchase' => 100000,
            'is_active' => true,
        ]);

        Voucher::create([
            'code' => 'POTONG25',
            'type' => VoucherType::Fixed,
            'value' => 25000,
            'min_purchase' => 200000,
            'quota' => 50,
            'per_user_limit' => 1,
            'is_active' => true,
        ]);

        Voucher::create([
            'code' => 'KEDALUWARSA',
            'type' => VoucherType::Fixed,
            'value' => 50000,
            'min_purchase' => 0,
            'ends_at' => now()->subDay(),
            'is_active' => true,
        ]);
    }

    private function category(string $name, ?Category $parent = null): Category
    {
        return Category::create([
            'parent_id' => $parent?->id,
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }

    /**
     * Cartesian product of option values, e.g. Ukuran × Warna.
     *
     * @param  array<string, list<string>>  $options
     * @return list<array<string, string>>
     */
    private function combinations(array $options): array
    {
        $result = [[]];

        foreach ($options as $name => $values) {
            $next = [];

            foreach ($result as $combination) {
                foreach ($values as $value) {
                    $next[] = [...$combination, $name => $value];
                }
            }

            $result = $next;
        }

        return $result;
    }
}
