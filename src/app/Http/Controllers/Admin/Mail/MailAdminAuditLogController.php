<?php

namespace App\Http\Controllers\Admin\Mail;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Mail\Audit\MailAdminAuditLogIndexRequest;
use App\Http\Resources\Admin\Mail\MailAdminAuditLogResource;
use App\Models\Admin\Mail\MailAdminAuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MailAdminAuditLogController extends Controller
{
    public function __invoke(
        MailAdminAuditLogIndexRequest $request
    ): AnonymousResourceCollection {
        $query = MailAdminAuditLog::query()
            ->with([
                'actor:id,email,username,first_name,last_name',
                'mailbox:id,name,email_address',
            ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));

            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('request_id', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('user_agent', 'like', "%{$search}%")
                    ->orWhereHas(
                        'actor',
                        function (Builder $query) use ($search): void {
                            $query
                                ->where('email', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        }
                    )
                    ->orWhereHas(
                        'mailbox',
                        function (Builder $query) use ($search): void {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere(
                                    'email_address',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
            });
        }

        foreach (
            [
                'actor_id',
                'mailbox_id',
                'event',
                'status',
                'subject_type',
                'subject_id',
                'request_id',
            ] as $column
        ) {
            if ($request->filled($column)) {
                $query->where(
                    $column,
                    $request->validated($column)
                );
            }
        }

        if ($request->filled('created_from')) {
            $query->where(
                'created_at',
                '>=',
                $request->date('created_from')?->startOfDay()
            );
        }

        if ($request->filled('created_to')) {
            $query->where(
                'created_at',
                '<=',
                $request->date('created_to')?->endOfDay()
            );
        }

        return MailAdminAuditLogResource::collection(
            $query
                ->latest('id')
                ->paginate(
                    perPage: $request->integer('per_page', 25)
                )
                ->withQueryString()
        );
    }
}
