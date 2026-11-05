<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->unsignedBigInteger('variant_key')->default(0);
            $table->decimal('inventory_value', 20, 6)->nullable();
            $table->decimal('average_cost', 20, 10)->nullable()->change();
        });
        DB::table('warehouse_stocks')->whereNotNull('product_variant_id')->update(['variant_key' => DB::raw('product_variant_id')]);
        // Existing balances have no verified cost ledger. Keep their value unknown.
        DB::table('warehouse_stocks')->where('quantity', 0)->update(['inventory_value' => 0, 'average_cost' => null]);
        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->unique(['warehouse_id', 'product_id', 'variant_key'], 'warehouse_product_variant_key_unique');
        });
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('unit_cost', 20, 10)->nullable()->change();
            $table->decimal('value_delta', 20, 6)->nullable();
            $table->decimal('value_before', 20, 6)->nullable();
            $table->decimal('value_after', 20, 6)->nullable();
            $table->string('event_key')->nullable()->unique();
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('unit_cost_snapshot', 20, 10)->nullable()->change();
        });
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->decimal('inventory_value_removed', 20, 6)->nullable();
            $table->decimal('cost_variance', 20, 6)->nullable();
        });
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->string('idempotency_key')->nullable()->unique();
        });
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->string('idempotency_key')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->dropColumn('idempotency_key');
        });
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropColumn('idempotency_key');
        });
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->dropColumn(['inventory_value_removed', 'cost_variance']);
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('unit_cost_snapshot', 18, 4)->nullable()->change();
        });
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn(['value_delta', 'value_before', 'value_after', 'event_key']);
            $table->decimal('unit_cost', 14, 2)->nullable()->change();
        });
        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->dropUnique('warehouse_product_variant_key_unique');
            $table->dropColumn(['variant_key', 'inventory_value']);
            $table->decimal('average_cost', 14, 2)->default(0)->change();
        });
    }
};
