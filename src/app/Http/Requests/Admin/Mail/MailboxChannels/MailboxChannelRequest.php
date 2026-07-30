<?php

namespace App\Http\Requests\Admin\Mail\MailboxChannels;

use App\Enums\Admin\Mail\MailAuthenticationType;
use App\Enums\Admin\Mail\MailboxChannelDirection;
use App\Enums\Admin\Mail\MailboxDriver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MailboxChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider_connection_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    'mail_provider_connections',
                    'id'
                )->whereNull('deleted_at'),
            ],
            'name' => [
                'required',
                'string',
                'max:120',
            ],
            'direction' => [
                'required',
                Rule::enum(MailboxChannelDirection::class),
            ],
            'driver' => [
                'required',
                Rule::enum(MailboxDriver::class),
            ],
            'auth_type' => [
                'required',
                Rule::enum(MailAuthenticationType::class),
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
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $direction = MailboxChannelDirection::tryFrom(
                    (string) $this->input('direction')
                );

                $driver = MailboxDriver::tryFrom(
                    (string) $this->input('driver')
                );

                if ($direction === null || $driver === null) {
                    return;
                }

                if (
                    $direction === MailboxChannelDirection::Incoming
                    && !$driver->supportsIncoming()
                ) {
                    $validator->errors()->add(
                        'driver',
                        "Driver [{$driver->value}] does not support incoming mail."
                    );
                }

                if (
                    $direction === MailboxChannelDirection::Outgoing
                    && !$driver->supportsOutgoing()
                ) {
                    $validator->errors()->add(
                        'driver',
                        "Driver [{$driver->value}] does not support outgoing mail."
                    );
                }

                if (
                    $this->boolean('is_primary')
                    && !$this->boolean('is_enabled')
                ) {
                    $validator->errors()->add(
                        'is_primary',
                        'A primary mailbox channel must be enabled.'
                    );
                }
            },
        ];
    }
}
