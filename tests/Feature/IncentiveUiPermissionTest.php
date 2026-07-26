<?php

namespace Tests\Feature;

use App\Models\EmployeeIncentiveSummary;
use App\Models\IncentivePeriod;
use App\Models\IncentivePlan;
use App\Models\User;
use Database\Seeders\IncentivePermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncentiveUiPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(IncentivePermissionSeeder::class);
    }

    public function test_employee_can_view_only_their_own_incentive_screen(): void
    {
        $employee = User::factory()->create(['name' => 'Own Employee']);
        $other = User::factory()->create(['name' => 'Other Employee']);
        $employee->assignRole('Cashier');
        $other->assignRole('Cashier');

        $period = IncentivePeriod::query()->create([
            'name' => 'July 2026',
            'period_type' => 'monthly',
            'starts_on' => '2026-07-01',
            'ends_on' => '2026-07-31',
        ]);
        $ownPlan = IncentivePlan::query()->create([
            'name' => 'Own visible plan',
            'code' => 'OWN-PLAN',
            'status' => 'active',
        ]);
        $otherPlan = IncentivePlan::query()->create([
            'name' => 'Other hidden plan',
            'code' => 'OTHER-PLAN',
            'status' => 'active',
        ]);
        EmployeeIncentiveSummary::query()->create([
            'employee_id' => $employee->id,
            'incentive_plan_id' => $ownPlan->id,
            'incentive_period_id' => $period->id,
            'net_sales' => 12500,
            'estimated_commission' => 125,
        ]);
        EmployeeIncentiveSummary::query()->create([
            'employee_id' => $other->id,
            'incentive_plan_id' => $otherPlan->id,
            'incentive_period_id' => $period->id,
            'net_sales' => 999999,
            'estimated_commission' => 9999,
        ]);

        $this->actingAs($employee)
            ->get(route('admin.incentives.mine'))
            ->assertOk()
            ->assertSee('Own visible plan')
            ->assertDontSee('Other hidden plan')
            ->assertDontSee('999,999');

        $this->actingAs($employee)
            ->get(route('admin.incentives.employees.show', $other))
            ->assertForbidden();
        $this->actingAs($employee)
            ->get(route('admin.incentives.index'))
            ->assertForbidden();
    }

    public function test_authorized_manager_can_open_dashboard_and_another_employee(): void
    {
        $manager = User::factory()->create();
        $employee = User::factory()->create(['name' => 'Visible employee']);
        $manager->assignRole('Manager');
        $employee->assignRole('Cashier');

        $this->actingAs($manager)
            ->get(route('admin.incentives.index'))
            ->assertOk()
            ->assertSee('الحوافز والعمولات')
            ->assertSee(route('admin.incentives.employees.show', $employee), false);

        $this->actingAs($manager)
            ->get(route('admin.incentives.employees.show', $employee))
            ->assertOk()
            ->assertSee('Visible employee');
    }

    public function test_owner_can_create_plan_assignment_and_first_rule_atomically(): void
    {
        $owner = User::factory()->create();
        $employee = User::factory()->create();
        $owner->assignRole('Owner');
        $employee->assignRole('Cashier');

        $this->actingAs($owner)
            ->post(route('admin.incentives.plans.store'), [
                'name' => 'Main sales plan',
                'code' => 'main_sales_2026',
                'description' => 'Configurable plan',
                'status' => 'active',
                'period_type' => 'monthly',
                'conflict_resolution' => 'sum_all',
                'target_basis' => 'net_sales',
                'target_amount' => '100000',
                'ignored_return_reasons' => "Manufacturing defect\nSupplier mistake",
                'assignment_scope' => 'employee',
                'employee_id' => $employee->id,
                'assignment_priority' => 100,
                'rule_name' => 'Net sales percentage',
                'calculation_basis' => 'net_sales',
                'calculation_method' => 'percentage',
                'rate' => '1.250000',
                'return_impact_mode' => 'reverse_full',
                'rule_priority' => 100,
                'is_stackable' => 1,
                'stops_processing' => 0,
            ])
            ->assertRedirect(route('admin.incentives.index'));

        $plan = IncentivePlan::query()->where('code', 'MAIN_SALES_2026')->firstOrFail();
        $this->assertDatabaseHas('incentive_plan_assignments', [
            'incentive_plan_id' => $plan->id,
            'scope_type' => 'employee',
            'employee_id' => $employee->id,
        ]);
        $this->assertDatabaseHas('incentive_rules', [
            'incentive_plan_id' => $plan->id,
            'calculation_basis' => 'net_sales',
            'calculation_method' => 'percentage',
        ]);
        $this->assertDatabaseHas('incentive_rule_versions', ['version' => 1]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'incentives.plan_created',
            'subject_id' => $plan->id,
        ]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'incentives.rule_created']);
    }

    public function test_owner_can_create_and_calculate_period_but_cashier_cannot(): void
    {
        $owner = User::factory()->create();
        $cashier = User::factory()->create();
        $owner->assignRole('Owner');
        $cashier->assignRole('Cashier');

        $plan = IncentivePlan::query()->create([
            'name' => 'Active plan',
            'code' => 'ACTIVE-PLAN',
            'status' => 'active',
        ]);
        $plan->assignments()->create([
            'scope_type' => 'employee',
            'employee_id' => $cashier->id,
            'priority' => 100,
            'is_active' => true,
        ]);
        $plan->rules()->create([
            'name' => 'Invoice commission',
            'calculation_basis' => 'invoice_count',
            'calculation_method' => 'fixed_per_invoice',
            'fixed_amount' => 10,
            'return_impact_mode' => 'reverse_full',
            'priority' => 100,
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('admin.incentives.periods.store'), [
                'period_name' => 'July 2026',
                'calculation_period_type' => 'monthly',
                'period_starts_on' => '2026-07-01',
                'period_ends_on' => '2026-07-31',
            ])
            ->assertRedirect(route('admin.incentives.index'));

        $period = IncentivePeriod::query()->firstOrFail();
        $this->actingAs($owner)
            ->post(route('admin.incentives.periods.calculate', $period))
            ->assertRedirect(route('admin.incentives.index'));

        $this->assertSame('calculated', $period->fresh()->status);
        $this->assertDatabaseHas('employee_incentive_summaries', [
            'employee_id' => $cashier->id,
            'incentive_period_id' => $period->id,
        ]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'incentives.period_created']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'incentives.period_calculated']);

        $this->actingAs($cashier)
            ->post(route('admin.incentives.periods.calculate', $period))
            ->assertForbidden();
        $this->actingAs($cashier)
            ->post(route('admin.incentives.plans.store'), [])
            ->assertForbidden();
    }

    public function test_sensitive_permissions_are_not_granted_to_cashier_or_manager(): void
    {
        $cashier = User::factory()->create();
        $manager = User::factory()->create();
        $owner = User::factory()->create();
        $cashier->assignRole('Cashier');
        $manager->assignRole('Manager');
        $owner->assignRole('Owner');

        $this->assertTrue($cashier->can('incentives.view_own'));
        $this->assertFalse($cashier->can('incentives.view_all_employees'));
        $this->assertFalse($cashier->can('incentives.approve'));
        $this->assertFalse($cashier->can('incentives.pay'));
        $this->assertFalse($manager->can('incentives.pay'));
        $this->assertFalse($manager->can('incentives.reopen_period'));
        $this->assertTrue($owner->can('incentives.pay'));
        $this->assertTrue($owner->can('incentives.reopen_period'));
    }
}
