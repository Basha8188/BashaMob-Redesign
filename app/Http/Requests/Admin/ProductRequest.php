<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class ProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Backward compatibility for older API clients that predate purchase_cost.
        // An explicitly empty field still fails validation in the current UI.
        if (! $this->exists('purchase_cost') && $this->filled('price')) {
            $this->merge(['purchase_cost' => $this->input('price')]);
        }
    }

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
            'purchase_cost' => ['required', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => [$this->isMethod('post') ? 'required' : 'sometimes', 'integer', 'min:0'],
            'image' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(4 * 1024)],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.required' => 'اكتب كودًا للمنتج.',
            'sku.unique' => 'كود المنتج مستخدم بالفعل. استخدم كودًا مختلفًا.',
            'barcode.digits' => 'الباركود يجب أن يتكون من 13 رقمًا، أو اتركه فارغًا للتوليد التلقائي.',
            'barcode.unique' => 'الباركود مستخدم بالفعل لمنتج آخر.',
            'name.required' => 'اكتب اسم المنتج.',
            'price.required' => 'اكتب سعر البيع.',
            'price.numeric' => 'سعر البيع يجب أن يكون رقمًا صحيحًا.',
            'quantity.required' => 'اكتب الكمية المتاحة.',
            'quantity.numeric' => 'الكمية يجب أن تكون رقمًا صحيحًا.',
            'quantity.integer' => 'الكمية يجب أن تكون رقمًا صحيحًا بدون كسور.',
            'purchase_cost.required' => 'اكتب تكلفة شراء المنتج حتى تظهر قيمة المخزون بصورة صحيحة.',
            'purchase_cost.numeric' => 'تكلفة الشراء يجب أن تكون رقمًا صحيحًا.',
            'image.image' => 'الملف المرفوع يجب أن يكون صورة.',
            'image.mimes' => 'صيغة الصورة يجب أن تكون JPG أو PNG أو WebP.',
            'image.max' => 'حجم الصورة يجب ألا يتجاوز 4 ميجابايت.',
        ];
    }
}
