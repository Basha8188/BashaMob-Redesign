<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DevicePassportEvent extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'occurred_at' => 'datetime'];
    }

    public function device()
    {
        return $this->belongsTo(CustomerDevice::class, 'customer_device_id');
    }

    public function eventable()
    {
        return $this->morphTo();
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
