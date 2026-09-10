<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InventoryAdjustmentRequest;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function store(InventoryAdjustmentRequest $r): JsonResponse
    {
        $d = $r->validated();
        $warehouse = Warehouse::where('name', $d['warehouse'])->orWhere('code', $d['warehouse'])->firstOrFail();
        $product = Product::where('name', $d['product'])->orWhere('sku', $d['product'])->firstOrFail();
        $current = (float) WarehouseStock::where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->sum('quantity');
        $diff = (float) $d['stock'] - $current;
        $product->update(['reorder_level' => $d['minimum']]);
        if ($diff) {
            $this->inventory->adjust($warehouse, $product, $diff, $diff > 0 ? StockMovementType::AdjustmentIn : StockMovementType::AdjustmentOut, $product);
        }

        return response()->json(['message' => 'تم ضبط المخزون وتسجيل الحركة.', 'row' => ['id' => WarehouseStock::where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->value('id'), 'cells' => [$warehouse->name, $product->name, number_format((float) $d['stock'], 3), $d['minimum'], (float) $d['stock'] <= $d['minimum'] ? 'منخفض' : 'متوفر']]], 201);
    }
}
