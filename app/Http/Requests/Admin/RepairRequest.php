<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RepairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->isMethod('post') ? 'create-repairs' : 'update-repair-status') ?? false;
    }

    public function rules(): array
    {
        return ['number' => ['nullable', 'string', 'max:80'], 'customer' => ['required', 'string', 'max:120'], 'customer_phone' => [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'max:24'], 'device' => ['required', 'string', 'max:190'], 'fault' => ['required', 'string', 'max:3000'], 'status' => ['required', 'string', 'max:60']];
    }
}
