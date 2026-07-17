<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->isMethod('post') ? 'create-products' : 'edit-products') ?? false;
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:80', Rule::unique('products', 'sku')->ignore($this->route('product'))],
            'barcode' => ['nullable', 'digits:13', Rule::unique('products', 'barcode')->ignore($this->route('product'))],
            'name' => ['required', 'string', 'max:190'],
            'brand' => ['nullable', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => [$this->isMethod('post') ? 'required' : 'sometimes', 'numeric', 'min:0'],
            'image' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(4 * 1024)],
        ];
    }
}
