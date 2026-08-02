<?php

namespace App\Services\Admin\Mail\Settings;

use App\Enums\Admin\Mail\MailboxHealthStatus;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MailboxChannelAdminService
{
    public function __construct(
        private readonly SecretConfigurationMerger $secrets,
    ) {}

    public function create(
        Mailbox $mailbox,
        array $data,
    ): MailboxChannel {
        return DB::transaction(
            function () use ($mailbox, $data): MailboxChannel {
                $mailbox = Mailbox::query()
                    ->lockForUpdate()
                    ->findOrFail($mailbox->id);

                $hasChannelForDirection = $mailbox
                    ->channels()
                    ->where('direction', $data['direction'])
                    ->exists();

                $isPrimary = (bool) $data['is_primary']
                    || (
                        ! $hasChannelForDirection
                        && (bool) $data['is_enabled']
                    );

                if ($isPrimary) {
                    $this->clearOtherPrimaryChannels(
                        mailboxId: $mailbox->id,
                        direction: $data['direction'],
                    );
                }

                return $mailbox->channels()->create([
                    'provider_connection_id' => $data['provider_connection_id'] ?? null,
                    'name' => trim($data['name']),
                    'direction' => $data['direction'],
                    'driver' => $data['driver'],
                    'auth_type' => $data['auth_type'],
                    'is_enabled' => (bool) $data['is_enabled'],
                    'is_primary' => $isPrimary,
                    'failover_order' => (int) $data['failover_order'],
                    'configuration' => $data['configuration'] ?? [],
                    'secret_configuration' => $this->secrets->merge(
                        existing: [],
                        incoming: $data['secret_configuration'] ?? [],
                        clearKeys: $data['clear_secret_keys'] ?? [],
                    ),
                    'health_status' => MailboxHealthStatus::Unknown,
                ]);
            },
            3,
        );
    }

    public function update(
        MailboxChannel $channel,
        array $data,
    ): MailboxChannel {
        return DB::transaction(
            function () use ($channel, $data): MailboxChannel {
                $channel = MailboxChannel::query()
                    ->lockForUpdate()
                    ->findOrFail($channel->id);

                $identityChanged = $channel->direction->value !== $data['direction']
                    || $channel->driver->value !== $data['driver'];

                if ($identityChanged) {
                    $this->assertTransportIdentityCanChange(
                        $channel
                    );
                }

                if ((bool) $data['is_primary']) {
                    $this->clearOtherPrimaryChannels(
                        mailboxId: $channel->mailbox_id,
                        direction: $data['direction'],
                        exceptChannelId: $channel->id,
                    );
                }

                $channel->fill([
                    'provider_connection_id' => $data['provider_connection_id'] ?? null,
                    'name' => trim($data['name']),
                    'direction' => $data['direction'],
                    'driver' => $data['driver'],
                    'auth_type' => $data['auth_type'],
                    'is_enabled' => (bool) $data['is_enabled'],
                    'is_primary' => (bool) $data['is_primary'],
                    'failover_order' => (int) $data['failover_order'],
                    'configuration' => $data['configuration'] ?? [],
                    'secret_configuration' => $this->secrets->merge(
                        existing: $channel->secret_configuration,
                        incoming: $data['secret_configuration'] ?? [],
                        clearKeys: $data['clear_secret_keys'] ?? [],
                    ),
                    'health_status' => MailboxHealthStatus::Unknown,
                    'last_checked_at' => null,
                    'last_success_at' => null,
                    'last_activity_at' => null,
                    'last_error_at' => null,
                    'last_error_code' => null,
                    'last_error_message' => null,
                ])->save();

                if ($identityChanged) {
                    $channel->syncState()->delete();
                }

                return $channel->fresh();
            },
            3,
        );
    }

    public function delete(MailboxChannel $channel): void
    {
        DB::transaction(
            function () use ($channel): void {
                $channel = MailboxChannel::query()
                    ->lockForUpdate()
                    ->findOrFail($channel->id);

                if (
                    $channel->emailMessages()->exists()
                    || $channel->messageAttempts()->exists()
                    || $channel->webhookEvents()->exists()
                    || $channel->quarantines()->exists()
                ) {
                    throw ValidationException::withMessages([
                        'channel' => [
                            'A mailbox channel with mail history cannot be deleted. Disable it instead.',
                        ],
                    ]);
                }

                $channel->delete();
            },
            3,
        );
    }

    private function assertTransportIdentityCanChange(
        MailboxChannel $channel
    ): void {
        if (
            $channel->emailMessages()->exists()
            || $channel->messageAttempts()->exists()
            || $channel->webhookEvents()->exists()
            || $channel->subscriptions()->exists()
            || $channel->quarantines()->exists()
        ) {
            throw ValidationException::withMessages([
                'driver' => [
                    'The direction or driver cannot be changed after a channel has processed mail. Create a new channel instead.',
                ],
            ]);
        }
    }

    private function clearOtherPrimaryChannels(
        int $mailboxId,
        string $direction,
        ?int $exceptChannelId = null,
    ): void {
        MailboxChannel::query()
            ->where('mailbox_id', $mailboxId)
            ->where('direction', $direction)
            ->when(
                $exceptChannelId !== null,
                fn ($query) => $query->whereKeyNot(
                    $exceptChannelId
                )
            )
            ->where('is_primary', true)
            ->update([
                'is_primary' => false,
            ]);
    }
}
