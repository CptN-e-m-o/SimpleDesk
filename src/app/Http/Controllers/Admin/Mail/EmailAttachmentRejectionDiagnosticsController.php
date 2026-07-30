<?php

namespace App\Http\Controllers\Admin\Mail;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Mail\Diagnostics\EmailAttachmentRejectionDiagnosticsIndexRequest;
use App\Http\Resources\Admin\Mail\EmailAttachmentRejectionDiagnosticResource;
use App\Models\Admin\Mail\EmailAttachmentRejection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmailAttachmentRejectionDiagnosticsController extends Controller
{
    public function __invoke(
        EmailAttachmentRejectionDiagnosticsIndexRequest $request
    ): AnonymousResourceCollection {
        $query =
            EmailAttachmentRejection::query()
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
                        'reason_code',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'reason_message',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        foreach (
            [
                'email_message_id',
                'reason_code',
                'mime_type',
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

        return EmailAttachmentRejectionDiagnosticResource::collection(
            $query
                ->latest('id')
                ->paginate(
                    perPage:
                    $request->integer(
                        'per_page',
                        25
                    )
                )
                ->withQueryString()
        );
    }
}
