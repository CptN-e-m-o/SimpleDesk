<?php

namespace App\Services\Admin\Mail\Settings;

use App\Data\Admin\Mail\MailAdminActionResultData;
use App\Enums\Admin\Mail\EmailQuarantineResolution;
use App\Exceptions\Admin\Mail\EmailQuarantineException;
use App\Exceptions\Admin\Mail\MailAdminActionException;
use App\Models\Admin\Mail\EmailMessageQuarantine;
use App\Services\Admin\Mail\Quarantine\EmailMessageQuarantineService;
use Throwable;

class EmailQuarantineAdminActionService
{
    public function __construct(
        private readonly EmailMessageQuarantineService $quarantines,
        private readonly MailAdminActionLock $locks,
    ) {}

    public function retry(
        EmailMessageQuarantine $quarantine,
        ?int $releasedById = null,
    ): MailAdminActionResultData {
        $quarantine->refresh();

        if ($quarantine->isReleasedForRetry()) {
            return new MailAdminActionResultData(
                action: 'quarantine_retry',
                dispatched: false,
                message: 'Quarantine retry is already queued.',
                details: [
                    'quarantine_id' => $quarantine->id,
                    'email_message_id' => $quarantine->email_message_id,
                ],
            );
        }

        if ($quarantine->isResolved()) {
            throw new MailAdminActionException(
                message: "Quarantine record [{$quarantine->id}] has already been resolved.",
                errorCode: 'quarantine_already_resolved',
                field: 'quarantine',
            );
        }

        if (! $this->locks->acquire(
            'retry-quarantine',
            $quarantine->id
        )) {
            return new MailAdminActionResultData(
                action: 'quarantine_retry',
                dispatched: false,
                message: 'Quarantine retry is already queued.',
                details: [
                    'quarantine_id' => $quarantine->id,
                    'email_message_id' => $quarantine->email_message_id,
                ],
            );
        }

        try {
            $quarantine = $this->quarantines->retry(
                quarantineId: $quarantine->id,
                releasedById: $releasedById,
                dispatch: true,
            );
        } catch (EmailQuarantineException $exception) {
            $this->locks->release(
                'retry-quarantine',
                $quarantine->id
            );

            throw new MailAdminActionException(
                message: $exception->getMessage(),
                errorCode: $exception->errorCode(),
                field: 'quarantine',
                previous: $exception,
            );
        } catch (Throwable $exception) {
            $this->locks->release(
                'retry-quarantine',
                $quarantine->id
            );

            throw $exception;
        }

        return new MailAdminActionResultData(
            action: 'quarantine_retry',
            dispatched: true,
            message: 'Quarantined email processing was queued for retry.',
            details: [
                'quarantine_id' => $quarantine->id,
                'email_message_id' => $quarantine->email_message_id,
                'resolution' => $quarantine->resolution?->value,
            ],
        );
    }

    public function ignore(
        EmailMessageQuarantine $quarantine,
        ?int $releasedById = null,
        ?string $reason = null,
    ): MailAdminActionResultData {
        $quarantine->refresh();

        if (
            $quarantine->isResolved()
            && $quarantine->resolution
            === EmailQuarantineResolution::Ignored
        ) {
            return new MailAdminActionResultData(
                action: 'quarantine_ignore',
                dispatched: false,
                message: 'Quarantine record has already been ignored.',
                details: [
                    'quarantine_id' => $quarantine->id,
                    'email_message_id' => $quarantine->email_message_id,
                    'resolution' => EmailQuarantineResolution::Ignored->value,
                ],
            );
        }

        if ($quarantine->isResolved()) {
            throw new MailAdminActionException(
                message: "Quarantine record [{$quarantine->id}] has already been resolved.",
                errorCode: 'quarantine_already_resolved',
                field: 'quarantine',
            );
        }

        if ($quarantine->isReleasedForRetry()) {
            throw new MailAdminActionException(
                message: "Quarantine record [{$quarantine->id}] is currently being retried.",
                errorCode: 'quarantine_retry_in_progress',
                field: 'quarantine',
            );
        }

        if (! $this->locks->acquire(
            'ignore-quarantine',
            $quarantine->id
        )) {
            return new MailAdminActionResultData(
                action: 'quarantine_ignore',
                dispatched: false,
                message: 'Quarantine ignore action has already been accepted.',
                details: [
                    'quarantine_id' => $quarantine->id,
                    'email_message_id' => $quarantine->email_message_id,
                ],
            );
        }

        try {
            $quarantine = $this->quarantines->ignore(
                quarantineId: $quarantine->id,
                releasedById: $releasedById,
                reason: $reason,
            );
        } catch (EmailQuarantineException $exception) {
            $this->locks->release(
                'ignore-quarantine',
                $quarantine->id
            );

            throw new MailAdminActionException(
                message: $exception->getMessage(),
                errorCode: $exception->errorCode(),
                field: 'quarantine',
                previous: $exception,
            );
        } catch (Throwable $exception) {
            $this->locks->release(
                'ignore-quarantine',
                $quarantine->id
            );

            throw $exception;
        }

        return new MailAdminActionResultData(
            action: 'quarantine_ignore',
            dispatched: false,
            message: 'Quarantine record was ignored.',
            details: [
                'quarantine_id' => $quarantine->id,
                'email_message_id' => $quarantine->email_message_id,
                'resolution' => $quarantine->resolution?->value,
            ],
        );
    }
}
