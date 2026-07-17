<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->owner = User::factory()->create();
        $this->owner->assignRole('Owner');
        $this->actingAs($this->owner);
        Warehouse::create(['name' => 'المخزن الرئيسي', 'code' => 'MAIN', 'is_default' => true]);
    }

    public function test_product_button_creates_real_product_and_stock(): void
    {
        Storage::fake('public');

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
        $response = $this->post('/admin/products', ['sku' => 'IPH-16', 'barcode' => '', 'name' => 'iPhone 16', 'brand' => 'Apple', 'price' => 62900, 'quantity' => 3, 'image' => UploadedFile::fake()->createWithContent('iphone.png', $png)], ['Accept' => 'application/json']);
        $response->assertCreated()->assertJsonPath('row.cells.0', 'IPH-16');

        $product = Product::where('sku', 'IPH-16')->firstOrFail();
        $this->assertMatchesRegularExpression('/^\d{13}$/', $product->barcode);
        $this->assertSame($product->barcode, $response->json('row.cells.1'));
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
        $this->assertDatabaseHas('warehouse_stocks', ['quantity' => 3]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'product.created']);
    }

    public function test_product_barcode_is_unique_and_can_be_supplied_manually(): void
    {
        $payload = ['sku' => 'CABLE-1', 'barcode' => '6221234567895', 'name' => 'USB Cable', 'price' => 250, 'quantity' => 2];

        $this->postJson('/admin/products', $payload)->assertCreated();
        $this->postJson('/admin/products', [...$payload, 'sku' => 'CABLE-2'])->assertUnprocessable()->assertJsonValidationErrors('barcode');
    }

    public function test_employee_name_edit_is_persisted_in_database(): void
    {
        $employee = User::factory()->create(['name' => 'الاسم القديم']);
        $employee->assignRole('Cashier');

        $this->patchJson("/admin/team/{$employee->id}", [
            'name' => 'الاسم الجديد', 'email' => $employee->email, 'role' => 'Cashier', 'permissions' => 'المبيعات', 'status' => 'نشط',
        ])->assertOk()->assertJsonPath('row.cells.0', 'الاسم الجديد');

        $this->assertSame('الاسم الجديد', $employee->fresh()->name);
        $this->assertDatabaseHas('activity_logs', ['action' => 'staff.updated', 'subject_id' => $employee->id]);
    }

    public function test_cashier_cannot_create_products(): void
    {
        $cashier = User::factory()->create();
        $cashier->assignRole('Cashier');
        $this->actingAs($cashier)->postJson('/admin/products', ['sku' => 'X', 'name' => 'X', 'price' => 1, 'quantity' => 1])->assertForbidden();
    }
}
