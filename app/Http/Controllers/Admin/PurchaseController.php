<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductTrackingType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\NumberSequenceService;
use App\Services\PurchaseService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseController extends Controller
{
    public function __construct(private PurchaseService $service, private NumberSequenceService $numbers) {}

    public function store(PurchaseRequest $r): JsonResponse
    {
        return DB::transaction(function () use ($r) {
            $d = $r->validated();
            $supplier = Supplier::firstOrCreate(['name' => $d['supplier']], ['phone' => ($d['supplier_phone'] ?? null) ?: null, 'created_by' => $r->user()->id]);
            $product = Product::where('sku', $d['product_sku'])->firstOrFail();
            $warehouse = Warehouse::where('is_default', true)->firstOrFail();
            $devices = $d['devices'] ?? [];
            $serialized = $product->tracking_type !== ProductTrackingType::Quantity;
            if ($serialized && ! BigDecimal::of((string) $d['quantity'])->isEqualTo((string) (int) $d['quantity'])) {
                throw ValidationException::withMessages(['quantity' => 'Serialized device quantity must be a whole number.']);
            }
            if (! $serialized && $devices) {
                throw ValidationException::withMessages(['devices' => 'Identifiers are only accepted for serialized products.']);
            }
            $total = $devices && $serialized
                ? (string) array_reduce($devices, fn (BigDecimal $sum, array $device) => $sum->plus((string) ($device['purchase_cost'] ?? $d['unit_cost'])), BigDecimal::zero())->toScale(2, RoundingMode::HALF_UP)
                : (string) BigDecimal::of((string) $d['quantity'])->multipliedBy((string) $d['unit_cost'])->toScale(2, RoundingMode::HALF_UP);
            $purchase = Purchase::create(['invoice_number' => ($d['number'] ?? null) ?: $this->numbers->next('purchase', 'PUR-'.now()->format('Y').'-'), 'supplier_id' => $supplier->id, 'warehouse_id' => $warehouse->id, 'status' => 'ordered', 'subtotal' => $total, 'total' => $total, 'due_amount' => $total, 'ordered_at' => $d['date'], 'created_by' => $r->user()->id]);
            $purchase->items()->create(['product_id' => $product->id, 'quantity' => $d['quantity'], 'unit_cost' => $d['unit_cost'], 'total' => $total]);
            if (BigDecimal::of((string) ($d['paid_amount'] ?? 0))->isPositive()) {
                $purchase = $this->service->pay($purchase, (string) $d['paid_amount']);
            }if (in_array($d['status'], ['مستلمة', 'Received', 'received'], true)) {
                $purchase = $this->service->receive($purchase, $devices);
            }

            return response()->json(['message' => 'تم إنشاء فاتورة الشراء.', 'row' => $this->row($purchase)], 201);
        });
    }

    public function receive(Request $r, Purchase $purchase): JsonResponse
    {
        abort_unless($r->user()->can('receive-purchases'), 403);
        $d = $r->validate(['devices' => ['sometimes', 'array'], 'devices.*.imei_1' => ['nullable', 'string', 'max:255'], 'devices.*.imei_2' => ['nullable', 'string', 'max:255'], 'devices.*.serial_number' => ['nullable', 'string', 'max:255'], 'devices.*.purchase_cost' => ['nullable', 'numeric', 'min:0', 'decimal:0,2']]);

        return response()->json(['message' => 'تم استلام البضاعة وإضافتها للمخزون.', 'purchase' => $this->service->receive($purchase, $d['devices'] ?? [])]);
    }

    public function destroy(Purchase $purchase): JsonResponse
    {
        abort_unless(request()->user()->can('create-purchases'), 403);
        $this->service->cancel($purchase);

        return response()->json(['message' => 'تم إلغاء أمر الشراء.']);
    }

    public function pay(Request $r, Purchase $purchase): JsonResponse
    {
        $d = $r->validate(['amount' => ['required', 'numeric', 'gt:0'], 'method' => ['required', 'string']]);
        abort_unless($r->user()->can('create-purchases'), 403);

        return response()->json(['message' => 'تم تسجيل دفعة المورد.', 'purchase' => $this->service->pay($purchase, (string) $d['amount'], $d['method'])]);
    }

    private function row(Purchase $p): array
    {
        $p->load('supplier');

        return ['id' => $p->id, 'cells' => [$p->invoice_number, $p->supplier->name, $p->created_at->format('Y-m-d'), number_format((float) $p->total, 2), $p->status]];
    }
}
