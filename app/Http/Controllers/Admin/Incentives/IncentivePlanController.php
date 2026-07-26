<?php

namespace App\Http\Controllers\Admin\Incentives;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Incentives\StoreIncentivePlanRequest;
use App\Models\IncentivePlan;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class IncentivePlanController extends Controller
{
    public function store(StoreIncentivePlanRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request): void {
            $ignoredReasons = collect(preg_split('/[\r\n,]+/u', (string) ($data['ignored_return_reasons'] ?? '')))
                ->map(fn ($reason) => trim($reason))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $plan = IncentivePlan::query()->create([
                'name' => $data['name'],
                'code' => strtoupper($data['code']),
                'description' => $data['description'] ?? null,
                'status' => $data['status'],
                'period_type' => $data['period_type'],
                'conflict_resolution' => $data['conflict_resolution'],
                'target_basis' => $data['target_basis'] ?? null,
                'target_amount' => $data['target_amount'] ?? null,
                'starts_on' => $data['starts_on'] ?? null,
                'ends_on' => $data['ends_on'] ?? null,
                'settings' => ['ignored_return_reasons' => $ignoredReasons],
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $assignment = $plan->assignments()->create([
                'scope_type' => $data['assignment_scope'],
                'employee_id' => $data['assignment_scope'] === 'employee' ? $data['employee_id'] : null,
                'role_name' => $data['assignment_scope'] === 'role' ? $data['role_name'] : null,
                'priority' => $data['assignment_priority'],
                'starts_on' => $data['starts_on'] ?? null,
                'ends_on' => $data['ends_on'] ?? null,
                'is_active' => true,
            ]);

            $rule = $plan->rules()->create([
                'name' => $data['rule_name'],
                'description' => $data['rule_description'] ?? null,
                'rule_type' => 'sales_commission',
                'calculation_basis' => $data['calculation_basis'],
                'calculation_method' => $data['calculation_method'],
                'rate' => $data['rate'] ?? null,
                'fixed_amount' => $data['fixed_amount'] ?? null,
                'minimum_basis' => $data['minimum_basis'] ?? null,
                'maximum_eligible_basis' => $data['maximum_eligible_basis'] ?? null,
                'minimum_commission' => $data['minimum_commission'] ?? null,
                'maximum_commission' => $data['maximum_commission'] ?? null,
                'return_impact_mode' => $data['return_impact_mode'],
                'return_impact_rate' => $data['return_impact_rate'] ?? null,
                'priority' => $data['rule_priority'],
                'is_stackable' => (bool) $data['is_stackable'],
                'stops_processing' => (bool) $data['stops_processing'],
                'is_active' => true,
                'starts_on' => $data['starts_on'] ?? null,
                'ends_on' => $data['ends_on'] ?? null,
                'version' => 1,
                'conditions' => [],
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $rule->versions()->create([
                'version' => 1,
                'snapshot' => $rule->only([
                    'name',
                    'description',
                    'rule_type',
                    'calculation_basis',
                    'calculation_method',
                    'rate',
                    'fixed_amount',
                    'minimum_basis',
                    'maximum_eligible_basis',
                    'minimum_commission',
                    'maximum_commission',
                    'return_impact_mode',
                    'return_impact_rate',
                    'priority',
                    'is_stackable',
                    'stops_processing',
                    'starts_on',
                    'ends_on',
                    'conditions',
                ]),
                'effective_at' => now(),
                'created_by' => $request->user()->id,
            ]);

            ActivityLogger::record('incentives.plan_created', $plan, newValues: [
                'assignment_id' => $assignment->id,
                'assignment_scope' => $assignment->scope_type,
                'rule_id' => $rule->id,
                'rule_basis' => $rule->calculation_basis,
                'rule_method' => $rule->calculation_method,
            ], request: $request);
            ActivityLogger::record('incentives.rule_created', $rule, newValues: [
                'plan_id' => $plan->id,
                'version' => $rule->version,
                'calculation_basis' => $rule->calculation_basis,
                'calculation_method' => $rule->calculation_method,
                'return_impact_mode' => $rule->return_impact_mode,
            ], request: $request);
        });

        return redirect()
            ->route('admin.incentives.index')
            ->with('success', 'تم إنشاء خطة الحوافز وربطها بالموظفين وإضافة أول قاعدة حساب بنجاح.');
    }
}
