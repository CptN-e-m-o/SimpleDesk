<?php

namespace App\Http\Requests\Admin\Mail\Diagnostics;

use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmailAttachmentDiagnosticsIndexRequest extends FormRequest
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

            'email_message_id' => [
                'nullable',
                'integer',
                'exists:email_messages,id',
            ],

            'mailbox_id' => [
                'nullable',
                'integer',
                'exists:mailboxes,id',
            ],

            'scan_status' => [
                'nullable',
                Rule::enum(
                    EmailAttachmentScanStatus::class
                ),
            ],

            'quarantined' => [
                'nullable',
                'boolean',
            ],

            'stale_pending' => [
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
