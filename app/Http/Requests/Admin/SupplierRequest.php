<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-suppliers') ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:120'], 'phone' => ['nullable', 'string', 'max:24', Rule::unique('suppliers', 'phone')->ignore($this->route('supplier'))], 'balance' => ['required', 'numeric'], 'status' => ['required', 'string', 'max:30']];
    }
}
