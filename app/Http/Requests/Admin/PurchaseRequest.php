<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create-purchases') ?? false;
    }

    public function rules(): array
    {
        return ['number' => ['nullable', 'string', 'max:80'], 'supplier' => ['required', 'string', 'max:120'], 'supplier_phone' => ['nullable', 'string', 'max:24'], 'date' => ['required', 'date'], 'product_sku' => ['required', 'exists:products,sku'], 'quantity' => ['required', 'numeric', 'gt:0'], 'unit_cost' => ['required', 'numeric', 'min:0'], 'paid_amount' => ['nullable', 'numeric', 'min:0'], 'status' => ['required', 'string', 'max:30']];
    }
}
