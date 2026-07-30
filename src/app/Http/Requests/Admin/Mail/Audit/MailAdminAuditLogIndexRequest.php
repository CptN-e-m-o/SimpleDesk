<?php

namespace App\Http\Requests\Admin\Mail\Audit;

use App\Enums\Admin\Mail\MailAdminAuditEvent;
use App\Enums\Admin\Mail\MailAdminAuditStatus;
use App\Enums\Admin\Mail\MailAdminAuditSubjectType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MailAdminAuditLogIndexRequest extends FormRequest
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

            'actor_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'mailbox_id' => [
                'nullable',
                'integer',
                'exists:mailboxes,id',
            ],

            'event' => [
                'nullable',
                Rule::enum(MailAdminAuditEvent::class),
            ],

            'status' => [
                'nullable',
                Rule::enum(MailAdminAuditStatus::class),
            ],

            'subject_type' => [
                'nullable',
                Rule::enum(MailAdminAuditSubjectType::class),
            ],

            'subject_id' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'request_id' => [
                'nullable',
                'uuid',
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
