<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncentivePlan extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:4',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'settings' => 'array',
        ];
    }

    public function assignments()
    {
        return $this->hasMany(IncentivePlanAssignment::class);
    }

    public function rules()
    {
        return $this->hasMany(IncentiveRule::class);
    }

    public function transactions()
    {
        return $this->hasMany(IncentiveTransaction::class);
    }
}
