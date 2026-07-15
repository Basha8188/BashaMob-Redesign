<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function adjust(Warehouse $warehouse, Product $product, float $quantity, StockMovementType $type, ?Model $reference = null, ?ProductVariant $variant = null, ?ProductSerial $serial = null, ?float $unitCost = null, ?string $notes = null): StockMovement
    {
        return DB::transaction(function () use ($warehouse, $product, $quantity, $type, $reference, $variant, $serial, $unitCost, $notes) {
            WarehouseStock::firstOrCreate(['warehouse_id' => $warehouse->id, 'product_id' => $product->id, 'product_variant_id' => $variant?->id], ['quantity' => 0, 'reserved_quantity' => 0, 'average_cost' => $unitCost ?? 0]);
            $stock = WarehouseStock::where('warehouse_id', $warehouse->id)->where('product_id', $product->id)
                ->where('product_variant_id', $variant?->id)->lockForUpdate()->firstOrFail();
            $before = (float) $stock->quantity;
            $after = round($before + $quantity, 3);
            if ($after < 0) {
                throw ValidationException::withMessages(['stock' => "المخزون غير كافٍ للمنتج {$product->name}."]);
            }

            $stock->quantity = $after;
            if ($quantity > 0 && $unitCost !== null) {
                $oldValue = $before * (float) $stock->average_cost;
                $stock->average_cost = $after > 0 ? round(($oldValue + ($quantity * $unitCost)) / $after, 2) : 0;
            }
            $stock->save();

            return StockMovement::create([
                'warehouse_id' => $warehouse->id, 'product_id' => $product->id, 'product_variant_id' => $variant?->id, 'product_serial_id' => $serial?->id,
                'type' => $type, 'quantity' => $quantity, 'quantity_before' => $before, 'quantity_after' => $after, 'unit_cost' => $unitCost,
                'reference_type' => $reference?->getMorphClass(), 'reference_id' => $reference?->getKey(), 'notes' => $notes, 'created_by' => auth()->id(),
            ]);
        });
    }
}
