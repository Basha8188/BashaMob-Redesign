<?php

namespace App\Http\Requests\Admin\Incentives;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncentivePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('incentives.calculate') ?? false;
    }

    public function rules(): array
    {
        return [
            'period_name' => ['required', 'string', 'max:160'],
            'calculation_period_type' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom'])],
            'period_starts_on' => [
                'required',
                'date',
                Rule::unique('incentive_periods', 'starts_on')
                    ->where(fn ($query) => $query->where('ends_on', $this->input('period_ends_on'))),
            ],
            'period_ends_on' => ['required', 'date', 'after_or_equal:period_starts_on'],
        ];
    }
}
