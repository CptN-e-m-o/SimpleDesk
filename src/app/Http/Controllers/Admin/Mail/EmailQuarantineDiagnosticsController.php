<?php

namespace App\Http\Controllers\Admin\Mail;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Mail\Diagnostics\EmailQuarantineDiagnosticsIndexRequest;
use App\Http\Resources\Admin\Mail\EmailQuarantineDiagnosticResource;
use App\Models\Admin\Mail\EmailMessageQuarantine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmailQuarantineDiagnosticsController extends Controller
{
    public function __invoke(
        EmailQuarantineDiagnosticsIndexRequest $request
    ): AnonymousResourceCollection {
        $query = EmailMessageQuarantine::query()
            ->with([
                'mailbox:id,name,email_address',

                'mailboxChannel:id,name',

                'emailMessage:id,mailbox_id,direction,status,sender_address,subject',

                'releasedBy:id,email,username',
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
                        'reason_code',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'reason_message',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereHas(
                        'emailMessage',
                        function (
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
                                );
                        }
                    );
            });
        }

        foreach (
            [
                'mailbox_id',
                'mailbox_channel_id',
                'stage',
                'reason_code',
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

        if ($request->filled('resolution')) {
            $resolution = $request->validated(
                'resolution'
            );

            match ($resolution) {
                'open' => $query
                    ->whereNull('released_at')
                    ->whereNull('resolved_at'),

                'released_for_retry' => $query
                    ->whereNotNull('released_at')
                    ->whereNull('resolved_at'),

                default => $query
                    ->whereNotNull('resolved_at')
                    ->where(
                        'resolution',
                        $resolution
                    ),
            };
        }

        if (
            $request->filled(
                'quarantined_from'
            )
        ) {
            $query->where(
                'last_quarantined_at',
                '>=',
                $request
                    ->date('quarantined_from')
                    ?->startOfDay()
            );
        }

        if (
            $request->filled(
                'quarantined_to'
            )
        ) {
            $query->where(
                'last_quarantined_at',
                '<=',
                $request
                    ->date('quarantined_to')
                    ?->endOfDay()
            );
        }

        return EmailQuarantineDiagnosticResource::collection(
            $query
                ->latest(
                    'last_quarantined_at'
                )
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
