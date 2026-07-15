<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
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

    public function pay(Purchase $purchase, float $amount, string $method = 'cash'): Purchase
    {
        return DB::transaction(function () use ($purchase, $amount, $method) {
            $purchase = Purchase::lockForUpdate()->findOrFail($purchase->id);
            if ($amount <= 0 || $amount > (float) $purchase->due_amount) {
                throw ValidationException::withMessages(['amount' => 'قيمة الدفعة غير صحيحة.']);
            }
            $purchase->payments()->create(['amount' => $amount, 'method' => $method, 'paid_at' => now(), 'paid_by' => auth()->id()]);
            $paid = (float) $purchase->payments()->sum('amount');
            $due = max(0, (float) $purchase->total - $paid);
            $purchase->update(['paid_amount' => $paid, 'due_amount' => $due, 'payment_status' => $due <= 0 ? 'paid' : 'partial']);
            if ($method === 'cash' && $register = CashRegister::where('is_active', true)->first()) {
                CashTransaction::create(['cash_register_id' => $register->id, 'type' => 'purchase_payment', 'amount' => -$amount, 'reference_type' => $purchase->getMorphClass(), 'reference_id' => $purchase->id, 'payment_method' => $method, 'description' => 'سداد مشتريات '.$purchase->invoice_number, 'created_by' => auth()->id()]);
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

    public function returnItems(Purchase $purchase, array $lines, NumberSequenceService $numbers): PurchaseReturn
    {
        return DB::transaction(function () use ($purchase, $lines, $numbers) {
            $purchase = Purchase::with(['items.product', 'warehouse'])->lockForUpdate()->findOrFail($purchase->id);
            if ($purchase->status !== 'received') {
                throw ValidationException::withMessages(['purchase' => 'المرتجع متاح للمشتريات المستلمة فقط.']);
            }$return = PurchaseReturn::create(['return_number' => $numbers->next('purchase_return', 'PRET-'.now()->format('Y').'-'), 'purchase_id' => $purchase->id, 'status' => 'completed', 'created_by' => auth()->id()]);
            $total = 0;
            foreach ($lines as $line) {
                $item = $purchase->items->firstWhere('id', (int) $line['purchase_item_id']);
                $qty = (float) $line['quantity'];
                if (! $item || $qty <= 0 || $qty > (float) $item->received_quantity) {
                    throw ValidationException::withMessages(['quantity' => 'كمية مرتجع المشتريات غير صحيحة.']);
                }$lineTotal = $qty * (float) $item->unit_cost;
                $return->items()->create(['purchase_item_id' => $item->id, 'quantity' => $qty, 'unit_cost' => $item->unit_cost, 'total' => $lineTotal]);
                $item->decrement('received_quantity', $qty);
                $this->inventory->adjust($purchase->warehouse, $item->product, -$qty, StockMovementType::PurchaseReturn, $return, null, null, (float) $item->unit_cost);
                $total += $lineTotal;
            }$return->update(['total' => $total]);
            ActivityLogger::record('purchase.returned', $return);

            return $return->fresh('items');
        });
    }
}
