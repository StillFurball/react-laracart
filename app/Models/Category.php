<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string|null $image_path
 */
#[Fillable(['parent_id', 'name', 'slug', 'image_path'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    public const MAX_DEPTH = 3;

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Depth of this category in the tree, starting at 1 for a root category.
     */
    public function depth(): int
    {
        $depth = 1;
        $parentId = $this->parent_id;

        while ($parentId !== null && $depth <= self::MAX_DEPTH) {
            $depth++;
            $parentId = Category::whereKey($parentId)->value('parent_id');
        }

        return $depth;
    }

    /**
     * Number of levels below this category (0 when it has no children).
     */
    public function subtreeHeight(): int
    {
        $height = 0;
        $level = [$this->id];

        while ($height < self::MAX_DEPTH) {
            $level = Category::whereIn('parent_id', $level)->pluck('id')->all();

            if ($level === []) {
                break;
            }

            $height++;
        }

        return $height;
    }

    /**
     * IDs of this category and all of its descendants (the tree is at most three levels deep).
     *
     * @return list<int>
     */
    public function selfAndDescendantIds(): array
    {
        $ids = [$this->id];
        $level = [$this->id];

        for ($i = 1; $i < self::MAX_DEPTH && $level !== []; $i++) {
            $level = Category::whereIn('parent_id', $level)->pluck('id')->all();
            $ids = [...$ids, ...$level];
        }

        return $ids;
    }
}
