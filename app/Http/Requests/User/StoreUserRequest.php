<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role_id === UserRole::Admin;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role_id' => ['required', Rule::enum(UserRole::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('validation_name_required'),
            'email.required' => __('validation_email_required'),
            'email.unique' => __('validation_email_unique'),
            'password.required' => __('validation_password_required'),
            'password.confirmed' => __('validation_password_confirmed'),
            'role_id.required' => __('validation_role_required'),
        ];
    }
}
