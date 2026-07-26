<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncentiveRule extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:6',
            'fixed_amount' => 'decimal:4',
            'minimum_basis' => 'decimal:4',
            'maximum_eligible_basis' => 'decimal:4',
            'minimum_commission' => 'decimal:4',
            'maximum_commission' => 'decimal:4',
            'return_impact_rate' => 'decimal:6',
            'is_stackable' => 'boolean',
            'stops_processing' => 'boolean',
            'is_active' => 'boolean',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'conditions' => 'array',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(IncentivePlan::class, 'incentive_plan_id');
    }

    public function versions()
    {
        return $this->hasMany(IncentiveRuleVersion::class);
    }
}
