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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function store(ProductRequest $request): JsonResponse
    {
        $imagePath = $request->file('image')?->store('products', 'public');

        try {
            return DB::transaction(function () use ($request, $imagePath) {
                $d = $request->validated();
                $brandName = $d['brand'] ?? null;
                $brand = $brandName ? Brand::firstOrCreate(['name' => $brandName], ['slug' => Str::slug($brandName).'-'.Str::lower(Str::random(4))]) : null;
                $p = Product::create(['sku' => $d['sku'], 'barcode' => ($d['barcode'] ?? null) ?: $this->generateBarcode(), 'name' => $d['name'], 'brand_id' => $brand?->id, 'image_path' => $imagePath, 'tracking_type' => ProductTrackingType::Quantity, 'sale_price' => $d['price'], 'created_by' => $request->user()->id]);
                $w = Warehouse::where('is_default', true)->firstOrFail();
                if ((float) $d['quantity'] > 0) {
                    $this->inventory->adjust($w, $p, (float) $d['quantity'], StockMovementType::OpeningBalance, $p);
                }
                ActivityLogger::record('product.created', $p, newValues: $p->toArray());

                return response()->json(['message' => 'تمت إضافة المنتج وحفظ المخزون.', 'row' => $this->row($p)], 201);
            });
        } catch (\Throwable $exception) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $exception;
        }
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $newImagePath = $request->file('image')?->store('products', 'public');
        $oldImagePath = $product->image_path;

        try {
            $response = DB::transaction(function () use ($request, $product, $newImagePath) {
                $old = $product->toArray();
                $d = $request->validated();
                $brandName = $d['brand'] ?? null;
                $brand = $brandName ? Brand::firstOrCreate(['name' => $brandName], ['slug' => Str::slug($brandName).'-'.Str::lower(Str::random(4))]) : null;
                $product->update(['sku' => $d['sku'], 'barcode' => ($d['barcode'] ?? null) ?: ($product->barcode ?: $this->generateBarcode()), 'name' => $d['name'], 'brand_id' => $brand?->id, 'image_path' => $newImagePath ?: $product->image_path, 'sale_price' => $d['price'], 'updated_by' => $request->user()->id]);
                if (array_key_exists('quantity', $d)) {
                    $w = Warehouse::where('is_default', true)->firstOrFail();
                    $current = (float) WarehouseStock::where('warehouse_id', $w->id)->where('product_id', $product->id)->sum('quantity');
                    $diff = (float) $d['quantity'] - $current;
                    if ($diff) {
                        $this->inventory->adjust($w, $product, $diff, $diff > 0 ? StockMovementType::AdjustmentIn : StockMovementType::AdjustmentOut, $product);
                    }
                }
                ActivityLogger::record('product.updated', $product, $old, $product->fresh()->toArray());

                return response()->json(['message' => 'تم حفظ تعديلات المنتج.', 'row' => $this->row($product->fresh())]);
            });
        } catch (\Throwable $exception) {
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }

            throw $exception;
        }

        if ($newImagePath && $oldImagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return $response;
    }

    public function destroy(Product $product): JsonResponse
    {
        abort_unless(request()->user()->can('delete-products'), 403);
        if ($product->stocks()->where('quantity', '>', 0)->exists()) {
            return response()->json(['message' => 'لا يمكن حذف منتج له رصيد بالمخزن.'], 422);
        }
        $product->delete();
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        ActivityLogger::record('product.deleted', $product);

        return response()->json(['message' => 'تم حذف المنتج.']);
    }

    private function row(Product $p): array
    {
        $p->load(['brand', 'stocks']);

        return ['id' => $p->id, 'cells' => [$p->sku, $p->barcode, $p->name, $p->brand?->name ?? '—', number_format((float) $p->sale_price, 2), number_format((float) $p->stocks->sum('quantity'), 3)], 'image_url' => $p->image_path ? Storage::disk('public')->url($p->image_path) : null];
    }

    private function generateBarcode(): string
    {
        do {
            $body = '200'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
            $sum = 0;
            foreach (str_split($body) as $index => $digit) {
                $sum += (int) $digit * ($index % 2 === 0 ? 1 : 3);
            }
            $barcode = $body.((10 - ($sum % 10)) % 10);
        } while (Product::withTrashed()->where('barcode', $barcode)->exists());

        return $barcode;
    }
}
