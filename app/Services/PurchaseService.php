<?php

namespace App\Services;

use App\Enums\ProductSerialStatus;
use App\Enums\ProductTrackingType;
use App\Enums\StockMovementType;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\ProductSerial;
use App\Models\ProductSerialIdentifier;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(private InventoryService $inventory) {}

    public function receive(Purchase $purchase, array $devices = []): Purchase
    {
        return DB::transaction(function () use ($purchase, $devices) {
            $purchase = Purchase::with(['items.product', 'warehouse'])->lockForUpdate()->findOrFail($purchase->id);
            if ($purchase->status === 'received') {
                return $purchase;
            }
            if (! in_array($purchase->status, ['draft', 'ordered', 'partially_received'], true)) {
                throw ValidationException::withMessages(['purchase' => 'لا يمكن استلام أمر الشراء.']);
            }
            foreach ($purchase->items as $item) {
                $remaining = BigDecimal::of($item->quantity)->minus($item->received_quantity);
                if ($remaining->isZero()) {
                    continue;
                }
                if ($item->product->tracking_type !== ProductTrackingType::Quantity) {
                    $entries = count($purchase->items) === 1 && array_is_list($devices) ? $devices : ($devices[$item->id] ?? []);
                    if (! $remaining->isEqualTo(count($entries))) {
                        throw ValidationException::withMessages(['devices' => 'Device count must match the quantity being received.']);
                    }
                    $previousCost = ProductSerial::where('purchase_item_id', $item->id)->where('status', '!=', ProductSerialStatus::Returned->value)->pluck('purchase_cost')
                        ->reduce(fn (BigDecimal $sum, $cost) => $sum->plus((string) $cost), BigDecimal::zero());
                    $costTotal = BigDecimal::zero();
                    foreach ($entries as $entry) {
                        $costTotal = $costTotal->plus($this->receiveDevice($purchase, $item, $entry));
                    }
                    if (! $costTotal->isEqualTo(BigDecimal::of($item->total)->minus($previousCost))) {
                        throw ValidationException::withMessages(['devices' => 'The sum of device costs must match the purchase line total.']);
                    }
                    $item->update(['received_quantity' => $item->quantity]);

                    continue;
                }
                $this->inventory->adjust($purchase->warehouse, $item->product, (string) $remaining, StockMovementType::Purchase, $purchase, $item->variant, null, $item->unit_cost, eventKey: 'purchase-receipt:'.$item->id);
                $item->update(['received_quantity' => $item->quantity]);
            }
            $purchase->update(['status' => 'received', 'received_at' => now()]);
            ActivityLogger::record('purchase.received', $purchase);

            return $purchase->fresh('items');
        });
    }

    private function receiveDevice(Purchase $purchase, $item, array $entry): BigDecimal
    {
        $identifiers = [];
        foreach (['imei_1', 'imei_2', 'serial_number'] as $kind) {
            $value = strtoupper(trim((string) ($entry[$kind] ?? '')));
            if ($value !== '') {
                $identifiers[$kind] = $value;
            }
        }
        if (! $identifiers || count($identifiers) !== count(array_unique(array_values($identifiers)))) {
            throw ValidationException::withMessages(['devices' => 'Each device needs distinct IMEI or serial identifiers.']);
        }
        if ($item->product->tracking_type === ProductTrackingType::Imei && ! isset($identifiers['imei_1'])) {
            throw ValidationException::withMessages(['devices' => 'IMEI1 is required for an IMEI-tracked product.']);
        }
        if (ProductSerialIdentifier::whereIn('identifier', array_values($identifiers))->exists()) {
            throw ValidationException::withMessages(['devices' => 'An IMEI or serial number is already registered.']);
        }
        $cost = BigDecimal::of((string) ($entry['purchase_cost'] ?? $item->unit_cost));
        if ($cost->isNegative() || $cost->getScale() > 2) {
            throw ValidationException::withMessages(['devices' => 'Device cost must be a nonnegative amount with at most two decimal places.']);
        }
        $serial = $item->product->serials()->create([
            'product_variant_id' => $item->product_variant_id,
            'warehouse_id' => $purchase->warehouse_id,
            'supplier_id' => $purchase->supplier_id,
            'purchase_item_id' => $item->id,
            'imei_1' => $identifiers['imei_1'] ?? null,
            'imei_2' => $identifiers['imei_2'] ?? null,
            'serial_number' => $identifiers['serial_number'] ?? null,
            'purchase_cost' => (string) $cost,
            'status' => ProductSerialStatus::Available,
        ]);
        foreach ($identifiers as $kind => $identifier) {
            $serial->identifiers()->create(['kind' => $kind, 'identifier' => $identifier]);
        }
        $this->inventory->adjust($purchase->warehouse, $item->product, '1', StockMovementType::Purchase, $purchase, $item->variant, $serial, (string) $cost, eventKey: 'purchase-device:'.$serial->id);

        return $cost;
    }

    public function pay(Purchase $purchase, string|int $amount, string $method = 'cash'): Purchase
    {
        return DB::transaction(function () use ($purchase, $amount, $method) {
            $purchase = Purchase::lockForUpdate()->findOrFail($purchase->id);
            $payment = BigDecimal::of((string) $amount);
            if (! $payment->isPositive() || $payment->isGreaterThan(BigDecimal::of($purchase->due_amount))) {
                throw ValidationException::withMessages(['amount' => 'قيمة الدفعة غير صحيحة.']);
            }
            $purchase->payments()->create(['amount' => $amount, 'method' => $method, 'paid_at' => now(), 'paid_by' => auth()->id()]);
            $paid = BigDecimal::of((string) $purchase->payments()->sum('amount'));
            $due = BigDecimal::max(BigDecimal::zero(), BigDecimal::of($purchase->total)->minus($paid));
            $purchase->update(['paid_amount' => (string) $paid, 'due_amount' => (string) $due, 'payment_status' => $due->isZero() ? 'paid' : 'partial']);
            if ($method === 'cash' && $register = CashRegister::where('is_active', true)->first()) {
                CashTransaction::create(['cash_register_id' => $register->id, 'type' => 'purchase_payment', 'amount' => (string) $payment->negated(), 'reference_type' => $purchase->getMorphClass(), 'reference_id' => $purchase->id, 'payment_method' => $method, 'description' => 'سداد مشتريات '.$purchase->invoice_number, 'created_by' => auth()->id()]);
            }
            ActivityLogger::record('purchase.payment', $purchase, newValues: ['amount' => $amount]);

            return $purchase->fresh('payments');
        });
    }

    public function cancel(Purchase $purchase): Purchase
    {
        return DB::transaction(function () use ($purchase) {
            $purchase = Purchase::lockForUpdate()->findOrFail($purchase->id);
            if ($purchase->status === 'received') {
                throw ValidationException::withMessages(['purchase' => 'لا يمكن إلغاء مشتريات تم استلامها؛ استخدم مرتجع المشتريات.']);
            }if ($purchase->status !== 'cancelled') {
                $purchase->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            }ActivityLogger::record('purchase.cancelled', $purchase);

            return $purchase;
        });
    }

    public function returnItems(Purchase $purchase, array $lines, NumberSequenceService $numbers, ?string $idempotencyKey = null): PurchaseReturn
    {
        return DB::transaction(function () use ($purchase, $lines, $numbers, $idempotencyKey) {
            $purchase = Purchase::with(['items.product', 'warehouse'])->lockForUpdate()->findOrFail($purchase->id);
            if ($idempotencyKey && $existing = PurchaseReturn::where('idempotency_key', $idempotencyKey)->where('purchase_id', $purchase->id)->first()) {
                return $existing;
            }
            if ($purchase->status !== 'received') {
                throw ValidationException::withMessages(['purchase' => 'المرتجع متاح للمشتريات المستلمة فقط.']);
            }$return = PurchaseReturn::create(['return_number' => $numbers->next('purchase_return', 'PRET-'.now()->format('Y').'-'), 'purchase_id' => $purchase->id, 'status' => 'completed', 'idempotency_key' => $idempotencyKey, 'created_by' => auth()->id()]);
            $total = BigDecimal::zero();
            foreach ($lines as $line) {
                $item = $purchase->items->firstWhere('id', (int) $line['purchase_item_id']);
                $qty = BigDecimal::of((string) $line['quantity']);
                if (! $item || ! $qty->isPositive() || $qty->isGreaterThan(BigDecimal::of($item->received_quantity))) {
                    throw ValidationException::withMessages(['quantity' => 'كمية مرتجع المشتريات غير صحيحة.']);
                }
                if ($item->product->tracking_type !== ProductTrackingType::Quantity) {
                    $serialIds = $line['product_serial_ids'] ?? [];
                    if (! $qty->isEqualTo(count($serialIds)) || count($serialIds) !== count(array_unique($serialIds))) {
                        throw ValidationException::withMessages(['product_serial_ids' => 'Select the exact devices being returned.']);
                    }
                    foreach ($serialIds as $serialId) {
                        $serial = ProductSerial::lockForUpdate()->findOrFail($serialId);
                        if ($serial->purchase_item_id !== $item->id || $serial->warehouse_id !== $purchase->warehouse_id || $serial->status !== ProductSerialStatus::Available) {
                            throw ValidationException::withMessages(['product_serial_ids' => 'Device is not available from this purchase.']);
                        }
                        $lineTotal = BigDecimal::of($serial->purchase_cost);
                        $returnItem = $return->items()->create(['purchase_item_id' => $item->id, 'quantity' => 1, 'unit_cost' => (string) $lineTotal, 'total' => (string) $lineTotal]);
                        $movement = $this->inventory->adjust($purchase->warehouse, $item->product, '-1', StockMovementType::PurchaseReturn, $return, $item->variant, $serial, (string) $lineTotal, eventKey: 'purchase-return-device:'.$returnItem->id);
                        $removed = BigDecimal::of($movement->value_delta)->negated();
                        $returnItem->update(['inventory_value_removed' => (string) $removed, 'cost_variance' => (string) $lineTotal->minus($removed)]);
                        $serial->update(['status' => ProductSerialStatus::Returned]);
                        $total = $total->plus($lineTotal);
                    }
                    $item->decrement('received_quantity', (string) $qty);

                    continue;
                }
                $lineTotal = $qty->multipliedBy($item->unit_cost)->toScale(2, RoundingMode::HALF_UP);
                $returnItem = $return->items()->create(['purchase_item_id' => $item->id, 'quantity' => (string) $qty, 'unit_cost' => $item->unit_cost, 'total' => (string) $lineTotal]);
                $item->decrement('received_quantity', (string) $qty);
                $movement = $this->inventory->adjust($purchase->warehouse, $item->product, (string) $qty->negated(), StockMovementType::PurchaseReturn, $return, $item->variant, null, eventKey: 'purchase-return:'.$returnItem->id);
                $removed = BigDecimal::of($movement->value_delta)->negated();
                $returnItem->update(['inventory_value_removed' => (string) $removed, 'cost_variance' => (string) $lineTotal->minus($removed)]);
                $total = $total->plus($lineTotal);
            }$return->update(['total' => (string) $total]);
            ActivityLogger::record('purchase.returned', $return);

            return $return->fresh('items');
        });
    }
}
