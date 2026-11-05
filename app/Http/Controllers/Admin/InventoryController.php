<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductTrackingType;
use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InventoryAdjustmentRequest;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\InventoryService;
use Brick\Math\BigDecimal;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function store(InventoryAdjustmentRequest $r): JsonResponse
    {
        $d = $r->validated();
        $warehouse = Warehouse::where('name', $d['warehouse'])->orWhere('code', $d['warehouse'])->firstOrFail();
        $product = Product::where('name', $d['product'])->orWhere('sku', $d['product'])->firstOrFail();
        if ($product->tracking_type !== ProductTrackingType::Quantity) {
            throw ValidationException::withMessages(['product' => 'Serialized stock changes through device purchases, sales, and returns only.']);
        }
        DB::transaction(function () use ($warehouse, $product, $d) {
            $current = WarehouseStock::where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->where('variant_key', 0)->lockForUpdate()->value('quantity') ?? '0';
            $diff = BigDecimal::of((string) $d['stock'])->minus($current);
            $product->update(['reorder_level' => $d['minimum']]);
            if (! $diff->isZero()) {
                $this->inventory->adjust($warehouse, $product, (string) $diff, $diff->isPositive() ? StockMovementType::AdjustmentIn : StockMovementType::AdjustmentOut, $product, unitCost: $diff->isPositive() ? ($d['unit_cost'] ?? null) : null, notes: $diff->isPositive() ? 'Manual adjustment: stated unit cost' : 'Manual adjustment: carrying cost');
            }
        });

        return response()->json(['message' => 'تم ضبط المخزون وتسجيل الحركة.', 'row' => ['id' => WarehouseStock::where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->value('id'), 'cells' => [$warehouse->name, $product->name, number_format((float) $d['stock'], 3), $d['minimum'], (float) $d['stock'] <= $d['minimum'] ? 'منخفض' : 'متوفر']]], 201);
    }
}
