<?php

namespace App\Services\Incentives;

use App\Models\IncentiveRule;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Validation\ValidationException;

class CommissionCalculator
{
    public function calculate(IncentiveRule $rule, string $basisAmount, string $quantity = '1'): array
    {
        $basis = BigDecimal::of($basisAmount);
        $quantityDecimal = BigDecimal::of($quantity);

        if ($rule->minimum_basis !== null && $basis->isLessThan(BigDecimal::of($rule->minimum_basis))) {
            return ['eligible_basis' => $basis->toScale(4, RoundingMode::HALF_UP)->__toString(), 'amount' => '0.0000'];
        }

        $eligible = $rule->maximum_eligible_basis !== null
            ? $basis->min(BigDecimal::of($rule->maximum_eligible_basis))
            : $basis;

        $amount = match ($rule->calculation_method) {
            'percentage' => $eligible
                ->multipliedBy(BigDecimal::of($rule->rate ?? '0'))
                ->dividedBy('100', 8, RoundingMode::HALF_UP),
            'fixed_per_invoice' => BigDecimal::of($rule->fixed_amount ?? '0'),
            'fixed_per_item', 'fixed_per_unit' => BigDecimal::of($rule->fixed_amount ?? '0')->multipliedBy($quantityDecimal),
            default => throw ValidationException::withMessages([
                'calculation_method' => 'طريقة حساب العمولة غير مدعومة.',
            ]),
        };

        if ($rule->minimum_commission !== null) {
            $amount = $amount->max(BigDecimal::of($rule->minimum_commission));
        }
        if ($rule->maximum_commission !== null) {
            $amount = $amount->min(BigDecimal::of($rule->maximum_commission));
        }

        return [
            'eligible_basis' => $eligible->toScale(4, RoundingMode::HALF_UP)->__toString(),
            'amount' => $amount->toScale(4, RoundingMode::HALF_UP)->__toString(),
        ];
    }
}
