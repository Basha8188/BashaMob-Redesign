<?php

namespace App\Http\Controllers\Admin\Incentives;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Incentives\StoreIncentivePeriodRequest;
use App\Models\IncentivePeriod;
use App\Services\ActivityLogger;
use App\Services\Incentives\IncentiveEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncentivePeriodController extends Controller
{
    public function store(StoreIncentivePeriodRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request): void {
            $period = IncentivePeriod::query()->create([
                'name' => $data['period_name'],
                'period_type' => $data['calculation_period_type'],
                'starts_on' => $data['period_starts_on'],
                'ends_on' => $data['period_ends_on'],
                'status' => 'draft',
                'created_by' => $request->user()->id,
            ]);

            ActivityLogger::record('incentives.period_created', $period, newValues: [
                'period_type' => $period->period_type,
                'starts_on' => $period->starts_on->toDateString(),
                'ends_on' => $period->ends_on->toDateString(),
            ], request: $request);
        });

        return redirect()
            ->route('admin.incentives.index')
            ->with('success', 'تم إنشاء فترة الاحتساب. يمكنك الآن تشغيل الحساب عليها.');
    }

    public function calculate(
        Request $request,
        IncentivePeriod $period,
        IncentiveEngine $engine,
    ): RedirectResponse {
        abort_unless($request->user()?->can('incentives.calculate'), 403);
        $engine->calculatePeriod($period);

        return redirect()
            ->route('admin.incentives.index')
            ->with('success', 'اكتمل حساب الفترة وتم تحديث ملخصات الموظفين.');
    }
}
