<?php

namespace App\Services;

use App\Enums\ProductSerialStatus;
use App\Enums\ProductTrackingType;
use App\Enums\StockMovementType;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\ProductSerial;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\WarehouseStock;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(private InventoryService $inventory, private NumberSequenceService $numbers, private DevicePassportService $passports) {}

    public function finalize(Sale $sale, array $payments = []): Sale
    {
        return DB::transaction(function () use ($sale, $payments) {
            $sale = Sale::with(['items.product', 'items.serial', 'warehouse'])->lockForUpdate()->findOrFail($sale->id);
            if ($sale->status === 'finalized') {
                return $sale;
            }
            if ($sale->status !== 'draft') {
                throw ValidationException::withMessages(['sale' => 'لا يمكن اعتماد هذه الفاتورة.']);
            }

            foreach ($sale->items as $item) {
                $tracked = $item->product->tracking_type !== ProductTrackingType::Quantity;
                if ($tracked && ! $item->product_serial_id || ! $tracked && $item->product_serial_id) {
                    throw ValidationException::withMessages(['serial' => 'A serialized product requires its own device identifier.']);
                }
                $serial = null;
                if ($tracked) {
                    $serial = ProductSerial::lockForUpdate()->findOrFail($item->product_serial_id);
                    if ($serial->status !== ProductSerialStatus::Available) {
                        throw ValidationException::withMessages(['serial' => 'تم بيع هذا الـ IMEI أو Serial من قبل.']);
                    }
                    if ($serial->product_id !== $item->product_id || $serial->warehouse_id !== $sale->warehouse_id || $serial->product_variant_id !== $item->product_variant_id) {
                        throw ValidationException::withMessages(['serial' => 'Device does not match the product, variant, and warehouse.']);
                    }
                    if (! BigDecimal::of($item->quantity)->isEqualTo(1)) {
                        throw ValidationException::withMessages(['quantity' => 'المنتج المتسلسل يجب أن تكون كميته 1.']);
                    }
                    if (! $serial->purchase_item_id && ! $serial->purchase_cost) {
                        throw ValidationException::withMessages(['serial' => 'Device cost is unknown.']);
                    }
                    $unitCost = $serial->purchase_cost;
                    $costSource = 'serial_purchase_cost';
                    $serial->update(['status' => ProductSerialStatus::Sold, 'sold_at' => now(), 'sale_item_id' => $item->id]);
                    $this->passports->syncSoldSerial($serial->fresh(), $sale);
                } else {
                    [$unitCost, $costSource] = $this->resolveUnitCost($sale, $item);
                }
                $movement = $this->inventory->adjust($sale->warehouse, $item->product, (string) BigDecimal::of($item->quantity)->negated(), StockMovementType::Sale, $sale, $item->product_variant_id ? $item->product->variants()->find($item->product_variant_id) : null, $serial, $tracked ? $unitCost : null, eventKey: 'sale:'.$item->id);
                $item->update(['unit_cost_snapshot' => $movement->unit_cost, 'cost_source' => $costSource]);
            }

            foreach ($payments as $payment) {
                $sale->payments()->create(['amount' => $payment['amount'], 'method' => $payment['method'], 'reference' => $payment['reference'] ?? null, 'paid_at' => $payment['paid_at'] ?? now(), 'received_by' => auth()->id()]);
                if ($payment['method'] === 'cash' && $register = CashRegister::where('is_active', true)->first()) {
                    CashTransaction::create(['cash_register_id' => $register->id, 'type' => 'sale_payment', 'amount' => $payment['amount'], 'reference_type' => $sale->getMorphClass(), 'reference_id' => $sale->id, 'payment_method' => 'cash', 'description' => 'تحصيل فاتورة '.$sale->invoice_number, 'created_by' => auth()->id()]);
                }
            }
            $paid = BigDecimal::of((string) $sale->payments()->sum('amount'));
            $total = BigDecimal::of($sale->total);
            $sale->update(['status' => 'finalized', 'finalized_at' => now(), 'paid_amount' => (string) $paid, 'due_amount' => (string) BigDecimal::max(BigDecimal::zero(), $total->minus($paid)), 'payment_status' => $paid->isZero() ? 'unpaid' : ($paid->isGreaterThanOrEqualTo($total) ? 'paid' : 'partial')]);
            ActivityLogger::record('sale.finalized', $sale, newValues: $sale->fresh()->toArray());

            return $sale->fresh(['items', 'payments']);
        });
    }

    public function cancel(Sale $sale): Sale
    {
        return DB::transaction(function () use ($sale) {
            $sale = Sale::with(['items.product', 'items.serial', 'warehouse'])->lockForUpdate()->findOrFail($sale->id);
            if ($sale->status === 'cancelled') {
                return $sale;
            }
            if ($sale->status !== 'finalized') {
                throw ValidationException::withMessages(['sale' => 'لا يمكن إلغاء هذه الفاتورة.']);
            }
            foreach ($sale->items as $item) {
                if (BigDecimal::of($item->returned_quantity)->isPositive()) {
                    throw ValidationException::withMessages(['sale' => 'Cannot cancel a sale with existing returns.']);
                }
                if ($item->product_serial_id) {
                    $serial = ProductSerial::lockForUpdate()->findOrFail($item->product_serial_id);
                    if ($serial->status !== ProductSerialStatus::Sold || $serial->sale_item_id !== $item->id) {
                        throw ValidationException::withMessages(['serial' => 'Device is no longer eligible for cancellation.']);
                    }
                } else {
                    $serial = null;
                }
                $this->inventory->adjust($sale->warehouse, $item->product, $item->quantity, StockMovementType::SaleReturn, $sale, $item->product_variant_id ? $item->product->variants()->find($item->product_variant_id) : null, $serial, $item->unit_cost_snapshot, 'Sale cancellation', 'sale-cancel:'.$item->id);
                $serial?->update(['status' => ProductSerialStatus::Available, 'sold_at' => null]);
            }
            $sale->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            ActivityLogger::record('sale.cancelled', $sale);

            return $sale->fresh();
        });
    }

    public function refund(Sale $sale, array $lines, ?string $reason = null, ?string $idempotencyKey = null): SaleReturn
    {
        return DB::transaction(function () use ($sale, $lines, $reason, $idempotencyKey) {
            $sale = Sale::with(['items.product', 'warehouse'])->lockForUpdate()->findOrFail($sale->id);
            if ($idempotencyKey && $existing = SaleReturn::where('idempotency_key', $idempotencyKey)->where('sale_id', $sale->id)->first()) {
                return $existing;
            }
            if ($sale->status !== 'finalized') {
                throw ValidationException::withMessages(['sale' => 'المرتجع متاح للفواتير المعتمدة فقط.']);
            }
            $return = SaleReturn::create(['return_number' => $this->numbers->next('sale_return', 'SRET-'.now()->format('Y').'-'), 'sale_id' => $sale->id, 'status' => 'completed', 'reason' => $reason, 'idempotency_key' => $idempotencyKey, 'created_by' => auth()->id()]);
            $total = BigDecimal::zero();
            foreach ($lines as $line) {
                $item = $sale->items->firstWhere('id', (int) $line['sale_item_id']);
                $quantity = BigDecimal::of((string) $line['quantity']);
                if (! $item || ! $quantity->isPositive() || $quantity->isGreaterThan(BigDecimal::of($item->quantity)->minus($item->returned_quantity))) {
                    throw ValidationException::withMessages(['quantity' => 'كمية المرتجع أكبر من الكمية المتاحة.']);
                }
                $lineTotal = $quantity->multipliedBy($item->unit_price)->toScale(2, RoundingMode::HALF_UP);
                $total = $total->plus($lineTotal);
                $serial = $item->product_serial_id ? ProductSerial::lockForUpdate()->findOrFail($item->product_serial_id) : null;
                $condition = $line['device_condition'] ?? null;
                if ($serial && (! $quantity->isEqualTo(1) || ! in_array($condition, ['available', 'damaged', 'inspection'], true) || $serial->status !== ProductSerialStatus::Sold || $serial->sale_item_id !== $item->id)) {
                    throw ValidationException::withMessages(['device_condition' => 'Specify a valid condition for the sold device.']);
                }
                $returnItem = $return->items()->create(['sale_item_id' => $item->id, 'product_serial_id' => $serial?->id, 'device_condition' => $condition, 'quantity' => (string) $quantity, 'unit_price' => $item->unit_price, 'total' => (string) $lineTotal]);
                $item->increment('returned_quantity', (string) $quantity);
                if (! $serial || $condition === 'available') {
                    $this->inventory->adjust($sale->warehouse, $item->product, (string) $quantity, StockMovementType::SaleReturn, $return, $item->product_variant_id ? $item->product->variants()->find($item->product_variant_id) : null, $serial, $item->unit_cost_snapshot, eventKey: 'sale-return:'.$returnItem->id);
                }
                $serial?->update(['status' => match ($condition) {
                    'available' => ProductSerialStatus::Available,
                    'damaged' => ProductSerialStatus::Damaged,
                    default => ProductSerialStatus::Returned,
                }, 'sold_at' => null]);
            }
            $return->update(['total' => (string) $total]);
            ActivityLogger::record('sale.refunded', $return);

            return $return->fresh('items');
        });
    }

    public function pay(Sale $sale, string|int $amount, string $method = 'cash', ?string $reference = null): Sale
    {
        return DB::transaction(function () use ($sale, $amount, $method, $reference) {
            $sale = Sale::lockForUpdate()->findOrFail($sale->id);
            $payment = BigDecimal::of((string) $amount);
            if ($sale->status !== 'finalized' || ! $payment->isPositive() || $payment->isGreaterThan(BigDecimal::of($sale->due_amount))) {
                throw ValidationException::withMessages(['amount' => 'قيمة الدفعة غير صحيحة أو تتجاوز المتبقي.']);
            }
            $sale->payments()->create(['amount' => $amount, 'method' => $method, 'reference' => $reference, 'paid_at' => now(), 'received_by' => auth()->id()]);
            $paid = BigDecimal::of((string) $sale->payments()->sum('amount'));
            $due = BigDecimal::max(BigDecimal::zero(), BigDecimal::of($sale->total)->minus($paid));
            $sale->update(['paid_amount' => (string) $paid, 'due_amount' => (string) $due, 'payment_status' => $due->isZero() ? 'paid' : 'partial']);
            if ($method === 'cash' && $register = CashRegister::where('is_active', true)->first()) {
                CashTransaction::create(['cash_register_id' => $register->id, 'type' => 'sale_payment', 'amount' => $amount, 'reference_type' => $sale->getMorphClass(), 'reference_id' => $sale->id, 'payment_method' => $method, 'description' => 'تحصيل فاتورة '.$sale->invoice_number, 'created_by' => auth()->id()]);
            }
            ActivityLogger::record('sale.payment_received', $sale, newValues: ['amount' => $amount, 'due_amount' => (string) $due]);

            return $sale->fresh('payments');
        });
    }

    private function resolveUnitCost(Sale $sale, $item): array
    {
        if ($item->serial?->purchase_cost !== null) {
            return [(string) $item->serial->purchase_cost, 'serial_purchase_cost'];
        }

        $stockCost = WarehouseStock::query()
            ->where('warehouse_id', $sale->warehouse_id)
            ->where('product_id', $item->product_id)
            ->when(
                $item->product_variant_id,
                fn ($query) => $query->where('product_variant_id', $item->product_variant_id),
                fn ($query) => $query->whereNull('product_variant_id')
            )
            ->value('average_cost');
        if ($stockCost !== null) {
            return [(string) $stockCost, 'warehouse_average_cost'];
        }
        throw ValidationException::withMessages(['unit_cost' => 'Inventory cost is unknown. Reconcile the stock before selling.']);
    }
}
