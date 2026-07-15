<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-staff') ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($this->route('user'))], 'role' => ['required', Rule::in(['Owner', 'Manager', 'Cashier', 'Technician', 'Inventory Clerk', 'Accountant'])], 'permissions' => ['nullable', 'string', 'max:3000'], 'status' => ['required', 'string', 'max:30']];
    }
}
