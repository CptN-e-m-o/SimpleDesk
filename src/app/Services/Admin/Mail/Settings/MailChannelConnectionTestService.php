<?php

namespace App\Services\Admin\Mail\Settings;

use App\Data\Admin\Mail\MailConnectionTestResultData;
use App\Exceptions\Admin\Mail\MailDriverException;
use App\Models\Admin\Mail\MailboxChannel;
use App\Services\Admin\Mail\MailChannelHealthRecorder;
use App\Services\Admin\Mail\MailChannelTester;
use Throwable;

class MailChannelConnectionTestService
{
    public function __construct(
        private readonly MailChannelTester $tester,
        private readonly MailChannelHealthRecorder $health,
        private readonly MailConnectionTestResultSanitizer $sanitizer,
    ) {}

    public function test(
        MailboxChannel $channel
    ): MailConnectionTestResultData {
        $channel->loadMissing([
            'mailbox',
            'providerConnection',
        ]);

        try {
            $result = $this->tester->test($channel);
        } catch (MailDriverException $exception) {
            $result = MailConnectionTestResultData::failure(
                message: $exception->getMessage(),
                details: [
                    'channel_id' => $channel->id,
                    'direction' => $channel->direction->value,
                    'driver' => $channel->driver->value,
                    'error_code' => $exception->driverErrorCode(),
                    'retryable' => $exception->retryable(),
                    'failover_allowed' => $exception->failoverAllowed(),
                    'context' => $exception->context(),
                ],
            );
        } catch (Throwable $exception) {
            $this->health->markFailure(
                channel: $channel,
                errorCode: 'connection_test_unexpected_error',
                errorMessage: 'The connection test failed unexpectedly.',
            );

            report($exception);

            $result = MailConnectionTestResultData::failure(
                message: 'The connection test failed unexpectedly.',
                details: [
                    'channel_id' => $channel->id,
                    'direction' => $channel->direction->value,
                    'driver' => $channel->driver->value,
                    'error_code' => 'connection_test_unexpected_error',
                ],
            );
        }

        return $this->sanitizer->sanitize($result);
    }
}
