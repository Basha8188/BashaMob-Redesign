<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create-sales') ?? false;
    }

    public function rules(): array
    {
        return ['number' => ['nullable', 'string', 'max:80'], 'customer' => ['required', 'string', 'max:120'], 'customer_phone' => ['required', 'string', 'max:24'], 'date' => ['required', 'date'], 'product_sku' => ['required', 'exists:products,sku'], 'quantity' => ['required', 'numeric', 'gt:0'], 'unit_price' => ['required', 'numeric', 'min:0'], 'paid_amount' => ['nullable', 'numeric', 'min:0'], 'status' => ['required', 'string', 'max:30'], 'product_serial_id' => ['nullable', 'integer', 'exists:product_serials,id'], 'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id']];
    }
}
