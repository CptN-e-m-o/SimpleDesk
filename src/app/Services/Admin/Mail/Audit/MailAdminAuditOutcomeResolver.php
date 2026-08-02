<?php

namespace App\Services\Admin\Mail\Audit;

use App\Enums\Admin\Mail\MailAdminAuditStatus;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MailAdminAuditOutcomeResolver
{
    public function __construct(
        private readonly MailAdminAuditResponseReader $responses,
    ) {}

    public function fromResponse(
        Response $response
    ): MailAdminAuditStatus {
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 500) {
            return MailAdminAuditStatus::Failed;
        }

        if ($statusCode >= 400) {
            return MailAdminAuditStatus::Rejected;
        }

        $payload = $this->responses->read($response);
        $successful = data_get($payload, 'data.successful');

        if ($successful === false) {
            return MailAdminAuditStatus::Failed;
        }

        return MailAdminAuditStatus::Succeeded;
    }

    public function fromException(
        Throwable $exception
    ): MailAdminAuditStatus {
        return $exception instanceof ValidationException
            ? MailAdminAuditStatus::Rejected
            : MailAdminAuditStatus::Failed;
    }
}
