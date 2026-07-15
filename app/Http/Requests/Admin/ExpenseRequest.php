<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-expenses') ?? false;
    }

    public function rules(): array
    {
        return ['number' => ['nullable', 'string', 'max:80'], 'description' => ['required', 'string', 'max:2000'], 'date' => ['required', 'date'], 'amount' => ['required', 'numeric', 'gt:0'], 'type' => ['required', 'string', 'max:80']];
    }
}
