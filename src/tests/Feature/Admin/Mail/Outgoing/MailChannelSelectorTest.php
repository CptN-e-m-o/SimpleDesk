<?php

namespace Tests\Feature\Admin\Mail\Outgoing;

use App\Enums\Admin\Mail\MailboxChannelDirection;
use App\Enums\Admin\Mail\MailboxDriver;
use App\Enums\Admin\Mail\MailboxHealthStatus;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use App\Services\Admin\Mail\MailChannelSelector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MailChannelSelectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_mailbox_has_no_outgoing_candidates(): void
    {
        $mailbox = $this->createMailbox(
            active: false
        );

        $this->createChannel(
            mailbox: $mailbox,
            name: 'Primary SMTP',
            primary: true,
            failoverOrder: 0,
            healthStatus: MailboxHealthStatus::Healthy,
        );

        $channels = $this->selector()->outgoingCandidates(
            $mailbox
        );

        $this->assertTrue(
            $channels->isEmpty()
        );
    }

    public function test_disabled_channel_is_excluded(): void
    {
        $mailbox = $this->createMailbox();

        $enabledChannel = $this->createChannel(
            mailbox: $mailbox,
            name: 'Enabled SMTP',
            primary: true,
            failoverOrder: 0,
            healthStatus: MailboxHealthStatus::Healthy,
        );

        $this->createChannel(
            mailbox: $mailbox,
            name: 'Disabled SMTP',
            primary: false,
            failoverOrder: 10,
            healthStatus: MailboxHealthStatus::Healthy,
            enabled: false,
        );

        $channels = $this->selector()->outgoingCandidates(
            $mailbox
        );

        $this->assertCount(
            1,
            $channels
        );

        $this->assertSame(
            $enabledChannel->id,
            $channels->first()->id
        );
    }

    public function test_incoming_channel_is_not_returned_for_outgoing_selection(): void
    {
        $mailbox = $this->createMailbox();

        $outgoingChannel = $this->createChannel(
            mailbox: $mailbox,
            name: 'Outgoing SMTP',
            primary: true,
            failoverOrder: 0,
            healthStatus: MailboxHealthStatus::Healthy,
            direction: MailboxChannelDirection::Outgoing,
        );

        $this->createChannel(
            mailbox: $mailbox,
            name: 'Incoming IMAP',
            primary: true,
            failoverOrder: 0,
            healthStatus: MailboxHealthStatus::Healthy,
            direction: MailboxChannelDirection::Incoming,
            driver: MailboxDriver::Imap,
        );

        $channels = $this->selector()->outgoingCandidates(
            $mailbox
        );

        $this->assertCount(
            1,
            $channels
        );

        $this->assertSame(
            $outgoingChannel->id,
            $channels->first()->id
        );
    }

    public function test_primary_channel_is_first_when_health_status_is_equal(): void
    {
        $mailbox = $this->createMailbox();

        $fallback = $this->createChannel(
            mailbox: $mailbox,
            name: 'Fallback SMTP',
            primary: false,
            failoverOrder: 0,
            healthStatus: MailboxHealthStatus::Healthy,
        );

        $primary = $this->createChannel(
            mailbox: $mailbox,
            name: 'Primary SMTP',
            primary: true,
            failoverOrder: 100,
            healthStatus: MailboxHealthStatus::Healthy,
        );

        $channels = $this->selector()->outgoingCandidates(
            $mailbox
        );

        $this->assertSame(
            [
                $primary->id,
                $fallback->id,
            ],
            $channels
                ->pluck('id')
                ->all()
        );
    }

    public function test_health_status_has_priority_over_primary_flag(): void
    {
        $mailbox = $this->createMailbox();

        $primaryUnknown = $this->createChannel(
            mailbox: $mailbox,
            name: 'Primary Unknown SMTP',
            primary: true,
            failoverOrder: 0,
            healthStatus: MailboxHealthStatus::Unknown,
        );

        $fallbackHealthy = $this->createChannel(
            mailbox: $mailbox,
            name: 'Fallback Healthy SMTP',
            primary: false,
            failoverOrder: 100,
            healthStatus: MailboxHealthStatus::Healthy,
        );

        $channels = $this->selector()->outgoingCandidates(
            $mailbox
        );

        $this->assertSame(
            [
                $fallbackHealthy->id,
                $primaryUnknown->id,
            ],
            $channels
                ->pluck('id')
                ->all()
        );
    }

    public function test_failed_channel_inside_cooldown_is_excluded_when_another_channel_is_available(): void
    {
        $mailbox = $this->createMailbox();

        $failedPrimary = $this->createChannel(
            mailbox: $mailbox,
            name: 'Failed Primary SMTP',
            primary: true,
            failoverOrder: 0,
            healthStatus: MailboxHealthStatus::Failed,
            lastErrorAt: now()->subSeconds(30),
        );

        $healthyFallback = $this->createChannel(
            mailbox: $mailbox,
            name: 'Healthy Fallback SMTP',
            primary: false,
            failoverOrder: 10,
            healthStatus: MailboxHealthStatus::Healthy,
        );

        $channels = $this
            ->selector(
                failedChannelCooldownSeconds: 300
            )
            ->outgoingCandidates(
                $mailbox
            );

        $this->assertCount(
            1,
            $channels
        );

        $this->assertSame(
            $healthyFallback->id,
            $channels->first()->id
        );

        $this->assertFalse(
            $channels->contains(
                fn (MailboxChannel $channel): bool => $channel->id === $failedPrimary->id
            )
        );
    }

    public function test_failed_channel_is_returned_after_cooldown_expires(): void
    {
        $mailbox = $this->createMailbox();

        $failedPrimary = $this->createChannel(
            mailbox: $mailbox,
            name: 'Recovered Primary SMTP',
            primary: true,
            failoverOrder: 0,
            healthStatus: MailboxHealthStatus::Failed,
            lastErrorAt: now()->subSeconds(600),
        );

        $warningFallback = $this->createChannel(
            mailbox: $mailbox,
            name: 'Warning Fallback SMTP',
            primary: false,
            failoverOrder: 10,
            healthStatus: MailboxHealthStatus::Warning,
        );

        $channels = $this
            ->selector(
                failedChannelCooldownSeconds: 300
            )
            ->outgoingCandidates(
                $mailbox
            );

        $this->assertCount(
            2,
            $channels
        );

        /*
         * Warning has a better health priority than Failed,
         * even though the failed channel is primary.
         */
        $this->assertSame(
            [
                $warningFallback->id,
                $failedPrimary->id,
            ],
            $channels
                ->pluck('id')
                ->all()
        );
    }

    public function test_all_failed_channels_remain_available_when_every_channel_is_inside_cooldown(): void
    {
        $mailbox = $this->createMailbox();

        $primary = $this->createChannel(
            mailbox: $mailbox,
            name: 'Failed Primary SMTP',
            primary: true,
            failoverOrder: 0,
            healthStatus: MailboxHealthStatus::Failed,
            lastErrorAt: now()->subSeconds(30),
        );

        $fallback = $this->createChannel(
            mailbox: $mailbox,
            name: 'Failed Fallback SMTP',
            primary: false,
            failoverOrder: 10,
            healthStatus: MailboxHealthStatus::Failed,
            lastErrorAt: now()->subSeconds(60),
        );

        $channels = $this
            ->selector(
                failedChannelCooldownSeconds: 300
            )
            ->outgoingCandidates(
                $mailbox
            );

        /*
         * Selector intentionally falls back to all configured channels
         * when filtering by cooldown would leave no candidates.
         */
        $this->assertSame(
            [
                $primary->id,
                $fallback->id,
            ],
            $channels
                ->pluck('id')
                ->all()
        );
    }

    public function test_failover_order_is_used_for_equal_non_primary_channels(): void
    {
        $mailbox = $this->createMailbox();

        $third = $this->createChannel(
            mailbox: $mailbox,
            name: 'Third SMTP',
            primary: false,
            failoverOrder: 30,
            healthStatus: MailboxHealthStatus::Healthy,
        );

        $first = $this->createChannel(
            mailbox: $mailbox,
            name: 'First SMTP',
            primary: false,
            failoverOrder: 10,
            healthStatus: MailboxHealthStatus::Healthy,
        );

        $second = $this->createChannel(
            mailbox: $mailbox,
            name: 'Second SMTP',
            primary: false,
            failoverOrder: 20,
            healthStatus: MailboxHealthStatus::Healthy,
        );

        $channels = $this->selector()->outgoingCandidates(
            $mailbox
        );

        $this->assertSame(
            [
                $first->id,
                $second->id,
                $third->id,
            ],
            $channels
                ->pluck('id')
                ->all()
        );
    }

    private function selector(
        int $failedChannelCooldownSeconds = 300
    ): MailChannelSelector {
        return new MailChannelSelector(
            failedChannelCooldownSeconds: $failedChannelCooldownSeconds
        );
    }

    private function createMailbox(
        bool $active = true
    ): Mailbox {
        $token = strtolower(
            (string) Str::ulid()
        );

        return Mailbox::query()->create([
            'name' => "Selector Mailbox {$token}",

            'email_address' => "selector-{$token}@example.test",

            'display_name' => 'Selector Mailbox',

            'department_id' => null,

            'is_active' => $active,

            'is_default_outgoing' => false,

            'internal_notes' => null,
        ]);
    }

    private function createChannel(
        Mailbox $mailbox,
        string $name,
        bool $primary,
        int $failoverOrder,
        MailboxHealthStatus $healthStatus,
        bool $enabled = true,
        MailboxChannelDirection $direction =
        MailboxChannelDirection::Outgoing,
        MailboxDriver $driver =
        MailboxDriver::Smtp,
        mixed $lastErrorAt = null,
    ): MailboxChannel {
        $token = strtolower(
            (string) Str::ulid()
        );

        $channel = new MailboxChannel;

        $channel->forceFill([
            'mailbox_id' => $mailbox->id,

            'provider_connection_id' => null,

            'name' => "{$name} {$token}",

            'direction' => $direction,

            'driver' => $driver,

            'is_primary' => $primary,

            'failover_order' => $failoverOrder,

            'is_enabled' => $enabled,

            'configuration' => [],

            'health_status' => $healthStatus,

            'last_checked_at' => $lastErrorAt,

            'last_success_at' => null,

            'last_error_at' => $lastErrorAt,

            'last_error_code' => $lastErrorAt !== null
                    ? 'test_channel_failure'
                    : null,

            'last_error_message' => $lastErrorAt !== null
                    ? 'Test channel failure.'
                    : null,
        ])->save();

        return $channel->fresh();
    }
}
