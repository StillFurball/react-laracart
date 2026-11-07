<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProductImageController extends Controller
{
    /**
     * Upload images to the local public disk, appended after existing ones.
     */
    public function store(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'images' => ['required', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $sortOrder = (int) $product->images()->max('sort_order');

        foreach ($request->file('images') as $file) {
            $product->images()->create([
                'path' => $file->store('products', 'public'),
                'sort_order' => ++$sortOrder,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Gambar diunggah.']);

        return to_route('admin.products.edit', $product);
    }

    public function destroy(ProductImage $productImage): RedirectResponse
    {
        Storage::disk('public')->delete($productImage->path);
        $productImage->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Gambar dihapus.']);

        return to_route('admin.products.edit', $productImage->product_id);
    }
}
