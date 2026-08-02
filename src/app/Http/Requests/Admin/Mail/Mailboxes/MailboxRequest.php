<?php

namespace App\Http\Requests\Admin\Mail\Mailboxes;

use App\Models\Admin\Mail\Mailbox;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MailboxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email_address')) {
            $this->merge([
                'email_address' => mb_strtolower(
                    trim((string) $this->input('email_address'))
                ),
            ]);
        }
    }

    public function rules(): array
    {
        $mailbox = $this->route('mailbox');

        return [
            'name' => [
                'required',
                'string',
                'max:120',
            ],
            'email_address' => [
                'required',
                'email:rfc',
                'max:254',
                Rule::unique('mailboxes', 'email_address')
                    ->ignore(
                        $mailbox instanceof Mailbox
                            ? $mailbox->id
                            : null
                    )
                    ->whereNull('deleted_at'),
            ],
            'display_name' => [
                'nullable',
                'string',
                'max:120',
            ],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')
                    ->whereNull('deleted_at'),
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
            'is_default_outgoing' => [
                'required',
                'boolean',
            ],
            'internal_notes' => [
                'nullable',
                'string',
                'max:10000',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (
                    $this->boolean('is_default_outgoing')
                    && ! $this->boolean('is_active')
                ) {
                    $validator->errors()->add(
                        'is_default_outgoing',
                        'The default outgoing mailbox must be active.'
                    );
                }
            },
        ];
    }
}
