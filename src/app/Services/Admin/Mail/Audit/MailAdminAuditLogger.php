<?php

namespace App\Services\Admin\Mail\Audit;

use App\Enums\Admin\Mail\MailAdminAuditEvent;
use App\Enums\Admin\Mail\MailAdminAuditStatus;
use App\Models\Admin\Mail\MailAdminAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MailAdminAuditLogger
{
    public function __construct(
        private readonly MailAdminAuditTargetResolver $targets,
        private readonly MailAdminAuditContextFactory $contexts,
    ) {
    }

    public function record(
        MailAdminAuditEvent $event,
        MailAdminAuditStatus $status,
        Request $request,
        ?Response $response,
        int $durationMilliseconds,
        ?Throwable $exception = null,
    ): MailAdminAuditLog {
        $target = $this->targets->resolve(
            event: $event,
            request: $request,
            response: $response,
        );

        $requestId = trim(
            (string) $request->header('X-Request-ID')
        );

        if (!Str::isUuid($requestId)) {
            $requestId = (string) Str::uuid();
        }

        $actorId = $request->user()?->getAuthIdentifier();

        return MailAdminAuditLog::query()->create([
            'actor_id' => is_numeric($actorId)
                ? (int) $actorId
                : null,
            'mailbox_id' => $target->mailboxId,
            'event' => $event,
            'status' => $status,
            'subject_type' => $target->subjectType,
            'subject_id' => $target->subjectId,
            'request_id' => $requestId,
            'ip_address' => $this->limit(
                $request->ip(),
                45
            ),
            'user_agent' => $this->limit(
                $request->userAgent(),
                2000
            ),
            'context' => $this->contexts->make(
                event: $event,
                status: $status,
                target: $target,
                request: $request,
                response: $response,
                durationMilliseconds: $durationMilliseconds,
                exception: $exception,
            ),
            'created_at' => now(),
        ]);
    }

    private function limit(
        ?string $value,
        int $length
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : mb_substr($value, 0, $length);
    }
}
