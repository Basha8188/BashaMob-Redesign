<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
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

    public function test_maintenance_tracking_is_at_the_top_and_uses_an_overlay_result(): void
    {
        $this->get(route('maintenance'))
            ->assertOk()
            ->assertSeeInOrder(['maintenance-tracking-top', 'inner-hero'], false)
            ->assertSee('data-tracking-modal', false)
            ->assertSee('data-tracking-results', false)
            ->assertSee('data-request-launch', false);
    }

    public function test_every_admin_module_is_available_to_authenticated_staff(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Owner');
        $this->actingAs($user);

        $this->get(route('admin.dashboard'))->assertOk();

        foreach (['store', 'products', 'sales', 'purchases', 'inventory', 'repairs', 'customers', 'suppliers', 'expenses', 'reports', 'team', 'settings'] as $module) {
            $this->get(route('admin.module', $module))->assertOk();
        }
    }

    public function test_unknown_admin_module_returns_not_found(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Owner');
        $this->actingAs($user);
        $this->get(route('admin.module', 'unknown'))->assertNotFound();
    }
}
