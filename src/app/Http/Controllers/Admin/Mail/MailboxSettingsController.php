<?php

namespace App\Http\Controllers\Admin\Mail;

use App\Enums\Admin\Mail\MailboxChannelDirection;
use App\Http\Controllers\Controller;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use BackedEnum;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Inertia;
use Inertia\Response;

class MailboxSettingsController extends Controller
{
    public function __invoke(): Response
    {
        $mailboxes = Mailbox::query()
            ->with([
                'department:id,name',
                'channels' => function ($query): void {
                    $query
                        ->orderByDesc('is_primary')
                        ->orderBy('failover_order')
                        ->orderBy('id');
                },
            ])
            ->orderBy('name')
            ->get();

        $items = $mailboxes
            ->map(
                fn (Mailbox $mailbox): array =>
                $this->mailboxData(
                    $mailbox
                )
            )
            ->values();

        $activeCount = $items
            ->where(
                'is_active',
                true
            )
            ->count();

        $configuredCount = $items
            ->filter(
                fn (array $mailbox): bool =>
                $this->isConfigured(
                    $mailbox
                )
            )
            ->count();

        $healthyCount = $items
            ->filter(
                fn (array $mailbox): bool =>
                $this->isHealthy(
                    $mailbox
                )
            )
            ->count();

        $needsAttentionCount = $items
            ->filter(
                fn (array $mailbox): bool =>
                $this->needsAttention(
                    $mailbox
                )
            )
            ->count();

        return Inertia::render(
            'Admin/Email/Mailboxes/Index',
            [
                'mailboxes' => $items,

                'summary' => [
                    'total' =>
                        $items->count(),

                    'active' =>
                        $activeCount,

                    'configured' =>
                        $configuredCount,

                    'healthy' =>
                        $healthyCount,

                    'needs_attention' =>
                        $needsAttentionCount,
                ],

                'system_mail_configured' =>
                    $configuredCount > 0,
            ]
        );
    }

    private function mailboxData(
        Mailbox $mailbox
    ): array {
        $incomingChannel =
            $this->preferredChannel(
                channels:
                $mailbox->channels,

                direction:
                MailboxChannelDirection::Incoming,
            );

        $outgoingChannel =
            $this->preferredChannel(
                channels:
                $mailbox->channels,

                direction:
                MailboxChannelDirection::Outgoing,
            );

        return [
            'id' =>
                $mailbox->id,

            'name' =>
                $mailbox->name,

            'email_address' =>
                $mailbox->email_address,

            'display_name' =>
                $mailbox->display_name,

            'is_active' =>
                (bool) $mailbox->is_active,

            'is_default_outgoing' =>
                (bool) $mailbox
                    ->is_default_outgoing,

            'department' =>
                $mailbox->department !== null
                    ? [
                    'id' =>
                        $mailbox
                            ->department
                            ->id,

                    'name' =>
                        $mailbox
                            ->department
                            ->name,
                ]
                    : null,

            'incoming_channel' =>
                $this->channelData(
                    $incomingChannel
                ),

            'outgoing_channel' =>
                $this->channelData(
                    $outgoingChannel
                ),

            'channels_count' =>
                $mailbox
                    ->channels
                    ->count(),

            'created_at' =>
                $mailbox
                    ->created_at
                    ?->toIso8601String(),

            'updated_at' =>
                $mailbox
                    ->updated_at
                    ?->toIso8601String(),
        ];
    }

    private function preferredChannel(
        Collection $channels,
        MailboxChannelDirection $direction,
    ): ?MailboxChannel {
        $directionChannels = $channels
            ->filter(
                fn (
                    MailboxChannel $channel
                ): bool =>
                    $this->enumValue(
                        $channel->direction
                    )
                    === $direction->value
            )
            ->values();

        $enabledChannel =
            $directionChannels->first(
                fn (
                    MailboxChannel $channel
                ): bool =>
                (bool) $channel
                    ->is_enabled
            );

        if ($enabledChannel !== null) {
            return $enabledChannel;
        }

        return $directionChannels->first();
    }

    private function channelData(
        ?MailboxChannel $channel
    ): ?array {
        if ($channel === null) {
            return null;
        }

        return [
            'id' =>
                $channel->id,

            'name' =>
                $channel->name,

            'direction' =>
                $this->enumValue(
                    $channel->direction
                ),

            'driver' =>
                $this->enumValue(
                    $channel->driver
                ),

            'health_status' =>
                $this->enumValue(
                    $channel->health_status
                ),

            'is_primary' =>
                (bool) $channel
                    ->is_primary,

            'is_enabled' =>
                (bool) $channel
                    ->is_enabled,

            'failover_order' =>
                (int) $channel
                    ->failover_order,

            'last_checked_at' =>
                $channel
                    ->last_checked_at
                    ?->toIso8601String(),

            'last_success_at' =>
                $channel
                    ->last_success_at
                    ?->toIso8601String(),

            'last_error_at' =>
                $channel
                    ->last_error_at
                    ?->toIso8601String(),
        ];
    }

    private function isConfigured(
        array $mailbox
    ): bool {
        if (!$mailbox['is_active']) {
            return false;
        }

        $incoming =
            $mailbox['incoming_channel'];

        $outgoing =
            $mailbox['outgoing_channel'];

        return $incoming !== null
            && $outgoing !== null
            && $incoming['is_enabled']
            && $outgoing['is_enabled'];
    }

    private function isHealthy(
        array $mailbox
    ): bool {
        if (
            !$this->isConfigured(
                $mailbox
            )
        ) {
            return false;
        }

        return $mailbox[
            'incoming_channel'
            ]['health_status'] === 'healthy'
            && $mailbox[
            'outgoing_channel'
            ]['health_status'] === 'healthy';
    }

    private function needsAttention(
        array $mailbox
    ): bool {
        if (!$mailbox['is_active']) {
            return false;
        }

        return !$this->isHealthy(
            $mailbox
        );
    }

    private function enumValue(
        mixed $value
    ): ?string {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        if ($value === null) {
            return null;
        }

        return (string) $value;
    }
}
