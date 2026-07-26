<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncentiveTransaction extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'base_amount' => 'decimal:4',
            'rate' => 'decimal:6',
            'fixed_amount' => 'decimal:4',
            'calculated_amount' => 'decimal:4',
            'return_impact' => 'decimal:4',
            'snapshot' => 'array',
            'calculated_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function plan()
    {
        return $this->belongsTo(IncentivePlan::class, 'incentive_plan_id');
    }

    public function rule()
    {
        return $this->belongsTo(IncentiveRule::class, 'incentive_rule_id');
    }

    public function period()
    {
        return $this->belongsTo(IncentivePeriod::class, 'incentive_period_id');
    }
}
