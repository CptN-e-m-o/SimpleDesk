<?php

namespace App\Http\Controllers\Admin\Mail;

use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Mail\Diagnostics\EmailAttachmentDiagnosticsIndexRequest;
use App\Http\Resources\Admin\Mail\EmailAttachmentDiagnosticResource;
use App\Models\Admin\Mail\EmailAttachment;
use App\Services\Admin\Mail\Diagnostics\MailDiagnosticsThresholds;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmailAttachmentDiagnosticsController extends Controller
{
    public function __invoke(
        EmailAttachmentDiagnosticsIndexRequest $request,
        MailDiagnosticsThresholds $thresholds,
    ): AnonymousResourceCollection {
        $query = EmailAttachment::query()
            ->with(
                'emailMessage:id,mailbox_id,direction,status,sender_address,subject'
            );

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->string(
                    'search'
                )
            );

            $query->where(function (
                Builder $query
            ) use ($search): void {
                $query
                    ->where(
                        'file_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'mime_type',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'checksum_sha256',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        foreach (
            [
                'email_message_id',
                'scan_status',
            ] as $column
        ) {
            if ($request->filled($column)) {
                $query->where(
                    $column,
                    $request->validated(
                        $column
                    )
                );
            }
        }

        if ($request->filled('mailbox_id')) {
            $mailboxId = (int) $request
                ->validated('mailbox_id');

            $query->whereHas(
                'emailMessage',
                fn (
                    Builder $query
                ) => $query->where(
                    'mailbox_id',
                    $mailboxId
                )
            );
        }

        if ($request->has('quarantined')) {
            $request->boolean('quarantined')
                ? $query->whereNotNull(
                    'quarantined_at'
                )
                : $query->whereNull(
                    'quarantined_at'
                );
        }

        if (
            $request->boolean(
                'stale_pending'
            )
        ) {
            $query
                ->where(
                    'scan_status',
                    EmailAttachmentScanStatus::Pending
                        ->value
                )
                ->where(
                    'updated_at',
                    '<=',
                    $thresholds
                        ->attachmentPendingCutoff()
                );
        }

        if (
            $request->filled(
                'created_from'
            )
        ) {
            $query->where(
                'created_at',
                '>=',
                $request
                    ->date('created_from')
                    ?->startOfDay()
            );
        }

        if (
            $request->filled(
                'created_to'
            )
        ) {
            $query->where(
                'created_at',
                '<=',
                $request
                    ->date('created_to')
                    ?->endOfDay()
            );
        }

        return EmailAttachmentDiagnosticResource::collection(
            $query
                ->latest('id')
                ->paginate(
                    perPage: $request->integer(
                        'per_page',
                        25
                    )
                )
                ->withQueryString()
        );
    }
}
