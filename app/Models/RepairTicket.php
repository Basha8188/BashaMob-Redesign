<?php

namespace App\Models;

use App\Enums\RepairStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairTicket extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => RepairStatus::class, 'estimated_cost' => 'decimal:2', 'final_cost' => 'decimal:2', 'paid_amount' => 'decimal:2', 'due_amount' => 'decimal:2', 'received_at' => 'datetime', 'expected_delivery_at' => 'datetime'];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(RepairStatusHistory::class);
    }

    public function parts()
    {
        return $this->hasMany(RepairPart::class);
    }

    public function device()
    {
        return $this->belongsTo(CustomerDevice::class, 'customer_device_id');
    }

    public function inspection()
    {
        return $this->hasOne(RepairInspection::class);
    }

    public function quotations()
    {
        return $this->hasMany(RepairQuotation::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
