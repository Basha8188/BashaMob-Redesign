<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncentivePeriod extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'calculated_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'closed_at' => 'datetime',
            'settings_snapshot' => 'array',
        ];
    }

    public function transactions()
    {
        return $this->hasMany(IncentiveTransaction::class);
    }

    public function summaries()
    {
        return $this->hasMany(EmployeeIncentiveSummary::class);
    }
}
