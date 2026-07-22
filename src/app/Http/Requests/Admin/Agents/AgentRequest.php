<?php

namespace App\Http\Requests\Admin\Agents;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class AgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function baseRules(?int $agentId = null): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($agentId),
            ],

            'username' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($agentId),
            ],

            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],

            'phone_country_iso2' => ['nullable', 'string', 'size:2'],
            'phone_country_code' => ['nullable', 'string', 'max:8'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'phone_ext' => ['nullable', 'string', 'max:255'],

            'mobile_country_iso2' => ['nullable', 'string', 'size:2'],
            'mobile_country_code' => ['nullable', 'string', 'max:8'],
            'mobile_number' => ['nullable', 'string', 'max:255'],

            'timezone' => ['required', 'string', 'timezone'],
            'signature' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],

            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => [
                'integer',
                Rule::exists('roles', 'id')->where('type', 'agent'),
            ],

            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', 'exists:departments,id'],

            'team_ids' => ['nullable', 'array'],
            'team_ids.*' => ['integer', 'exists:teams,id'],
        ];
    }
}
