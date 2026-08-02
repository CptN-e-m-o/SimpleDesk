<?php

namespace App\Http\Requests\Admin\Mail\Mailboxes;

use App\Enums\Admin\Mail\ImapEncryption;
use App\Enums\Admin\Mail\MailAuthenticationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveIncomingMailboxSetupRequest extends FormRequest
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

            'folder' => trim(
                (string) $this->input(
                    'folder',
                    'INBOX'
                )
            ),

            'processed_folder' => $this->filled(
                'processed_folder'
            )
                ? trim(
                    (string) $this->input(
                        'processed_folder'
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
                    ImapEncryption::class
                ),
            ],

            'username' => [
                Rule::requiredIf(
                    fn (): bool =>
                        $this->input('auth_type')
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

            'validate_cert' => [
                'required',
                'boolean',
            ],

            'folder' => [
                'required',
                'string',
                'max:255',
            ],

            'processed_folder' => [
                'nullable',
                'string',
                'max:255',
            ],

            'create_processed_folder' => [
                'required',
                'boolean',
            ],

            'expunge_on_delete' => [
                'required',
                'boolean',
            ],

            'store_raw_message' => [
                'required',
                'boolean',
            ],

            'max_raw_message_mb' => [
                'required',
                'integer',
                'between:1,1024',
            ],

            'max_attachment_mb' => [
                'required',
                'integer',
                'between:1,1024',
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
