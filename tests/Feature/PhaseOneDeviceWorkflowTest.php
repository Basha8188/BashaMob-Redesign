<?php

namespace Tests\Feature;

use App\Enums\ProductSerialStatus;
use App\Enums\ProductTrackingType;
use App\Enums\RepairStatus;
use App\Enums\StockMovementType;
use App\Models\Customer;
use App\Models\CustomerDevice;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\RepairQuotation;
use App\Models\RepairTicket;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\QrCodeService;
use App\Services\RepairService;
use App\Services\SaleService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseOneDeviceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Customer $customer;

    private CustomerDevice $device;

    private RepairTicket $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->owner = User::factory()->create();
        $this->owner->assignRole('Owner');
        $this->customer = Customer::create(['name' => 'أحمد محمد', 'phone' => '01011111111']);
        $warehouse = Warehouse::create(['name' => 'الرئيسي', 'code' => 'MAIN', 'is_default' => true, 'is_active' => true]);
        $this->device = CustomerDevice::create([
            'internal_code' => 'DEV-TEST-001',
            'customer_id' => $this->customer->id,
            'type' => 'هاتف',
            'brand' => 'Samsung',
            'model' => 'S24',
            'imei_1' => '123456789012345',
            'first_seen_at' => now(),
        ]);
        $this->device->ownershipHistory()->create(['customer_id' => $this->customer->id, 'started_at' => now()]);
        $this->ticket = RepairTicket::create([
            'ticket_number' => 'REP-TEST-001',
            'customer_id' => $this->customer->id,
            'customer_device_id' => $this->device->id,
            'warehouse_id' => $warehouse->id,
            'device_type' => 'هاتف',
            'brand' => 'Samsung',
            'model' => 'S24',
            'reported_problem' => 'كسر في الشاشة',
            'status' => RepairStatus::AwaitingInspection,
            'received_at' => now(),
            'created_by' => $this->owner->id,
        ]);
    }

    public function test_device_passport_can_be_created_and_duplicate_imei_is_rejected(): void
    {
        $secondCustomer = Customer::create(['name' => 'عميل آخر', 'phone' => '01022222222']);
        $response = $this->actingAs($this->owner)->post('/admin/device-passports', [
            'customer_id' => $secondCustomer->id,
            'type' => 'هاتف',
            'brand' => 'Apple',
            'model' => 'iPhone 16',
            'imei_1' => '999999999999999',
        ]);
        $created = CustomerDevice::where('imei_1', '999999999999999')->firstOrFail();
        $response->assertRedirect(route('admin.device-passports.show', $created));
        $this->assertDatabaseHas('device_ownership_histories', ['customer_device_id' => $created->id, 'customer_id' => $secondCustomer->id]);
        $this->assertDatabaseHas('device_passport_events', ['customer_device_id' => $created->id, 'event_type' => 'passport_created']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'device_passport.created', 'subject_id' => $created->id]);

        $this->actingAs($this->owner)->post('/admin/device-passports', [
            'customer_id' => $secondCustomer->id,
            'type' => 'هاتف',
            'imei_1' => '999999999999999',
        ])->assertSessionHasErrors('imei_1');
        $this->assertSame(1, CustomerDevice::where('imei_1', '999999999999999')->count());
        $this->assertStringStartsWith('data:image/svg+xml;base64,', app(QrCodeService::class)->dataUri('https://example.test/device'));
    }

    public function test_full_inspection_quotation_and_digital_approval_workflow(): void
    {
        $this->actingAs($this->owner)->post(route('admin.repairs.inspections.store', $this->ticket), [
            'has_google_account' => 1,
            'has_icloud_account' => 0,
            'is_locked' => 1,
            'lock_type' => 'PIN',
            'accessories' => ['الشاحن', 'الجراب'],
            'damage_map' => [['x' => 45, 'y' => 30, 'type' => 'crack']],
            'employee_notes' => 'كسر ظاهر أعلى الشاشة',
            'terms_snapshot' => 'شروط الصيانة وقت الاستلام',
            'customer_consent' => 'approved',
        ])->assertRedirect(route('admin.repairs.inspections.receipt', $this->ticket));

        $this->assertDatabaseHas('repair_inspections', ['repair_ticket_id' => $this->ticket->id, 'customer_device_id' => $this->device->id, 'lock_type' => 'PIN']);
        $this->assertSame(RepairStatus::AwaitingQuotation, $this->ticket->fresh()->status);

        $quoteResponse = $this->actingAs($this->owner)->post(route('admin.repairs.quotations.store', $this->ticket), [
            'fault_description' => 'الشاشة مكسورة',
            'inspection_result' => 'يجب تغيير الشاشة',
            'items' => [['description' => 'شاشة أصلية', 'quantity' => 1, 'unit_price' => 3000]],
            'labor_cost' => 500,
            'discount' => 100,
            'tax' => 0,
            'required_deposit' => 1000,
            'estimated_days' => 3,
            'warranty_terms' => 'ضمان 30 يومًا',
            'valid_hours' => 72,
        ]);

        $quotation = RepairQuotation::firstOrFail();
        $quoteResponse->assertRedirect(route('admin.repairs.quotations.show', $quotation));
        $this->assertSame('3400.00', $quotation->total);
        $this->assertSame(RepairStatus::WaitingCustomerApproval, $this->ticket->fresh()->status);
        $token = $quoteResponse->getSession()->get('quotation_public_token_'.$quotation->id);
        $this->assertNotEmpty($token);

        $this->get(route('repair-quotations.public.show', $token))->assertOk()->assertSee('3,400.00');
        $this->post(route('repair-quotations.public.respond', $token), ['decision' => 'accept', 'phone' => '01000000000'])->assertSessionHasErrors('phone');
        $this->post(route('repair-quotations.public.respond', $token), ['decision' => 'accept', 'phone' => $this->customer->phone, 'note' => 'موافق'])->assertRedirect();

        $this->assertDatabaseHas('repair_quotation_responses', ['repair_quotation_id' => $quotation->id, 'decision' => 'accept', 'phone' => $this->customer->phone]);
        $this->assertSame('approved', $quotation->fresh()->status);
        $this->assertSame(RepairStatus::Approved, $this->ticket->fresh()->status);
        $this->assertNotEmpty($quotation->responses()->first()->quotation_snapshot);
        $this->assertDatabaseHas('activity_logs', ['action' => 'repair_quotation.accept', 'subject_id' => $quotation->id]);

        $this->post(route('repair-quotations.public.respond', $token), ['decision' => 'accept', 'phone' => $this->customer->phone])->assertSessionHasErrors('link');
        $this->assertDatabaseCount('repair_quotation_responses', 1);
    }

    public function test_repair_cannot_start_without_customer_approval(): void
    {
        $technician = User::factory()->create();
        $technician->assignRole('Technician');
        $this->actingAs($technician);
        $this->expectException(ValidationException::class);
        app(RepairService::class)->changeStatus($this->ticket, RepairStatus::InProgress);
    }

    public function test_customer_can_reject_a_quotation_with_a_reason(): void
    {
        $this->actingAs($this->owner)->post(route('admin.repairs.inspections.store', $this->ticket), [
            'damage_map' => [], 'customer_consent' => 'approved', 'employee_notes' => 'تم الفحص',
        ])->assertRedirect();
        $response = $this->actingAs($this->owner)->post(route('admin.repairs.quotations.store', $this->ticket), [
            'fault_description' => 'عطل بالشاشة',
            'items' => [['description' => 'شاشة', 'quantity' => 1, 'unit_price' => 2500]],
            'labor_cost' => 300, 'discount' => 0, 'tax' => 0, 'required_deposit' => 0,
        ]);
        $quotation = RepairQuotation::firstOrFail();
        $token = $response->getSession()->get('quotation_public_token_'.$quotation->id);

        $this->post(route('repair-quotations.public.respond', $token), [
            'decision' => 'reject', 'phone' => $this->customer->phone, 'note' => 'السعر مرتفع',
        ])->assertRedirect();

        $this->assertSame('rejected', $quotation->fresh()->status);
        $this->assertSame(RepairStatus::CustomerRejected, $this->ticket->fresh()->status);
        $this->assertDatabaseHas('repair_quotation_responses', ['decision' => 'reject', 'note' => 'السعر مرتفع']);
    }

    public function test_public_passport_requires_a_valid_signature_and_hides_owner_data(): void
    {
        $this->get(route('device-passports.verify', ['code' => $this->device->internal_code]))->assertForbidden();
        $url = URL::signedRoute('device-passports.verify', ['code' => $this->device->internal_code]);
        $this->get($url)->assertOk()->assertSee($this->device->internal_code)->assertDontSee($this->customer->phone);
    }

    public function test_staff_without_permission_cannot_open_device_passports(): void
    {
        $accountant = User::factory()->create();
        $accountant->assignRole('Accountant');
        $this->actingAs($accountant)->get('/admin/device-passports')->assertForbidden();
    }

    public function test_selling_an_imei_device_creates_and_links_its_passport(): void
    {
        $warehouse = Warehouse::firstOrFail();
        $product = Product::create(['sku' => 'PHONE-PASS', 'name' => 'هاتف تجريبي', 'tracking_type' => ProductTrackingType::Imei, 'purchase_price' => 5000, 'sale_price' => 7000]);
        app(InventoryService::class)->adjust($warehouse, $product, 1, StockMovementType::OpeningBalance);
        $serial = ProductSerial::create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'imei_1' => '777777777777777', 'status' => ProductSerialStatus::Available, 'purchase_cost' => 5000, 'sale_price' => 7000]);
        $sale = Sale::create(['invoice_number' => 'SAL-PASSPORT', 'customer_id' => $this->customer->id, 'warehouse_id' => $warehouse->id, 'cashier_id' => $this->owner->id, 'status' => 'draft', 'subtotal' => 7000, 'total' => 7000, 'due_amount' => 7000]);
        $sale->items()->create(['product_id' => $product->id, 'product_serial_id' => $serial->id, 'quantity' => 1, 'unit_price' => 7000, 'total' => 7000]);

        app(SaleService::class)->finalize($sale);

        $passport = CustomerDevice::where('product_serial_id', $serial->id)->firstOrFail();
        $this->assertSame($this->customer->id, $passport->customer_id);
        $this->assertSame('777777777777777', $passport->imei_1);
        $this->assertDatabaseHas('device_passport_events', ['customer_device_id' => $passport->id, 'event_type' => 'device_sold']);
    }
}
