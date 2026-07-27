<?php

namespace App\Services\Admin\Mail;

use App\Enums\Mail\MailboxChannelDirection;
use App\Enums\Mail\MailboxHealthStatus;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use Illuminate\Support\Collection;

class MailChannelSelector
{
    public function __construct(
        private readonly int $failedChannelCooldownSeconds,
    ) {
    }

    public function incomingCandidates(
        Mailbox $mailbox
    ): Collection {
        return $this->candidates(
            mailbox: $mailbox,
            direction: MailboxChannelDirection::Incoming,
        );
    }

    public function outgoingCandidates(
        Mailbox $mailbox
    ): Collection {
        return $this->candidates(
            mailbox: $mailbox,
            direction: MailboxChannelDirection::Outgoing,
        );
    }

    private function candidates(
        Mailbox $mailbox,
        MailboxChannelDirection $direction,
    ): Collection {
        if (!$mailbox->is_active) {
            return collect();
        }

        $channels = $mailbox
            ->channels()
            ->with('providerConnection')
            ->where('direction', $direction->value)
            ->where('is_enabled', true)
            ->get()
            ->filter(function (MailboxChannel $channel) use (
                $direction
            ): bool {
                if (
                    $channel->provider_connection_id !== null
                    && (
                        $channel->providerConnection === null
                        || !$channel->providerConnection->is_active
                    )
                ) {
                    return false;
                }

                return match ($direction) {
                    MailboxChannelDirection::Incoming =>
                    $channel->driver->supportsIncoming(),

                    MailboxChannelDirection::Outgoing =>
                    $channel->driver->supportsOutgoing(),
                };
            });

        if ($channels->isEmpty()) {
            return collect();
        }

        $availableOutsideCooldown = $channels->reject(
            fn (MailboxChannel $channel): bool =>
            $this->isInsideFailedCooldown($channel)
        );

        if ($availableOutsideCooldown->isNotEmpty()) {
            $channels = $availableOutsideCooldown;
        }

        return $channels
            ->sort(function (
                MailboxChannel $left,
                MailboxChannel $right,
            ): int {
                return $this->sortKey($left)
                    <=> $this->sortKey($right);
            })
            ->values();
    }

    private function isInsideFailedCooldown(
        MailboxChannel $channel
    ): bool {
        if (
            $channel->health_status
            !== MailboxHealthStatus::Failed
        ) {
            return false;
        }

        if ($channel->last_error_at === null) {
            return false;
        }

        return $channel->last_error_at->greaterThan(
            now()->subSeconds(
                $this->failedChannelCooldownSeconds
            )
        );
    }

    private function sortKey(
        MailboxChannel $channel
    ): array {
        $healthPriority = match ($channel->health_status) {
            MailboxHealthStatus::Healthy => 0,
            MailboxHealthStatus::Unknown => 1,
            MailboxHealthStatus::Warning => 2,
            MailboxHealthStatus::Failed => 3,
            MailboxHealthStatus::Disabled => 4,
        };

        return [
            $healthPriority,
            $channel->is_primary ? 0 : 1,
            $channel->failover_order,
            $channel->id,
        ];
    }
}
