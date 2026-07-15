<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function store(Request $r): JsonResponse
    {
        abort_unless($r->user()->can('create-products'), 403);
        $d = $r->validate(['name' => ['required', 'string', 'max:120'], 'parent_id' => ['nullable', 'exists:categories,id']]);
        $c = Category::create(['name' => $d['name'], 'slug' => Str::slug($d['name']).'-'.Str::lower(Str::random(4)), 'parent_id' => $d['parent_id'] ?? null]);

        return response()->json($c, 201);
    }

    public function update(Request $r, Category $category): JsonResponse
    {
        abort_unless($r->user()->can('edit-products'), 403);
        $d = $r->validate(['name' => ['required', 'string', 'max:120'], 'is_active' => ['sometimes', 'boolean']]);
        $category->update($d);

        return response()->json($category);
    }

    public function destroy(Category $category): JsonResponse
    {
        abort_unless(request()->user()->can('delete-products'), 403);
        abort_if($category->products()->exists(), 422, 'التصنيف مرتبط بمنتجات.');
        $category->delete();

        return response()->json(['message' => 'تم حذف التصنيف.']);
    }
}
