<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function adjust(Warehouse $warehouse, Product $product, string|int|float $quantity, StockMovementType $type, ?Model $reference = null, ?ProductVariant $variant = null, ?ProductSerial $serial = null, string|int|float|null $unitCost = null, ?string $notes = null, ?string $eventKey = null): StockMovement
    {
        return DB::transaction(function () use ($warehouse, $product, $quantity, $type, $reference, $variant, $serial, $unitCost, $notes, $eventKey) {
            if ($eventKey && $existing = StockMovement::where('event_key', $eventKey)->first()) {
                return $existing;
            }
            $qty = BigDecimal::of((string) $quantity);
            if ($qty->isZero() || $qty->getScale() > 3) {
                throw ValidationException::withMessages(['quantity' => 'Quantity must be nonzero and have at most three decimal places.']);
            }
            $inbound = $qty->isPositive();
            $purchaseLike = in_array($type, [StockMovementType::Purchase, StockMovementType::OpeningBalance, StockMovementType::AdjustmentIn, StockMovementType::TransferIn], true);
            $returnLike = in_array($type, [StockMovementType::SaleReturn, StockMovementType::RepairPartReturn], true);
            if ($inbound && ! $purchaseLike && ! $returnLike || ! $inbound && ($purchaseLike || $returnLike)) {
                throw ValidationException::withMessages(['type' => 'Movement direction does not match its type.']);
            }
            if ($type === StockMovementType::OpeningBalance && $unitCost === null) {
                $unitCost = $product->purchase_price;
                $notes = trim(($notes ? $notes.'; ' : '').'Opening cost from product purchase price');
            }
            if ($inbound && $unitCost === null) {
                throw ValidationException::withMessages(['unit_cost' => 'A documented cost is required for stock entering inventory.']);
            }
            if ($unitCost !== null && BigDecimal::of((string) $unitCost)->isNegative()) {
                throw ValidationException::withMessages(['unit_cost' => 'Cost cannot be negative.']);
            }
            $key = $variant?->id ?? 0;
            WarehouseStock::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'product_id' => $product->id, 'variant_key' => $key],
                ['product_variant_id' => $variant?->id, 'quantity' => 0, 'reserved_quantity' => 0, 'average_cost' => null, 'inventory_value' => 0]
            );
            $stock = WarehouseStock::where('warehouse_id', $warehouse->id)->where('product_id', $product->id)
                ->where('variant_key', $key)->lockForUpdate()->firstOrFail();
            $before = BigDecimal::of($stock->getRawOriginal('quantity'));
            $after = $before->plus($qty);
            if ($after->isNegative()) {
                throw ValidationException::withMessages(['stock' => "Insufficient stock for {$product->name}."]);
            }
            $oldValue = $stock->inventory_value === null ? null : BigDecimal::of($stock->inventory_value);
            $cost = $unitCost === null ? null : BigDecimal::of((string) $unitCost);
            if (! $inbound) {
                if ($oldValue === null || $stock->average_cost === null) {
                    throw ValidationException::withMessages(['unit_cost' => 'Inventory cost is unknown; reconcile this balance before issuing stock.']);
                }
                if ($serial) {
                    if ($cost === null) {
                        throw ValidationException::withMessages(['unit_cost' => 'A serialized issue requires its device cost.']);
                    }
                    $delta = $qty->multipliedBy($cost)->toScale(6, RoundingMode::HALF_UP);
                } else {
                    // The final fungible issue takes the entire residual value.
                    $delta = $after->isZero() ? $oldValue->negated() : $qty->multipliedBy($oldValue->dividedBy($before, 12, RoundingMode::HALF_UP))->toScale(6, RoundingMode::HALF_UP);
                    $cost = $delta->dividedBy($qty, 10, RoundingMode::HALF_UP);
                }
            } else {
                $delta = $qty->multipliedBy($cost)->toScale(6, RoundingMode::HALF_UP);
            }
            $newValue = $oldValue === null ? null : $oldValue->plus($delta);
            if ($before->isZero()) {
                $newValue = $delta;
            }
            if ($newValue !== null && ($newValue->isNegative() || $after->isZero() && ! $newValue->isZero())) {
                throw ValidationException::withMessages(['stock' => 'Device costs do not reconcile with inventory value.']);
            }
            // Unknown historical value remains unknown until the balance is reconciled to zero.
            $average = $after->isZero() || $newValue === null ? null : $newValue->dividedBy($after, 10, RoundingMode::HALF_UP);
            $stock->update(['quantity' => (string) $after, 'inventory_value' => $newValue === null ? null : (string) $newValue, 'average_cost' => $average === null ? null : (string) $average]);

            return StockMovement::create([
                'warehouse_id' => $warehouse->id, 'product_id' => $product->id, 'product_variant_id' => $variant?->id, 'product_serial_id' => $serial?->id,
                'type' => $type, 'quantity' => (string) $qty, 'quantity_before' => (string) $before, 'quantity_after' => (string) $after,
                'unit_cost' => $cost === null ? null : (string) $cost, 'value_delta' => $newValue === null ? null : (string) $delta,
                'value_before' => $oldValue === null ? null : (string) $oldValue, 'value_after' => $newValue === null ? null : (string) $newValue,
                'event_key' => $eventKey, 'reference_type' => $reference?->getMorphClass(), 'reference_id' => $reference?->getKey(),
                'notes' => $notes, 'created_by' => auth()->id(),
            ]);
        }, 3);
    }
}
