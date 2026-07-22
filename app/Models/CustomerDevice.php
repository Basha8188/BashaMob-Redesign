<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerDevice extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['first_seen_at' => 'datetime'];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function productSerial()
    {
        return $this->belongsTo(ProductSerial::class);
    }

    public function repairs()
    {
        return $this->hasMany(RepairTicket::class);
    }

    public function events()
    {
        return $this->hasMany(DevicePassportEvent::class)->orderByDesc('occurred_at');
    }

    public function ownershipHistory()
    {
        return $this->hasMany(DeviceOwnershipHistory::class)->orderByDesc('started_at');
    }

    public function inspections()
    {
        return $this->hasMany(RepairInspection::class);
    }

    public function quotations()
    {
        return $this->hasMany(RepairQuotation::class);
    }
}
