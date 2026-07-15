<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashSession extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['opening_balance' => 'decimal:2', 'closing_balance' => 'decimal:2', 'expected_balance' => 'decimal:2', 'opened_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function register()
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }
}
