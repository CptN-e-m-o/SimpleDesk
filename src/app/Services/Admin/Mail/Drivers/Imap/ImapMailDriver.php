<?php

namespace App\Services\Admin\Mail\Drivers\Imap;

use App\Contracts\Admin\Mail\IncomingMailDriver;
use App\Data\Admin\Mail\ImapChannelConfigurationData;
use App\Data\Admin\Mail\IncomingCursorData;
use App\Data\Admin\Mail\IncomingFetchResultData;
use App\Data\Admin\Mail\MailConnectionTestResultData;
use App\Data\Admin\Mail\NormalizedInboundMessageData;
use App\Enums\Admin\Mail\ImapInitialSyncPolicy;
use App\Enums\Admin\Mail\IncomingAcknowledgeAction;
use App\Enums\Admin\Mail\MailboxDriver;
use App\Exceptions\Admin\Mail\MailDriverException;
use App\Models\Admin\Mail\MailboxChannel;
use Carbon\CarbonImmutable;
use Throwable;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Folder;

class ImapMailDriver implements IncomingMailDriver
{
    public function __construct(
        private readonly ImapChannelConfigurationFactory $configurationFactory,
        private readonly ImapClientFactory $clientFactory,
        private readonly ImapMessageNormalizer $normalizer,
        private readonly ImapExceptionMapper $exceptions,
    ) {
    }

    public function driver(): MailboxDriver
    {
        return MailboxDriver::Imap;
    }

    public function test(
        MailboxChannel $channel
    ): MailConnectionTestResultData {
        $configuration = $this
            ->configurationFactory
            ->make($channel);

        $client = $this
            ->clientFactory
            ->make($configuration);

        $startedAt = hrtime(true);

        try {
            $client->connect();

            $folderInfo = $client->checkFolder(
                $configuration->folder
            );

            $latencyMilliseconds = (int) round(
                (
                    hrtime(true) - $startedAt
                ) / 1_000_000
            );

            return MailConnectionTestResultData::success(
                message:
                'IMAP connection and authentication succeeded.',

                latencyMilliseconds:
                $latencyMilliseconds,

                details: [
                    'host' =>
                        $configuration->host,

                    'port' =>
                        $configuration->port,

                    'encryption' =>
                        $configuration
                            ->encryption
                            ->value,

                    'folder' =>
                        $configuration->folder,

                    'exists' =>
                        (int) (
                            $folderInfo['exists']
                            ?? 0
                        ),

                    'recent' =>
                        (int) (
                            $folderInfo['recent']
                            ?? 0
                        ),

                    'uidvalidity' =>
                        (int) (
                            $folderInfo['uidvalidity']
                            ?? 0
                        ),

                    'uidnext' =>
                        (int) (
                            $folderInfo['uidnext']
                            ?? 0
                        ),
                ],
            );
        } catch (Throwable $exception) {
            throw $this->exceptions->map(
                exception: $exception,
                operation: 'connection test',
            );
        } finally {
            $this->disconnect($client);
        }
    }

    public function fetch(
        MailboxChannel $channel,
        ?IncomingCursorData $cursor = null,
        int $limit = 100,
    ): IncomingFetchResultData {
        $configuration = $this
            ->configurationFactory
            ->make($channel);

        $client = $this
            ->clientFactory
            ->make($configuration);

        try {
            $client->connect();

            $folderInfo = $client->checkFolder(
                $configuration->folder
            );

            $uidValidity = (int) (
                $folderInfo['uidvalidity']
                ?? 0
            );

            $uidNext = max(
                1,
                (int) (
                    $folderInfo['uidnext']
                    ?? 1
                )
            );

            if ($uidValidity < 1) {
                throw new MailDriverException(
                    message:
                    'IMAP server did not return '
                    . 'a valid UIDVALIDITY.',

                    driverErrorCode:
                    'imap_invalid_uidvalidity',

                    retryable: true,
                    failoverAllowed: true,
                    affectsChannelHealth: true,
                );
            }

            [
                $lastUid,
                $cursorReset,
            ] = $this->resolveCursor(
                channel: $channel,
                cursor: $cursor,
                folder: $configuration->folder,
                uidValidity: $uidValidity,
            );

            $folder = $client->getFolder(
                $configuration->folder
            );

            if (!$folder instanceof Folder) {
                throw new MailDriverException(
                    message:
                    "IMAP folder "
                    . "[{$configuration->folder}] "
                    . 'was not found.',

                    driverErrorCode:
                    'imap_folder_not_found',

                    retryable: false,
                    failoverAllowed: true,
                    affectsChannelHealth: true,
                );
            }

            $initialSync = $this
                ->initialSyncContext(
                    channel: $channel,
                    cursor: $cursor,
                    cursorReset: $cursorReset,
                );

            $startUid = max(
                1,
                $lastUid + 1
            );

            $items = [];
            $hasMore = false;

            if (
                !(
                    $initialSync['active']
                    && $initialSync['policy']
                    === ImapInitialSyncPolicy::FromNow
                )
            ) {
                $query = $folder
                    ->messages()
                    ->whereUid(
                        "{$startUid}:*"
                    )
                    ->leaveUnread()
                    ->fetchOrderAsc();

                if ($initialSync['active']) {
                    match (
                    $initialSync['policy']
                    ) {
                        ImapInitialSyncPolicy::Unseen =>
                        $query->unseen(),

                        ImapInitialSyncPolicy::RecentDays =>
                        $query->since(
                            $initialSync['since']
                        ),

                        ImapInitialSyncPolicy::All,
                        ImapInitialSyncPolicy::FromNow =>
                        null,
                    };
                }

                $messages = $query
                    ->limit($limit + 1)
                    ->get();

                $items = array_values(
                    $messages->all()
                );

                $hasMore =
                    count($items) > $limit;

                if ($hasMore) {
                    $items = array_slice(
                        $items,
                        0,
                        $limit
                    );
                }
            }

            $normalizedMessages = [];
            $normalizationFailures = [];

            $largestUid = $lastUid;

            foreach ($items as $message) {
                $messageUid = (int) $message
                    ->getUid();

                if ($messageUid < 1) {
                    throw new MailDriverException(
                        message:
                        'IMAP message has no valid UID.',

                        driverErrorCode:
                        'imap_missing_message_uid',

                        retryable: true,
                        failoverAllowed: false,
                        affectsChannelHealth: true,
                    );
                }

                $largestUid = max(
                    $largestUid,
                    $messageUid
                );

                try {
                    $normalizedMessages[] =
                        $this
                            ->normalizer
                            ->normalize(
                                message: $message,

                                configuration:
                                $configuration,

                                uidValidity:
                                $uidValidity,
                            );
                } catch (Throwable $exception) {
                    $normalizationFailures[] =
                        $this
                            ->normalizer
                            ->failed(
                                message: $message,

                                configuration:
                                $configuration,

                                uidValidity:
                                $uidValidity,

                                exception:
                                $exception,
                            );
                }
            }

            $initialSyncCompleted =
                !$initialSync['active']
                || !$hasMore;

            $nextCursor = $this->nextCursor(
                currentUid: $lastUid,
                largestFetchedUid: $largestUid,
                uidNext: $uidNext,
                hasFetchedItems: $items !== [],
                hasMore: $hasMore,
                initialSyncActive:
                $initialSync['active'],
                initialSyncPolicy:
                $initialSync['policy'],
            );

            $metadata = [
                'folder' =>
                    $configuration->folder,

                'uidvalidity' =>
                    $uidValidity,

                'uidnext' =>
                    $uidNext,

                'cursor_reset' =>
                    $cursorReset,

                'normalized_count' =>
                    count(
                        $normalizedMessages
                    ),

                'normalization_failure_count' =>
                    count(
                        $normalizationFailures
                    ),

                'initial_sync_policy' =>
                    $initialSync['policy']->value,

                'initial_sync_completed' =>
                    $initialSyncCompleted,

                'initial_sync_started_at' =>
                    $initialSync['started_at'],

                'initial_sync_recent_days' =>
                    $initialSync['recent_days'],

                'initial_sync_since' =>
                    $initialSync['since']
                        ?->toIso8601String(),

                'initial_sync_skipped_existing' =>
                    $initialSync['active']
                    && $initialSync['policy']
                    === ImapInitialSyncPolicy::FromNow,
            ];

            if ($initialSyncCompleted) {
                $metadata[
                'initial_sync_completed_at'
                ] = now()->toIso8601String();
            } elseif (
                isset(
                    $cursor?->metadata[
                    'initial_sync_completed_at'
                    ]
                )
            ) {
                $metadata[
                'initial_sync_completed_at'
                ] = $cursor->metadata[
                'initial_sync_completed_at'
                ];
            }

            return new IncomingFetchResultData(
                messages:
                $normalizedMessages,

                nextCursor:
                (string) $nextCursor,

                hasMore:
                $hasMore,

                metadata:
                $metadata,

                failures:
                $normalizationFailures,
            );
        } catch (Throwable $exception) {
            throw $this->exceptions->map(
                exception: $exception,
                operation: 'fetch',
            );
        } finally {
            $this->disconnect($client);
        }
    }

    public function acknowledge(
        MailboxChannel $channel,
        NormalizedInboundMessageData $message,
        IncomingAcknowledgeAction $action,
    ): void {
        $this->acknowledgeMany(
            channel: $channel,
            messages: [$message],
            action: $action,
        );
    }

    public function acknowledgeMany(
        MailboxChannel $channel,
        array $messages,
        IncomingAcknowledgeAction $action,
    ): int {
        if ($messages === []) {
            return 0;
        }

        foreach ($messages as $message) {
            if (
                !$message
                    instanceof NormalizedInboundMessageData
            ) {
                throw new MailDriverException(
                    message:
                    'IMAP acknowledgement received '
                    . 'an invalid message object.',

                    driverErrorCode:
                    'imap_invalid_acknowledgement',

                    retryable: false,
                    failoverAllowed: false,
                    affectsChannelHealth: false,
                );
            }
        }

        if (
            $action
            === IncomingAcknowledgeAction::Keep
        ) {
            return count($messages);
        }

        $configuration = $this
            ->configurationFactory
            ->make($channel);

        $client = $this
            ->clientFactory
            ->make($configuration);

        try {
            $client->connect();

            $folderInfo = $client->checkFolder(
                $configuration->folder
            );

            $currentUidValidity = (int) (
                $folderInfo['uidvalidity']
                ?? 0
            );

            $this->assertUidValidity(
                messages: $messages,
                currentUidValidity:
                $currentUidValidity,
            );

            $folder = $client->getFolder(
                $configuration->folder
            );

            if (!$folder instanceof Folder) {
                throw new MailDriverException(
                    message:
                    "IMAP folder "
                    . "[{$configuration->folder}] "
                    . 'was not found.',

                    driverErrorCode:
                    'imap_folder_not_found',

                    retryable: false,
                    failoverAllowed: true,
                    affectsChannelHealth: true,
                );
            }

            if (
                $action
                === IncomingAcknowledgeAction::Move
            ) {
                $this->ensureProcessedFolder(
                    client: $client,
                    configuration: $configuration,
                );
            }

            $acknowledged = 0;

            foreach ($messages as $message) {
                $uid = (int) (
                    $message->metadata['imap_uid']
                    ?? 0
                );

                if ($uid < 1) {
                    throw new MailDriverException(
                        message:
                        'Normalized IMAP message '
                        . 'does not contain a valid UID.',

                        driverErrorCode:
                        'imap_missing_message_uid',

                        retryable: false,
                        failoverAllowed: false,
                        affectsChannelHealth: false,
                    );
                }

                try {
                    $imapMessage = $folder
                        ->messages()
                        ->getMessageByUid($uid);
                } catch (Throwable $exception) {
                    if (
                        $this
                            ->exceptions
                            ->isMessageNotFound(
                                $exception
                            )
                    ) {
                        $acknowledged++;

                        continue;
                    }

                    throw $exception;
                }

                match ($action) {
                    IncomingAcknowledgeAction::Keep =>
                    null,

                    IncomingAcknowledgeAction::MarkRead =>
                    $imapMessage->setFlag(
                        'Seen'
                    ),

                    IncomingAcknowledgeAction::Move =>
                    $imapMessage->move(
                        $configuration
                            ->processedFolder
                    ),

                    IncomingAcknowledgeAction::Delete =>
                    $imapMessage->delete(
                        $configuration
                            ->expungeOnDelete
                    ),
                };

                $acknowledged++;
            }

            return $acknowledged;
        } catch (Throwable $exception) {
            throw $this->exceptions->map(
                exception: $exception,
                operation: 'acknowledgement',
            );
        } finally {
            $this->disconnect($client);
        }
    }

    /**
     * @return array{
     *     active: bool,
     *     policy: ImapInitialSyncPolicy,
     *     recent_days: int,
     *     since: ?CarbonImmutable,
     *     started_at: string
     * }
     */
    private function initialSyncContext(
        MailboxChannel $channel,
        ?IncomingCursorData $cursor,
        bool $cursorReset,
    ): array {
        $cursorMetadata =
            $cursor?->metadata
            ?? [];

        if (
            $cursor === null
            || $cursorReset
        ) {
            $active = true;
        } elseif (
            array_key_exists(
                'initial_sync_completed',
                $cursorMetadata
            )
        ) {
            $active = !$this->booleanValue(
                value:
                $cursorMetadata[
                'initial_sync_completed'
                ],

                default: true,
            );
        } else {
            $active = false;
        }

        $policyValue = $active
            ? (
                $cursorMetadata[
                'initial_sync_policy'
                ]
                ?? $channel->configuration[
            'initial_sync_policy'
            ]
                ?? config(
                    'simpledesk-mail-imap.initial_sync_policy',
                    'from_now'
                )
            )
            : (
                $cursorMetadata[
                'initial_sync_policy'
                ]
                ?? $channel->configuration[
            'initial_sync_policy'
            ]
                ?? config(
                    'simpledesk-mail-imap.initial_sync_policy',
                    'from_now'
                )
            );

        $policy =
            ImapInitialSyncPolicy::resolve(
                $policyValue
            );

        $recentDays = $this->recentDays(
            channel: $channel,
            cursorMetadata: $cursorMetadata,
        );

        $since = null;

        if (
            $policy
            === ImapInitialSyncPolicy::RecentDays
        ) {
            $since = $this->initialSyncSince(
                cursorMetadata:
                $cursorMetadata,

                recentDays:
                $recentDays,
            );
        }

        $startedAt =
            isset(
                $cursorMetadata[
                'initial_sync_started_at'
                ]
            )
            && is_string(
                $cursorMetadata[
                'initial_sync_started_at'
                ]
            )
            && trim(
                $cursorMetadata[
                'initial_sync_started_at'
                ]
            ) !== ''
                ? $cursorMetadata[
            'initial_sync_started_at'
            ]
                : now()->toIso8601String();

        return [
            'active' => $active,
            'policy' => $policy,
            'recent_days' => $recentDays,
            'since' => $since,
            'started_at' => $startedAt,
        ];
    }

    private function recentDays(
        MailboxChannel $channel,
        array $cursorMetadata,
    ): int {
        $value =
            $cursorMetadata[
            'initial_sync_recent_days'
            ]
            ?? $channel->configuration[
        'initial_sync_recent_days'
        ]
            ?? config(
                'simpledesk-mail-imap.initial_sync_recent_days',
                7
            );

        return max(
            1,
            min(
                3650,
                (int) $value
            )
        );
    }

    private function initialSyncSince(
        array $cursorMetadata,
        int $recentDays,
    ): CarbonImmutable {
        $storedValue =
            $cursorMetadata[
            'initial_sync_since'
            ] ?? null;

        if (
            is_string($storedValue)
            && trim($storedValue) !== ''
        ) {
            try {
                return CarbonImmutable::parse(
                    $storedValue
                );
            } catch (Throwable) {
                //
            }
        }

        return CarbonImmutable::now()
            ->subDays($recentDays)
            ->startOfDay();
    }

    private function nextCursor(
        int $currentUid,
        int $largestFetchedUid,
        int $uidNext,
        bool $hasFetchedItems,
        bool $hasMore,
        bool $initialSyncActive,
        ImapInitialSyncPolicy $initialSyncPolicy,
    ): int {
        if (
            $initialSyncActive
            && $hasMore
        ) {
            return max(
                $currentUid,
                $largestFetchedUid
            );
        }

        if (
            $initialSyncActive
            && in_array(
                $initialSyncPolicy,
                [
                    ImapInitialSyncPolicy::FromNow,
                    ImapInitialSyncPolicy::Unseen,
                    ImapInitialSyncPolicy::RecentDays,
                ],
                true,
            )
        ) {
            return max(
                $currentUid,
                $largestFetchedUid,
                $uidNext - 1
            );
        }

        if ($hasFetchedItems) {
            return max(
                $currentUid,
                $largestFetchedUid
            );
        }

        return max(
            $currentUid,
            $uidNext - 1
        );
    }

    private function booleanValue(
        mixed $value,
        bool $default,
    ): bool {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        if (is_string($value)) {
            return match (
            strtolower(
                trim($value)
            )
            ) {
                '1',
                'true',
                'yes',
                'on' => true,

                '0',
                'false',
                'no',
                'off' => false,

                default => $default,
            };
        }

        return $default;
    }

    private function resolveCursor(
        MailboxChannel $channel,
        ?IncomingCursorData $cursor,
        string $folder,
        int $uidValidity,
    ): array {
        if ($cursor === null) {
            return [
                0,
                false,
            ];
        }

        if (
            $cursor->mailboxChannelId
            !== $channel->id
        ) {
            throw new MailDriverException(
                message:
                'IMAP cursor belongs to another channel.',

                driverErrorCode:
                'imap_invalid_cursor_channel',

                retryable: false,
                failoverAllowed: false,
                affectsChannelHealth: false,
            );
        }

        $storedUidValidity =
            $cursor->metadata['uidvalidity']
            ?? null;

        $storedFolder =
            $cursor->metadata['folder']
            ?? null;

        if (
            $storedUidValidity !== null
            && (int) $storedUidValidity
            !== $uidValidity
        ) {
            return [
                0,
                true,
            ];
        }

        if (
            $storedFolder !== null
            && (string) $storedFolder
            !== $folder
        ) {
            return [
                0,
                true,
            ];
        }

        if (!ctype_digit($cursor->value)) {
            throw new MailDriverException(
                message:
                'IMAP cursor must contain '
                . 'a numeric UID.',

                driverErrorCode:
                'imap_invalid_cursor',

                retryable: false,
                failoverAllowed: false,
                affectsChannelHealth: false,
            );
        }

        return [
            (int) $cursor->value,
            false,
        ];
    }

    private function assertUidValidity(
        array $messages,
        int $currentUidValidity,
    ): void {
        foreach ($messages as $message) {
            $messageUidValidity = (int) (
                $message->metadata[
                'imap_uidvalidity'
                ]
                ?? 0
            );

            if (
                $messageUidValidity < 1
                || $messageUidValidity
                !== $currentUidValidity
            ) {
                throw new MailDriverException(
                    message:
                    'IMAP UIDVALIDITY changed before '
                    . 'the message was acknowledged.',

                    driverErrorCode:
                    'imap_uidvalidity_changed',

                    retryable: true,
                    failoverAllowed: false,
                    affectsChannelHealth: true,
                );
            }
        }
    }

    private function ensureProcessedFolder(
        Client $client,
        ImapChannelConfigurationData $configuration,
    ): void {
        if (
            $configuration->processedFolder
            === null
        ) {
            throw new MailDriverException(
                message:
                'Processed folder is required '
                . 'when post_fetch_action is move.',

                driverErrorCode:
                'imap_processed_folder_missing',

                retryable: false,
                failoverAllowed: false,
                affectsChannelHealth: false,
            );
        }

        try {
            $folder = $client->getFolder(
                $configuration->processedFolder
            );

            if ($folder instanceof Folder) {
                return;
            }
        } catch (Throwable $exception) {
            if (
                !$this
                    ->exceptions
                    ->isFolderNotFound(
                        $exception
                    )
            ) {
                throw $exception;
            }
        }

        if (
            !$configuration
                ->createProcessedFolder
        ) {
            throw new MailDriverException(
                message:
                "IMAP folder "
                . "[{$configuration->processedFolder}] "
                . 'does not exist.',

                driverErrorCode:
                'imap_processed_folder_not_found',

                retryable: false,
                failoverAllowed: false,
                affectsChannelHealth: false,
            );
        }

        $client->createFolder(
            $configuration->processedFolder,
            false,
        );
    }

    private function disconnect(
        Client $client
    ): void {
        try {
            if ($client->isConnected()) {
                $client->disconnect();
            }
        } catch (Throwable) {
            //
        }
    }
}
