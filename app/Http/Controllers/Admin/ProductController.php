<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Shop\AdjustStock;
use App\Enums\ProductStatus;
use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Product list with search (name / SKU) and status filter.
     */
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(ProductStatus::class)],
        ]);

        $search = $filters['q'] ?? null;

        $products = Product::with('category:id,name')
            ->withCount('variants')
            ->withSum('variants as total_stock', 'stock')
            ->withMin('variants as min_price', 'price')
            ->when($search, fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';

                $query->where('name', 'like', $term)
                    ->orWhereHas('variants', fn (Builder $variants) => $variants->where('sku', 'like', $term));
            }))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'category' => $product->category->name,
                'status' => $product->status->value,
                'variants_count' => $product->variants_count,
                'total_stock' => (int) $product->total_stock,
                'min_price' => (int) $product->min_price,
            ]);

        return Inertia::render('admin/products/index', [
            'products' => $products,
            'filters' => ['q' => $search ?? '', 'status' => $filters['status'] ?? ''],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/products/create', [
            'categories' => self::categoryOptions(),
            'statuses' => array_column(ProductStatus::cases(), 'value'),
        ]);
    }

    /**
     * Create a product together with its first (default) variant.
     */
    public function store(ProductRequest $request, AdjustStock $adjustStock): RedirectResponse
    {
        $product = DB::transaction(function () use ($request, $adjustStock): Product {
            $product = Product::create($request->safe()->only(['category_id', 'name', 'slug', 'description', 'status', 'weight_gram']));

            $variant = $product->variants()->create([
                'sku' => $request->validated('sku'),
                'price' => $request->integer('price'),
                'compare_at_price' => $request->validated('compare_at_price'),
                'weight_gram' => $request->integer('weight_gram'),
                'low_stock_threshold' => (int) config('shop.low_stock_threshold'),
            ]);

            if ($request->integer('stock') > 0) {
                $adjustStock->handle($variant, $request->integer('stock'), StockMovementType::In, $request->user(), note: 'Stok awal');
            }

            return $product;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Produk dibuat. Tambahkan gambar atau varian lain bila perlu.']);

        return to_route('admin.products.edit', $product);
    }

    /**
     * Edit page: product fields, images, variants and stock adjustments.
     */
    public function edit(Product $product): Response
    {
        $product->load(['images', 'variants' => fn ($query) => $query->orderBy('id')]);

        return Inertia::render('admin/products/edit', [
            'product' => [
                ...$product->only(['id', 'category_id', 'name', 'slug', 'description', 'weight_gram']),
                'status' => $product->status->value,
                'images' => $product->images->map->only(['id', 'url']),
                'variants' => $product->variants->map(fn (ProductVariant $variant): array => [
                    ...$variant->only(['id', 'sku', 'price', 'compare_at_price', 'stock', 'low_stock_threshold', 'weight_gram']),
                    'attributes' => $variant->getAttribute('attributes') ?? (object) [],
                    'label' => $variant->label(),
                ]),
            ],
            'categories' => self::categoryOptions(),
            'statuses' => array_column(ProductStatus::cases(), 'value'),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Produk diperbarui.']);

        return to_route('admin.products.edit', $product);
    }

    /**
     * Soft delete: past orders keep their snapshot and variant reference.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Produk dihapus.']);

        return to_route('admin.products.index');
    }

    /**
     * Categories as "Parent › Child" options.
     *
     * @return list<array{id: int, name: string}>
     */
    public static function categoryOptions(): array
    {
        $categories = Category::get(['id', 'parent_id', 'name'])->keyBy('id');

        return $categories
            ->map(function (Category $category) use ($categories): array {
                $path = [$category->name];
                $parent = $categories->get($category->parent_id);

                while ($parent !== null) {
                    array_unshift($path, $parent->name);
                    $parent = $categories->get($parent->parent_id);
                }

                return ['id' => $category->id, 'name' => implode(' › ', $path)];
            })
            ->sortBy('name')
            ->values()
            ->all();
    }
}
