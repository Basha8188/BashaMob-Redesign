<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncentiveRuleVersion extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'effective_at' => 'datetime'];
    }

    public function rule()
    {
        return $this->belongsTo(IncentiveRule::class, 'incentive_rule_id');
    }
}
