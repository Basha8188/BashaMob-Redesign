<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncentivePlanAssignment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'is_active' => 'boolean'];
    }

    public function plan()
    {
        return $this->belongsTo(IncentivePlan::class, 'incentive_plan_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
