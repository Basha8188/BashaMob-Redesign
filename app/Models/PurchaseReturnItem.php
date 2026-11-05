<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'unit_cost' => 'decimal:2', 'total' => 'decimal:2', 'inventory_value_removed' => 'decimal:6', 'cost_variance' => 'decimal:6'];
    }
}
