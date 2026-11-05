<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\InventoryService;
use App\Services\InventoryValuationService;
use App\Services\NumberSequenceService;
use App\Services\PurchaseService;
use App\Services\SaleService;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryCostLedgerTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private Product $product;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->staff = User::factory()->create();
        $this->actingAs($this->staff);
        $this->warehouse = Warehouse::create(['name' => 'Main', 'code' => 'MAIN', 'is_default' => true]);
        $this->product = Product::create(['sku' => 'COST-1', 'name' => 'Part', 'purchase_price' => 100, 'sale_price' => 200]);
    }

    private function move(string $quantity, StockMovementType $type, ?string $cost = null, ?string $key = null): StockMovement
    {
        return app(InventoryService::class)->adjust($this->warehouse, $this->product, $quantity, $type, unitCost: $cost, eventKey: $key);
    }

    public function test_weighted_average_uses_remaining_stock_and_preserves_ledger_value(): void
    {
        $this->move('10', StockMovementType::Purchase, '1000');
        $this->move('-8', StockMovementType::Sale);
        $stock = WarehouseStock::firstOrFail();
        $this->assertSame('1000.0000000000', $stock->average_cost);
        $this->move('5', StockMovementType::Purchase, '900');
        $stock->refresh();
        $this->assertSame('7.000', $stock->quantity);
        $this->assertSame('6500.000000', $stock->inventory_value);
        $this->assertSame('928.5714285714', $stock->average_cost);
        $this->assertSame('6500.000000', app(InventoryValuationService::class)->total());
        $this->assertSame('6500.000000', (string) BigDecimal::of(StockMovement::sum('value_delta'))->toScale(6));
    }

    public function test_sale_return_after_new_price_uses_original_cost_and_cancellation_is_idempotent(): void
    {
        $this->move('2', StockMovementType::Purchase, '100');
        $sale = Sale::create(['invoice_number' => 'SALE-1', 'warehouse_id' => $this->warehouse->id, 'cashier_id' => $this->staff->id, 'status' => 'draft', 'subtotal' => 150, 'total' => 150]);
        $item = $sale->items()->create(['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 150, 'total' => 150]);
        app(SaleService::class)->finalize($sale);
        $this->assertSame('100.0000000000', $item->fresh()->unit_cost_snapshot);
        $this->move('1', StockMovementType::Purchase, '200');
        app(SaleService::class)->refund($sale, [['sale_item_id' => $item->id, 'quantity' => 1]]);
        $this->assertSame('400.000000', WarehouseStock::first()->inventory_value);
        $this->assertSame('133.3333333333', WarehouseStock::first()->average_cost);
        $this->expectException(ValidationException::class);
        app(SaleService::class)->cancel($sale->fresh());
    }

    public function test_purchase_return_removes_carrying_value_and_records_supplier_variance(): void
    {
        $supplier = Supplier::create(['name' => 'Vendor']);
        $purchase = Purchase::create(['invoice_number' => 'PUR-COST', 'supplier_id' => $supplier->id, 'warehouse_id' => $this->warehouse->id, 'status' => 'ordered', 'total' => 200, 'due_amount' => 200]);
        $item = $purchase->items()->create(['product_id' => $this->product->id, 'quantity' => 2, 'unit_cost' => 100, 'total' => 200]);
        app(PurchaseService::class)->receive($purchase);
        app(PurchaseService::class)->receive($purchase->fresh());
        $this->assertDatabaseCount('stock_movements', 1);
        $this->move('2', StockMovementType::Purchase, '200');
        $return = app(PurchaseService::class)->returnItems($purchase->fresh(), [['purchase_item_id' => $item->id, 'quantity' => 1]], app(NumberSequenceService::class));
        $this->assertSame('450.000000', WarehouseStock::first()->inventory_value);
        $this->assertSame('150.000000', $return->items->first()->inventory_value_removed);
        $this->assertSame('-50.000000', $return->items->first()->cost_variance);
    }

    public function test_depletion_repurchase_adjustments_and_event_key(): void
    {
        $this->move('0.125', StockMovementType::Purchase, '0.333333');
        $this->move('-0.125', StockMovementType::Sale);
        $this->assertSame('0.000000', WarehouseStock::first()->inventory_value);
        $this->move('1', StockMovementType::Purchase, '9.123456');
        $this->assertSame('9.1234560000', WarehouseStock::first()->average_cost);
        $this->move('1', StockMovementType::AdjustmentIn, '8', 'adjustment:1');
        $this->move('1', StockMovementType::AdjustmentIn, '8', 'adjustment:1');
        $this->assertDatabaseCount('stock_movements', 4);
        $this->move('-1', StockMovementType::AdjustmentOut);
        $this->assertSame('1.000', WarehouseStock::first()->quantity);
        $this->expectException(ValidationException::class);
        $this->move('-2', StockMovementType::Sale);
    }

    public function test_unknown_legacy_cost_is_not_valued_at_sale_price(): void
    {
        WarehouseStock::create(['warehouse_id' => $this->warehouse->id, 'product_id' => $this->product->id, 'variant_key' => 0, 'quantity' => 1, 'inventory_value' => null, 'average_cost' => null]);
        $this->assertNull(app(InventoryValuationService::class)->total());
        $this->assertSame(1, app(InventoryValuationService::class)->summary()['unknown_stock_rows']);
    }

    public function test_last_piece_cannot_be_sold_twice_and_return_retry_is_idempotent(): void
    {
        $this->move('1', StockMovementType::Purchase, '100');
        $sales = app(SaleService::class);
        $first = Sale::create(['invoice_number' => 'SALE-LAST-1', 'warehouse_id' => $this->warehouse->id, 'cashier_id' => $this->staff->id, 'status' => 'draft', 'total' => 150]);
        $item = $first->items()->create(['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 150, 'total' => 150]);
        $sales->finalize($first);
        $second = Sale::create(['invoice_number' => 'SALE-LAST-2', 'warehouse_id' => $this->warehouse->id, 'cashier_id' => $this->staff->id, 'status' => 'draft', 'total' => 150]);
        $second->items()->create(['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 150, 'total' => 150]);
        try {
            $sales->finalize($second);
            $this->fail('The last piece was sold twice.');
        } catch (ValidationException) {
            $this->assertSame('0.000', WarehouseStock::first()->quantity);
        }
        $lines = [['sale_item_id' => $item->id, 'quantity' => 1]];
        $return = $sales->refund($first, $lines, idempotencyKey: 'return-last-1');
        $this->assertSame($return->id, $sales->refund($first, $lines, idempotencyKey: 'return-last-1')->id);
        $this->assertSame('1.000', WarehouseStock::first()->quantity);
        $this->assertDatabaseCount('sale_return_items', 1);
    }
}
