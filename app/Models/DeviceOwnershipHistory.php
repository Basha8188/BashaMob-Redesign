<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceOwnershipHistory extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function device()
    {
        return $this->belongsTo(CustomerDevice::class, 'customer_device_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function source()
    {
        return $this->morphTo();
    }
}
