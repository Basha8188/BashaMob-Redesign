<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairPart extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'unit_cost' => 'decimal:2', 'sale_price' => 'decimal:2'];
    }

    public function ticket()
    {
        return $this->belongsTo(RepairTicket::class, 'repair_ticket_id');
    }
}
