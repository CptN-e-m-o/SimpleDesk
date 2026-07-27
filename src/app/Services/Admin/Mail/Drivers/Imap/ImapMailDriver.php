<?php

namespace App\Services\Admin\Mail\Drivers\Imap;

use App\Contracts\Admin\Mail\IncomingMailDriver;
use App\Data\Admin\Mail\IncomingCursorData;
use App\Data\Admin\Mail\IncomingFetchResultData;
use App\Data\Admin\Mail\MailConnectionTestResultData;
use App\Data\Admin\Mail\NormalizedInboundMessageData;
use App\Enums\Admin\Mail\IncomingAcknowledgeAction;
use App\Enums\Admin\Mail\MailboxDriver;
use App\Exceptions\Admin\Mail\MailDriverException;
use App\Models\Admin\Mail\MailboxChannel;
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
                    'host' => $configuration->host,
                    'port' => $configuration->port,
                    'encryption' =>
                        $configuration->encryption->value,
                    'folder' => $configuration->folder,
                    'exists' =>
                        (int) ($folderInfo['exists'] ?? 0),
                    'recent' =>
                        (int) ($folderInfo['recent'] ?? 0),
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

            $uidNext = (int) (
                $folderInfo['uidnext']
                ?? 1
            );

            if ($uidValidity < 1) {
                throw new MailDriverException(
                    message:
                    'IMAP server did not return a valid UIDVALIDITY.',
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
                    "IMAP folder [{$configuration->folder}] "
                    . 'was not found.',
                    driverErrorCode:
                    'imap_folder_not_found',
                    retryable: false,
                    failoverAllowed: true,
                    affectsChannelHealth: true,
                );
            }

            $startUid = max(
                1,
                $lastUid + 1
            );

            $messages = $folder
                ->messages()
                ->whereUid(
                    "{$startUid}:*"
                )
                ->leaveUnread()
                ->fetchOrderAsc()
                ->limit($limit + 1)
                ->get();

            $items = array_values(
                $messages->all()
            );

            $hasMore = count($items) > $limit;

            if ($hasMore) {
                $items = array_slice(
                    $items,
                    0,
                    $limit
                );
            }

            $normalizedMessages = [];
            $largestUid = $lastUid;

            foreach ($items as $message) {
                $messageUid = (int) $message->getUid();

                $largestUid = max(
                    $largestUid,
                    $messageUid
                );

                $normalizedMessages[] =
                    $this->normalizer->normalize(
                        message: $message,
                        configuration: $configuration,
                        uidValidity: $uidValidity,
                    );
            }

            /*
             * Если новых писем не было, всё равно сохраняем
             * значение UIDNEXT - 1. Это позволяет записать
             * актуальный UIDVALIDITY и не начинать каждый раз
             * с первого письма.
             */
            $nextCursor = $normalizedMessages !== []
                ? $largestUid
                : max(
                    $lastUid,
                    $uidNext - 1
                );

            return new IncomingFetchResultData(
                messages: $normalizedMessages,
                nextCursor: (string) $nextCursor,
                hasMore: $hasMore,
                metadata: [
                    'folder' =>
                        $configuration->folder,
                    'uidvalidity' => $uidValidity,
                    'uidnext' => $uidNext,
                    'cursor_reset' => $cursorReset,
                ],
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

        if ($action === IncomingAcknowledgeAction::Keep) {
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
                    "IMAP folder [{$configuration->folder}] "
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
                    /*
                     * После move/delete сообщение может уже
                     * отсутствовать при повторной попытке.
                     * Такое подтверждение считаем успешным.
                     */
                    if (
                        $this->exceptions
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
                    IncomingAcknowledgeAction::Keep => null,

                    IncomingAcknowledgeAction::MarkRead =>
                    $imapMessage->setFlag('Seen'),

                    IncomingAcknowledgeAction::Move =>
                    $imapMessage->move(
                        $configuration->processedFolder
                    ),

                    IncomingAcknowledgeAction::Delete =>
                    $imapMessage->delete(
                        $configuration->expungeOnDelete
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
            && (string) $storedFolder !== $folder
        ) {
            return [
                0,
                true,
            ];
        }

        if (
            !ctype_digit($cursor->value)
        ) {
            throw new MailDriverException(
                message:
                'IMAP cursor must contain a numeric UID.',
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
                $message->metadata['imap_uidvalidity']
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
            $configuration->processedFolder === null
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
                !$this->exceptions
                    ->isFolderNotFound($exception)
            ) {
                throw $exception;
            }
        }

        if (
            !$configuration->createProcessedFolder
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
