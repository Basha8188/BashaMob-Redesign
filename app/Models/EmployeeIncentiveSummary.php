<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeIncentiveSummary extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'gross_sales' => 'decimal:4',
            'net_sales' => 'decimal:4',
            'returns_total' => 'decimal:4',
            'eligible_profit' => 'decimal:4',
            'target_amount' => 'decimal:4',
            'achievement_percent' => 'decimal:4',
            'estimated_commission' => 'decimal:4',
            'approved_commission' => 'decimal:4',
            'bonuses' => 'decimal:4',
            'penalties' => 'decimal:4',
            'snapshot' => 'array',
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

    public function period()
    {
        return $this->belongsTo(IncentivePeriod::class, 'incentive_period_id');
    }
}
