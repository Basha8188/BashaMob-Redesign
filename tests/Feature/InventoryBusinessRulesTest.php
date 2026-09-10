<?php

namespace Tests\Feature;

use App\Enums\ProductSerialStatus;
use App\Enums\ProductTrackingType;
use App\Enums\RepairStatus;
use App\Enums\StockMovementType;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Purchase;
use App\Models\RepairTicket;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\InventoryService;
use App\Services\PurchaseService;
use App\Services\RepairService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Warehouse $warehouse;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->staff = User::factory()->create();
        $this->actingAs($this->staff);
        $this->warehouse = Warehouse::create(['name' => 'المخزن الرئيسي', 'code' => 'MAIN', 'is_default' => true]);
        $this->product = Product::create(['sku' => 'PART-1', 'name' => 'قطعة غيار', 'tracking_type' => ProductTrackingType::Quantity, 'purchase_price' => 100, 'sale_price' => 150]);
    }

    public function test_sale_deducts_stock_once_and_cancel_restores_it(): void
    {
        app(InventoryService::class)->adjust($this->warehouse, $this->product, 5, StockMovementType::OpeningBalance);
        $sale = Sale::create(['invoice_number' => 'SAL-1', 'warehouse_id' => $this->warehouse->id, 'cashier_id' => $this->staff->id, 'status' => 'draft', 'subtotal' => 300, 'total' => 300, 'due_amount' => 300]);
        $sale->items()->create(['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 150, 'total' => 300]);

        app(SaleService::class)->finalize($sale);
        app(SaleService::class)->finalize($sale->fresh());
        $this->assertSame('3.000', WarehouseStock::first()->quantity);
        $this->assertDatabaseCount('stock_movements', 2);

        app(SaleService::class)->cancel($sale->fresh());
        $this->assertSame('5.000', WarehouseStock::first()->quantity);
    }

    public function test_stock_cannot_become_negative(): void
    {
        $this->expectException(ValidationException::class);
        app(InventoryService::class)->adjust($this->warehouse, $this->product, -1, StockMovementType::Sale);
    }

    public function test_same_imei_cannot_be_sold_twice(): void
    {
        $phone = Product::create(['sku' => 'PHONE-1', 'name' => 'هاتف', 'tracking_type' => ProductTrackingType::Imei, 'purchase_price' => 10000, 'sale_price' => 12000]);
        app(InventoryService::class)->adjust($this->warehouse, $phone, 1, StockMovementType::OpeningBalance);
        $serial = ProductSerial::create(['product_id' => $phone->id, 'warehouse_id' => $this->warehouse->id, 'imei_1' => '123456789012345', 'status' => ProductSerialStatus::Available, 'purchase_cost' => 10000, 'sale_price' => 12000]);

        $first = Sale::create(['invoice_number' => 'SAL-IMEI-1', 'warehouse_id' => $this->warehouse->id, 'cashier_id' => $this->staff->id, 'status' => 'draft', 'subtotal' => 12000, 'total' => 12000, 'due_amount' => 12000]);
        $first->items()->create(['product_id' => $phone->id, 'product_serial_id' => $serial->id, 'quantity' => 1, 'unit_price' => 12000, 'total' => 12000]);
        app(SaleService::class)->finalize($first);

        $second = Sale::create(['invoice_number' => 'SAL-IMEI-2', 'warehouse_id' => $this->warehouse->id, 'cashier_id' => $this->staff->id, 'status' => 'draft', 'subtotal' => 12000, 'total' => 12000, 'due_amount' => 12000]);
        $second->items()->create(['product_id' => $phone->id, 'product_serial_id' => $serial->id, 'quantity' => 1, 'unit_price' => 12000, 'total' => 12000]);
        try {
            app(SaleService::class)->finalize($second);
            $this->fail('The same IMEI was sold twice.');
        } catch (ValidationException) {
        }

        $this->assertSame(ProductSerialStatus::Sold, $serial->fresh()->status);
        $this->assertSame('0.000', WarehouseStock::where('product_id', $phone->id)->first()->quantity);
    }

    public function test_return_cannot_exceed_sold_quantity(): void
    {
        app(InventoryService::class)->adjust($this->warehouse, $this->product, 2, StockMovementType::OpeningBalance);
        $sale = Sale::create(['invoice_number' => 'SAL-RET', 'warehouse_id' => $this->warehouse->id, 'cashier_id' => $this->staff->id, 'status' => 'draft', 'subtotal' => 150, 'total' => 150, 'due_amount' => 150]);
        $item = $sale->items()->create(['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 150, 'total' => 150]);
        app(SaleService::class)->finalize($sale);

        $this->expectException(ValidationException::class);
        app(SaleService::class)->refund($sale->fresh(), [['sale_item_id' => $item->id, 'quantity' => 2]]);
    }

    public function test_receiving_purchase_increases_stock_once(): void
    {
        $supplier = Supplier::create(['name' => 'مورد', 'phone' => '01000000000']);
        $purchase = Purchase::create(['invoice_number' => 'PUR-1', 'supplier_id' => $supplier->id, 'warehouse_id' => $this->warehouse->id, 'status' => 'ordered', 'subtotal' => 300, 'total' => 300, 'due_amount' => 300, 'created_by' => $this->staff->id]);
        $purchase->items()->create(['product_id' => $this->product->id, 'quantity' => 3, 'unit_cost' => 100, 'total' => 300]);

        app(PurchaseService::class)->receive($purchase);
        app(PurchaseService::class)->receive($purchase->fresh());
        $this->assertSame('3.000', WarehouseStock::first()->quantity);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_repair_status_history_and_part_usage_are_recorded(): void
    {
        app(InventoryService::class)->adjust($this->warehouse, $this->product, 2, StockMovementType::OpeningBalance);
        $customer = Customer::create(['name' => 'أحمد', 'phone' => '01011111111']);
        $ticket = RepairTicket::create(['ticket_number' => 'REP-1', 'customer_id' => $customer->id, 'warehouse_id' => $this->warehouse->id, 'device_type' => 'موبايل', 'reported_problem' => 'شاشة', 'status' => RepairStatus::Received, 'received_at' => now()]);

        app(RepairService::class)->changeStatus($ticket, RepairStatus::Diagnosing, 'بدأ الفحص');
        app(RepairService::class)->usePart($ticket, $this->warehouse, $this->product, 1, 175);

        $this->assertDatabaseHas('repair_status_history', ['repair_ticket_id' => $ticket->id, 'to_status' => 'diagnosing']);
        $this->assertDatabaseHas('repair_parts', ['repair_ticket_id' => $ticket->id, 'product_id' => $this->product->id]);
        $this->assertSame('1.000', WarehouseStock::first()->quantity);
    }
}
