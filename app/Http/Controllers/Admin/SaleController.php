<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaleRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Warehouse;
use App\Services\NumberSequenceService;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function __construct(private SaleService $sales, private NumberSequenceService $numbers) {}

    public function store(SaleRequest $r): JsonResponse
    {
        return DB::transaction(function () use ($r) {
            $d = $r->validated();
            $customer = Customer::firstOrCreate(['phone' => $d['customer_phone']], ['name' => $d['customer'], 'created_by' => $r->user()->id]);
            $product = Product::where('sku', $d['product_sku'])->firstOrFail();
            $warehouse = Warehouse::where('is_default', true)->firstOrFail();
            $total = round((float) $d['quantity'] * (float) $d['unit_price'], 2);
            $sale = Sale::create(['invoice_number' => $d['number'] ?: $this->numbers->next('sale', 'SAL-'.now()->format('Y').'-'), 'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id, 'cashier_id' => $r->user()->id, 'status' => 'draft', 'subtotal' => $total, 'total' => $total, 'due_amount' => $total, 'created_at' => $d['date']]);
            $sale->items()->create(['product_id' => $product->id, 'quantity' => $d['quantity'], 'unit_price' => $d['unit_price'], 'total' => $total]);
            if (in_array($d['status'], ['مكتملة', 'Paid', 'finalized'], true)) {
                $sale = $this->sales->finalize($sale, (float) ($d['paid_amount'] ?? 0) > 0 ? [['amount' => $d['paid_amount'], 'method' => 'cash']] : []);
            }

return response()->json(['message' => 'تم إنشاء فاتورة البيع وحفظها فعليًا.', 'row' => $this->row($sale)], 201);
        });
    }

    public function destroy(Sale $sale): JsonResponse
    {
        abort_unless(request()->user()->can('cancel-sales'), 403);
        if ($sale->status === 'draft') {
            $sale->delete();
        } else {
            $this->sales->cancel($sale);
        }

return response()->json(['message' => 'تم إلغاء الفاتورة وعكس المخزون إن كان قد خُصم.']);
    }

    public function pay(Request $r, Sale $sale): JsonResponse
    {
        $d = $r->validate(['amount' => ['required', 'numeric', 'gt:0'], 'method' => ['required', 'string']]);
        abort_unless($r->user()->can('create-sales'), 403);
        $sale = $this->sales->pay($sale, (float) $d['amount'], $d['method']);

        return response()->json(['message' => 'تم تسجيل الدفعة.', 'sale' => $sale]);
    }

    public function refund(Request $r, Sale $sale): JsonResponse
    {
        $d = $r->validate(['items' => ['required', 'array', 'min:1'], 'items.*.sale_item_id' => ['required', 'integer'], 'items.*.quantity' => ['required', 'numeric', 'gt:0'], 'reason' => ['nullable', 'string']]);
        abort_unless($r->user()->can('refund-sales'), 403);

        return response()->json(['message' => 'تم تسجيل المرتجع وعكس المخزون.', 'return' => $this->sales->refund($sale, $d['items'], $d['reason'] ?? null)]);
    }

    private function row(Sale $s): array
    {
        $s->load('customer');

        return ['id' => $s->id, 'cells' => [$s->invoice_number, $s->customer?->name ?? 'عميل نقدي', $s->created_at->format('Y-m-d'), number_format((float) $s->total,2), $s->status]];
    }
}
