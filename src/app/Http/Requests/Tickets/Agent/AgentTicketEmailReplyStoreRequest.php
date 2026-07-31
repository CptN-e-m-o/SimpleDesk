<?php

namespace App\Http\Requests\Tickets\Agent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class AgentTicketEmailReplyStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(
            'agent.tickets.reply'
        ) ?? false;
    }

    public function rules(): array
    {
        $attachmentRules = [
            'bail',
            'file',
            'max:'.$this->maxAttachmentKilobytes(),
        ];

        $allowedMimeTypes = $this->allowedMimeTypes();

        if ($allowedMimeTypes !== []) {
            $attachmentRules[] =
                'mimetypes:'.implode(',', $allowedMimeTypes);
        }

        return [
            'message' => [
                'required',
                'string',
                'min:2',
                'max:100000',
            ],
            'attachments' => [
                'nullable',
                'array',
                'max:'.$this->maxAttachmentCount(),
            ],
            'attachments.*' => $attachmentRules,
        ];
    }

    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(
            function (Validator $validator): void {
                $attachments = $this->file(
                    'attachments',
                    []
                );

                if (! is_array($attachments)) {
                    return;
                }

                $totalSize = 0;

                foreach ($attachments as $attachment) {
                    if (! $attachment instanceof UploadedFile) {
                        continue;
                    }

                    $size = $attachment->getSize();

                    if ($size !== false) {
                        $totalSize += $size;
                    }
                }

                if (
                    $totalSize
                    > $this->maxTotalAttachmentBytes()
                ) {
                    $validator->errors()->add(
                        'attachments',
                        'The total size of attachments is too large.'
                    );
                }
            }
        );
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Please enter a reply.',
            'message.min' => 'Reply must be at least 2 characters.',
            'message.max' => 'Reply is too long.',
            'attachments.array' => 'Attachments must be uploaded as a list.',
            'attachments.max' => 'Too many attachments were uploaded.',
            'attachments.*.file' => 'Every attachment must be a valid file.',
            'attachments.*.max' => 'An attachment exceeds the size limit.',
            'attachments.*.mimetypes' => 'An attachment has a disallowed file type.',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function allowedMimeTypes(): array
    {
        $mimeTypes = config(
            'simpledesk-mail.outgoing.allowed_attachment_mime_types',
            []
        );

        if (! is_array($mimeTypes)) {
            return [];
        }

        return array_values(
            array_filter(
                $mimeTypes,
                static fn (mixed $mimeType): bool => is_string($mimeType)
                    && trim($mimeType) !== ''
            )
        );
    }

    private function maxAttachmentCount(): int
    {
        return max(
            0,
            (int) config(
                'simpledesk-mail.outgoing.max_attachment_count',
                10
            )
        );
    }

    private function maxAttachmentKilobytes(): int
    {
        return max(
            1,
            (int) ceil(
                $this->maxAttachmentBytes() / 1024
            )
        );
    }

    private function maxAttachmentBytes(): int
    {
        return max(
            1,
            (int) config(
                'simpledesk-mail.outgoing.max_attachment_bytes',
                25 * 1024 * 1024
            )
        );
    }

    private function maxTotalAttachmentBytes(): int
    {
        return max(
            $this->maxAttachmentBytes(),
            (int) config(
                'simpledesk-mail.outgoing.max_total_attachment_bytes',
                40 * 1024 * 1024
            )
        );
    }
}
