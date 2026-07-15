<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(private InventoryService $inventory) {}

    public function receive(Purchase $purchase): Purchase
    {
        return DB::transaction(function () use ($purchase) {
            $purchase = Purchase::with(['items.product', 'warehouse'])->lockForUpdate()->findOrFail($purchase->id);
            if ($purchase->status === 'received') {
                return $purchase;
            }
            if (! in_array($purchase->status, ['draft', 'ordered', 'partially_received'], true)) {
                throw ValidationException::withMessages(['purchase' => 'لا يمكن استلام أمر الشراء.']);
            }
            foreach ($purchase->items as $item) {
                $remaining = (float) $item->quantity - (float) $item->received_quantity;
                if ($remaining <= 0) {
                    continue;
                }
                $this->inventory->adjust($purchase->warehouse, $item->product, $remaining, StockMovementType::Purchase, $purchase, null, null, (float) $item->unit_cost);
                $item->update(['received_quantity' => $item->quantity]);
            }
            $purchase->update(['status' => 'received', 'received_at' => now()]);
            ActivityLogger::record('purchase.received', $purchase);

            return $purchase->fresh('items');
        });
    }
}
