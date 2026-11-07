<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CategoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('name'))),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique(Category::class)->ignore($this->route('category'))],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * Keep the tree at most three levels deep and free of cycles.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty() || $this->input('parent_id') === null) {
                    return;
                }

                /** @var Category|null $category */
                $category = $this->route('category');
                $parent = Category::findOrFail($this->integer('parent_id'));

                if ($category !== null && in_array($parent->id, $category->selfAndDescendantIds(), true)) {
                    $validator->errors()->add('parent_id', 'Induk kategori tidak boleh kategori itu sendiri atau turunannya.');

                    return;
                }

                $height = $category?->subtreeHeight() ?? 0;

                if ($parent->depth() + 1 + $height > Category::MAX_DEPTH) {
                    $validator->errors()->add('parent_id', 'Kategori maksimal '.Category::MAX_DEPTH.' level.');
                }
            },
        ];
    }
}
