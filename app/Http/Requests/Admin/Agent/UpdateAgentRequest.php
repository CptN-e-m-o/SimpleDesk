<?php

namespace App\Http\Requests\Admin\Agent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgentRequest extends FormRequest
{
    public function rules(): array
    {
        $agentId = $this->route('agent')->id;

        return [
            'role_id' => ['required', 'integer'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($agentId)],
            'login' => ['nullable', 'string', Rule::unique('users', 'login')->ignore($agentId)],
            'last_name' => ['nullable', 'string'],
            'first_name' => ['required', 'string'],
            'patronymic' => ['nullable', 'string'],
            'phone_number' => ['nullable', 'string'],
            'mobile_phone' => ['nullable', 'string'],
            'internal_number' => ['nullable', 'string'],
            'timezone' => ['required', 'string'],
            'signature' => ['nullable', 'string'],
        ];
    }

    public function validatedData(): array
    {
        return $this->validated();
    }
}
