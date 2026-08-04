<?php

namespace App\Http\Requests\Admin\Mail\Mailboxes;

use App\Enums\Admin\Mail\MailAuthenticationType;
use App\Enums\Admin\Mail\SmtpEncryption;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveOutgoingMailboxSetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim(
                (string) $this->input('name')
            ),

            'host' => trim(
                (string) $this->input('host')
            ),

            'username' => trim(
                (string) $this->input('username')
            ),

            'local_domain' => $this->filled(
                'local_domain'
            )
                ? trim(
                    (string) $this->input(
                        'local_domain'
                    )
                )
                : null,

            'source_ip' => $this->filled(
                'source_ip'
            )
                ? trim(
                    (string) $this->input(
                        'source_ip'
                    )
                )
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:120',
            ],

            'auth_type' => [
                'required',
                Rule::in([
                    MailAuthenticationType::None->value,
                    MailAuthenticationType::Password->value,
                ]),
            ],

            'host' => [
                'required',
                'string',
                'max:255',
            ],

            'port' => [
                'required',
                'integer',
                'between:1,65535',
            ],

            'encryption' => [
                'required',
                Rule::enum(
                    SmtpEncryption::class
                ),
            ],

            'username' => [
                Rule::requiredIf(
                    fn (): bool => $this->input('auth_type')
                        === MailAuthenticationType::Password->value
                ),
                'nullable',
                'string',
                'max:254',
            ],

            'password' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'timeout' => [
                'required',
                'integer',
                'between:1,300',
            ],

            'verify_peer' => [
                'required',
                'boolean',
            ],

            'local_domain' => [
                'nullable',
                'string',
                'max:255',
            ],

            'source_ip' => [
                'nullable',
                'ip',
            ],

            'max_per_second' => [
                'nullable',
                'numeric',
                'gt:0',
            ],

            'restart_threshold' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'restart_threshold_sleep' => [
                'required',
                'integer',
                'min:0',
            ],

            'ping_threshold' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'is_enabled' => [
                'required',
                'boolean',
            ],

            'is_primary' => [
                'required',
                'boolean',
            ],

            'failover_order' => [
                'required',
                'integer',
                'between:0,32767',
            ],
        ];
    }
}
