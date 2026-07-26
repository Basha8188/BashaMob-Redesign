<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('unit_cost_snapshot', 18, 4)->nullable()->after('unit_price');
            $table->string('cost_source', 30)->nullable()->after('unit_cost_snapshot');
        });

        // Existing non-serial sales do not have a reliable historical cost. Preserve a
        // clearly labelled estimate now so later product price changes cannot rewrite it.
        DB::table('sale_items')
            ->select(['id', 'sale_id', 'product_id', 'product_variant_id', 'product_serial_id'])
            ->whereNull('unit_cost_snapshot')
            ->orderBy('id')
            ->chunkById(500, function ($items) {
                foreach ($items as $item) {
                    $serialCost = $item->product_serial_id
                        ? DB::table('product_serials')->where('id', $item->product_serial_id)->value('purchase_cost')
                        : null;
                    $variantCost = $item->product_variant_id
                        ? DB::table('product_variants')->where('id', $item->product_variant_id)->value('purchase_price')
                        : null;
                    $warehouseId = DB::table('sales')->where('id', $item->sale_id)->value('warehouse_id');
                    $stockCost = $warehouseId
                        ? DB::table('warehouse_stocks')
                            ->where('warehouse_id', $warehouseId)
                            ->where('product_id', $item->product_id)
                            ->when(
                                $item->product_variant_id,
                                fn ($query) => $query->where('product_variant_id', $item->product_variant_id),
                                fn ($query) => $query->whereNull('product_variant_id')
                            )
                            ->value('average_cost')
                        : null;
                    $productCost = DB::table('products')->where('id', $item->product_id)->value('purchase_price');
                    $cost = $serialCost ?? $stockCost ?? $variantCost ?? $productCost;

                    if ($cost !== null) {
                        DB::table('sale_items')->where('id', $item->id)->update([
                            'unit_cost_snapshot' => $cost,
                            'cost_source' => $serialCost !== null ? 'serial_purchase_cost' : 'estimated_backfill',
                        ]);
                    }
                }
            });

        Schema::create('incentive_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 60)->unique();
            $table->text('description')->nullable();
            $table->string('status', 24)->default('draft')->index();
            $table->string('period_type', 24)->default('monthly');
            $table->string('conflict_resolution', 24)->default('sum_all');
            $table->string('target_basis', 40)->nullable();
            $table->decimal('target_amount', 18, 4)->nullable();
            $table->date('starts_on')->nullable()->index();
            $table->date('ends_on')->nullable()->index();
            $table->json('settings')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('incentive_plan_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incentive_plan_id')->constrained()->cascadeOnDelete();
            $table->string('scope_type', 24)->index();
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role_name', 100)->nullable();
            $table->unsignedInteger('priority')->default(100);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['employee_id', 'is_active']);
            $table->index(['role_name', 'is_active']);
        });

        Schema::create('incentive_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incentive_plan_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('rule_type', 40)->default('sales_commission')->index();
            $table->string('calculation_basis', 40)->index();
            $table->string('calculation_method', 40)->index();
            $table->decimal('rate', 12, 6)->nullable();
            $table->decimal('fixed_amount', 18, 4)->nullable();
            $table->decimal('minimum_basis', 18, 4)->nullable();
            $table->decimal('maximum_eligible_basis', 18, 4)->nullable();
            $table->decimal('minimum_commission', 18, 4)->nullable();
            $table->decimal('maximum_commission', 18, 4)->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_method', 30)->nullable();
            $table->string('return_impact_mode', 30)->default('reverse_full');
            $table->decimal('return_impact_rate', 12, 6)->nullable();
            $table->unsignedInteger('priority')->default(100)->index();
            $table->boolean('is_stackable')->default(true);
            $table->boolean('stops_processing')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->date('starts_on')->nullable()->index();
            $table->date('ends_on')->nullable()->index();
            $table->unsignedInteger('version')->default(1);
            $table->json('conditions')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['incentive_plan_id', 'is_active', 'priority'], 'incentive_rules_plan_active_priority');
        });

        Schema::create('incentive_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('period_type', 24)->default('monthly');
            $table->date('starts_on')->index();
            $table->date('ends_on')->index();
            $table->string('status', 24)->default('draft')->index();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('settings_snapshot')->nullable();
            $table->timestamps();
            $table->unique(['starts_on', 'ends_on'], 'incentive_period_dates_unique');
        });

        Schema::create('incentive_rule_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incentive_rule_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->timestamp('effective_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['incentive_rule_id', 'version'], 'incentive_rule_version_unique');
        });

        Schema::create('incentive_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('incentive_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('incentive_rule_id')->constrained()->restrictOnDelete();
            $table->foreignId('incentive_rule_version_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('incentive_period_id')->constrained()->restrictOnDelete();
            $table->string('source_type', 100);
            $table->unsignedBigInteger('source_id');
            $table->string('transaction_type', 30)->index();
            $table->string('calculation_basis', 40);
            $table->decimal('base_amount', 18, 4)->default(0);
            $table->decimal('rate', 12, 6)->nullable();
            $table->decimal('fixed_amount', 18, 4)->nullable();
            $table->decimal('calculated_amount', 18, 4);
            $table->decimal('return_impact', 18, 4)->default(0);
            $table->string('status', 24)->default('calculated')->index();
            $table->uuid('calculation_run_id')->nullable()->index();
            $table->foreignId('reversal_of_id')->nullable()->constrained('incentive_transactions')->nullOnDelete();
            $table->json('snapshot');
            $table->timestamp('calculated_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(
                ['employee_id', 'incentive_rule_id', 'incentive_period_id', 'source_type', 'source_id', 'transaction_type'],
                'incentive_transaction_idempotency'
            );
            $table->index(['employee_id', 'incentive_period_id', 'status'], 'incentive_employee_period_status');
            $table->index(['source_type', 'source_id'], 'incentive_transaction_source');
        });

        Schema::create('employee_incentive_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('incentive_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('incentive_period_id')->constrained()->restrictOnDelete();
            $table->decimal('gross_sales', 18, 4)->default(0);
            $table->decimal('net_sales', 18, 4)->default(0);
            $table->decimal('returns_total', 18, 4)->default(0);
            $table->decimal('eligible_profit', 18, 4)->default(0);
            $table->decimal('target_amount', 18, 4)->nullable();
            $table->decimal('achievement_percent', 9, 4)->nullable();
            $table->decimal('estimated_commission', 18, 4)->default(0);
            $table->decimal('approved_commission', 18, 4)->default(0);
            $table->decimal('bonuses', 18, 4)->default(0);
            $table->decimal('penalties', 18, 4)->default(0);
            $table->string('status', 24)->default('calculated')->index();
            $table->json('snapshot')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'incentive_plan_id', 'incentive_period_id'], 'employee_incentive_summary_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_incentive_summaries');
        Schema::dropIfExists('incentive_transactions');
        Schema::dropIfExists('incentive_rule_versions');
        Schema::dropIfExists('incentive_periods');
        Schema::dropIfExists('incentive_rules');
        Schema::dropIfExists('incentive_plan_assignments');
        Schema::dropIfExists('incentive_plans');

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['unit_cost_snapshot', 'cost_source']);
        });
    }
};
