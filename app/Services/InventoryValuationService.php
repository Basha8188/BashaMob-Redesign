<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class InventoryValuationService
{
    public function total(): float
    {
        return (float) DB::table('warehouse_stocks')
            ->join('products', 'products.id', '=', 'warehouse_stocks.product_id')
            ->whereNull('products.deleted_at')
            ->sum(DB::raw('warehouse_stocks.quantity * COALESCE(NULLIF(warehouse_stocks.average_cost, 0), NULLIF(products.purchase_price, 0), products.sale_price, 0)'));
    }
}
