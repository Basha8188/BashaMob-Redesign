<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['type' => StockMovementType::class, 'quantity' => 'decimal:3', 'quantity_before' => 'decimal:3', 'quantity_after' => 'decimal:3', 'unit_cost' => 'decimal:2'];
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
