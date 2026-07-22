<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairQuotationResponse extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['quotation_snapshot' => 'array'];
    }

    public function quotation()
    {
        return $this->belongsTo(RepairQuotation::class, 'repair_quotation_id');
    }
}
