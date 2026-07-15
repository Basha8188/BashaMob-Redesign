<?php

namespace App\Models;

use App\Enums\ProductSerialStatus;
use Illuminate\Database\Eloquent\Model;

class ProductSerial extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => ProductSerialStatus::class, 'purchase_cost' => 'decimal:2', 'sale_price' => 'decimal:2', 'warranty_start' => 'date', 'warranty_end' => 'date', 'sold_at' => 'datetime'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
