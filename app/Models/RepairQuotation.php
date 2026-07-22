<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairQuotation extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['parts_total' => 'decimal:2', 'labor_cost' => 'decimal:2', 'discount' => 'decimal:2', 'tax' => 'decimal:2', 'total' => 'decimal:2', 'required_deposit' => 'decimal:2', 'snapshot' => 'array', 'expires_at' => 'datetime', 'decided_at' => 'datetime'];
    }

    public function ticket()
    {
        return $this->belongsTo(RepairTicket::class, 'repair_ticket_id');
    }

    public function device()
    {
        return $this->belongsTo(CustomerDevice::class, 'customer_device_id');
    }

    public function items()
    {
        return $this->hasMany(RepairQuotationItem::class);
    }

    public function responses()
    {
        return $this->hasMany(RepairQuotationResponse::class);
    }

    public function accessTokens()
    {
        return $this->morphMany(PublicAccessToken::class, 'tokenable');
    }
}
