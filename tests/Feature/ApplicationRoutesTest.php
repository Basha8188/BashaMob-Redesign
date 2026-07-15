<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_are_available(): void
    {
        foreach (['home', 'shop', 'maintenance', 'computers', 'staff.login'] as $route) {
            $this->get(route($route))->assertOk();
        }

        $this->get(route('customer.device-request'))->assertRedirect(route('maintenance'));
    }

    public function test_every_admin_module_is_available_to_authenticated_staff(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.dashboard'))->assertOk();

        foreach (['store', 'products', 'sales', 'purchases', 'inventory', 'repairs', 'customers', 'suppliers', 'expenses', 'reports', 'team', 'settings'] as $module) {
            $this->get(route('admin.module', $module))->assertOk();
        }
    }

    public function test_unknown_admin_module_returns_not_found(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.module', 'unknown'))->assertNotFound();
    }
}
