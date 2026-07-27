<?php

namespace App\Services\Admin\Mail;

use App\Data\Admin\Mail\MailAddressData;
use App\Data\Admin\Mail\OutgoingSendResultData;
use App\Enums\Admin\Mail\EmailMessageAttemptStatus;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Enums\Admin\Mail\MailboxChannelDirection;
use App\Exceptions\Admin\Mail\AllMailChannelsFailedException;
use App\Exceptions\Admin\Mail\MailDriverException;
use App\Exceptions\Admin\Mail\MailDriverNotRegisteredException;
use App\Exceptions\Admin\Mail\NoAvailableMailChannelException;
use App\Exceptions\Admin\Mail\OutgoingMessageStateException;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\EmailMessageAttempt;
use App\Models\Admin\Mail\MailboxChannel;
use Illuminate\Support\Facades\DB;
use Throwable;

class OutgoingMailFailoverService
{
    public function __construct(
        private readonly MailDriverRegistry $drivers,
        private readonly MailChannelSelector $selector,
        private readonly MailChannelHealthRecorder $health,
        private readonly OutgoingEmailMessageFactory $messageFactory,
        private readonly int $sendingLockSeconds,
    ) {
    }

    public function send(
        EmailMessage $message
    ): OutgoingSendResultData {
        $message = $this->claimMessage(
            $message
        );

        $mailbox = $message->mailbox;

        if ($mailbox === null) {
            throw new OutgoingMessageStateException(
                "Email message [{$message->id}] "
                . 'has no mailbox.'
            );
        }

        $channels = $this
            ->selector
            ->outgoingCandidates($mailbox);

        if ($channels->isEmpty()) {
            $this->markMessageFailed(
                message: $message,
                errorCode: 'no_available_channel',
                errorMessage:
                'No outgoing mail channel is available.',
            );

            throw new NoAvailableMailChannelException(
                mailboxId: $mailbox->id,
                direction:
                MailboxChannelDirection::Outgoing,
            );
        }

        try {
            $payload = $this
                ->messageFactory
                ->make($message);
        } catch (Throwable $exception) {
            $this->markMessageFailed(
                message: $message,
                errorCode:
                'outgoing_payload_preparation_failed',
                errorMessage:
                $exception->getMessage(),
            );

            throw $exception;
        }

        $attemptNumber =
            ((int) $message
                ->attempts()
                ->max('attempt_number'))
            + 1;

        $failures = [];

        foreach ($channels as $channel) {
            $attempt = $this->startAttempt(
                message: $message,
                channel: $channel,
                attemptNumber: $attemptNumber,
            );

            $attemptNumber++;

            try {
                $driver = $this->drivers->outgoing(
                    $channel->driver
                );

                $result = $driver->send(
                    channel: $channel,
                    message: $payload,
                );

                $this->completeSuccessfulAttempt(
                    message: $message,
                    attempt: $attempt,
                    channel: $channel,
                    result: $result,
                );

                $this->health->markSuccess(
                    channel: $channel,
                    hasActivity: true,
                );

                return $result;
            } catch (MailDriverException $exception) {
                $this->completeFailedAttempt(
                    attempt: $attempt,
                    exception: $exception,
                );

                if (
                    $exception->affectsChannelHealth()
                ) {
                    $this->health->markFailure(
                        channel: $channel,
                        errorCode:
                        $exception->driverErrorCode(),
                        errorMessage:
                        $exception->getMessage(),
                    );
                }

                $failures[] = $this->failureData(
                    channel: $channel,
                    exception: $exception,
                );

                if (
                    !$exception->failoverAllowed()
                ) {
                    break;
                }
            } catch (
            MailDriverNotRegisteredException $exception
            ) {
                $this->completeConfigurationFailure(
                    attempt: $attempt,
                    exception: $exception,
                );

                $failures[] = $this->failureData(
                    channel: $channel,
                    exception: $exception,
                );
            } catch (Throwable $exception) {
                $this->completeUnexpectedFailure(
                    attempt: $attempt,
                    exception: $exception,
                );

                $this->markMessageFailed(
                    message: $message,
                    errorCode:
                    'unexpected_driver_error',
                    errorMessage:
                    $exception->getMessage(),
                    channel: $channel,
                );

                throw $exception;
            }
        }

        $failureMessage = collect($failures)
            ->map(
                static fn (
                    array $failure
                ): string =>
                    "[{$failure['driver']}] "
                    . $failure['message']
            )
            ->implode('; ');

        $this->markMessageFailed(
            message: $message,
            errorCode: 'all_channels_failed',
            errorMessage: $failureMessage,
            channel: $channels->last(),
        );

        throw new AllMailChannelsFailedException(
            mailboxId: $mailbox->id,
            direction:
            MailboxChannelDirection::Outgoing,
            failures: $failures,
        );
    }

    private function claimMessage(
        EmailMessage $message
    ): EmailMessage {
        return DB::transaction(
            function () use (
                $message
            ): EmailMessage {
                $lockedMessage = EmailMessage::query()
                    ->with('mailbox')
                    ->lockForUpdate()
                    ->findOrFail($message->id);

                if (
                    $lockedMessage->direction
                    !== EmailMessageDirection::Outgoing
                ) {
                    throw new OutgoingMessageStateException(
                        "Email message [{$lockedMessage->id}] "
                        . 'is not outgoing.'
                    );
                }

                if (in_array(
                    $lockedMessage->status,
                    [
                        EmailMessageStatus::Sent,
                        EmailMessageStatus::Delivered,
                    ],
                    true,
                )) {
                    throw new OutgoingMessageStateException(
                        "Email message [{$lockedMessage->id}] "
                        . 'has already been sent.'
                    );
                }

                $sendingLockThreshold =
                    now()->subSeconds(
                        $this->sendingLockSeconds
                    );

                if (
                    $lockedMessage->status
                    === EmailMessageStatus::Sending
                    && $lockedMessage
                        ->processing_started_at
                    !== null
                    && $lockedMessage
                        ->processing_started_at
                        ->greaterThan(
                            $sendingLockThreshold
                        )
                ) {
                    throw new OutgoingMessageStateException(
                        "Email message [{$lockedMessage->id}] "
                        . 'is already being processed.'
                    );
                }

                $lockedMessage->forceFill([
                    'status' =>
                        EmailMessageStatus::Sending,
                    'processing_started_at' => now(),
                    'processed_at' => null,
                    'failed_at' => null,
                    'failure_code' => null,
                    'failure_message' => null,
                ])->save();

                return $lockedMessage;
            }
        );
    }

    private function startAttempt(
        EmailMessage $message,
        MailboxChannel $channel,
        int $attemptNumber,
    ): EmailMessageAttempt {
        return $message->attempts()->create([
            'mailbox_channel_id' => $channel->id,
            'attempt_number' => $attemptNumber,
            'driver' => $channel->driver,
            'status' =>
                EmailMessageAttemptStatus::Processing,
            'started_at' => now(),
        ]);
    }

    private function completeSuccessfulAttempt(
        EmailMessage $message,
        EmailMessageAttempt $attempt,
        MailboxChannel $channel,
        OutgoingSendResultData $result,
    ): void {
        DB::transaction(
            function () use (
                $message,
                $attempt,
                $channel,
                $result,
            ): void {
                $attempt->forceFill([
                    'status' =>
                        EmailMessageAttemptStatus::Succeeded,
                    'external_message_id' =>
                        $result->externalMessageId,
                    'internet_message_id' =>
                        $result->internetMessageId,
                    'accepted_recipients' =>
                        $this->addressesToArray(
                            $result->acceptedRecipients
                        ),
                    'rejected_recipients' =>
                        $this->addressesToArray(
                            $result->rejectedRecipients
                        ),
                    'provider_response' =>
                        $result->providerResponse,
                    'metadata' =>
                        $result->metadata,
                    'completed_at' => now(),
                ])->save();

                $message->forceFill([
                    'mailbox_channel_id' =>
                        $channel->id,
                    'driver' =>
                        $channel->driver,
                    'status' =>
                        EmailMessageStatus::Sent,
                    'external_message_id' =>
                        $result->externalMessageId,
                    'internet_message_id' =>
                        $result->internetMessageId,
                    'sent_at' =>
                        $result->sentAt,
                    'processed_at' => now(),
                    'failed_at' => null,
                    'failure_code' => null,
                    'failure_message' => null,
                ])->save();
            }
        );
    }

    private function completeFailedAttempt(
        EmailMessageAttempt $attempt,
        MailDriverException $exception,
    ): void {
        $attempt->forceFill([
            'status' =>
                EmailMessageAttemptStatus::Failed,
            'retryable' =>
                $exception->retryable(),
            'failover_allowed' =>
                $exception->failoverAllowed(),
            'error_class' =>
                $exception::class,
            'error_code' =>
                $exception->driverErrorCode(),
            'error_message' =>
                $exception->getMessage(),
            'metadata' =>
                $exception->context(),
            'failed_at' => now(),
            'completed_at' => now(),
        ])->save();
    }

    private function completeConfigurationFailure(
        EmailMessageAttempt $attempt,
        Throwable $exception,
    ): void {
        $attempt->forceFill([
            'status' =>
                EmailMessageAttemptStatus::Failed,
            'retryable' => false,
            'failover_allowed' => true,
            'error_class' => $exception::class,
            'error_code' =>
                'driver_not_registered',
            'error_message' =>
                $exception->getMessage(),
            'failed_at' => now(),
            'completed_at' => now(),
        ])->save();
    }

    private function completeUnexpectedFailure(
        EmailMessageAttempt $attempt,
        Throwable $exception,
    ): void {
        $attempt->forceFill([
            'status' =>
                EmailMessageAttemptStatus::Failed,
            'retryable' => false,
            'failover_allowed' => false,
            'error_class' => $exception::class,
            'error_code' =>
                'unexpected_driver_error',
            'error_message' =>
                $exception->getMessage(),
            'failed_at' => now(),
            'completed_at' => now(),
        ])->save();
    }

    private function markMessageFailed(
        EmailMessage $message,
        string $errorCode,
        string $errorMessage,
        ?MailboxChannel $channel = null,
    ): void {
        $message->forceFill([
            'mailbox_channel_id' =>
                $channel?->id
                ?? $message->mailbox_channel_id,
            'driver' =>
                $channel?->driver
                ?? $message->driver,
            'status' =>
                EmailMessageStatus::Failed,
            'processed_at' => now(),
            'failed_at' => now(),
            'failure_code' => $errorCode,
            'failure_message' => $errorMessage,
        ])->save();
    }

    private function failureData(
        MailboxChannel $channel,
        Throwable $exception,
    ): array {
        return [
            'channel_id' => $channel->id,
            'driver' =>
                $channel->driver->value,
            'message' =>
                $exception->getMessage(),
            'exception' =>
                $exception::class,
        ];
    }

    /**
     * @param array<int, MailAddressData> $addresses
     */
    private function addressesToArray(
        array $addresses
    ): array {
        return array_map(
            static fn (
                MailAddressData $address
            ): array => $address->toArray(),
            $addresses,
        );
    }
}
