<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $counts = Category::withCount(['products', 'children'])->get()->keyBy('id');

        return Inertia::render('admin/categories/index', [
            'categories' => collect(ProductController::categoryOptions())->map(fn (array $option): array => [
                ...$option,
                'slug' => $counts[$option['id']]->slug,
                'products_count' => $counts[$option['id']]->products_count,
                'children_count' => $counts[$option['id']]->children_count,
                'image' => $counts[$option['id']]->image_path ? Storage::disk('public')->url($counts[$option['id']]->image_path) : null,
            ]),
            'parentOptions' => self::parentOptions(),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        Category::create([
            ...$request->safe()->only(['name', 'slug', 'parent_id']),
            'image_path' => $request->file('image')?->store('categories', 'public'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori dibuat.']);

        return to_route('admin.categories.index');
    }

    public function edit(Category $category): Response
    {
        $excluded = $category->selfAndDescendantIds();

        return Inertia::render('admin/categories/edit', [
            'category' => [
                ...$category->only(['id', 'name', 'slug', 'parent_id']),
                'image' => $category->image_path ? Storage::disk('public')->url($category->image_path) : null,
            ],
            'parentOptions' => array_values(array_filter(
                self::parentOptions(),
                fn (array $option): bool => ! in_array($option['id'], $excluded, true),
            )),
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->safe()->only(['name', 'slug', 'parent_id']);

        if ($request->hasFile('image')) {
            if ($category->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }

            $data['image_path'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori diperbarui.']);

        return to_route('admin.categories.index');
    }

    /**
     * Only empty categories (no products, no children) can be deleted.
     */
    public function destroy(Category $category): RedirectResponse
    {
        if ($category->children()->exists() || $category->products()->withTrashed()->exists()) {
            throw ValidationException::withMessages(['category' => 'Kategori masih memiliki sub kategori atau produk.']);
        }

        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }

        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori dihapus.']);

        return to_route('admin.categories.index');
    }

    /**
     * Categories that can still receive children (depth below the maximum).
     *
     * @return list<array{id: int, name: string}>
     */
    private static function parentOptions(): array
    {
        $depths = Category::get(['id', 'parent_id'])->keyBy('id');
        $depthOf = function (int $id) use ($depths): int {
            $depth = 1;
            $parentId = $depths[$id]->parent_id;

            while ($parentId !== null) {
                $depth++;
                $parentId = $depths[$parentId]->parent_id;
            }

            return $depth;
        };

        return array_values(array_filter(
            ProductController::categoryOptions(),
            fn (array $option): bool => $depthOf($option['id']) < Category::MAX_DEPTH,
        ));
    }
}
