<?php

namespace App\Http\Controllers\Admin\Incentives;

use App\Http\Controllers\Controller;
use App\Models\EmployeeIncentiveSummary;
use App\Models\IncentivePeriod;
use App\Models\IncentiveTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeIncentiveController extends Controller
{
    public function mine(Request $request): View
    {
        abort_unless($request->user()?->canAny([
            'incentives.view_own',
            'incentives.view_all_employees',
        ]), 403);

        return $this->render($request, $request->user(), true);
    }

    public function show(Request $request, User $employee): View
    {
        abort_unless($request->user()?->can('incentives.view_all_employees'), 403);
        abort_unless($employee->is_staff, 404);

        return $this->render($request, $employee, $employee->is($request->user()));
    }

    private function render(Request $request, User $employee, bool $isOwn): View
    {
        $periods = IncentivePeriod::query()
            ->whereHas('summaries', fn (Builder $query) => $query->where('employee_id', $employee->id))
            ->latest('starts_on')
            ->get();

        $requestedPeriodId = $request->integer('period');
        $selectedPeriod = $requestedPeriodId
            ? $periods->firstWhere('id', $requestedPeriodId)
            : $periods->first();

        if ($requestedPeriodId && ! $selectedPeriod) {
            abort(404);
        }

        $summaryQuery = EmployeeIncentiveSummary::query()
            ->with(['plan', 'period'])
            ->where('employee_id', $employee->id)
            ->when($selectedPeriod, fn (Builder $query) => $query->where('incentive_period_id', $selectedPeriod->id));

        $summaries = $summaryQuery->get();
        $transactions = IncentiveTransaction::query()
            ->with(['plan', 'rule', 'period'])
            ->where('employee_id', $employee->id)
            ->when($selectedPeriod, fn (Builder $query) => $query->where('incentive_period_id', $selectedPeriod->id))
            ->latest('calculated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.incentives.employee', [
            'employee' => $employee,
            'isOwn' => $isOwn,
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriod,
            'summaries' => $summaries,
            'transactions' => $transactions,
            'metrics' => [
                'gross_sales' => $summaries->sum(fn ($summary) => (float) $summary->gross_sales),
                'net_sales' => $summaries->sum(fn ($summary) => (float) $summary->net_sales),
                'returns_total' => $summaries->sum(fn ($summary) => (float) $summary->returns_total),
                'eligible_profit' => $summaries->sum(fn ($summary) => (float) $summary->eligible_profit),
                'target_amount' => $summaries->sum(fn ($summary) => (float) ($summary->target_amount ?? 0)),
                'estimated_commission' => $summaries->sum(fn ($summary) => (float) $summary->estimated_commission),
                'approved_commission' => $summaries->sum(fn ($summary) => (float) $summary->approved_commission),
                'bonuses' => $summaries->sum(fn ($summary) => (float) $summary->bonuses),
                'penalties' => $summaries->sum(fn ($summary) => (float) $summary->penalties),
            ],
        ]);
    }
}
