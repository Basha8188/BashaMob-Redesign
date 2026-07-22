<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairInspection extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['has_google_account' => 'boolean', 'has_icloud_account' => 'boolean', 'is_locked' => 'boolean', 'accessories' => 'array', 'damage_map' => 'array', 'completed_at' => 'datetime', 'consented_at' => 'datetime'];
    }

    public function ticket()
    {
        return $this->belongsTo(RepairTicket::class, 'repair_ticket_id');
    }

    public function device()
    {
        return $this->belongsTo(CustomerDevice::class, 'customer_device_id');
    }

    public function photos()
    {
        return $this->hasMany(RepairInspectionPhoto::class);
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }
}
