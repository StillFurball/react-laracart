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

        $phones = $this->category('Ponsel & Tablet');
        $smartphones = $this->category('Smartphone', $phones);
        $tablets = $this->category('Tablet', $phones);
        $computers = $this->category('Komputer');
        $laptops = $this->category('Laptop', $computers);
        $peripherals = $this->category('Aksesori Komputer', $computers);
        $inputDevices = $this->category('Keyboard & Mouse', $peripherals);
        $audio = $this->category('Audio');
        $headphones = $this->category('Headphone', $audio);
        $speakers = $this->category('Speaker', $audio);
        $wearables = $this->category('Jam Pintar');
        $accessories = $this->category('Aksesori');

        $catalog = [
            [$smartphones, 'Smartphone Aero 12 5G', 190, 5499000, ['Penyimpanan' => ['128 GB', '256 GB'], 'Warna' => ['Hitam', 'Biru']]],
            [$smartphones, 'Smartphone Lumen A5', 185, 2299000, ['Warna' => ['Hitam', 'Hijau']]],
            [$tablets, 'Tablet Nova Pad 11', 480, 4299000, ['Penyimpanan' => ['128 GB', '256 GB']]],
            [$laptops, 'Laptop Zenit Air 14', 1300, 11999000, ['Penyimpanan' => ['512 GB', '1 TB'], 'Warna' => ['Silver', 'Abu']]],
            [$laptops, 'Laptop Kerja Orbit 15', 1700, 7499000, []],
            [$inputDevices, 'Keyboard Mekanikal Tala 75', 800, 899000, ['Warna' => ['Hitam', 'Putih']]],
            [$inputDevices, 'Mouse Nirkabel Glide', 90, 249000, ['Warna' => ['Hitam', 'Putih', 'Biru']]],
            [$headphones, 'Headphone Nirkabel Senyap ANC', 250, 1799000, ['Warna' => ['Hitam', 'Silver']]],
            [$headphones, 'Earbuds TWS Pico', 50, 499000, ['Warna' => ['Putih', 'Hitam']]],
            [$speakers, 'Speaker Bluetooth Gema Mini', 600, 649000, []],
            [$wearables, 'Jam Pintar Pulse 2', 60, 1499000, ['Warna' => ['Hitam', 'Silver']]],
            [$accessories, 'Power Bank 20.000 mAh', 400, 349000, []],
            [$accessories, 'Charger GaN 65 W', 150, 299000, []],
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
                    // Each storage tier above the first costs a million more.
                    'price' => $price + (int) array_search($attributes['Penyimpanan'] ?? null, $options['Penyimpanan'] ?? [], true) * 1000000,
                    'compare_at_price' => $index % 3 === 0 ? (int) round($price * 1.1, -3) : null,
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
            'name' => 'Kamera Aksi (Draft)',
            'slug' => 'kamera-aksi-draft',
            'description' => 'Produk draft: tidak tampil di toko.',
            'status' => ProductStatus::Draft,
            'weight_gram' => 200,
        ])->variants()->create(['sku' => 'MS-DRAFT', 'price' => 1500000, 'weight_gram' => 200]);

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
