<?php

namespace App\Http\Requests\Admin\Agent;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'role_id' => ['required', 'integer'],
            'email' => ['required', 'email', 'unique:users,email'],
            'login' => ['nullable', 'string', 'unique:users,login'],
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
