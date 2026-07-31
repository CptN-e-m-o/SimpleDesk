<?php

namespace Tests\Feature\Admin\Mail\Incoming;

use App\Data\Admin\Mail\IncomingCursorData;
use App\Data\Admin\Mail\IncomingFetchResultData;
use App\Enums\Admin\Mail\IncomingAcknowledgeAction;
use App\Enums\Admin\Mail\MailboxChannelDirection;
use App\Enums\Admin\Mail\MailboxDriver;
use App\Enums\Admin\Mail\MailboxHealthStatus;
use App\Exceptions\Admin\Mail\AllMailChannelsFailedException;
use App\Exceptions\Admin\Mail\MailDriverException;
use App\Exceptions\Admin\Mail\NoAvailableMailChannelException;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use App\Services\Admin\Mail\InboundNormalizationFailurePersister;
use App\Services\Admin\Mail\IncomingEmailMessagePersister;
use App\Services\Admin\Mail\IncomingMailAcknowledger;
use App\Services\Admin\Mail\IncomingMailboxSyncService;
use App\Services\Admin\Mail\IncomingMailFetchService;
use App\Services\Admin\Mail\MailChannelHealthRecorder;
use App\Services\Admin\Mail\MailChannelSelector;
use App\Services\Admin\Mail\Quarantine\EmailMessageQuarantineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class IncomingMailboxSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_available_channel_throws_exception(): void
    {
        $mailbox = $this->createMailbox();

        $selector = Mockery::mock(
            MailChannelSelector::class
        );

        $selector
            ->shouldReceive('incomingCandidates')
            ->once()
            ->withArgs(
                fn (Mailbox $argument): bool => $argument->id === $mailbox->id
            )
            ->andReturn(
                collect()
            );

        $fetcher = Mockery::mock(
            IncomingMailFetchService::class
        );

        $fetcher->shouldNotReceive(
            'fetch'
        );

        $persister = Mockery::mock(
            IncomingEmailMessagePersister::class
        );

        $persister->shouldNotReceive(
            'persist'
        );

        $normalizationFailures = Mockery::mock(
            InboundNormalizationFailurePersister::class
        );

        $quarantine = Mockery::mock(
            EmailMessageQuarantineService::class
        );

        $acknowledger = Mockery::mock(
            IncomingMailAcknowledger::class
        );

        $acknowledger->shouldNotReceive(
            'acknowledgeMany'
        );

        $health = Mockery::mock(
            MailChannelHealthRecorder::class
        );

        $health->shouldNotReceive(
            'markSuccess'
        );

        $health->shouldNotReceive(
            'markFailure'
        );

        $service = $this->service(
            selector: $selector,
            fetcher: $fetcher,
            persister: $persister,
            acknowledger: $acknowledger,
            health: $health,
            normalizationFailures: $normalizationFailures,
            quarantine: $quarantine,
        );

        $this->expectException(
            NoAvailableMailChannelException::class
        );

        $service->synchronize(
            $mailbox
        );
    }

    public function test_empty_page_updates_cursor_and_completes_sync(): void
    {
        $mailbox = $this->createMailbox();

        $channel = $this->createChannel(
            $mailbox
        );

        $selector = $this->selectorWithChannels([
            $channel,
        ]);

        $fetcher = Mockery::mock(
            IncomingMailFetchService::class
        );

        $fetcher
            ->shouldReceive('fetch')
            ->once()
            ->withArgs(
                function (
                    MailboxChannel $argumentChannel,
                    ?IncomingCursorData $cursor,
                    int $limit,
                ) use ($channel): bool {
                    return $argumentChannel->id
                        === $channel->id
                        && $cursor === null
                        && $limit === 50;
                }
            )
            ->andReturn(
                new IncomingFetchResultData(
                    messages: [],
                    nextCursor: '12',
                    hasMore: false,
                    metadata: [
                        'folder' => 'INBOX',
                        'uidvalidity' => 1001,
                    ],
                )
            );

        $persister = $this->unusedPersister();

        $normalizationFailures =
            $this->unusedNormalizationFailures();

        $quarantine =
            $this->unusedQuarantine();

        $acknowledger =
            $this->unusedAcknowledger();

        $health = Mockery::mock(
            MailChannelHealthRecorder::class
        );

        $health
            ->shouldReceive('markSuccess')
            ->once()
            ->withArgs(
                fn (
                    MailboxChannel $argumentChannel,
                    bool $hasActivity,
                ): bool => $argumentChannel->id
                    === $channel->id
                    && $hasActivity === false
            );

        $health->shouldNotReceive(
            'markFailure'
        );

        $service = $this->service(
            selector: $selector,
            fetcher: $fetcher,
            persister: $persister,
            acknowledger: $acknowledger,
            health: $health,
            batchSize: 50,
            normalizationFailures: $normalizationFailures,
            quarantine: $quarantine,
        );

        $result = $service->synchronize(
            $mailbox
        );

        $this->assertSame(
            $mailbox->id,
            $result->mailboxId
        );

        $this->assertSame(
            $channel->id,
            $result->mailboxChannelId
        );

        $this->assertSame(
            MailboxDriver::Imap,
            $result->driver
        );

        $this->assertSame(
            1,
            $result->pages
        );

        $this->assertSame(
            0,
            $result->fetched
        );

        $this->assertSame(
            0,
            $result->stored
        );

        $this->assertSame(
            0,
            $result->duplicates
        );

        $this->assertSame(
            0,
            $result->acknowledged
        );

        $this->assertFalse(
            $result->truncated
        );

        $this->assertSame(
            '12',
            $result->nextCursor
        );

        $state = $channel
            ->syncState()
            ->firstOrFail();

        $this->assertSame(
            '12',
            $state->cursor
        );

        $this->assertSame(
            [
                'folder' => 'INBOX',
                'uidvalidity' => 1001,
                'quarantined_count' => 0,
            ],
            $state->cursor_metadata
        );

        $this->assertNotNull(
            $state->last_sync_started_at
        );

        $this->assertNotNull(
            $state->last_sync_completed_at
        );

        $this->assertNull(
            $state->last_sync_failed_at
        );

        $this->assertSame(
            0,
            $state->consecutive_failures
        );

        $this->assertSame(
            0,
            $state->last_fetched_count
        );

        $this->assertSame(
            0,
            $state->last_stored_count
        );

        $this->assertSame(
            0,
            $state->last_duplicate_count
        );

        $this->assertSame(
            0,
            $state->last_acknowledged_count
        );
    }

    public function test_existing_cursor_is_passed_to_fetcher(): void
    {
        $mailbox = $this->createMailbox();

        $channel = $this->createChannel(
            $mailbox
        );

        $state = $channel
            ->syncState()
            ->firstOrCreate([]);

        $state->forceFill([
            'cursor' => '40',

            'cursor_metadata' => [
                'folder' => 'INBOX',
                'uidvalidity' => 1001,
            ],

            'consecutive_failures' => 0,
        ])->save();

        $selector = $this->selectorWithChannels([
            $channel,
        ]);

        $fetcher = Mockery::mock(
            IncomingMailFetchService::class
        );

        $fetcher
            ->shouldReceive('fetch')
            ->once()
            ->withArgs(
                function (
                    MailboxChannel $argumentChannel,
                    ?IncomingCursorData $cursor,
                    int $limit,
                ) use ($channel): bool {
                    return $argumentChannel->id
                        === $channel->id
                        && $cursor instanceof IncomingCursorData
                        && $cursor->mailboxChannelId
                        === $channel->id
                        && $cursor->value === '40'
                        && $cursor->metadata === [
                            'folder' => 'INBOX',
                            'uidvalidity' => 1001,
                        ]
                        && $limit === 25;
                }
            )
            ->andReturn(
                new IncomingFetchResultData(
                    messages: [],
                    nextCursor: '45',
                    hasMore: false,
                    metadata: [
                        'folder' => 'INBOX',
                        'uidvalidity' => 1001,
                    ],
                )
            );

        $health = $this->successfulHealthRecorder(
            channel: $channel,
            hasActivity: false
        );

        $service = $this->service(
            selector: $selector,
            fetcher: $fetcher,
            persister: $this->unusedPersister(),
            acknowledger: $this->unusedAcknowledger(),
            health: $health,
            batchSize: 25,
            normalizationFailures: $this->unusedNormalizationFailures(),
            quarantine: $this->unusedQuarantine(),
        );

        $result = $service->synchronize(
            $mailbox
        );

        $this->assertSame(
            '45',
            $result->nextCursor
        );

        $state->refresh();

        $this->assertSame(
            '45',
            $state->cursor
        );

        $this->assertSame(
            [
                'folder' => 'INBOX',
                'uidvalidity' => 1001,
                'quarantined_count' => 0,
            ],
            $state->cursor_metadata
        );
    }

    public function test_fetch_failure_can_switch_to_fallback_channel_before_any_message_is_persisted(): void
    {
        $mailbox = $this->createMailbox();

        $primary = $this->createChannel(
            mailbox: $mailbox,
            name: 'Primary IMAP',
            primary: true,
            failoverOrder: 0,
        );

        $fallback = $this->createChannel(
            mailbox: $mailbox,
            name: 'Fallback IMAP',
            primary: false,
            failoverOrder: 10,
        );

        $selector = $this->selectorWithChannels([
            $primary,
            $fallback,
        ]);

        $fetcher = Mockery::mock(
            IncomingMailFetchService::class
        );

        $fetcher
            ->shouldReceive('fetch')
            ->once()
            ->ordered()
            ->withArgs(
                fn (
                    MailboxChannel $channel,
                    ?IncomingCursorData $cursor,
                    int $limit,
                ): bool => $channel->id === $primary->id
                    && $cursor === null
                    && $limit === 100
            )
            ->andThrow(
                new MailDriverException(
                    message: 'Primary IMAP connection failed.',

                    driverErrorCode: 'imap_connection_failed',

                    retryable: true,

                    failoverAllowed: true,

                    affectsChannelHealth: true,
                )
            );

        $fetcher
            ->shouldReceive('fetch')
            ->once()
            ->ordered()
            ->withArgs(
                fn (
                    MailboxChannel $channel,
                    ?IncomingCursorData $cursor,
                    int $limit,
                ): bool => $channel->id === $fallback->id
                    && $cursor === null
                    && $limit === 100
            )
            ->andReturn(
                new IncomingFetchResultData(
                    messages: [],
                    nextCursor: '5',
                    hasMore: false,
                    metadata: [
                        'folder' => 'INBOX',
                        'uidvalidity' => 2002,
                    ],
                )
            );

        $health = Mockery::mock(
            MailChannelHealthRecorder::class
        );

        $health
            ->shouldReceive('markFailure')
            ->once()
            ->withArgs(
                fn (
                    MailboxChannel $channel,
                    ?string $errorCode,
                    string $errorMessage,
                ): bool => $channel->id === $primary->id
                    && $errorCode
                    === 'imap_connection_failed'
                    && $errorMessage
                    === 'Primary IMAP connection failed.'
            );

        $health
            ->shouldReceive('markSuccess')
            ->once()
            ->withArgs(
                fn (
                    MailboxChannel $channel,
                    bool $hasActivity,
                ): bool => $channel->id === $fallback->id
                    && $hasActivity === false
            );

        $service = $this->service(
            selector: $selector,
            fetcher: $fetcher,
            persister: $this->unusedPersister(),
            acknowledger: $this->unusedAcknowledger(),
            health: $health,
            normalizationFailures: $this->unusedNormalizationFailures(),
            quarantine: $this->unusedQuarantine(),
        );

        $result = $service->synchronize(
            $mailbox
        );

        $this->assertSame(
            $fallback->id,
            $result->mailboxChannelId
        );

        $this->assertSame(
            '5',
            $result->nextCursor
        );

        $primaryState = $primary
            ->syncState()
            ->firstOrFail();

        $fallbackState = $fallback
            ->syncState()
            ->firstOrFail();

        $this->assertSame(
            1,
            $primaryState->consecutive_failures
        );

        $this->assertSame(
            'imap_connection_failed',
            $primaryState->last_error_code
        );

        $this->assertNotNull(
            $primaryState->last_sync_failed_at
        );

        $this->assertSame(
            0,
            $fallbackState->consecutive_failures
        );

        $this->assertSame(
            '5',
            $fallbackState->cursor
        );

        $this->assertNotNull(
            $fallbackState->last_sync_completed_at
        );
    }

    public function test_has_more_without_next_cursor_fails_sync(): void
    {
        $mailbox = $this->createMailbox();

        $channel = $this->createChannel(
            $mailbox
        );

        $selector = $this->selectorWithChannels([
            $channel,
        ]);

        $fetcher = Mockery::mock(
            IncomingMailFetchService::class
        );

        $fetcher
            ->shouldReceive('fetch')
            ->once()
            ->andReturn(
                new IncomingFetchResultData(
                    messages: [],
                    nextCursor: null,
                    hasMore: true,
                    metadata: [
                        'folder' => 'INBOX',
                        'uidvalidity' => 3003,
                    ],
                )
            );

        $health = Mockery::mock(
            MailChannelHealthRecorder::class
        );

        $health
            ->shouldReceive('markFailure')
            ->once()
            ->withArgs(
                fn (
                    MailboxChannel $argumentChannel,
                    ?string $errorCode,
                    string $errorMessage,
                ): bool => $argumentChannel->id
                    === $channel->id
                    && $errorCode
                    === 'missing_next_cursor'
                    && str_contains(
                        $errorMessage,
                        'without a next cursor'
                    )
            );

        $health->shouldNotReceive(
            'markSuccess'
        );

        $service = $this->service(
            selector: $selector,
            fetcher: $fetcher,
            persister: $this->unusedPersister(),
            acknowledger: $this->unusedAcknowledger(),
            health: $health,
            normalizationFailures: $this->unusedNormalizationFailures(),
            quarantine: $this->unusedQuarantine(),
        );

        try {
            $service->synchronize(
                $mailbox
            );

            $this->fail(
                'Expected AllMailChannelsFailedException was not thrown.'
            );
        } catch (
            AllMailChannelsFailedException $exception
        ) {
            $this->assertNotSame(
                '',
                trim($exception->getMessage())
            );
        }

        $state = $channel
            ->syncState()
            ->firstOrFail();

        $this->assertSame(
            1,
            $state->consecutive_failures
        );

        $this->assertSame(
            'missing_next_cursor',
            $state->last_error_code
        );

        $this->assertNotNull(
            $state->last_sync_failed_at
        );

        $this->assertNull(
            $state->last_sync_completed_at
        );
    }

    public function test_sync_is_truncated_when_page_limit_is_reached(): void
    {
        $mailbox = $this->createMailbox();

        $channel = $this->createChannel(
            $mailbox
        );

        $selector = $this->selectorWithChannels([
            $channel,
        ]);

        $fetcher = Mockery::mock(
            IncomingMailFetchService::class
        );

        $fetcher
            ->shouldReceive('fetch')
            ->once()
            ->ordered()
            ->withArgs(
                fn (
                    MailboxChannel $argumentChannel,
                    ?IncomingCursorData $cursor,
                    int $limit,
                ): bool => $argumentChannel->id
                    === $channel->id
                    && $cursor === null
                    && $limit === 10
            )
            ->andReturn(
                new IncomingFetchResultData(
                    messages: [],
                    nextCursor: '10',
                    hasMore: true,
                    metadata: [
                        'folder' => 'INBOX',
                        'uidvalidity' => 4004,
                    ],
                )
            );

        $fetcher
            ->shouldReceive('fetch')
            ->once()
            ->ordered()
            ->withArgs(
                fn (
                    MailboxChannel $argumentChannel,
                    ?IncomingCursorData $cursor,
                    int $limit,
                ): bool => $argumentChannel->id
                    === $channel->id
                    && $cursor
                    instanceof IncomingCursorData
                    && $cursor->value === '10'
                    && $limit === 10
            )
            ->andReturn(
                new IncomingFetchResultData(
                    messages: [],
                    nextCursor: '20',
                    hasMore: true,
                    metadata: [
                        'folder' => 'INBOX',
                        'uidvalidity' => 4004,
                    ],
                )
            );

        $health = $this->successfulHealthRecorder(
            channel: $channel,
            hasActivity: false
        );

        $service = $this->service(
            selector: $selector,
            fetcher: $fetcher,
            persister: $this->unusedPersister(),
            acknowledger: $this->unusedAcknowledger(),
            health: $health,
            batchSize: 10,
            maxPagesPerRun: 2,
            normalizationFailures: $this->unusedNormalizationFailures(),
            quarantine: $this->unusedQuarantine(),
        );

        $result = $service->synchronize(
            $mailbox
        );

        $this->assertSame(
            2,
            $result->pages
        );

        $this->assertTrue(
            $result->truncated
        );

        $this->assertSame(
            '20',
            $result->nextCursor
        );

        $state = $channel
            ->syncState()
            ->firstOrFail();

        $this->assertSame(
            '20',
            $state->cursor
        );

        $this->assertSame(
            [
                'folder' => 'INBOX',
                'uidvalidity' => 4004,
                'quarantined_count' => 0,
            ],
            $state->cursor_metadata
        );

        $this->assertNotNull(
            $state->last_sync_completed_at
        );
    }

    private function service(
        MailChannelSelector $selector,
        IncomingMailFetchService $fetcher,
        IncomingEmailMessagePersister $persister,
        IncomingMailAcknowledger $acknowledger,
        MailChannelHealthRecorder $health,
        int $batchSize = 100,
        int $maxPagesPerRun = 5,
        ?InboundNormalizationFailurePersister $normalizationFailures = null,
        ?EmailMessageQuarantineService $quarantine = null,
    ): IncomingMailboxSyncService {
        $normalizationFailures ??= Mockery::mock(
            InboundNormalizationFailurePersister::class
        );

        $quarantine ??= Mockery::mock(
            EmailMessageQuarantineService::class
        );

        return new IncomingMailboxSyncService(
            selector: $selector,
            fetcher: $fetcher,
            persister: $persister,
            normalizationFailures: $normalizationFailures,
            quarantine: $quarantine,
            acknowledger: $acknowledger,
            health: $health,
            batchSize: $batchSize,
            maxPagesPerRun: $maxPagesPerRun,
            defaultAction: IncomingAcknowledgeAction::Keep,
        );
    }

    private function selectorWithChannels(
        array $channels
    ): MailChannelSelector {
        $selector = Mockery::mock(
            MailChannelSelector::class
        );

        $selector
            ->shouldReceive('incomingCandidates')
            ->once()
            ->andReturn(
                collect($channels)
            );

        return $selector;
    }

    private function unusedPersister(): IncomingEmailMessagePersister
    {
        $persister = Mockery::mock(
            IncomingEmailMessagePersister::class
        );

        $persister->shouldNotReceive(
            'persist'
        );

        return $persister;
    }

    private function unusedNormalizationFailures(): InboundNormalizationFailurePersister
    {
        $persister = Mockery::mock(
            InboundNormalizationFailurePersister::class
        );

        return $persister;
    }

    private function unusedQuarantine(): EmailMessageQuarantineService
    {
        $quarantine = Mockery::mock(
            EmailMessageQuarantineService::class
        );

        return $quarantine;
    }

    private function unusedAcknowledger(): IncomingMailAcknowledger
    {
        $acknowledger = Mockery::mock(
            IncomingMailAcknowledger::class
        );

        $acknowledger->shouldNotReceive(
            'acknowledgeMany'
        );

        return $acknowledger;
    }

    private function successfulHealthRecorder(
        MailboxChannel $channel,
        bool $hasActivity,
    ): MailChannelHealthRecorder {
        $health = Mockery::mock(
            MailChannelHealthRecorder::class
        );

        $health
            ->shouldReceive('markSuccess')
            ->once()
            ->withArgs(
                fn (
                    MailboxChannel $argumentChannel,
                    bool $argumentHasActivity,
                ): bool => $argumentChannel->id
                    === $channel->id
                    && $argumentHasActivity
                    === $hasActivity
            );

        $health->shouldNotReceive(
            'markFailure'
        );

        return $health;
    }

    private function createMailbox(): Mailbox
    {
        $token = strtolower(
            (string) Str::ulid()
        );

        return Mailbox::query()->create([
            'name' => "Incoming Sync Mailbox {$token}",

            'email_address' => "incoming-sync-{$token}@example.test",

            'display_name' => 'Incoming Sync Mailbox',

            'department_id' => null,

            'is_active' => true,

            'is_default_outgoing' => false,

            'internal_notes' => null,
        ]);
    }

    private function createChannel(
        Mailbox $mailbox,
        string $name = 'Incoming IMAP',
        bool $primary = true,
        int $failoverOrder = 0,
    ): MailboxChannel {
        $token = strtolower(
            (string) Str::ulid()
        );

        $channel = new MailboxChannel;

        $channel->forceFill([
            'mailbox_id' => $mailbox->id,

            'provider_connection_id' => null,

            'name' => "{$name} {$token}",

            'direction' => MailboxChannelDirection::Incoming,

            'driver' => MailboxDriver::Imap,

            'is_primary' => $primary,

            'failover_order' => $failoverOrder,

            'is_enabled' => true,

            'configuration' => [
                'post_fetch_action' => IncomingAcknowledgeAction::Keep->value,
            ],

            'health_status' => MailboxHealthStatus::Unknown,
        ])->save();

        return $channel->fresh();
    }
}
