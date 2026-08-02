<?php

namespace App\Http\Resources\Admin\Mail;

use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Services\Admin\Mail\Diagnostics\MailDiagnosticsThresholds;
use App\Services\Admin\Mail\MailSensitiveDataRedactor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailAttachmentDiagnosticResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        $redactor = app(
            MailSensitiveDataRedactor::class
        );

        $thresholds = app(
            MailDiagnosticsThresholds::class
        );

        $scanResult = array_intersect_key(
            $this->scan_result ?? [],
            array_flip([
                'driver',
                'signature',
                'error_code',
                'message',
                'scanned_bytes',
            ])
        );

        return [
            'id' => $this->id,

            'email_message_id' => $this->email_message_id,

            'message' => $this->whenLoaded(
                'emailMessage',
                fn (): ?array => $this->emailMessage === null
                    ? null
                    : [
                        'id' => $this->emailMessage->id,

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

            'size' => $this->size,

            'checksum_sha256' => $this->checksum_sha256,

            'content_id' => $this->content_id,

            'is_inline' => $this->is_inline,

            'scan_status' => $this->scan_status->value,

            'is_stale_pending' => $this->scan_status
                === EmailAttachmentScanStatus::Pending
                && $this
                    ->updated_at
                    ?->lessThanOrEqualTo(
                        $thresholds
                            ->attachmentPendingCutoff()
                    ),

            'scanned_at' => $this
                ->scanned_at
                ?->toIso8601String(),

            'quarantined_at' => $this
                ->quarantined_at
                ?->toIso8601String(),

            'scan_result' => $redactor->sanitizeArray(
                $scanResult
            ),

            'created_at' => $this
                ->created_at
                ?->toIso8601String(),

            'updated_at' => $this
                ->updated_at
                ?->toIso8601String(),
        ];
    }
}
