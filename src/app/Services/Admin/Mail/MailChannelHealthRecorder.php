<?php

namespace App\Services\Admin\Mail;

use App\Enums\Mail\MailboxHealthStatus;
use App\Models\Admin\Mail\MailboxChannel;

class MailChannelHealthRecorder
{
    public function markSuccess(
        MailboxChannel $channel,
        bool $hasActivity = false,
    ): void {
        $now = now();

        $values = [
            'health_status' => MailboxHealthStatus::Healthy,
            'last_checked_at' => $now,
            'last_success_at' => $now,
            'last_error_code' => null,
            'last_error_message' => null,
        ];

        if ($hasActivity) {
            $values['last_activity_at'] = $now;
        }

        $channel->forceFill($values)->save();

        if ($channel->providerConnection !== null) {
            $channel->providerConnection->forceFill([
                'health_status' => MailboxHealthStatus::Healthy,
                'last_checked_at' => $now,
                'last_success_at' => $now,
                'last_error_code' => null,
                'last_error_message' => null,
            ])->save();
        }
    }

    public function markFailure(
        MailboxChannel $channel,
        ?string $errorCode,
        string $errorMessage,
    ): void {
        $now = now();

        $channel->forceFill([
            'health_status' => MailboxHealthStatus::Failed,
            'last_checked_at' => $now,
            'last_error_at' => $now,
            'last_error_code' => $errorCode,
            'last_error_message' => $errorMessage,
        ])->save();

        if ($channel->providerConnection !== null) {
            $channel->providerConnection->forceFill([
                'health_status' => MailboxHealthStatus::Failed,
                'last_checked_at' => $now,
                'last_error_at' => $now,
                'last_error_code' => $errorCode,
                'last_error_message' => $errorMessage,
            ])->save();
        }
    }
}
