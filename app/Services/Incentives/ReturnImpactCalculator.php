<?php

namespace App\Services\Incentives;

use App\Models\IncentiveRule;
use App\Models\IncentiveTransaction;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class ReturnImpactCalculator
{
    public function calculate(
        IncentiveRule $rule,
        IncentiveTransaction $original,
        string $eligibleReturnRatio,
    ): string {
        if ($rule->return_impact_mode === 'ignore') {
            return '0.0000';
        }

        $ratio = BigDecimal::min(
            BigDecimal::max(BigDecimal::of($eligibleReturnRatio), BigDecimal::zero()),
            BigDecimal::one()
        );
        $impact = BigDecimal::of($original->calculated_amount)->multipliedBy($ratio);

        if ($rule->return_impact_mode === 'reverse_percentage') {
            $impact = $impact
                ->multipliedBy(BigDecimal::of($rule->return_impact_rate ?? '0'))
                ->dividedBy('100', 10, RoundingMode::HALF_UP);
        }

        return $impact->negated()->toScale(4, RoundingMode::HALF_UP)->__toString();
    }
}
