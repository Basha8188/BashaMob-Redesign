<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function sessions()
    {
        return $this->hasMany(CashSession::class);
    }

    public function transactions()
    {
        return $this->hasMany(CashTransaction::class);
    }
}
