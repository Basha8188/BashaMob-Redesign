<?php

namespace App\Services\Incentives;

use App\Models\IncentiveRule;
use App\Models\Sale;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class RuleEvaluator
{
    public function applies(IncentiveRule $rule, Sale $sale, CarbonInterface $date): bool
    {
        if (! $rule->is_active) {
            return false;
        }
        if ($rule->starts_on && $date->lt($rule->starts_on->startOfDay())) {
            return false;
        }
        if ($rule->ends_on && $date->gt($rule->ends_on->endOfDay())) {
            return false;
        }
        if ($rule->payment_method && ! $sale->payments->contains('method', $rule->payment_method)) {
            return false;
        }

        return $this->eligibleItems($rule, $sale)->isNotEmpty();
    }

    public function eligibleItems(IncentiveRule $rule, Sale $sale): Collection
    {
        return $sale->items->filter(function ($item) use ($rule) {
            if ($rule->product_id && (int) $item->product_id !== (int) $rule->product_id) {
                return false;
            }
            if ($rule->category_id && (int) $item->product?->category_id !== (int) $rule->category_id) {
                return false;
            }
            if ($rule->brand_id && (int) $item->product?->brand_id !== (int) $rule->brand_id) {
                return false;
            }

            return true;
        })->values();
    }
}
