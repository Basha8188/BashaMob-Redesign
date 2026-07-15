<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-settings') ?? false;
    }

    public function rules(): array
    {
        return ['section' => ['required', 'string', 'max:50'], 'name' => ['required', 'string', 'max:120', Rule::unique('settings', 'key')->ignore($this->route('setting'))], 'value' => ['nullable', 'string', 'max:5000'], 'action' => ['nullable', 'string', 'max:80'], 'status' => ['nullable', 'string', 'max:30']];
    }
}
