<?php

namespace App\Services\Admin\Mail;

use App\Data\Mail\IncomingCursorData;
use App\Data\Mail\IncomingMailboxSyncResultData;
use App\Enums\Mail\IncomingAcknowledgeAction;
use App\Enums\Mail\MailboxChannelDirection;
use App\Exceptions\Admin\Mail\MailDriverException;
use App\Exceptions\Mail\AllMailChannelsFailedException;
use App\Exceptions\Mail\MailDriverNotRegisteredException;
use App\Exceptions\Mail\NoAvailableMailChannelException;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use App\Models\Admin\Mail\MailboxChannelSyncState;
use Throwable;

class IncomingMailboxSyncService
{
    public function __construct(
        private readonly MailChannelSelector $selector,
        private readonly IncomingMailFetchService $fetcher,
        private readonly IncomingEmailMessagePersister $persister,
        private readonly IncomingMailAcknowledger $acknowledger,
        private readonly MailChannelHealthRecorder $health,
        private readonly int $batchSize,
        private readonly int $maxPagesPerRun,
        private readonly IncomingAcknowledgeAction $defaultAction,
    ) {
    }

    public function synchronize(
        Mailbox $mailbox
    ): IncomingMailboxSyncResultData {
        $channels = $this->selector->incomingCandidates(
            $mailbox
        );

        if ($channels->isEmpty()) {
            throw new NoAvailableMailChannelException(
                mailboxId: $mailbox->id,
                direction: MailboxChannelDirection::Incoming,
            );
        }

        $failures = [];

        foreach ($channels as $channel) {
            try {
                return $this->synchronizeChannel(
                    mailbox: $mailbox,
                    channel: $channel,
                );
            } catch (MailDriverException $exception) {
                $failures[] = [
                    'channel_id' => $channel->id,
                    'driver' => $channel->driver->value,
                    'message' => $exception->getMessage(),
                    'exception' => $exception::class,
                ];

                if (!$exception->failoverAllowed()) {
                    break;
                }
            } catch (
            MailDriverNotRegisteredException $exception
            ) {
                $failures[] = [
                    'channel_id' => $channel->id,
                    'driver' => $channel->driver->value,
                    'message' => $exception->getMessage(),
                    'exception' => $exception::class,
                ];
            }
        }

        throw new AllMailChannelsFailedException(
            mailboxId: $mailbox->id,
            direction: MailboxChannelDirection::Incoming,
            failures: $failures,
        );
    }

    private function synchronizeChannel(
        Mailbox $mailbox,
        MailboxChannel $channel,
    ): IncomingMailboxSyncResultData {
        $state = $channel
            ->syncState()
            ->firstOrCreate([]);

        $this->markSyncStarted($state);

        $cursor = $this->makeCursor(
            channel: $channel,
            state: $state,
        );

        $pages = 0;
        $fetched = 0;
        $stored = 0;
        $duplicates = 0;
        $acknowledged = 0;
        $hasMore = false;

        try {
            do {
                $result = $this->fetcher->fetch(
                    channel: $channel,
                    cursor: $cursor,
                    limit: $this->batchSize,
                );

                $pages++;

                foreach ($result->messages as $message) {
                    $fetched++;

                    $persistenceResult =
                        $this->persister->persist(
                            channel: $channel,
                            message: $message,
                        );

                    if ($persistenceResult->duplicate) {
                        $duplicates++;
                    } else {
                        $stored++;
                    }

                    $this->acknowledger->acknowledge(
                        channel: $channel,
                        message: $message,
                        action: $this->acknowledgeAction(
                            $channel
                        ),
                    );

                    $acknowledged++;
                }

                $this->validateCursorProgress(
                    currentCursor: $cursor,
                    nextCursor: $result->nextCursor,
                    hasMore: $result->hasMore,
                );

                if ($result->nextCursor !== null) {
                    $cursor = new IncomingCursorData(
                        mailboxChannelId: $channel->id,
                        value: $result->nextCursor,
                        metadata: $result->metadata,
                    );
                }

                $this->savePageProgress(
                    state: $state,
                    cursor: $cursor,
                    fetched: $fetched,
                    stored: $stored,
                    duplicates: $duplicates,
                    acknowledged: $acknowledged,
                );

                $hasMore = $result->hasMore;
            } while (
                $hasMore
                && $pages < $this->maxPagesPerRun
            );

            $this->markSyncCompleted(
                state: $state,
                cursor: $cursor,
                fetched: $fetched,
                stored: $stored,
                duplicates: $duplicates,
                acknowledged: $acknowledged,
            );

            $this->health->markSuccess(
                channel: $channel,
                hasActivity: $fetched > 0,
            );

            return new IncomingMailboxSyncResultData(
                mailboxId: $mailbox->id,
                mailboxChannelId: $channel->id,
                driver: $channel->driver,
                pages: $pages,
                fetched: $fetched,
                stored: $stored,
                duplicates: $duplicates,
                acknowledged: $acknowledged,
                truncated:
                $hasMore
                && $pages >= $this->maxPagesPerRun,
                nextCursor: $cursor?->value,
            );
        } catch (MailDriverException $exception) {
            $this->markSyncFailed(
                state: $state,
                errorCode: $exception->driverErrorCode(),
                errorMessage: $exception->getMessage(),
            );

            if ($exception->affectsChannelHealth()) {
                $this->health->markFailure(
                    channel: $channel,
                    errorCode: $exception->driverErrorCode(),
                    errorMessage: $exception->getMessage(),
                );
            }

            if ($stored > 0 || $duplicates > 0) {
                throw new MailDriverException(
                    message: $exception->getMessage(),
                    driverErrorCode:
                    $exception->driverErrorCode(),
                    retryable: $exception->retryable(),
                    failoverAllowed: false,
                    affectsChannelHealth:
                    $exception->affectsChannelHealth(),
                    context: $exception->context(),
                    previous: $exception,
                );
            }

            throw $exception;
        } catch (Throwable $exception) {
            $this->markSyncFailed(
                state: $state,
                errorCode: 'mailbox_sync_failed',
                errorMessage: $exception->getMessage(),
            );

            throw $exception;
        }
    }

    private function makeCursor(
        MailboxChannel $channel,
        MailboxChannelSyncState $state,
    ): ?IncomingCursorData {
        if ($state->cursor === null) {
            return null;
        }

        return new IncomingCursorData(
            mailboxChannelId: $channel->id,
            value: $state->cursor,
            metadata: $state->cursor_metadata ?? [],
        );
    }

    private function acknowledgeAction(
        MailboxChannel $channel
    ): IncomingAcknowledgeAction {
        $value = $channel->configuration[
        'post_fetch_action'
        ] ?? null;

        if (is_string($value)) {
            $action = IncomingAcknowledgeAction::tryFrom(
                $value
            );

            if ($action !== null) {
                return $action;
            }
        }

        return $this->defaultAction;
    }

    private function validateCursorProgress(
        ?IncomingCursorData $currentCursor,
        ?string $nextCursor,
        bool $hasMore,
    ): void {
        if (!$hasMore) {
            return;
        }

        if ($nextCursor === null || $nextCursor === '') {
            throw new MailDriverException(
                message:
                'Mail driver returned hasMore=true '
                . 'without a next cursor.',
                driverErrorCode: 'missing_next_cursor',
                retryable: false,
                failoverAllowed: false,
                affectsChannelHealth: true,
            );
        }

        if (
            $currentCursor !== null
            && $currentCursor->value === $nextCursor
        ) {
            throw new MailDriverException(
                message:
                'Mail driver did not advance '
                . 'the synchronization cursor.',
                driverErrorCode: 'cursor_not_advanced',
                retryable: false,
                failoverAllowed: false,
                affectsChannelHealth: true,
            );
        }
    }

    private function markSyncStarted(
        MailboxChannelSyncState $state
    ): void {
        $state->forceFill([
            'last_sync_started_at' => now(),
            'last_error_code' => null,
            'last_error_message' => null,
        ])->save();
    }

    private function savePageProgress(
        MailboxChannelSyncState $state,
        ?IncomingCursorData $cursor,
        int $fetched,
        int $stored,
        int $duplicates,
        int $acknowledged,
    ): void {
        $state->forceFill([
            'cursor' => $cursor?->value,
            'cursor_metadata' => $cursor?->metadata,
            'last_fetched_count' => $fetched,
            'last_stored_count' => $stored,
            'last_duplicate_count' => $duplicates,
            'last_acknowledged_count' => $acknowledged,
        ])->save();
    }

    private function markSyncCompleted(
        MailboxChannelSyncState $state,
        ?IncomingCursorData $cursor,
        int $fetched,
        int $stored,
        int $duplicates,
        int $acknowledged,
    ): void {
        $state->forceFill([
            'cursor' => $cursor?->value,
            'cursor_metadata' => $cursor?->metadata,
            'last_sync_completed_at' => now(),
            'last_sync_failed_at' => null,
            'consecutive_failures' => 0,
            'last_fetched_count' => $fetched,
            'last_stored_count' => $stored,
            'last_duplicate_count' => $duplicates,
            'last_acknowledged_count' => $acknowledged,
            'last_error_code' => null,
            'last_error_message' => null,
        ])->save();
    }

    private function markSyncFailed(
        MailboxChannelSyncState $state,
        ?string $errorCode,
        string $errorMessage,
    ): void {
        $state->forceFill([
            'last_sync_failed_at' => now(),
            'consecutive_failures' =>
                $state->consecutive_failures + 1,
            'last_error_code' => $errorCode,
            'last_error_message' => $errorMessage,
        ])->save();
    }
}
