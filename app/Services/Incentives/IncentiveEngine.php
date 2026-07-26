<?php

namespace App\Services\Incentives;

use App\Models\EmployeeIncentiveSummary;
use App\Models\IncentivePeriod;
use App\Models\IncentivePlan;
use App\Models\IncentivePlanAssignment;
use App\Models\IncentiveRule;
use App\Models\IncentiveTransaction;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\ActivityLogger;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IncentiveEngine
{
    public function __construct(
        private CommissionCalculator $calculator,
        private RuleEvaluator $rules,
        private ReturnImpactCalculator $returns,
    ) {}

    public function calculatePeriod(IncentivePeriod $period, ?User $onlyEmployee = null): IncentivePeriod
    {
        return DB::transaction(function () use ($period, $onlyEmployee) {
            $period = IncentivePeriod::query()->lockForUpdate()->findOrFail($period->id);
            if (in_array($period->status, ['approved', 'paid', 'closed'], true)) {
                throw ValidationException::withMessages([
                    'period' => 'لا يمكن إعادة حساب فترة معتمدة أو مدفوعة أو مغلقة.',
                ]);
            }

            $employees = $onlyEmployee
                ? collect([$onlyEmployee])
                : User::query()->where('is_staff', true)->where('is_active', true)->get();
            $runId = (string) Str::uuid();

            foreach ($employees as $employee) {
                $this->calculateEmployee($employee, $period, $runId);
            }

            $period->update([
                'status' => 'calculated',
                'calculated_at' => now(),
                'calculated_by' => auth()->id(),
                'settings_snapshot' => [
                    'engine' => 'phase_one',
                    'precision' => 4,
                    'calculation_run_id' => $runId,
                    'calculated_employee_ids' => $employees->pluck('id')->all(),
                ],
            ]);

            ActivityLogger::record('incentives.period_calculated', $period, newValues: [
                'calculation_run_id' => $runId,
                'employee_count' => $employees->count(),
                'starts_on' => $period->starts_on->toDateString(),
                'ends_on' => $period->ends_on->toDateString(),
            ]);

            return $period->fresh(['summaries', 'transactions']);
        });
    }

    public function calculateEmployee(
        User $employee,
        IncentivePeriod $period,
        ?string $runId = null,
    ): ?EmployeeIncentiveSummary {
        $assignment = $this->assignmentFor($employee, $period);
        if (! $assignment) {
            return null;
        }

        $runId ??= (string) Str::uuid();
        $plan = $assignment->plan;
        $activeRules = $plan->rules
            ->where('is_active', true)
            ->sortBy(fn (IncentiveRule $rule) => sprintf('%010d-%010d', $rule->priority, $rule->id))
            ->values();

        $sales = Sale::query()
            ->with(['items.product', 'payments'])
            ->where('cashier_id', $employee->id)
            ->where('status', 'finalized')
            ->whereBetween('finalized_at', [
                $period->starts_on->copy()->startOfDay(),
                $period->ends_on->copy()->endOfDay(),
            ])
            ->get();

        foreach ($sales as $sale) {
            $this->calculateSale($employee, $period, $plan, $activeRules, $sale, $runId);
        }

        $returnAttribution = $plan->settings['return_attribution'] ?? 'seller';
        if ($returnAttribution !== 'none') {
            $returnQuery = SaleReturn::query()
                ->with([
                    'items.saleItem.product',
                    'sale.items.product',
                    'sale.payments',
                ])
                ->where('status', 'completed')
                ->whereBetween('created_at', [
                    $period->starts_on->copy()->startOfDay(),
                    $period->ends_on->copy()->endOfDay(),
                ]);

            if ($returnAttribution === 'return_executor') {
                $returnQuery->where('created_by', $employee->id);
            } else {
                $returnQuery->whereHas(
                    'sale',
                    fn (Builder $query) => $query->where('cashier_id', $employee->id)
                );
            }

            foreach ($returnQuery->get() as $return) {
                $this->calculateReturn($employee, $period, $plan, $activeRules, $return, $runId);
            }
        }

        $this->calculateCancellationAdjustments($employee, $period, $plan, $runId);

        return $this->refreshSummary($employee, $period, $plan, $sales);
    }

    private function calculateSale(
        User $employee,
        IncentivePeriod $period,
        IncentivePlan $plan,
        Collection $rules,
        Sale $sale,
        string $runId,
    ): void {
        $candidates = collect();

        foreach ($rules as $rule) {
            if (! $this->rules->applies($rule, $sale, $sale->finalized_at ?? $sale->created_at)) {
                continue;
            }
            if ($rule->payment_method && ! $this->qualifyingPayments($sale, $period, $rule)->isNotEmpty()) {
                continue;
            }

            $items = $this->rules->eligibleItems($rule, $sale);
            $basis = $this->basisFor($rule, $sale, $items, $period);
            $quantity = $this->sum($items->pluck('quantity')->all());
            $calculation = $this->calculator->calculate($rule, $basis, $quantity);

            $candidates->push(compact('rule', 'items', 'basis', 'calculation'));
        }

        foreach ($this->resolveConflicts($plan, $candidates) as $candidate) {
            /** @var IncentiveRule $rule */
            $rule = $candidate['rule'];
            $version = $rule->versions()->where('version', $rule->version)->first();

            IncentiveTransaction::query()->firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'incentive_rule_id' => $rule->id,
                    'incentive_period_id' => $period->id,
                    'source_type' => $sale->getMorphClass(),
                    'source_id' => $sale->id,
                    'transaction_type' => 'commission',
                ],
                [
                    'incentive_plan_id' => $plan->id,
                    'incentive_rule_version_id' => $version?->id,
                    'calculation_basis' => $rule->calculation_basis,
                    'base_amount' => $candidate['calculation']['eligible_basis'],
                    'rate' => $rule->rate,
                    'fixed_amount' => $rule->fixed_amount,
                    'calculated_amount' => $candidate['calculation']['amount'],
                    'return_impact' => '0.0000',
                    'status' => 'calculated',
                    'calculation_run_id' => $runId,
                    'snapshot' => $this->saleSnapshot(
                        $employee,
                        $plan,
                        $rule,
                        $sale,
                        $candidate['items'],
                        $candidate['basis']
                    ),
                    'calculated_at' => now(),
                ]
            );
        }
    }

    private function calculateReturn(
        User $employee,
        IncentivePeriod $period,
        IncentivePlan $plan,
        Collection $rules,
        SaleReturn $return,
        string $runId,
    ): void {
        $ignoredReasons = collect($plan->settings['ignored_return_reasons'] ?? [])
            ->filter()
            ->map(fn ($reason) => mb_strtolower(trim((string) $reason)));
        if ($return->reason && $ignoredReasons->contains(mb_strtolower(trim($return->reason)))) {
            return;
        }

        foreach ($rules as $rule) {
            if ($rule->return_impact_mode === 'ignore') {
                continue;
            }

            $original = IncentiveTransaction::query()
                ->where('incentive_rule_id', $rule->id)
                ->where('source_type', $return->sale->getMorphClass())
                ->where('source_id', $return->sale_id)
                ->where('transaction_type', 'commission')
                ->latest('id')
                ->first();
            if (! $original) {
                continue;
            }

            [$returnedBasis, $ratio, $eligibleReturnItems] = $this->returnImpactContext(
                $rule,
                $return,
                $original
            );
            if (BigDecimal::of($ratio)->isZero()) {
                continue;
            }

            $impact = $this->returns->calculate($rule, $original, $ratio);
            $status = ($plan->settings['return_requires_manager_approval'] ?? false)
                ? 'pending_review'
                : 'calculated';

            IncentiveTransaction::query()->firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'incentive_rule_id' => $rule->id,
                    'incentive_period_id' => $period->id,
                    'source_type' => $return->getMorphClass(),
                    'source_id' => $return->id,
                    'transaction_type' => 'return_reversal',
                ],
                [
                    'incentive_plan_id' => $plan->id,
                    'incentive_rule_version_id' => $original->incentive_rule_version_id,
                    'reversal_of_id' => $original->id,
                    'calculation_basis' => $original->calculation_basis,
                    'base_amount' => $returnedBasis,
                    'rate' => $original->rate,
                    'fixed_amount' => $original->fixed_amount,
                    'calculated_amount' => $impact,
                    'return_impact' => $impact,
                    'status' => $status,
                    'calculation_run_id' => $runId,
                    'snapshot' => [
                        'return_id' => $return->id,
                        'return_number' => $return->return_number,
                        'sale_id' => $return->sale_id,
                        'sale_number' => $return->sale->invoice_number,
                        'reason' => $return->reason,
                        'return_total' => (string) $return->total,
                        'eligible_return_basis' => $returnedBasis,
                        'eligible_return_ratio' => $ratio,
                        'eligible_return_items' => $eligibleReturnItems,
                        'original_transaction_id' => $original->id,
                        'return_policy' => $rule->return_impact_mode,
                        'return_policy_rate' => $rule->return_impact_rate,
                        'return_attribution' => $plan->settings['return_attribution'] ?? 'seller',
                        'rule_version' => $original->snapshot['rule']['version'] ?? $rule->version,
                    ],
                    'calculated_at' => now(),
                ]
            );
        }
    }

    private function calculateCancellationAdjustments(
        User $employee,
        IncentivePeriod $period,
        IncentivePlan $plan,
        string $runId,
    ): void {
        $cancelledSales = Sale::withTrashed()
            ->where('cashier_id', $employee->id)
            ->where('status', 'cancelled')
            ->whereBetween('cancelled_at', [
                $period->starts_on->copy()->startOfDay(),
                $period->ends_on->copy()->endOfDay(),
            ])
            ->get();

        foreach ($cancelledSales as $sale) {
            $originals = IncentiveTransaction::query()
                ->where('employee_id', $employee->id)
                ->where('source_type', $sale->getMorphClass())
                ->where('source_id', $sale->id)
                ->where('transaction_type', 'commission')
                ->get();

            foreach ($originals as $original) {
                IncentiveTransaction::query()->firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'incentive_rule_id' => $original->incentive_rule_id,
                        'incentive_period_id' => $period->id,
                        'source_type' => $sale->getMorphClass(),
                        'source_id' => $sale->id,
                        'transaction_type' => 'sale_cancellation',
                    ],
                    [
                        'incentive_plan_id' => $plan->id,
                        'incentive_rule_version_id' => $original->incentive_rule_version_id,
                        'reversal_of_id' => $original->id,
                        'calculation_basis' => $original->calculation_basis,
                        'base_amount' => $original->base_amount,
                        'rate' => $original->rate,
                        'fixed_amount' => $original->fixed_amount,
                        'calculated_amount' => BigDecimal::of($original->calculated_amount)
                            ->negated()
                            ->toScale(4, RoundingMode::HALF_UP)
                            ->__toString(),
                        'return_impact' => BigDecimal::of($original->calculated_amount)
                            ->negated()
                            ->toScale(4, RoundingMode::HALF_UP)
                            ->__toString(),
                        'status' => 'calculated',
                        'calculation_run_id' => $runId,
                        'snapshot' => [
                            'sale_id' => $sale->id,
                            'invoice_number' => $sale->invoice_number,
                            'cancelled_at' => $sale->cancelled_at?->toIso8601String(),
                            'original_transaction_id' => $original->id,
                        ],
                        'calculated_at' => now(),
                    ]
                );
            }
        }
    }

    private function refreshSummary(
        User $employee,
        IncentivePeriod $period,
        IncentivePlan $plan,
        Collection $sales,
    ): EmployeeIncentiveSummary {
        $grossSales = $this->sum(
            $sales->flatMap(fn (Sale $sale) => $sale->items)
                ->map(fn ($item) => BigDecimal::of($item->unit_price)
                    ->multipliedBy($item->quantity)
                    ->__toString())
                ->all()
        );
        $returnValues = SaleReturn::query()
            ->where('status', 'completed')
            ->whereHas('sale', fn (Builder $query) => $query->where('cashier_id', $employee->id))
            ->whereBetween('created_at', [
                $period->starts_on->copy()->startOfDay(),
                $period->ends_on->copy()->endOfDay(),
            ])
            ->pluck('total')
            ->all();
        $returns = $this->sum($returnValues);
        $netSales = BigDecimal::of($this->sum($sales->pluck('total')->all()))
            ->minus($returns)
            ->toScale(4, RoundingMode::HALF_UP)
            ->__toString();
        $profit = $this->sum(
            $sales->flatMap(fn (Sale $sale) => $sale->items)
                ->map(fn ($item) => BigDecimal::of($item->total)
                    ->minus(
                        BigDecimal::of($item->unit_cost_snapshot ?? $item->product?->purchase_price ?? '0')
                            ->multipliedBy($item->quantity)
                    )
                    ->__toString())
                ->all()
        );

        $transactions = IncentiveTransaction::query()
            ->where('employee_id', $employee->id)
            ->where('incentive_period_id', $period->id)
            ->where('incentive_plan_id', $plan->id);
        $estimated = $this->sum(
            (clone $transactions)
                ->whereNotIn('status', ['voided', 'rejected'])
                ->pluck('calculated_amount')
                ->all()
        );
        $approved = $this->sum(
            (clone $transactions)
                ->whereIn('status', ['approved', 'paid'])
                ->pluck('calculated_amount')
                ->all()
        );
        $target = $plan->target_amount;
        $achievement = null;
        if ($target !== null && BigDecimal::of($target)->isGreaterThan(0)) {
            $targetValue = match ($plan->target_basis) {
                'gross_sales' => $grossSales,
                'gross_profit' => $profit,
                default => $netSales,
            };
            $achievement = BigDecimal::of($targetValue)
                ->multipliedBy('100')
                ->dividedBy($target, 4, RoundingMode::HALF_UP)
                ->__toString();
        }

        return EmployeeIncentiveSummary::query()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'incentive_plan_id' => $plan->id,
                'incentive_period_id' => $period->id,
            ],
            [
                'gross_sales' => $grossSales,
                'net_sales' => $netSales,
                'returns_total' => $returns,
                'eligible_profit' => $profit,
                'target_amount' => $target,
                'achievement_percent' => $achievement,
                'estimated_commission' => $estimated,
                'approved_commission' => $approved,
                'bonuses' => '0.0000',
                'penalties' => BigDecimal::of($estimated)->isLessThan(0)
                    ? BigDecimal::of($estimated)->abs()->toScale(4, RoundingMode::HALF_UP)->__toString()
                    : '0.0000',
                'status' => $period->status === 'approved' ? 'approved' : 'calculated',
                'snapshot' => [
                    'sale_ids' => $sales->pluck('id')->all(),
                    'transaction_count' => (clone $transactions)->count(),
                    'generated_at' => now()->toIso8601String(),
                ],
            ]
        );
    }

    private function assignmentFor(User $employee, IncentivePeriod $period): ?IncentivePlanAssignment
    {
        $roleNames = $employee->getRoleNames();

        return IncentivePlanAssignment::query()
            ->with(['plan.rules.versions'])
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query
                ->whereNull('starts_on')
                ->orWhere('starts_on', '<=', $period->ends_on))
            ->where(fn (Builder $query) => $query
                ->whereNull('ends_on')
                ->orWhere('ends_on', '>=', $period->starts_on))
            ->whereHas('plan', fn (Builder $query) => $query
                ->where('status', 'active')
                ->where(fn (Builder $dates) => $dates
                    ->whereNull('starts_on')
                    ->orWhere('starts_on', '<=', $period->ends_on))
                ->where(fn (Builder $dates) => $dates
                    ->whereNull('ends_on')
                    ->orWhere('ends_on', '>=', $period->starts_on)))
            ->where(function (Builder $query) use ($employee, $roleNames) {
                $query->where(fn (Builder $employeeScope) => $employeeScope
                    ->where('scope_type', 'employee')
                    ->where('employee_id', $employee->id))
                    ->orWhere('scope_type', 'all_staff');

                if ($roleNames->isNotEmpty()) {
                    $query->orWhere(fn (Builder $roleScope) => $roleScope
                        ->where('scope_type', 'role')
                        ->whereIn('role_name', $roleNames));
                }
            })
            ->orderBy('priority')
            ->orderBy('id')
            ->first();
    }

    private function basisFor(
        IncentiveRule $rule,
        Sale $sale,
        Collection $items,
        IncentivePeriod $period,
    ): string {
        $gross = $this->sum($items->map(
            fn ($item) => BigDecimal::of($item->unit_price)
                ->multipliedBy($item->quantity)
                ->__toString()
        )->all());
        $net = $this->sum($items->pluck('total')->all());
        $profit = $this->sum($items->map(
            fn ($item) => BigDecimal::of($item->total)
                ->minus(
                    BigDecimal::of($item->unit_cost_snapshot ?? $item->product?->purchase_price ?? '0')
                        ->multipliedBy($item->quantity)
                )
                ->__toString()
        )->all());
        $quantity = $this->sum($items->pluck('quantity')->all());
        $eligiblePayments = $this->qualifyingPayments($sale, $period, $rule);
        $collected = $this->sum($eligiblePayments->pluck('amount')->all());
        $itemShare = BigDecimal::of($sale->total)->isGreaterThan(0)
            ? BigDecimal::of($net)->dividedBy($sale->total, 10, RoundingMode::HALF_UP)
            : BigDecimal::zero();

        $basis = match ($rule->calculation_basis) {
            'gross_sales' => $gross,
            'net_sales', 'category_sales', 'product_sales' => $net,
            'gross_profit', 'net_profit' => $profit,
            'invoice_count' => '1.0000',
            'item_quantity' => $quantity,
            'collected_amount' => BigDecimal::of($collected)
                ->multipliedBy($itemShare)
                ->toScale(4, RoundingMode::HALF_UP)
                ->__toString(),
            default => '0.0000',
        };

        if (
            $rule->payment_method
            && $rule->calculation_basis !== 'collected_amount'
            && $rule->calculation_method === 'percentage'
        ) {
            $paymentShare = BigDecimal::of($sale->total)->isGreaterThan(0)
                ? BigDecimal::of($collected)->dividedBy($sale->total, 10, RoundingMode::HALF_UP)->min(BigDecimal::one())
                : BigDecimal::zero();
            $basis = BigDecimal::of($basis)
                ->multipliedBy($paymentShare)
                ->toScale(4, RoundingMode::HALF_UP)
                ->__toString();
        }

        return $basis;
    }

    private function qualifyingPayments(
        Sale $sale,
        IncentivePeriod $period,
        IncentiveRule $rule,
    ): Collection {
        return $sale->payments
            ->filter(fn ($payment) => $payment->paid_at
                && $payment->paid_at->betweenIncluded(
                    $period->starts_on->copy()->startOfDay(),
                    $period->ends_on->copy()->endOfDay()
                ))
            ->when(
                $rule->payment_method,
                fn (Collection $payments) => $payments->where('method', $rule->payment_method)
            )
            ->values();
    }

    private function resolveConflicts(IncentivePlan $plan, Collection $candidates): Collection
    {
        if ($candidates->isEmpty()) {
            return collect();
        }

        return match ($plan->conflict_resolution) {
            'highest_only' => collect([
                $candidates->sort(function (array $left, array $right) {
                    $amountComparison = BigDecimal::of($right['calculation']['amount'])
                        ->compareTo(BigDecimal::of($left['calculation']['amount']));

                    return $amountComparison !== 0
                        ? $amountComparison
                        : [$left['rule']->priority, $left['rule']->id] <=> [$right['rule']->priority, $right['rule']->id];
                })->first(),
            ]),
            'first_match' => $candidates->take(1),
            'exclusive', 'exclusive_rule' => collect([
                $candidates->first(
                    fn (array $candidate) => ! $candidate['rule']->is_stackable
                        || $candidate['rule']->stops_processing
                ) ?? $candidates->first(),
            ]),
            default => $this->resolveStackableCandidates($candidates),
        };
    }

    private function resolveStackableCandidates(Collection $candidates): Collection
    {
        $selected = collect();
        foreach ($candidates as $candidate) {
            if (! $candidate['rule']->is_stackable) {
                return collect([$candidate]);
            }

            $selected->push($candidate);
            if ($candidate['rule']->stops_processing) {
                break;
            }
        }

        return $selected;
    }

    private function returnImpactContext(
        IncentiveRule $rule,
        SaleReturn $return,
        IncentiveTransaction $original,
    ): array {
        $snapshotItems = collect($original->snapshot['items'] ?? [])->keyBy('sale_item_id');
        $eligibleReturnItems = $return->items
            ->filter(fn ($returnItem) => $snapshotItems->has((int) $returnItem->sale_item_id))
            ->values();

        if ($eligibleReturnItems->isEmpty()) {
            return ['0.0000', '0.0000000000', []];
        }

        $originalMonetary = $this->sum($snapshotItems->pluck('line_total')->all());
        $returnedMonetary = $this->sum($eligibleReturnItems->pluck('total')->all());
        $originalQuantity = $this->sum($snapshotItems->pluck('quantity')->all());
        $returnedQuantity = $this->sum($eligibleReturnItems->pluck('quantity')->all());
        $originalProfit = $this->sum($snapshotItems->map(
            fn (array $item) => BigDecimal::of($item['line_total'] ?? '0')
                ->minus(
                    BigDecimal::of($item['unit_cost'] ?? '0')
                        ->multipliedBy($item['quantity'] ?? '0')
                )
                ->__toString()
        )->all());
        $returnedProfit = $this->sum($eligibleReturnItems->map(function ($returnItem) use ($snapshotItems) {
            $snapshot = $snapshotItems->get((int) $returnItem->sale_item_id);

            return BigDecimal::of($returnItem->total)
                ->minus(
                    BigDecimal::of($snapshot['unit_cost'] ?? '0')
                        ->multipliedBy($returnItem->quantity)
                )
                ->__toString();
        })->all());

        [$returnedBasis, $denominator] = match ($rule->calculation_basis) {
            'item_quantity' => [$returnedQuantity, $originalQuantity],
            'gross_profit', 'net_profit' => [$returnedProfit, $originalProfit],
            default => [$returnedMonetary, $originalMonetary],
        };
        $denominatorDecimal = BigDecimal::of($denominator);
        $ratio = $denominatorDecimal->isGreaterThan(0)
            ? BigDecimal::of($returnedBasis)
                ->dividedBy($denominatorDecimal, 10, RoundingMode::HALF_UP)
                ->max(BigDecimal::zero())
                ->min(BigDecimal::one())
                ->__toString()
            : '0.0000000000';

        if (app()->runningUnitTests() && env('INCENTIVE_DEBUG_RETURN')) {
            dump(compact('originalMonetary', 'returnedMonetary', 'originalQuantity', 'returnedQuantity', 'returnedBasis', 'denominator', 'ratio'));
        }

        return [
            $returnedBasis,
            $ratio,
            $eligibleReturnItems->map(fn ($item) => [
                'sale_return_item_id' => $item->id,
                'sale_item_id' => $item->sale_item_id,
                'quantity' => (string) $item->quantity,
                'total' => (string) $item->total,
            ])->all(),
        ];
    }

    private function saleSnapshot(
        User $employee,
        IncentivePlan $plan,
        IncentiveRule $rule,
        Sale $sale,
        Collection $items,
        string $basis,
    ): array {
        return [
            'employee_id' => $employee->id,
            'warehouse_id' => $sale->warehouse_id,
            'sale_id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
            'sale_total' => (string) $sale->total,
            'sale_discount' => (string) $sale->discount,
            'sale_tax' => (string) $sale->tax,
            'basis_amount' => $basis,
            'plan' => [
                'id' => $plan->id,
                'code' => $plan->code,
                'conflict_resolution' => $plan->conflict_resolution,
            ],
            'rule' => [
                'id' => $rule->id,
                'name' => $rule->name,
                'version' => $rule->version,
                'basis' => $rule->calculation_basis,
                'method' => $rule->calculation_method,
                'rate' => $rule->rate,
                'fixed_amount' => $rule->fixed_amount,
                'minimum_basis' => $rule->minimum_basis,
                'maximum_eligible_basis' => $rule->maximum_eligible_basis,
                'minimum_commission' => $rule->minimum_commission,
                'maximum_commission' => $rule->maximum_commission,
                'return_impact_mode' => $rule->return_impact_mode,
                'return_impact_rate' => $rule->return_impact_rate,
            ],
            'items' => $items->map(fn ($item) => [
                'sale_item_id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => (string) $item->quantity,
                'unit_price' => (string) $item->unit_price,
                'unit_cost' => (string) ($item->unit_cost_snapshot ?? $item->product?->purchase_price ?? '0'),
                'cost_source' => $item->cost_source ?? 'legacy_product_fallback',
                'line_total' => (string) $item->total,
            ])->all(),
            'finalized_at' => ($sale->finalized_at ?? $sale->created_at)->toIso8601String(),
        ];
    }

    private function sum(array $values): string
    {
        $total = BigDecimal::zero();
        foreach ($values as $value) {
            $total = $total->plus(BigDecimal::of((string) ($value ?? '0')));
        }

        return $total->toScale(4, RoundingMode::HALF_UP)->__toString();
    }
}
