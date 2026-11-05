<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class InventoryAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('adjust-inventory') ?? false;
    }

    public function rules(): array
    {
        return ['warehouse' => ['required', 'string', 'max:120'], 'product' => ['required', 'string', 'max:190'], 'stock' => ['required', 'numeric', 'min:0', 'decimal:0,3'], 'minimum' => ['required', 'numeric', 'min:0'], 'unit_cost' => ['nullable', 'numeric', 'min:0'], 'status' => ['nullable', 'string', 'max:30']];
    }
}
