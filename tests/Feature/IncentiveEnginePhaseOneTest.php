<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\IncentivePeriod;
use App\Models\IncentivePlan;
use App\Models\IncentivePlanAssignment;
use App\Models\IncentiveRule;
use App\Models\IncentiveTransaction;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Incentives\IncentiveEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IncentiveEnginePhaseOneTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    private Warehouse $warehouse;

    private IncentivePeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-20 12:00:00');
        $this->employee = User::factory()->create([
            'is_staff' => true,
            'is_active' => true,
        ]);
        $this->warehouse = Warehouse::create([
            'name' => 'Main',
            'code' => 'MAIN',
            'is_default' => true,
            'is_active' => true,
        ]);
        $this->period = IncentivePeriod::create([
            'name' => 'July 2026',
            'period_type' => 'monthly',
            'starts_on' => '2026-07-01',
            'ends_on' => '2026-07-31',
            'status' => 'draft',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_percentage_and_fixed_commissions_are_calculated_with_decimal_precision(): void
    {
        $plan = $this->plan();
        $this->rule($plan, [
            'name' => 'Ten percent',
            'calculation_basis' => 'net_sales',
            'calculation_method' => 'percentage',
            'rate' => '10.000000',
            'priority' => 10,
        ]);
        $this->rule($plan, [
            'name' => 'Invoice bonus',
            'calculation_basis' => 'invoice_count',
            'calculation_method' => 'fixed_per_invoice',
            'fixed_amount' => '7.2500',
            'priority' => 20,
        ]);
        $product = $this->product('PHONE');
        $this->sale([[$product, '3.000', '33.33', '99.99']]);

        $this->engine()->calculatePeriod($this->period, $this->employee);

        $amounts = IncentiveTransaction::query()
            ->where('transaction_type', 'commission')
            ->orderBy('calculated_amount')
            ->pluck('calculated_amount')
            ->all();

        $this->assertSame(['7.2500', '9.9990'], $amounts);
        $this->assertSame(
            '17.2490',
            $this->period->fresh()->summaries()->sole()->estimated_commission
        );
    }

    public function test_product_and_category_rules_only_use_eligible_sale_items(): void
    {
        $phones = Category::create(['name' => 'Phones', 'slug' => 'phones', 'is_active' => true]);
        $accessories = Category::create(['name' => 'Accessories', 'slug' => 'accessories', 'is_active' => true]);
        $phone = $this->product('PHONE', $phones);
        $case = $this->product('CASE', $accessories);
        $charger = $this->product('CHARGER', $accessories);
        $plan = $this->plan();
        $categoryRule = $this->rule($plan, [
            'name' => 'Accessories',
            'calculation_basis' => 'category_sales',
            'calculation_method' => 'percentage',
            'rate' => '10',
            'category_id' => $accessories->id,
            'priority' => 10,
        ]);
        $productRule = $this->rule($plan, [
            'name' => 'Case only',
            'calculation_basis' => 'product_sales',
            'calculation_method' => 'percentage',
            'rate' => '5',
            'product_id' => $case->id,
            'priority' => 20,
        ]);
        $this->sale([
            [$phone, '1', '1000', '1000'],
            [$case, '2', '100', '200'],
            [$charger, '1', '300', '300'],
        ]);

        $this->engine()->calculatePeriod($this->period, $this->employee);

        $this->assertSame(
            '50.0000',
            IncentiveTransaction::where('incentive_rule_id', $categoryRule->id)->sole()->calculated_amount
        );
        $productTransaction = IncentiveTransaction::where('incentive_rule_id', $productRule->id)->sole();
        $this->assertSame('10.0000', $productTransaction->calculated_amount);
        $this->assertSame([$case->id], collect($productTransaction->snapshot['items'])->pluck('product_id')->unique()->all());
    }

    public function test_partial_return_reverses_only_the_commission_for_eligible_returned_items(): void
    {
        $eligibleCategory = Category::create(['name' => 'Eligible', 'slug' => 'eligible', 'is_active' => true]);
        $otherCategory = Category::create(['name' => 'Other', 'slug' => 'other', 'is_active' => true]);
        $eligible = $this->product('ELIGIBLE', $eligibleCategory);
        $other = $this->product('OTHER', $otherCategory);
        $plan = $this->plan();
        $rule = $this->rule($plan, [
            'calculation_basis' => 'category_sales',
            'calculation_method' => 'percentage',
            'rate' => '10',
            'category_id' => $eligibleCategory->id,
            'return_impact_mode' => 'reverse_full',
        ]);
        $sale = $this->sale([
            [$eligible, '2', '100', '200'],
            [$other, '1', '300', '300'],
        ]);
        $eligibleLine = $sale->items()->where('product_id', $eligible->id)->firstOrFail();
        $return = SaleReturn::create([
            'return_number' => 'RET-1',
            'sale_id' => $sale->id,
            'total' => '100.00',
            'status' => 'completed',
            'reason' => 'Customer return',
            'created_by' => $this->employee->id,
            'created_at' => '2026-07-21 10:00:00',
            'updated_at' => '2026-07-21 10:00:00',
        ]);
        SaleReturnItem::create([
            'sale_return_id' => $return->id,
            'sale_item_id' => $eligibleLine->id,
            'quantity' => '1',
            'unit_price' => '100',
            'total' => '100',
        ]);

        $this->engine()->calculatePeriod($this->period, $this->employee);

        $this->assertSame(
            '20.0000',
            IncentiveTransaction::where('incentive_rule_id', $rule->id)
                ->where('transaction_type', 'commission')
                ->sole()
                ->calculated_amount
        );
        $reversal = IncentiveTransaction::where('incentive_rule_id', $rule->id)
            ->where('transaction_type', 'return_reversal')
            ->sole();
        $this->assertSame('0.5000000000', $reversal->snapshot['eligible_return_ratio']);
        $this->assertSame('-10.0000', $reversal->calculated_amount);
    }

    public function test_recalculation_is_idempotent_and_preserves_the_original_snapshot(): void
    {
        $plan = $this->plan();
        $rule = $this->rule($plan, [
            'calculation_basis' => 'net_sales',
            'calculation_method' => 'percentage',
            'rate' => '10',
            'version' => 1,
        ]);
        $this->sale([[$this->product('PHONE'), '1', '100', '100']]);
        $engine = $this->engine();

        $engine->calculatePeriod($this->period, $this->employee);
        $transaction = IncentiveTransaction::sole();
        $originalSnapshot = $transaction->snapshot;
        $originalCalculatedAt = $transaction->calculated_at->toISOString();

        $rule->update(['name' => 'Changed later', 'rate' => '25', 'version' => 2]);
        Carbon::setTestNow('2026-07-22 13:00:00');
        $engine->calculatePeriod($this->period->fresh(), $this->employee);

        $this->assertSame(1, IncentiveTransaction::count());
        $transaction = IncentiveTransaction::sole();
        $this->assertSame('10.0000', $transaction->calculated_amount);
        $this->assertSame($originalSnapshot, $transaction->snapshot);
        $this->assertSame($originalCalculatedAt, $transaction->calculated_at->toISOString());
    }

    public function test_highest_only_conflict_policy_records_only_the_highest_matching_rule(): void
    {
        $plan = $this->plan(['conflict_resolution' => 'highest_only']);
        $lowerRule = $this->rule($plan, [
            'name' => 'Lower',
            'calculation_basis' => 'net_sales',
            'calculation_method' => 'percentage',
            'rate' => '5',
            'priority' => 10,
        ]);
        $higherRule = $this->rule($plan, [
            'name' => 'Higher',
            'calculation_basis' => 'net_sales',
            'calculation_method' => 'percentage',
            'rate' => '12',
            'priority' => 20,
        ]);
        $this->sale([[$this->product('PHONE'), '1', '100', '100']]);

        $this->engine()->calculatePeriod($this->period, $this->employee);

        $this->assertDatabaseMissing('incentive_transactions', [
            'incentive_rule_id' => $lowerRule->id,
            'transaction_type' => 'commission',
        ]);
        $this->assertDatabaseHas('incentive_transactions', [
            'incentive_rule_id' => $higherRule->id,
            'transaction_type' => 'commission',
            'calculated_amount' => '12.0000',
        ]);
        $this->assertSame(1, IncentiveTransaction::count());
    }

    private function engine(): IncentiveEngine
    {
        return app(IncentiveEngine::class);
    }

    private function plan(array $overrides = []): IncentivePlan
    {
        $plan = IncentivePlan::create(array_merge([
            'name' => 'Default plan',
            'code' => 'PLAN-'.fake()->unique()->numerify('#####'),
            'status' => 'active',
            'period_type' => 'monthly',
            'conflict_resolution' => 'sum_all',
            'starts_on' => '2026-01-01',
        ], $overrides));

        IncentivePlanAssignment::create([
            'incentive_plan_id' => $plan->id,
            'scope_type' => 'employee',
            'employee_id' => $this->employee->id,
            'priority' => 1,
            'is_active' => true,
        ]);

        return $plan;
    }

    private function rule(IncentivePlan $plan, array $overrides = []): IncentiveRule
    {
        return IncentiveRule::create(array_merge([
            'incentive_plan_id' => $plan->id,
            'name' => 'Sales commission',
            'rule_type' => 'sales_commission',
            'calculation_basis' => 'net_sales',
            'calculation_method' => 'percentage',
            'rate' => '10',
            'return_impact_mode' => 'reverse_full',
            'priority' => 100,
            'is_stackable' => true,
            'stops_processing' => false,
            'is_active' => true,
            'version' => 1,
        ], $overrides));
    }

    private function product(string $sku, ?Category $category = null): Product
    {
        return Product::create([
            'category_id' => $category?->id,
            'sku' => $sku,
            'name' => $sku,
            'tracking_type' => 'quantity',
            'purchase_price' => '10',
            'sale_price' => '100',
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<int, array{0: Product, 1: numeric-string, 2: numeric-string, 3: numeric-string}>  $lines
     */
    private function sale(array $lines): Sale
    {
        $subtotal = array_sum(array_map(fn (array $line) => (float) $line[3], $lines));
        $sale = Sale::create([
            'invoice_number' => 'INV-'.fake()->unique()->numerify('#####'),
            'warehouse_id' => $this->warehouse->id,
            'cashier_id' => $this->employee->id,
            'status' => 'finalized',
            'subtotal' => $subtotal,
            'discount' => 0,
            'tax' => 0,
            'total' => $subtotal,
            'paid_amount' => $subtotal,
            'due_amount' => 0,
            'payment_status' => 'paid',
            'finalized_at' => '2026-07-20 10:00:00',
        ]);

        foreach ($lines as [$product, $quantity, $unitPrice, $total]) {
            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount' => 0,
                'tax' => 0,
                'total' => $total,
                'returned_quantity' => 0,
            ]);
        }

        return $sale->fresh('items');
    }
}
