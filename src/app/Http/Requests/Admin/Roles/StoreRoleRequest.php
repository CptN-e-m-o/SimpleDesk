<?php

namespace App\Http\Requests\Admin\Roles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255', 'unique:roles,label'],
            'name' => ['nullable', 'string', 'max:255', 'unique:roles,name'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['user', 'agent'])],
            'is_default' => ['boolean'],
            'permission_ids' => ['array'],
            'permission_ids.*' => [
                'integer',
                Rule::exists('permissions', 'id')->where('type', $this->input('type')),
            ],
        ];
    }
}
