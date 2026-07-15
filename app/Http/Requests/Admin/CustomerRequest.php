<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-customers') ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:120'], 'phone' => ['required', 'string', 'max:24', Rule::unique('customers', 'phone')->ignore($this->route('customer'))], 'email' => ['nullable', 'email', 'max:190'], 'history' => ['nullable', 'string', 'max:2000'], 'status' => ['required', 'string', 'max:30']];
    }
}
