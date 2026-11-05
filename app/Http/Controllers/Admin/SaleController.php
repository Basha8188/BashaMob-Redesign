<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductSerialStatus;
use App\Enums\ProductTrackingType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaleRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Sale;
use App\Models\Warehouse;
use App\Services\NumberSequenceService;
use App\Services\SaleService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            if ($product->tracking_type !== ProductTrackingType::Quantity && (! isset($d['product_serial_id']) || ! BigDecimal::of((string) $d['quantity'])->isEqualTo(1))) {
                throw ValidationException::withMessages(['product_serial_id' => 'Select one device for a serialized sale.']);
            }
            if ($product->tracking_type === ProductTrackingType::Quantity && isset($d['product_serial_id'])) {
                throw ValidationException::withMessages(['product_serial_id' => 'An accessory cannot use a device identifier.']);
            }
            $total = (string) BigDecimal::of((string) $d['quantity'])->multipliedBy((string) $d['unit_price'])->toScale(2, RoundingMode::HALF_UP);
            $sale = Sale::create(['invoice_number' => ($d['number'] ?? null) ?: $this->numbers->next('sale', 'SAL-'.now()->format('Y').'-'), 'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id, 'cashier_id' => $r->user()->id, 'status' => 'draft', 'subtotal' => $total, 'total' => $total, 'due_amount' => $total, 'created_at' => $d['date']]);
            $sale->items()->create(['product_id' => $product->id, 'product_variant_id' => $d['product_variant_id'] ?? null, 'product_serial_id' => $d['product_serial_id'] ?? null, 'quantity' => $d['quantity'], 'unit_price' => $d['unit_price'], 'total' => $total]);
            if (in_array($d['status'], ['مكتملة', 'Paid', 'finalized'], true)) {
                $sale = $this->sales->finalize($sale, BigDecimal::of((string) ($d['paid_amount'] ?? 0))->isPositive() ? [['amount' => $d['paid_amount'], 'method' => 'cash']] : []);
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
        $sale = $this->sales->pay($sale, (string) $d['amount'], $d['method']);

        return response()->json(['message' => 'تم تسجيل الدفعة.', 'sale' => $sale]);
    }

    public function refund(Request $r, Sale $sale): JsonResponse
    {
        $d = $r->validate(['items' => ['required', 'array', 'min:1'], 'items.*.sale_item_id' => ['required', 'integer'], 'items.*.quantity' => ['required', 'numeric', 'gt:0'], 'items.*.device_condition' => ['nullable', 'in:available,damaged,inspection'], 'reason' => ['nullable', 'string']]);
        abort_unless($r->user()->can('refund-sales'), 403);

        return response()->json(['message' => 'تم تسجيل المرتجع وعكس المخزون.', 'return' => $this->sales->refund($sale, $d['items'], $d['reason'] ?? null, $r->header('Idempotency-Key'))]);
    }

    public function availableDevices(Request $r): JsonResponse
    {
        abort_unless($r->user()->can('create-sales'), 403);
        $d = $r->validate(['product_sku' => ['required', 'exists:products,sku']]);
        $product = Product::where('sku', $d['product_sku'])->firstOrFail();
        $warehouse = Warehouse::where('is_default', true)->firstOrFail();
        $canSeeCost = $r->user()->can('view-device-cost');
        $devices = ProductSerial::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)
            ->where('status', ProductSerialStatus::Available->value)->orderBy('id')->get()
            ->map(function (ProductSerial $serial) use ($canSeeCost) {
                $row = ['id' => $serial->id, 'imei_1' => $serial->imei_1, 'imei_2' => $serial->imei_2, 'serial_number' => $serial->serial_number, 'product_variant_id' => $serial->product_variant_id];
                if ($canSeeCost) {
                    $row['purchase_cost'] = $serial->purchase_cost;
                }

                return $row;
            });

        return response()->json(['tracking_type' => $product->tracking_type->value, 'warehouse_id' => $warehouse->id, 'devices' => $devices]);
    }

    private function row(Sale $s): array
    {
        $s->load('customer');

        return ['id' => $s->id, 'cells' => [$s->invoice_number, $s->customer?->name ?? 'عميل نقدي', $s->created_at->format('Y-m-d'), number_format((float) $s->total, 2), $s->status]];
    }
}
