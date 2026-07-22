<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairQuotationItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'unit_price' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function quotation()
    {
        return $this->belongsTo(RepairQuotation::class, 'repair_quotation_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
