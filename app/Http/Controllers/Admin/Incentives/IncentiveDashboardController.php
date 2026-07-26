<?php

namespace App\Http\Controllers\Admin\Incentives;

use App\Http\Controllers\Controller;
use App\Models\EmployeeIncentiveSummary;
use App\Models\IncentivePeriod;
use App\Models\IncentivePlan;
use App\Models\IncentiveRule;
use App\Models\User;
use Illuminate\View\View;

class IncentiveDashboardController extends Controller
{
    public function __invoke(): View
    {
        $plans = IncentivePlan::query()
            ->withCount(['assignments', 'rules'])
            ->latest()
            ->paginate(10, ['*'], 'plans_page');

        $periods = IncentivePeriod::query()
            ->withCount(['summaries', 'transactions'])
            ->latest('starts_on')
            ->paginate(10, ['*'], 'periods_page');

        $summaryQuery = EmployeeIncentiveSummary::query();

        return view('admin.incentives.index', [
            'plans' => $plans,
            'periods' => $periods,
            'employees' => User::query()
                ->with('roles')
                ->where('is_staff', true)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'planCounts' => [
                'all' => IncentivePlan::query()->count(),
                'active' => IncentivePlan::query()->where('status', 'active')->count(),
                'rules' => IncentiveRule::query()->count(),
                'periods' => IncentivePeriod::query()->count(),
            ],
            'commissionTotals' => [
                'estimated' => (string) (clone $summaryQuery)->sum('estimated_commission'),
                'approved' => (string) (clone $summaryQuery)->sum('approved_commission'),
            ],
        ]);
    }
}
