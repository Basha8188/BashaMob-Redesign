<?php

namespace App\Services;

use App\Enums\RepairStatus;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\RepairTicket;
use App\Models\Warehouse;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RepairService
{
    public function __construct(private InventoryService $inventory) {}

    public function changeStatus(RepairTicket $ticket, RepairStatus $status, ?string $notes = null): RepairTicket
    {
        return DB::transaction(function () use ($ticket, $status, $notes) {
            $ticket = RepairTicket::lockForUpdate()->findOrFail($ticket->id);
            $old = $ticket->status;
            if ($old === $status) {
                return $ticket;
            }
            if ($status === RepairStatus::InProgress
                && ! $ticket->quotations()->where('status', 'approved')->exists()
                && ! auth()->user()?->can('repair_quotations.approve_override')) {
                throw ValidationException::withMessages(['status' => 'لا يمكن بدء الإصلاح قبل موافقة العميل على عرض السعر.']);
            }
            $ticket->update(['status' => $status]);
            $ticket->statusHistory()->create(['from_status' => $old->value, 'to_status' => $status->value, 'notes' => $notes, 'changed_by' => auth()->id()]);
            ActivityLogger::record('repair.status_changed', $ticket, ['status' => $old->value], ['status' => $status->value]);

            return $ticket->fresh('statusHistory');
        });
    }

    public function usePart(RepairTicket $ticket, Warehouse $warehouse, Product $product, float $quantity, float $salePrice): void
    {
        DB::transaction(function () use ($ticket, $warehouse, $product, $quantity, $salePrice) {
            $part = $ticket->parts()->create(['warehouse_id' => $warehouse->id, 'product_id' => $product->id, 'quantity' => $quantity, 'unit_cost' => 0, 'sale_price' => $salePrice, 'added_by' => auth()->id()]);
            $movement = $this->inventory->adjust($warehouse, $product, (string) BigDecimal::of((string) $quantity)->negated(), StockMovementType::RepairPartUsage, $part, eventKey: 'repair-part:'.$part->id);
            $part->update(['unit_cost' => (string) BigDecimal::of($movement->unit_cost)->toScale(2, RoundingMode::HALF_UP)]);
            ActivityLogger::record('repair.part_used', $ticket, newValues: ['product_id' => $product->id, 'quantity' => $quantity]);
        });
    }
}
