<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function devices()
    {
        return $this->hasMany(CustomerDevice::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function repairs()
    {
        return $this->hasMany(RepairTicket::class);
    }
}
