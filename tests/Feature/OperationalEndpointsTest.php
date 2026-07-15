<?php

namespace Tests\Feature;

use App\Enums\ProductTrackingType;
use App\Enums\StockMovementType;
use App\Models\CashRegister;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\InventoryService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Warehouse $warehouse;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->owner = User::factory()->create();
        $this->owner->assignRole('Owner');
        $this->actingAs($this->owner);
        $this->warehouse = Warehouse::create(['name' => 'المخزن الرئيسي', 'code' => 'MAIN', 'is_default' => true]);
        CashRegister::create(['name' => 'الخزنة الرئيسية', 'code' => 'CASH', 'warehouse_id' => $this->warehouse->id]);
        $this->product = Product::create(['sku' => 'SKU-1', 'name' => 'شاحن', 'tracking_type' => ProductTrackingType::Quantity, 'purchase_price' => 100, 'sale_price' => 150]);
        app(InventoryService::class)->adjust($this->warehouse, $this->product, 10, StockMovementType::OpeningBalance);
    }

    public function test_sales_button_creates_final_invoice_stock_and_cash_movements(): void
    {
        $this->postJson('/admin/sales', ['number' => '', 'customer' => 'أحمد', 'customer_phone' => '01012345678', 'date' => now()->toDateString(), 'total' => 300, 'status' => 'مكتملة', 'product_sku' => 'SKU-1', 'quantity' => 2, 'unit_price' => 150, 'paid_amount' => 300])->assertCreated();
        $this->assertDatabaseHas('sales', ['status' => 'finalized', 'total' => 300]);
        $this->assertSame('8.000', WarehouseStock::first()->quantity);
        $this->assertDatabaseHas('cash_transactions', ['type' => 'sale_payment', 'amount' => 300]);
    }

    public function test_purchase_button_receives_stock_and_records_supplier_payment(): void
    {
        $this->postJson('/admin/purchases', ['number' => '', 'supplier' => 'شركة النور', 'supplier_phone' => '01111111111', 'date' => now()->toDateString(), 'total' => 300, 'status' => 'مستلمة', 'product_sku' => 'SKU-1', 'quantity' => 3, 'unit_cost' => 100, 'paid_amount' => 100])->assertCreated();
        $this->assertSame('13.000', WarehouseStock::first()->quantity);
        $this->assertDatabaseHas('purchases', ['status' => 'received', 'paid_amount' => 100]);
        $this->assertDatabaseHas('cash_transactions', ['type' => 'purchase_payment', 'amount' => -100]);
    }

    public function test_repair_expense_and_setting_buttons_persist_real_data(): void
    {
        $this->postJson('/admin/repairs', ['number' => '', 'customer' => 'سارة', 'customer_phone' => '01222222222', 'device' => 'iPhone 13', 'fault' => 'الشاشة مكسورة', 'status' => 'قيد الإصلاح'])->assertCreated();
        $this->postJson('/admin/expenses', ['number' => '', 'description' => 'شحن بضاعة', 'date' => now()->toDateString(), 'amount' => 250, 'type' => 'مصروفات تشغيل'])->assertCreated();
        $this->postJson('/admin/settings', ['section' => 'general', 'name' => 'invoice_footer', 'value' => 'شكرًا لزيارتكم', 'action' => 'تعديل', 'status' => 'فعال'])->assertCreated();
        $this->assertDatabaseHas('repair_status_history', ['to_status' => 'in_progress']);
        $this->assertDatabaseHas('expenses', ['amount' => 250]);
        $this->assertDatabaseHas('settings', ['key' => 'invoice_footer']);
    }

    public function test_store_details_and_report_buttons_use_real_endpoints(): void
    {
        $this->postJson('/admin/store', [
            'store_name' => 'BashaMob Cairo',
            'store_phone' => '01012345678',
            'store_email' => 'info@bashamob.local',
            'tax_number' => '123456789',
            'store_address' => 'Cairo',
        ])->assertOk()->assertJsonPath('message', 'تم حفظ بيانات المحل بنجاح.');

        $this->assertDatabaseHas('settings', ['group' => 'store', 'key' => 'store_name', 'value' => 'BashaMob Cairo']);
        $this->getJson('/admin/reports/summary')->assertOk()->assertJsonStructure(['period', 'sales', 'purchases', 'stock_value']);
    }
}
