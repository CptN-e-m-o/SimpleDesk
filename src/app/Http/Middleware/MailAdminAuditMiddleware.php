<?php

namespace App\Http\Middleware;

use App\Enums\Admin\Mail\MailAdminAuditEvent;
use App\Services\Admin\Mail\Audit\MailAdminAuditLogger;
use App\Services\Admin\Mail\Audit\MailAdminAuditOutcomeResolver;
use Closure;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MailAdminAuditMiddleware
{
    public function __construct(
        private readonly MailAdminAuditLogger $logger,
        private readonly MailAdminAuditOutcomeResolver $outcomes,
    ) {
    }

    public function handle(
        Request $request,
        Closure $next,
        string $event
    ): Response {
        $auditEvent = MailAdminAuditEvent::tryFrom($event);

        if ($auditEvent === null) {
            throw new InvalidArgumentException(
                "Unknown mail audit event [{$event}]."
            );
        }

        $startedAt = hrtime(true);

        try {
            $response = $next($request);

            $this->recordSafely(
                event: $auditEvent,
                request: $request,
                response: $response,
                durationMilliseconds: $this->durationMilliseconds($startedAt),
            );

            return $response;
        } catch (Throwable $exception) {
            $this->recordSafely(
                event: $auditEvent,
                request: $request,
                response: null,
                durationMilliseconds: $this->durationMilliseconds($startedAt),
                exception: $exception,
            );

            throw $exception;
        }
    }

    private function recordSafely(
        MailAdminAuditEvent $event,
        Request $request,
        ?Response $response,
        int $durationMilliseconds,
        ?Throwable $exception = null,
    ): void {
        try {
            $status = $exception === null
                ? $this->outcomes->fromResponse($response)
                : $this->outcomes->fromException($exception);

            $this->logger->record(
                event: $event,
                status: $status,
                request: $request,
                response: $response,
                durationMilliseconds: $durationMilliseconds,
                exception: $exception,
            );
        } catch (Throwable $auditException) {
            report($auditException);
        }
    }

    private function durationMilliseconds(
        int $startedAt
    ): int {
        return (int) round(
            (hrtime(true) - $startedAt) / 1_000_000
        );
    }
}
