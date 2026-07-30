<?php

namespace App\Services\Admin\Mail\Settings;

use App\Data\Admin\Mail\MailConnectionTestResultData;
use App\Enums\Admin\Mail\MailboxHealthStatus;
use App\Models\Admin\Mail\MailProviderConnection;

class MailProviderConnectionTester
{
    public function __construct(
        private readonly MailChannelConnectionTestService $channels,
        private readonly MailConnectionTestResultSanitizer $sanitizer,
    ) {
    }

    public function test(
        MailProviderConnection $connection
    ): MailConnectionTestResultData {
        $startedAt = hrtime(true);

        $channels = $connection
            ->channels()
            ->where('is_enabled', true)
            ->with([
                'mailbox',
                'providerConnection',
            ])
            ->orderBy('direction')
            ->orderByDesc('is_primary')
            ->orderBy('failover_order')
            ->orderBy('id')
            ->get();

        if ($channels->isEmpty()) {
            $connection->forceFill([
                'health_status' => MailboxHealthStatus::Warning,
                'last_checked_at' => now(),
                'last_error_at' => now(),
                'last_error_code' => 'provider_connection_has_no_channels',
                'last_error_message' => 'No enabled mailbox channels reference this provider connection.',
            ])->save();

            return $this->sanitizer->sanitize(
                MailConnectionTestResultData::failure(
                    message: 'No enabled mailbox channels reference this provider connection.',
                    latencyMilliseconds: $this->latencyMilliseconds(
                        $startedAt
                    ),
                    details: [
                        'provider_connection_id' => $connection->id,
                        'total_channels' => 0,
                        'successful_channels' => 0,
                        'failed_channels' => 0,
                        'health_status' => MailboxHealthStatus::Warning->value,
                        'channels' => [],
                    ],
                )
            );
        }

        $results = [];
        $successfulCount = 0;

        foreach ($channels as $channel) {
            $result = $this->channels->test($channel);

            if ($result->successful) {
                $successfulCount++;
            }

            $results[] = [
                'channel_id' => $channel->id,
                'channel_name' => $channel->name,
                'mailbox_id' => $channel->mailbox_id,
                'mailbox_name' => $channel->mailbox?->name,
                'direction' => $channel->direction->value,
                'driver' => $channel->driver->value,
                'successful' => $result->successful,
                'message' => $result->message,
                'latency_ms' => $result->latencyMilliseconds,
                'details' => $result->details,
            ];
        }

        $totalCount = $channels->count();
        $failedCount = $totalCount - $successfulCount;

        $healthStatus = match (true) {
            $failedCount === 0 => MailboxHealthStatus::Healthy,
            $successfulCount === 0 => MailboxHealthStatus::Failed,
            default => MailboxHealthStatus::Warning,
        };

        $now = now();

        $values = [
            'health_status' => $healthStatus,
            'last_checked_at' => $now,
        ];

        if ($successfulCount > 0) {
            $values['last_success_at'] = $now;
        }

        if ($failedCount === 0) {
            $values['last_error_at'] = null;
            $values['last_error_code'] = null;
            $values['last_error_message'] = null;
        } else {
            $values['last_error_at'] = $now;

            $values['last_error_code'] = $successfulCount === 0
                ? 'provider_connection_test_failed'
                : 'provider_connection_test_partial_failure';

            $values['last_error_message'] = $successfulCount === 0
                ? 'All linked mailbox channel connection tests failed.'
                : 'One or more linked mailbox channel connection tests failed.';
        }

        $connection->forceFill($values)->save();

        $successful = $failedCount === 0;

        $message = match (true) {
            $successful => 'All linked mailbox channel connection tests succeeded.',
            $successfulCount === 0 => 'All linked mailbox channel connection tests failed.',
            default => 'Some linked mailbox channel connection tests failed.',
        };

        return $this->sanitizer->sanitize(
            new MailConnectionTestResultData(
                successful: $successful,
                message: $message,
                latencyMilliseconds: $this->latencyMilliseconds(
                    $startedAt
                ),
                details: [
                    'provider_connection_id' => $connection->id,
                    'total_channels' => $totalCount,
                    'successful_channels' => $successfulCount,
                    'failed_channels' => $failedCount,
                    'health_status' => $healthStatus->value,
                    'channels' => $results,
                ],
            )
        );
    }

    private function latencyMilliseconds(int $startedAt): int
    {
        return (int) round(
            (hrtime(true) - $startedAt) / 1_000_000
        );
    }
}
