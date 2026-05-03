<?php

namespace App\Http\Requests\Admin\Roles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'label' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'label')->ignore($role),
            ],
            'name' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($role),
            ],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['user', 'agent'])],
            'is_default' => ['boolean'],
            'permission_ids' => ['array'],
            'permission_ids.*' => [
                'integer',
                Rule::exists('permissions', 'id')->where('type', $role->type),
            ],
        ];
    }
}
