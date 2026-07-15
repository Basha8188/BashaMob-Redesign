<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductTrackingType;
use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\ActivityLogger;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function store(ProductRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $d = $request->validated();
            $brand = $d['brand'] ? Brand::firstOrCreate(['name' => $d['brand']], ['slug' => Str::slug($d['brand']).'-'.Str::lower(Str::random(4))]) : null;
            $p = Product::create(['sku' => $d['sku'], 'name' => $d['name'], 'brand_id' => $brand?->id, 'tracking_type' => ProductTrackingType::Quantity, 'sale_price' => $d['price'], 'created_by' => $request->user()->id]);
            $w = Warehouse::where('is_default', true)->firstOrFail();
            if ((float) $d['quantity'] > 0) {
                $this->inventory->adjust($w, $p, (float) $d['quantity'], StockMovementType::OpeningBalance, $p);
            }ActivityLogger::record('product.created', $p, newValues: $p->toArray());

            return response()->json(['message' => 'تمت إضافة المنتج وحفظ المخزون.', 'row' => $this->row($p)], 201);
        });
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        return DB::transaction(function () use ($request, $product) {
            $old = $product->toArray();
            $d = $request->validated();
            $brand = $d['brand'] ? Brand::firstOrCreate(['name' => $d['brand']], ['slug' => Str::slug($d['brand']).'-'.Str::lower(Str::random(4))]) : null;
            $product->update(['sku' => $d['sku'], 'name' => $d['name'], 'brand_id' => $brand?->id, 'sale_price' => $d['price'], 'updated_by' => $request->user()->id]);
            if (array_key_exists('quantity', $d)) {
                $w = Warehouse::where('is_default', true)->firstOrFail();
                $current = (float) WarehouseStock::where('warehouse_id', $w->id)->where('product_id', $product->id)->sum('quantity');
                $diff = (float) $d['quantity'] - $current;
                if ($diff) {
                    $this->inventory->adjust($w, $product, $diff, $diff > 0 ? StockMovementType::AdjustmentIn : StockMovementType::AdjustmentOut, $product);
                }
            }ActivityLogger::record('product.updated', $product, $old, $product->fresh()->toArray());

            return response()->json(['message' => 'تم حفظ تعديلات المنتج.', 'row' => $this->row($product->fresh())]);
        });
    }

    public function destroy(Product $product): JsonResponse
    {
        abort_unless(request()->user()->can('delete-products'), 403);
        if ($product->stocks()->where('quantity', '>', 0)->exists()) {
            return response()->json(['message' => 'لا يمكن حذف منتج له رصيد بالمخزن.'], 422);
        }$product->delete();
        ActivityLogger::record('product.deleted', $product);

        return response()->json(['message' => 'تم حذف المنتج.']);
    }

    private function row(Product $p): array
    {
        $p->load(['brand', 'stocks']);

        return ['id' => $p->id, 'cells' => [$p->sku, $p->name, $p->brand?->name ?? '—', number_format((float) $p->sale_price, 2), number_format((float) $p->stocks->sum('quantity'),3)]];
    }
}
