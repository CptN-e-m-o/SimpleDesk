<?php

namespace App\Http\Requests\Admin\Mail\Diagnostics;

use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Enums\Admin\Mail\MailboxDriver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmailMessageDiagnosticsIndexRequest extends FormRequest
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

            'ticket_id' => [
                'nullable',
                'integer',
                'exists:tickets,id',
            ],

            'direction' => [
                'nullable',
                Rule::enum(
                    EmailMessageDirection::class
                ),
            ],

            'status' => [
                'nullable',
                Rule::enum(
                    EmailMessageStatus::class
                ),
            ],

            'driver' => [
                'nullable',
                Rule::enum(
                    MailboxDriver::class
                ),
            ],

            'failure_code' => [
                'nullable',
                'string',
                'max:100',
            ],

            'stuck' => [
                'nullable',
                'boolean',
            ],

            'created_from' => [
                'nullable',
                'date',
            ],

            'created_to' => [
                'nullable',
                'date',
                'after_or_equal:created_from',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'between:1,100',
            ],
        ];
    }
}
