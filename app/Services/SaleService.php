<?php

namespace App\Services;

use App\Enums\ProductSerialStatus;
use App\Enums\StockMovementType;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\ProductSerial;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\WarehouseStock;
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
                [$unitCost, $costSource] = $this->resolveUnitCost($sale, $item);
                $item->update([
                    'unit_cost_snapshot' => $unitCost,
                    'cost_source' => $costSource,
                ]);

                if ($item->product_serial_id) {
                    $serial = ProductSerial::lockForUpdate()->findOrFail($item->product_serial_id);
                    if ($serial->status !== ProductSerialStatus::Available) {
                        throw ValidationException::withMessages(['serial' => 'تم بيع هذا الـ IMEI أو Serial من قبل.']);
                    }
                    if ((float) $item->quantity !== 1.0) {
                        throw ValidationException::withMessages(['quantity' => 'المنتج المتسلسل يجب أن تكون كميته 1.']);
                    }
                    $serial->update(['status' => ProductSerialStatus::Sold, 'sold_at' => now(), 'sale_item_id' => $item->id]);
                    $this->passports->syncSoldSerial($serial->fresh(), $sale);
                }
                $this->inventory->adjust($sale->warehouse, $item->product, -(float) $item->quantity, StockMovementType::Sale, $sale, $item->product_variant_id ? $item->product->variants()->find($item->product_variant_id) : null, $item->serial, (float) $unitCost);
            }

            foreach ($payments as $payment) {
                $sale->payments()->create(['amount' => $payment['amount'], 'method' => $payment['method'], 'reference' => $payment['reference'] ?? null, 'paid_at' => $payment['paid_at'] ?? now(), 'received_by' => auth()->id()]);
                if ($payment['method'] === 'cash' && $register = CashRegister::where('is_active', true)->first()) {
                    CashTransaction::create(['cash_register_id' => $register->id, 'type' => 'sale_payment', 'amount' => $payment['amount'], 'reference_type' => $sale->getMorphClass(), 'reference_id' => $sale->id, 'payment_method' => 'cash', 'description' => 'تحصيل فاتورة '.$sale->invoice_number, 'created_by' => auth()->id()]);
                }
            }
            $paid = (float) $sale->payments()->sum('amount');
            $total = (float) $sale->total;
            $sale->update(['status' => 'finalized', 'finalized_at' => now(), 'paid_amount' => $paid, 'due_amount' => max(0, $total - $paid), 'payment_status' => $paid <= 0 ? 'unpaid' : ($paid >= $total ? 'paid' : 'partial')]);
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
                $this->inventory->adjust($sale->warehouse, $item->product, (float) $item->quantity, StockMovementType::SaleReturn, $sale, null, $item->serial, (float) ($item->unit_cost_snapshot ?? $item->product->purchase_price), 'عكس فاتورة ملغاة');
                $item->serial?->update(['status' => ProductSerialStatus::Available, 'sold_at' => null, 'sale_item_id' => null]);
            }
            $sale->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            ActivityLogger::record('sale.cancelled', $sale);

            return $sale->fresh();
        });
    }

    public function refund(Sale $sale, array $lines, ?string $reason = null): SaleReturn
    {
        return DB::transaction(function () use ($sale, $lines, $reason) {
            $sale = Sale::with(['items.product', 'warehouse'])->lockForUpdate()->findOrFail($sale->id);
            if ($sale->status !== 'finalized') {
                throw ValidationException::withMessages(['sale' => 'المرتجع متاح للفواتير المعتمدة فقط.']);
            }
            $return = SaleReturn::create(['return_number' => $this->numbers->next('sale_return', 'SRET-'.now()->format('Y').'-'), 'sale_id' => $sale->id, 'status' => 'completed', 'reason' => $reason, 'created_by' => auth()->id()]);
            $total = 0;
            foreach ($lines as $line) {
                $item = $sale->items->firstWhere('id', (int) $line['sale_item_id']);
                $quantity = (float) $line['quantity'];
                if (! $item || $quantity <= 0 || $quantity > ((float) $item->quantity - (float) $item->returned_quantity)) {
                    throw ValidationException::withMessages(['quantity' => 'كمية المرتجع أكبر من الكمية المتاحة.']);
                }
                $lineTotal = round($quantity * (float) $item->unit_price, 2);
                $total += $lineTotal;
                $return->items()->create(['sale_item_id' => $item->id, 'quantity' => $quantity, 'unit_price' => $item->unit_price, 'total' => $lineTotal]);
                $item->increment('returned_quantity', $quantity);
                $this->inventory->adjust($sale->warehouse, $item->product, $quantity, StockMovementType::SaleReturn, $return, null, $item->serial, (float) ($item->unit_cost_snapshot ?? $item->product->purchase_price));
            }
            $return->update(['total' => $total]);
            ActivityLogger::record('sale.refunded', $return);

            return $return->fresh('items');
        });
    }

    public function pay(Sale $sale, float $amount, string $method = 'cash', ?string $reference = null): Sale
    {
        return DB::transaction(function () use ($sale, $amount, $method, $reference) {
            $sale = Sale::lockForUpdate()->findOrFail($sale->id);
            if ($sale->status !== 'finalized' || $amount <= 0 || $amount > (float) $sale->due_amount) {
                throw ValidationException::withMessages(['amount' => 'قيمة الدفعة غير صحيحة أو تتجاوز المتبقي.']);
            }
            $sale->payments()->create(['amount' => $amount, 'method' => $method, 'reference' => $reference, 'paid_at' => now(), 'received_by' => auth()->id()]);
            $paid = (float) $sale->payments()->sum('amount');
            $due = max(0, (float) $sale->total - $paid);
            $sale->update(['paid_amount' => $paid, 'due_amount' => $due, 'payment_status' => $due <= 0 ? 'paid' : 'partial']);
            if ($method === 'cash' && $register = CashRegister::where('is_active', true)->first()) {
                CashTransaction::create(['cash_register_id' => $register->id, 'type' => 'sale_payment', 'amount' => $amount, 'reference_type' => $sale->getMorphClass(), 'reference_id' => $sale->id, 'payment_method' => $method, 'description' => 'تحصيل فاتورة '.$sale->invoice_number, 'created_by' => auth()->id()]);
            }
            ActivityLogger::record('sale.payment_received', $sale, newValues: ['amount' => $amount, 'due_amount' => $due]);

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

        $variantCost = $item->product_variant_id
            ? $item->product->variants()->whereKey($item->product_variant_id)->value('purchase_price')
            : null;
        if ($variantCost !== null) {
            return [(string) $variantCost, 'variant_purchase_price'];
        }

        return [(string) ($item->product->purchase_price ?? 0), 'product_purchase_price'];
    }
}
