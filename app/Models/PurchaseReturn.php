<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['total' => 'decimal:2'];
    }

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }
}
