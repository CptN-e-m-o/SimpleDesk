<?php

namespace App\Http\Requests\Admin\Mail\OAuth;

use App\Enums\Admin\Mail\MailOAuthTenantMode;
use App\Enums\Admin\Mail\MailProvider;
use App\Models\Admin\Mail\MailProviderConnection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MailOAuthIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $provider = (string) $this->input(
            'provider'
        );

        $tenantMode = (string) $this->input(
            'tenant_mode'
        );

        if (
            $provider
            === MailProvider::Google->value
        ) {
            $this->merge([
                'tenant_mode' => null,
                'tenant_id' => null,
            ]);

            return;
        }

        if (
            $provider
            === MailProvider::Microsoft->value
            && $tenantMode
            !== MailOAuthTenantMode::Specific->value
        ) {
            $this->merge([
                'tenant_id' => null,
            ]);
        }
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
                Rule::in([
                    MailProvider::Google->value,
                    MailProvider::Microsoft->value,
                ]),
            ],

            'client_id' => [
                'required',
                'string',
                'max:512',
            ],

            'client_secret' => [
                Rule::requiredIf(
                    $this->requiresClientSecret()
                ),
                'nullable',
                'string',
                'max:4096',
            ],

            'tenant_mode' => [
                Rule::requiredIf(
                    $this->input('provider')
                    === MailProvider::Microsoft->value
                ),
                'nullable',
                Rule::enum(
                    MailOAuthTenantMode::class
                ),
            ],

            'tenant_id' => [
                Rule::requiredIf(
                    $this->input('provider')
                    === MailProvider::Microsoft->value
                    && $this->input('tenant_mode')
                    === MailOAuthTenantMode::Specific->value
                ),
                'nullable',
                'string',
                'max:255',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    private function requiresClientSecret(): bool
    {
        if (
            $this->routeIs(
                'admin.email.oauth-integrations.store'
            )
        ) {
            return true;
        }

        $connection = $this->route(
            'connection'
        );

        if (
            ! $connection
                instanceof MailProviderConnection
        ) {
            return false;
        }

        $provider = (string) $this->input(
            'provider'
        );

        $clientId = trim(
            (string) $this->input(
                'client_id'
            )
        );

        $currentProvider = (string) $connection
            ->getRawOriginal(
                'provider'
            );

        $currentClientId = trim(
            (string) (
                $connection
                    ->publicConfiguration()['client_id']
                ?? ''
            )
        );

        $clientSecret = $connection
            ->secrets()['client_secret']
            ?? null;

        $hasClientSecret =
            is_string($clientSecret)
            && trim($clientSecret) !== '';

        return
            ! $hasClientSecret
            || $provider !== $currentProvider
            || $clientId !== $currentClientId;
    }
}
