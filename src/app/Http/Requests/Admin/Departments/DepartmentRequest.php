<?php

namespace App\Http\Requests\Admin\Departments;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:public,private'],
            'business_hours' => ['nullable', 'string', 'max:255'],
            'outgoing_email' => ['nullable', 'email', 'max:255'],
            'department_status_id' => ['nullable', 'exists:department_statuses,id'],
            'signature' => ['nullable', 'string'],
            'is_default' => ['required', 'boolean'],

            'manager_ids' => ['nullable', 'array'],
            'manager_ids.*' => ['integer', 'exists:users,id'],

            'team_ids' => ['nullable', 'array'],
            'team_ids.*' => ['integer', 'exists:teams,id'],
        ];
    }
}
