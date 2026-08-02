<?php

namespace App\Http\Requests\Admin\Mail\Diagnostics;

use App\Enums\Admin\Mail\EmailQuarantineResolution;
use App\Enums\Admin\Mail\EmailQuarantineStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmailQuarantineDiagnosticsIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'mailbox_id' => [
                'nullable',
                'integer',
                'exists:mailboxes,id',
            ],

            'mailbox_channel_id' => [
                'nullable',
                'integer',
                'exists:mailbox_channels,id',
            ],

            'stage' => [
                'nullable',
                Rule::enum(
                    EmailQuarantineStage::class
                ),
            ],

            'resolution' => [
                'nullable',

                Rule::in(
                    array_merge(
                        [
                            'open',
                            'released_for_retry',
                        ],

                        array_map(
                            static fn (
                                EmailQuarantineResolution $resolution
                            ): string => $resolution->value,
                            EmailQuarantineResolution::cases()
                        )
                    )
                ),
            ],

            'reason_code' => [
                'nullable',
                'string',
                'max:100',
            ],

            'quarantined_from' => [
                'nullable',
                'date',
            ],

            'quarantined_to' => [
                'nullable',
                'date',
                'after_or_equal:quarantined_from',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'between:1,100',
            ],
        ];
    }
}
