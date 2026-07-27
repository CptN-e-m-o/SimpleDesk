<?php

namespace App\Services\Admin\Mail;

use App\Data\Mail\MailConnectionTestResultData;
use App\Enums\Mail\MailboxChannelDirection;
use App\Exceptions\Admin\Mail\MailDriverException;
use App\Models\Admin\Mail\MailboxChannel;

class MailChannelTester
{
    public function __construct(
        private readonly MailDriverRegistry $drivers,
        private readonly MailChannelHealthRecorder $health,
    ) {
    }

    public function test(
        MailboxChannel $channel
    ): MailConnectionTestResultData {
        try {
            $result = match ($channel->direction) {
                MailboxChannelDirection::Incoming =>
                $this->drivers
                    ->incoming($channel->driver)
                    ->test($channel),

                MailboxChannelDirection::Outgoing =>
                $this->drivers
                    ->outgoing($channel->driver)
                    ->test($channel),
            };

            if ($result->successful) {
                $this->health->markSuccess($channel);
            } else {
                $this->health->markFailure(
                    channel: $channel,
                    errorCode: 'connection_test_failed',
                    errorMessage: $result->message,
                );
            }

            return $result;
        } catch (MailDriverException $exception) {
            if ($exception->affectsChannelHealth()) {
                $this->health->markFailure(
                    channel: $channel,
                    errorCode: $exception->driverErrorCode(),
                    errorMessage: $exception->getMessage(),
                );
            }

            throw $exception;
        }
    }
}
