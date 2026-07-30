<?php

namespace App\Http\Requests\Admin\Mail\ProviderConnections;

use App\Enums\Admin\Mail\MailAuthenticationType;
use App\Enums\Admin\Mail\MailProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MailProviderConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:120',
            ],
            'provider' => [
                'required',
                Rule::enum(MailProvider::class),
            ],
            'auth_type' => [
                'required',
                Rule::enum(MailAuthenticationType::class),
            ],
            'account_identifier' => [
                'nullable',
                'string',
                'max:254',
            ],
            'tenant_identifier' => [
                'nullable',
                'string',
                'max:255',
            ],
            'configuration' => [
                'nullable',
                'array',
            ],
            'configuration.*' => [
                'nullable',
            ],
            'secret_configuration' => [
                'nullable',
                'array',
            ],
            'secret_configuration.*' => [
                'nullable',
            ],
            'clear_secret_keys' => [
                'nullable',
                'array',
            ],
            'clear_secret_keys.*' => [
                'required',
                'string',
                'max:100',
                'distinct',
            ],
            'scopes' => [
                'nullable',
                'array',
            ],
            'scopes.*' => [
                'required',
                'string',
                'max:255',
                'distinct',
            ],
            'token_expires_at' => [
                'nullable',
                'date',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }
}
