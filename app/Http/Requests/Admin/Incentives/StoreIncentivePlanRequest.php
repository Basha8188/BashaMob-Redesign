<?php

namespace App\Http\Requests\Admin\Incentives;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncentivePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('incentives.manage_plans') ?? false)
            && ($this->user()?->can('incentives.manage_rules') ?? false);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'code' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('incentive_plans', 'code')],
            'description' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', Rule::in(['draft', 'active', 'inactive'])],
            'period_type' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom'])],
            'conflict_resolution' => ['required', Rule::in(['sum_all', 'highest_only', 'first_match', 'exclusive_rule'])],
            'target_basis' => ['nullable', Rule::in(['gross_sales', 'net_sales', 'gross_profit', 'invoice_count'])],
            'target_amount' => ['nullable', 'decimal:0,4', 'min:0', 'max:99999999999999'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'ignored_return_reasons' => ['nullable', 'string', 'max:3000'],

            'assignment_scope' => ['required', Rule::in(['employee', 'role', 'all_staff'])],
            'employee_id' => [
                'nullable',
                'required_if:assignment_scope,employee',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_staff', true)->where('is_active', true)),
            ],
            'role_name' => [
                'nullable',
                'required_if:assignment_scope,role',
                Rule::exists('roles', 'name')->where(fn ($query) => $query->where('guard_name', 'web')),
            ],
            'assignment_priority' => ['required', 'integer', 'min:1', 'max:10000'],

            'rule_name' => ['required', 'string', 'max:160'],
            'rule_description' => ['nullable', 'string', 'max:3000'],
            'calculation_basis' => ['required', Rule::in([
                'gross_sales',
                'net_sales',
                'gross_profit',
                'net_profit',
                'invoice_count',
                'item_quantity',
                'collected_amount',
                'category_sales',
                'product_sales',
            ])],
            'calculation_method' => ['required', Rule::in([
                'percentage',
                'fixed_per_invoice',
                'fixed_per_item',
                'fixed_per_unit',
            ])],
            'rate' => ['nullable', 'required_if:calculation_method,percentage', 'decimal:0,6', 'min:0', 'max:100'],
            'fixed_amount' => [
                'nullable',
                'required_unless:calculation_method,percentage',
                'decimal:0,4',
                'min:0',
                'max:99999999999999',
            ],
            'minimum_basis' => ['nullable', 'decimal:0,4', 'min:0'],
            'maximum_eligible_basis' => ['nullable', 'decimal:0,4', 'gte:minimum_basis'],
            'minimum_commission' => ['nullable', 'decimal:0,4', 'min:0'],
            'maximum_commission' => ['nullable', 'decimal:0,4', 'gte:minimum_commission'],
            'return_impact_mode' => ['required', Rule::in(['reverse_full', 'reverse_percentage', 'ignore'])],
            'return_impact_rate' => ['nullable', 'required_if:return_impact_mode,reverse_percentage', 'decimal:0,6', 'min:0', 'max:100'],
            'rule_priority' => ['required', 'integer', 'min:1', 'max:10000'],
            'is_stackable' => ['required', 'boolean'],
            'stops_processing' => ['required', 'boolean'],
        ];
    }
}
