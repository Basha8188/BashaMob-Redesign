<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\NumberSequenceService;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function __construct(private PurchaseService $service, private NumberSequenceService $numbers) {}

    public function store(PurchaseRequest $r): JsonResponse
    {
        return DB::transaction(function () use ($r) {
            $d = $r->validated();
            $supplier = Supplier::firstOrCreate(['name' => $d['supplier']], ['phone' => $d['supplier_phone'] ?: null, 'created_by' => $r->user()->id]);
            $product = Product::where('sku', $d['product_sku'])->firstOrFail();
            $warehouse = Warehouse::where('is_default', true)->firstOrFail();
            $total = round((float) $d['quantity'] * (float) $d['unit_cost'], 2);
            $purchase = Purchase::create(['invoice_number' => $d['number'] ?: $this->numbers->next('purchase', 'PUR-'.now()->format('Y').'-'), 'supplier_id' => $supplier->id, 'warehouse_id' => $warehouse->id, 'status' => 'ordered', 'subtotal' => $total, 'total' => $total, 'due_amount' => $total, 'ordered_at' => $d['date'], 'created_by' => $r->user()->id]);
            $purchase->items()->create(['product_id' => $product->id, 'quantity' => $d['quantity'], 'unit_cost' => $d['unit_cost'], 'total' => $total]);
            if ((float) ($d['paid_amount'] ?? 0) > 0) {
                $purchase = $this->service->pay($purchase, (float) $d['paid_amount']);
            }if (in_array($d['status'], ['مستلمة', 'Received', 'received'], true)) {
                $purchase = $this->service->receive($purchase);
            }

return response()->json(['message' => 'تم إنشاء فاتورة الشراء.', 'row' => $this->row($purchase)], 201);
        });
    }

    public function receive(Purchase $purchase): JsonResponse
    {
        abort_unless(request()->user()->can('receive-purchases'), 403);

        return response()->json(['message' => 'تم استلام البضاعة وإضافتها للمخزون.', 'purchase' => $this->service->receive($purchase)]);
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

        return response()->json(['message' => 'تم تسجيل دفعة المورد.', 'purchase' => $this->service->pay($purchase, (float) $d['amount'], $d['method'])]);
    }

    private function row(Purchase $p): array
    {
        $p->load('supplier');

        return ['id' => $p->id, 'cells' => [$p->invoice_number, $p->supplier->name, $p->created_at->format('Y-m-d'), number_format((float) $p->total, 2), $p->status]];
    }
}
