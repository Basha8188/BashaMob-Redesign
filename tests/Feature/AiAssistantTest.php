<?php

namespace Tests\Feature;

use App\Enums\ProductTrackingType;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_a_non_technical_message_when_the_service_is_not_configured(): void
    {
        config(['services.openai.key' => null]);

        $response = $this->postJson('/assistant/ask', [
            'question' => 'ما حالة المخزون؟',
            'locale' => 'ar',
        ]);

        $response->assertStatus(503);
        $this->assertStringNotContainsString('OPENAI_API_KEY', $response->getContent());
        $this->assertStringNotContainsString('.env', $response->getContent());
    }

    public function test_it_returns_the_response_api_answer(): void
    {
        config([
            'services.openai.key' => 'test-key',
            'services.openai.model' => 'gpt-5.6-luna',
            'services.openai.url' => 'https://api.openai.com/v1/responses',
        ]);

        Http::fake([
            'api.openai.com/v1/responses' => Http::response([
                'output' => [[
                    'type' => 'message',
                    'content' => [['type' => 'output_text', 'text' => 'هذه إجابة مفيدة.']],
                ]],
            ]),
        ]);

        $this->postJson('/assistant/ask', [
            'question' => 'اشرح لي الفاتورة',
            'page' => 'المبيعات',
            'locale' => 'ar',
        ])->assertOk()->assertJsonPath('answer', 'هذه إجابة مفيدة.');

        Http::assertSent(fn ($request) => $request['model'] === 'gpt-5.6-luna'
            && $request['input'][0]['content'] === 'اشرح لي الفاتورة');
    }

    public function test_an_employee_can_prepare_and_confirm_a_sales_invoice_through_the_assistant(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $owner = User::factory()->create();
        $owner->assignRole('Owner');
        $warehouse = Warehouse::create(['name' => 'المخزن الرئيسي', 'code' => 'MAIN', 'is_default' => true, 'is_active' => true]);
        $product = Product::create([
            'sku' => 'SAM-A55',
            'name' => 'هاتف سامسونج A55',
            'tracking_type' => ProductTrackingType::Quantity,
            'purchase_price' => 8000,
            'sale_price' => 11000,
            'is_active' => true,
        ]);
        WarehouseStock::create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'quantity' => 5,
            'reserved_quantity' => 0,
            'average_cost' => 8000,
        ]);
        config([
            'services.openai.key' => 'test-key',
            'services.openai.model' => 'gpt-5.6-luna',
            'services.openai.url' => 'https://api.openai.com/v1/responses',
        ]);
        Http::fake([
            'api.openai.com/v1/responses' => Http::response([
                'output' => [[
                    'type' => 'function_call',
                    'call_id' => 'call_sales_1',
                    'name' => 'prepare_sales_invoice',
                    'arguments' => json_encode([
                        'product_query' => 'هاتف سامسونج A55',
                        'quantity' => 1,
                        'unit_price' => 10000,
                        'payment_method' => 'cash',
                        'customer_name' => null,
                        'customer_phone' => null,
                        'serial_number' => null,
                    ], JSON_UNESCAPED_UNICODE),
                ]],
            ]),
        ]);

        $prepared = $this->actingAs($owner)->postJson('/assistant/ask', [
            'question' => 'اعملي فاتورة بيع 1 هاتف سامسونج A55 سعر 10000 جنيه نقدي',
            'locale' => 'ar',
        ]);

        $prepared->assertOk()
            ->assertJsonPath('action.type', 'confirmation')
            ->assertJsonPath('action.details.2.value', '10,000.00 ج.م')
            ->assertJsonPath('action.details.3.value', '10,000.00 ج.م');
        $this->assertDatabaseCount('sales', 0);

        Http::assertSent(fn ($request) => $request['parallel_tool_calls'] === false
            && $request['tools'][0]['name'] === 'prepare_sales_invoice'
            && $request['tools'][0]['strict'] === true);

        $token = $prepared->json('action.token');
        $confirmed = $this->actingAs($owner)->postJson("/assistant/actions/{$token}/confirm", ['locale' => 'ar']);

        $confirmed->assertOk()
            ->assertJsonPath('invoice.total', 10000)
            ->assertJsonPath('invoice.status', 'finalized');
        $this->assertDatabaseHas('sales', [
            'cashier_id' => $owner->id,
            'total' => 10000,
            'paid_amount' => 10000,
            'payment_status' => 'paid',
            'status' => 'finalized',
        ]);
        $this->assertDatabaseHas('sale_payments', ['amount' => 10000, 'method' => 'cash', 'received_by' => $owner->id]);
        $this->assertDatabaseHas('warehouse_stocks', ['product_id' => $product->id, 'quantity' => 4]);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $owner->id, 'action' => 'ai.sale_invoice_created']);

        $this->actingAs($owner)
            ->postJson("/assistant/actions/{$token}/confirm", ['locale' => 'ar'])
            ->assertUnprocessable();
        $this->assertDatabaseCount('sales', 1);
    }

    public function test_the_assistant_does_not_offer_sales_tools_to_staff_without_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $accountant = User::factory()->create();
        $accountant->assignRole('Accountant');
        config(['services.openai.key' => 'test-key', 'services.openai.url' => 'https://api.openai.com/v1/responses']);
        Http::fake([
            'api.openai.com/v1/responses' => Http::response([
                'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => 'لا تملك صلاحية تنفيذ البيع.']]]],
            ]),
        ]);

        $this->actingAs($accountant)->postJson('/assistant/ask', [
            'question' => 'أنشئ فاتورة بيع',
            'locale' => 'ar',
        ])->assertOk();

        Http::assertSent(fn ($request) => ! isset($request['tools']));
    }

    public function test_a_clear_sales_command_can_be_prepared_locally_when_the_general_ai_service_is_unavailable(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $owner = User::factory()->create();
        $owner->assignRole('Owner');
        $warehouse = Warehouse::create(['name' => 'المخزن الرئيسي', 'code' => 'MAIN', 'is_default' => true, 'is_active' => true]);
        $product = Product::create([
            'sku' => 'SAM-S24',
            'name' => 'هاتف سامسونج S24',
            'tracking_type' => ProductTrackingType::Quantity,
            'purchase_price' => 8000,
            'sale_price' => 12000,
            'is_active' => true,
        ]);
        WarehouseStock::create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'quantity' => 3,
            'reserved_quantity' => 0,
            'average_cost' => 8000,
        ]);
        config(['services.openai.key' => null]);

        $this->actingAs($owner)->postJson('/assistant/ask', [
            'question' => 'اعملي فاتورة بيع 1 هاتف سامسونج S24 سعر 10000 جنيه نقدي',
            'locale' => 'ar',
        ])->assertOk()
            ->assertJsonPath('action.type', 'confirmation')
            ->assertJsonPath('action.details.0.value', 'هاتف سامسونج S24 — SAM-S24')
            ->assertJsonPath('action.details.3.value', '10,000.00 ج.م');

        $this->assertDatabaseCount('sales', 0);
        Http::assertNothingSent();
    }
}
