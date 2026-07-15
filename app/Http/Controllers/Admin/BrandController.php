<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function store(Request $r): JsonResponse
    {
        abort_unless($r->user()->can('create-products'), 403);
        $d = $r->validate(['name' => ['required', 'string', 'max:120', 'unique:brands,name']]);
        $b = Brand::create(['name' => $d['name'], 'slug' => Str::slug($d['name']).'-'.Str::lower(Str::random(4))]);

        return response()->json($b, 201);
    }

    public function update(Request $r, Brand $brand): JsonResponse
    {
        abort_unless($r->user()->can('edit-products'), 403);
        $brand->update($r->validate(['name' => ['required', 'string', 'max:120'], 'is_active' => ['sometimes', 'boolean']]));

        return response()->json($brand);
    }

    public function destroy(Brand $brand): JsonResponse
    {
        abort_unless(request()->user()->can('delete-products'), 403);
        abort_if($brand->products()->exists(), 422, 'الماركة مرتبطة بمنتجات.');
        $brand->delete();

        return response()->json(['message' => 'تم حذف الماركة.']);
    }
}
