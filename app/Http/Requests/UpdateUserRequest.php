<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role_id === UserRole::Admin;
    }

    public function rules(): array
    {
        $editingUserId = $this->route('user')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($editingUserId),
            ],
            'role_id' => ['required', Rule::enum(UserRole::class)],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ];
    }
}
