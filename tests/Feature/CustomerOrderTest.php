<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_purchase_request_creates_a_customer_and_pending_order(): void
    {
        $response = $this->postJson(route('customer.orders.store'), [
            'name' => 'أحمد محمد',
            'phone' => '01012345678',
            'email' => 'ahmed@example.com',
            'items' => [
                ['name' => 'iPhone 16 Pro', 'price' => 62900],
                ['name' => 'AirPods Pro', 'price' => 11500],
            ],
        ]);

        $response->assertCreated()->assertJsonStructure(['message', 'order_number', 'pending_count']);
        $this->assertDatabaseHas('customers', ['phone' => '01012345678', 'name' => 'أحمد محمد']);
        $this->assertDatabaseHas('customer_orders', ['status' => 'pending_review', 'total' => 74400]);
        $this->assertSame(2, count(CustomerOrder::firstOrFail()->items));
    }

    public function test_new_order_updates_an_existing_customer_instead_of_duplicating_it(): void
    {
        Customer::create(['name' => 'اسم قديم', 'phone' => '01012345678']);

        $this->postJson(route('customer.orders.store'), [
            'name' => 'اسم محدث',
            'phone' => '01012345678',
            'email' => 'new@example.com',
            'items' => [['name' => 'Galaxy S25 Ultra', 'price' => 54500]],
        ])->assertCreated();

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseHas('customers', ['phone' => '01012345678', 'name' => 'اسم محدث', 'email' => 'new@example.com']);
    }
}
