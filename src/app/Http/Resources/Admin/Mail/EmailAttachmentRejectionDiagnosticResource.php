<?php

namespace App\Http\Resources\Admin\Mail;

use App\Services\Admin\Mail\MailSensitiveDataRedactor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailAttachmentRejectionDiagnosticResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        $redactor = app(
            MailSensitiveDataRedactor::class
        );

        return [
            'id' => $this->id,

            'email_message_id' => $this->email_message_id,

            'message' => $this->whenLoaded(
                'emailMessage',
                fn (): ?array => $this->emailMessage === null
                    ? null
                    : [
                        'id' => $this
                            ->emailMessage
                            ->id,

                        'mailbox_id' => $this
                            ->emailMessage
                            ->mailbox_id,

                        'direction' => $this
                            ->emailMessage
                            ->direction
                            ->value,

                        'status' => $this
                            ->emailMessage
                            ->status
                            ->value,

                        'sender_address' => $this
                            ->emailMessage
                            ->sender_address,

                        'subject' => $this
                            ->emailMessage
                            ->subject,
                    ]
            ),

            'position' => $this->position,

            'file_name' => $this->file_name,

            'mime_type' => $this->mime_type,

            'reported_size' => $this->reported_size,

            'content_id' => $this->content_id,

            'is_inline' => $this->is_inline,

            'reason_code' => $this->reason_code,

            'reason_message' => $redactor->redactString(
                $this->reason_message
            ),

            'created_at' => $this
                ->created_at
                ?->toIso8601String(),
        ];
    }
}
