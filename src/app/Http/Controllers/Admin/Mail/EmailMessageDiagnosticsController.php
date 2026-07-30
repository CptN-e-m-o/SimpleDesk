<?php

namespace App\Http\Controllers\Admin\Mail;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Mail\Diagnostics\EmailMessageDiagnosticsIndexRequest;
use App\Http\Resources\Admin\Mail\EmailMessageDiagnosticResource;
use App\Models\Admin\Mail\EmailMessage;
use App\Services\Admin\Mail\Diagnostics\MailDiagnosticsThresholds;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmailMessageDiagnosticsController extends Controller
{
    public function __invoke(
        EmailMessageDiagnosticsIndexRequest $request,
        MailDiagnosticsThresholds $thresholds,
    ): AnonymousResourceCollection {
        $query = EmailMessage::query()
            ->with([
                'mailbox:id,name,email_address',

                'mailboxChannel:id,name,direction,driver',
            ])
            ->withCount([
                'attachments',

                'attachmentRejections',

                'attempts',
            ]);

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
                        'subject',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'sender_address',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'internet_message_id',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'external_message_id',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'idempotency_key',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'failure_code',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        foreach (
            [
                'mailbox_id',
                'mailbox_channel_id',
                'ticket_id',
                'direction',
                'status',
                'driver',
                'failure_code',
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

        if ($request->boolean('stuck')) {
            $thresholds
                ->applyStuckMessageConstraint(
                    $query
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

        return EmailMessageDiagnosticResource::collection(
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
