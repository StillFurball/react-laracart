<?php

use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('creating a product also creates its default variant and initial stock entry', function () {
    $category = Category::factory()->create();

    $this->actingAs($this->admin)->post(route('admin.products.store'), [
        'name' => 'Kaos Uji',
        'category_id' => $category->id,
        'status' => 'active',
        'weight_gram' => 250,
        'sku' => 'UJI-001',
        'price' => 75000,
        'stock' => 12,
    ])->assertSessionHasNoErrors();

    $product = Product::sole();
    $variant = $product->variants()->sole();

    expect($product->slug)->toBe('kaos-uji')
        ->and($variant)->sku->toBe('UJI-001')->stock->toBe(12)
        ->and($variant->stockMovements()->sole())
        ->type->toBe(StockMovementType::In)
        ->user_id->toBe($this->admin->id);
});

test('a variant is created with attributes from the option fields', function () {
    $product = Product::factory()->withVariant()->create();

    $this->actingAs($this->admin)->post(route('admin.products.variants.store', $product), [
        'sku' => 'UJI-L-HITAM',
        'option1_name' => 'Ukuran',
        'option1_value' => 'L',
        'option2_name' => 'Warna',
        'option2_value' => 'Hitam',
        'price' => 80000,
        'weight_gram' => 300,
        'low_stock_threshold' => 3,
        'stock' => 4,
    ])->assertSessionHasNoErrors();

    $variant = ProductVariant::firstWhere('sku', 'UJI-L-HITAM');

    expect($variant->getAttribute('attributes'))->toBe(['Ukuran' => 'L', 'Warna' => 'Hitam'])
        ->and($variant->label())->toBe('L / Hitam')
        ->and($variant->stock)->toBe(4);
});

test('the last variant of a product cannot be deleted', function () {
    $product = Product::factory()->withVariant()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.variants.destroy', $product->variants()->sole()))
        ->assertSessionHasErrors('variant');

    expect($product->variants()->count())->toBe(1);
});

test('a manual stock adjustment requires a reason', function () {
    $variant = ProductVariant::factory()->create(['stock' => 5]);

    $this->actingAs($this->admin)
        ->post(route('admin.variants.stock-adjustments.store', $variant), ['quantity' => 3])
        ->assertSessionHasErrors('note');

    expect($variant->fresh()->stock)->toBe(5);
});

test('a manual stock adjustment is recorded with the admin and reason', function () {
    $variant = ProductVariant::factory()->create(['stock' => 5]);

    $this->actingAs($this->admin)
        ->post(route('admin.variants.stock-adjustments.store', $variant), ['quantity' => -2, 'note' => 'Barang rusak'])
        ->assertSessionHasNoErrors();

    expect($variant->fresh()->stock)->toBe(3)
        ->and($variant->stockMovements()->sole())
        ->type->toBe(StockMovementType::Adjustment)
        ->quantity->toBe(-2)
        ->stock_after->toBe(3)
        ->note->toBe('Barang rusak')
        ->user_id->toBe($this->admin->id);
});

test('a stock adjustment cannot make stock negative', function () {
    $variant = ProductVariant::factory()->create(['stock' => 2]);

    $this->actingAs($this->admin)
        ->post(route('admin.variants.stock-adjustments.store', $variant), ['quantity' => -3, 'note' => 'Opname'])
        ->assertSessionHasErrors('stock');

    expect($variant->fresh()->stock)->toBe(2)
        ->and($variant->stockMovements()->count())->toBe(0);
});

test('categories are limited to three levels', function () {
    $level1 = Category::factory()->create();
    $level2 = Category::factory()->create(['parent_id' => $level1->id]);
    $level3 = Category::factory()->create(['parent_id' => $level2->id]);

    $this->actingAs($this->admin)
        ->post(route('admin.categories.store'), ['name' => 'Level Empat', 'parent_id' => $level3->id])
        ->assertSessionHasErrors('parent_id');

    $this->actingAs($this->admin)
        ->put(route('admin.categories.update', $level1), ['name' => $level1->name, 'parent_id' => Category::factory()->create()->id])
        ->assertSessionHasErrors('parent_id');
});

test('product images are stored on the public disk', function () {
    Storage::fake('public');
    $product = Product::factory()->withVariant()->create();

    // A real 1x1 PNG so the test does not depend on the GD extension.
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');

    $this->actingAs($this->admin)->post(route('admin.products.images.store', $product), [
        'images' => [UploadedFile::fake()->createWithContent('a.png', $png), UploadedFile::fake()->createWithContent('b.png', $png)],
    ])->assertSessionHasNoErrors();

    expect($product->images()->pluck('sort_order')->all())->toBe([1, 2]);
    Storage::disk('public')->assertExists($product->images()->first()->path);
});

test('a non-image upload is rejected', function () {
    Storage::fake('public');
    $product = Product::factory()->withVariant()->create();

    $this->actingAs($this->admin)->post(route('admin.products.images.store', $product), [
        'images' => [UploadedFile::fake()->create('script.php', 10, 'application/x-php')],
    ])->assertSessionHasErrors('images.0');

    expect($product->images()->count())->toBe(0);
});
