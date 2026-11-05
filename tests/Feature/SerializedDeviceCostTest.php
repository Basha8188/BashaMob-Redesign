<?php

namespace Tests\Feature;

use App\Enums\ProductSerialStatus;
use App\Enums\ProductTrackingType;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\InventoryService;
use App\Services\NumberSequenceService;
use App\Services\PurchaseService;
use App\Services\SaleService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SerializedDeviceCostTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private Product $phone;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->owner = User::factory()->create();
        $this->owner->assignRole('Owner');
        $this->actingAs($this->owner);
        $this->warehouse = Warehouse::create(['name' => 'Main', 'code' => 'MAIN', 'is_default' => true]);
        $this->phone = Product::create(['sku' => 'IPHONE-13', 'name' => 'iPhone 13', 'tracking_type' => ProductTrackingType::Imei, 'purchase_price' => 15000, 'sale_price' => 17000]);
    }

    private function receive(array $devices): Purchase
    {
        $supplier = Supplier::firstOrCreate(['name' => 'Supplier']);
        $total = array_reduce($devices, fn (BigDecimal $sum, array $device) => $sum->plus($device['purchase_cost']), BigDecimal::zero());
        $purchase = Purchase::create(['invoice_number' => 'PUR-'.uniqid(), 'supplier_id' => $supplier->id, 'warehouse_id' => $this->warehouse->id, 'status' => 'ordered', 'total' => (string) $total, 'due_amount' => (string) $total]);
        $purchase->items()->create(['product_id' => $this->phone->id, 'quantity' => count($devices), 'unit_cost' => (string) $total->dividedBy(count($devices), 2, RoundingMode::HALF_UP), 'total' => (string) $total]);
        app(PurchaseService::class)->receive($purchase, $devices);

        return $purchase->fresh();
    }

    private function draftSale(ProductSerial $device, string $number): Sale
    {
        $sale = Sale::create(['invoice_number' => $number, 'warehouse_id' => $this->warehouse->id, 'cashier_id' => $this->owner->id, 'status' => 'draft', 'total' => 17000]);
        $sale->items()->create(['product_id' => $this->phone->id, 'product_serial_id' => $device->id, 'quantity' => 1, 'unit_price' => 17000, 'total' => 17000]);

        return $sale;
    }

    public function test_three_devices_keep_distinct_costs_and_sale_uses_selected_cost(): void
    {
        $purchase = $this->receive([
            ['imei_1' => '111111111111111', 'purchase_cost' => '15000'],
            ['imei_1' => '222222222222222', 'imei_2' => '222222222222223', 'purchase_cost' => '15700'],
            ['imei_1' => '333333333333333', 'purchase_cost' => '14900'],
        ]);
        $devices = ProductSerial::orderBy('purchase_cost')->get();
        $this->assertSame(['14900.00', '15000.00', '15700.00'], $devices->pluck('purchase_cost')->all());
        $this->assertTrue($devices->every(fn ($device) => $device->purchase_item_id === $purchase->items->first()->id && $device->warehouse_id === $this->warehouse->id));
        $this->assertSame('45600.000000', WarehouseStock::first()->inventory_value);
        $chosen = ProductSerial::where('imei_1', '222222222222222')->firstOrFail();
        $sale = $this->draftSale($chosen, 'SAL-B');
        app(SaleService::class)->finalize($sale);
        $this->assertSame('15700.0000000000', $sale->items->first()->fresh()->unit_cost_snapshot);
        $this->assertSame('serial_purchase_cost', $sale->items->first()->fresh()->cost_source);
        $this->assertSame('29900.000000', WarehouseStock::first()->inventory_value);
        $this->assertSame('-15700.000000', $sale->fresh()->items->first()->serial->fresh()->status === ProductSerialStatus::Sold
            ? StockMovement::where('event_key', 'sale:'.$sale->items->first()->id)->value('value_delta') : '');
        $this->assertSame('1300.0000000000', (string) BigDecimal::of('17000')->minus($sale->items->first()->fresh()->unit_cost_snapshot)->toScale(10));
        app(PurchaseService::class)->receive($purchase, []);
        $this->assertDatabaseCount('product_serials', 3);
    }

    public function test_identity_conflict_and_quantity_mismatch_rollback_receipt(): void
    {
        $this->receive([['imei_1' => '444444444444444', 'imei_2' => '555555555555555', 'purchase_cost' => '15000']]);
        try {
            $this->receive([['imei_1' => '555555555555555', 'purchase_cost' => '15000']]);
            $this->fail('Cross-column duplicate was accepted.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('product_serials', 1);
        }
        try {
            $supplier = Supplier::first();
            $purchase = Purchase::create(['invoice_number' => 'PUR-COUNT', 'supplier_id' => $supplier->id, 'warehouse_id' => $this->warehouse->id, 'status' => 'ordered', 'total' => 30000]);
            $purchase->items()->create(['product_id' => $this->phone->id, 'quantity' => 2, 'unit_cost' => 15000, 'total' => 30000]);
            app(PurchaseService::class)->receive($purchase, [['imei_1' => '666666666666666', 'purchase_cost' => '15000']]);
            $this->fail('Mismatched device count was accepted.');
        } catch (ValidationException) {
            $this->assertSame('1.000', WarehouseStock::first()->quantity);
        }
    }

    public function test_cancel_and_healthy_return_restore_specific_value_while_damage_stays_unavailable(): void
    {
        $this->receive([
            ['imei_1' => '777777777777777', 'purchase_cost' => '15000'],
            ['imei_1' => '888888888888888', 'purchase_cost' => '15700'],
        ]);
        $device = ProductSerial::where('imei_1', '777777777777777')->firstOrFail();
        $sale = $this->draftSale($device, 'SAL-CANCEL');
        app(SaleService::class)->finalize($sale);
        app(SaleService::class)->cancel($sale);
        app(SaleService::class)->cancel($sale);
        $this->assertSame('30700.000000', WarehouseStock::first()->inventory_value);
        $this->assertSame(ProductSerialStatus::Available, $device->fresh()->status);

        $sale = $this->draftSale($device, 'SAL-RETURN');
        app(SaleService::class)->finalize($sale);
        $item = $sale->items->first();
        app(SaleService::class)->refund($sale, [['sale_item_id' => $item->id, 'quantity' => 1, 'device_condition' => 'available']], idempotencyKey: 'healthy-1');
        $this->assertSame(ProductSerialStatus::Available, $device->fresh()->status);
        $this->assertSame('30700.000000', WarehouseStock::first()->inventory_value);

        $damaged = ProductSerial::where('imei_1', '888888888888888')->firstOrFail();
        $sale = $this->draftSale($damaged, 'SAL-DAMAGED');
        app(SaleService::class)->finalize($sale);
        $item = $sale->items->first();
        $return = app(SaleService::class)->refund($sale, [['sale_item_id' => $item->id, 'quantity' => 1, 'device_condition' => 'damaged']], idempotencyKey: 'damaged-1');
        $this->assertSame($damaged->id, $return->items->first()->product_serial_id);
        $this->assertSame(ProductSerialStatus::Damaged, $damaged->fresh()->status);
        $this->assertSame('15000.000000', WarehouseStock::first()->inventory_value);
        $this->assertSame('1.000', WarehouseStock::first()->quantity);
    }

    public function test_accessory_sale_does_not_need_serial_and_cashier_cannot_see_device_cost(): void
    {
        $this->receive([['imei_1' => '999999999999999', 'purchase_cost' => '15000']]);
        $cashier = User::factory()->create();
        $cashier->assignRole('Cashier');
        $this->actingAs($cashier)->getJson('/admin/sales/available-devices?product_sku=IPHONE-13')
            ->assertOk()->assertJsonMissingPath('devices.0.purchase_cost');
        $this->actingAs($this->owner)->getJson('/admin/sales/available-devices?product_sku=IPHONE-13')
            ->assertOk()->assertJsonPath('devices.0.purchase_cost', '15000.00');
        $accessory = Product::create(['sku' => 'CABLE', 'name' => 'Cable', 'tracking_type' => ProductTrackingType::Quantity, 'purchase_price' => 10, 'sale_price' => 20]);
        app(InventoryService::class)->adjust($this->warehouse, $accessory, '1', StockMovementType::Purchase, unitCost: '10');
        $sale = Sale::create(['invoice_number' => 'SAL-CABLE', 'warehouse_id' => $this->warehouse->id, 'cashier_id' => $this->owner->id, 'status' => 'draft', 'total' => 20]);
        $sale->items()->create(['product_id' => $accessory->id, 'quantity' => 1, 'unit_price' => 20, 'total' => 20]);
        app(SaleService::class)->finalize($sale);
        $this->assertSame('10.0000000000', $sale->items->first()->fresh()->unit_cost_snapshot);
    }

    public function test_http_purchase_and_sale_require_selected_device_and_preserve_cost(): void
    {
        $this->postJson('/admin/purchases', [
            'supplier' => 'Supplier', 'supplier_phone' => '', 'date' => now()->toDateString(),
            'product_sku' => 'IPHONE-13', 'quantity' => 2, 'unit_cost' => 15000, 'status' => 'received',
            'devices' => [
                ['imei_1' => '101010101010101', 'purchase_cost' => '14900'],
                ['imei_1' => '202020202020202', 'purchase_cost' => '15100'],
            ],
        ])->assertCreated();
        $this->assertSame('30000.000000', WarehouseStock::first()->inventory_value);
        $this->postJson('/admin/sales', [
            'customer' => 'Buyer', 'customer_phone' => '01010000000', 'date' => now()->toDateString(),
            'product_sku' => 'IPHONE-13', 'quantity' => 1, 'unit_price' => 17000, 'status' => 'finalized',
        ])->assertUnprocessable();
        $device = ProductSerial::where('imei_1', '202020202020202')->firstOrFail();
        $this->postJson('/admin/sales', [
            'customer' => 'Buyer', 'customer_phone' => '01010000000', 'date' => now()->toDateString(),
            'product_sku' => 'IPHONE-13', 'product_serial_id' => $device->id,
            'quantity' => 1, 'unit_price' => 17000, 'status' => 'finalized',
        ])->assertCreated();
        $this->assertSame('15100.0000000000', Sale::first()->items->first()->unit_cost_snapshot);
        $this->assertSame('14900.000000', WarehouseStock::first()->inventory_value);
        $this->postJson('/admin/sales', [
            'customer' => 'Buyer', 'customer_phone' => '01010000000', 'date' => now()->toDateString(),
            'product_sku' => 'IPHONE-13', 'product_serial_id' => $device->id,
            'quantity' => 1, 'unit_price' => 17000, 'status' => 'finalized',
        ])->assertUnprocessable();
    }

    public function test_inspection_return_is_quarantined_and_cannot_be_sold_again(): void
    {
        $this->receive([['imei_1' => '303030303030303', 'purchase_cost' => '15000']]);
        $device = ProductSerial::firstOrFail();
        $sale = $this->draftSale($device, 'SAL-INSPECT');
        app(SaleService::class)->finalize($sale);
        $item = $sale->items->first();
        $return = app(SaleService::class)->refund($sale, [['sale_item_id' => $item->id, 'quantity' => 1, 'device_condition' => 'inspection']]);
        $this->assertSame($device->id, $return->items->first()->product_serial_id);
        $this->assertSame(ProductSerialStatus::Returned, $device->fresh()->status);
        $this->assertSame('0.000', WarehouseStock::first()->quantity);
        $this->assertSame('0.000000', WarehouseStock::first()->inventory_value);
        $this->getJson('/admin/sales/available-devices?product_sku=IPHONE-13')->assertJsonCount(0, 'devices');
    }

    public function test_device_from_another_warehouse_or_product_cannot_be_sold(): void
    {
        $this->receive([['imei_1' => '404040404040404', 'purchase_cost' => '15000']]);
        $device = ProductSerial::firstOrFail();
        $other = Warehouse::create(['name' => 'Second', 'code' => 'SECOND']);
        $sale = Sale::create(['invoice_number' => 'SAL-WRONG-WAREHOUSE', 'warehouse_id' => $other->id, 'cashier_id' => $this->owner->id, 'status' => 'draft', 'total' => 17000]);
        $sale->items()->create(['product_id' => $this->phone->id, 'product_serial_id' => $device->id, 'quantity' => 1, 'unit_price' => 17000, 'total' => 17000]);
        try {
            app(SaleService::class)->finalize($sale);
            $this->fail('Device from another warehouse was sold.');
        } catch (ValidationException) {
            $this->assertSame(ProductSerialStatus::Available, $device->fresh()->status);
        }
        $this->assertSame('15000.000000', WarehouseStock::first()->inventory_value);
    }

    public function test_supplier_return_removes_the_selected_device_cost(): void
    {
        $purchase = $this->receive([
            ['imei_1' => '505050505050505', 'purchase_cost' => '15000'],
            ['imei_1' => '606060606060606', 'purchase_cost' => '15700'],
        ]);
        $device = ProductSerial::where('imei_1', '606060606060606')->firstOrFail();
        $return = app(PurchaseService::class)->returnItems($purchase, [[
            'purchase_item_id' => $purchase->items->first()->id,
            'quantity' => 1,
            'product_serial_ids' => [$device->id],
        ]], app(NumberSequenceService::class));
        $this->assertSame('15700.00', $return->items->first()->unit_cost);
        $this->assertSame('15000.000000', WarehouseStock::first()->inventory_value);
        $this->assertSame(ProductSerialStatus::Returned, $device->fresh()->status);
    }

    public function test_manual_adjustment_cannot_create_unidentified_device_stock(): void
    {
        $this->postJson('/admin/inventory', [
            'warehouse' => 'MAIN', 'product' => 'IPHONE-13', 'stock' => 1,
            'minimum' => 0, 'unit_cost' => 15000,
        ])->assertUnprocessable();
        $this->assertDatabaseCount('product_serials', 0);
        $this->assertDatabaseCount('warehouse_stocks', 0);
    }

    public function test_ordered_purchase_receives_identifiers_later_once(): void
    {
        $this->postJson('/admin/purchases', [
            'supplier' => 'Supplier', 'date' => now()->toDateString(),
            'product_sku' => 'IPHONE-13', 'quantity' => 1, 'unit_cost' => 15000, 'status' => 'Pending',
        ])->assertCreated();
        $purchase = Purchase::firstOrFail();
        $this->assertDatabaseCount('product_serials', 0);
        $payload = ['devices' => [['imei_1' => '707070707070707', 'purchase_cost' => '15000']]];
        $this->postJson('/admin/purchases/'.$purchase->id.'/receive', $payload)->assertOk();
        $this->postJson('/admin/purchases/'.$purchase->id.'/receive', $payload)->assertOk();
        $this->assertDatabaseCount('product_serials', 1);
        $this->assertSame('15000.000000', WarehouseStock::first()->inventory_value);
    }

    public function test_product_form_can_classify_imei_without_creating_unidentified_opening_stock(): void
    {
        $this->postJson('/admin/products', [
            'sku' => 'PHONE-NEW', 'name' => 'New phone', 'tracking_type' => 'imei',
            'purchase_cost' => 10000, 'price' => 12000, 'quantity' => 0,
        ])->assertCreated();
        $this->assertSame(ProductTrackingType::Imei, Product::where('sku', 'PHONE-NEW')->firstOrFail()->tracking_type);
        $this->assertDatabaseCount('warehouse_stocks', 0);
    }
}
