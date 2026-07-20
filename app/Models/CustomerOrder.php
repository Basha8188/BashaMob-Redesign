<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerOrder extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'total' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
