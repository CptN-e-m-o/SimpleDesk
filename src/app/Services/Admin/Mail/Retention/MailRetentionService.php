<?php

namespace App\Services\Admin\Mail\Retention;

use App\Data\Admin\Mail\MailRetentionResultData;
use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Models\Admin\Mail\EmailAttachment;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\EmailMessageAttempt;
use App\Models\Admin\Mail\EmailMessageQuarantine;
use App\Models\Ticket;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Throwable;

class MailRetentionService
{
    public const CATEGORY_RAW_MESSAGES = 'raw_messages';
    public const CATEGORY_CLEAN_ATTACHMENTS = 'clean_attachments';
    public const CATEGORY_QUARANTINED_ATTACHMENTS = 'quarantined_attachments';
    public const CATEGORY_ATTEMPTS = 'attempts';
    public const CATEGORY_QUARANTINES = 'quarantines';
    public const CATEGORY_MESSAGES = 'messages';
    public const CATEGORY_AUDIT = 'audit';

    public function __construct(
        private readonly FilesystemFactory $filesystem,
    ) {
    }

    public static function categories(): array
    {
        return [
            self::CATEGORY_RAW_MESSAGES,
            self::CATEGORY_CLEAN_ATTACHMENTS,
            self::CATEGORY_QUARANTINED_ATTACHMENTS,
            self::CATEGORY_ATTEMPTS,
            self::CATEGORY_QUARANTINES,
            self::CATEGORY_MESSAGES,
            self::CATEGORY_AUDIT,
        ];
    }

    public function prune(
        array $categories,
        bool $dryRun,
        int $limit,
        ?CarbonImmutable $before = null,
    ): MailRetentionResultData {
        $startedAt = CarbonImmutable::now();
        $stats = [];

        foreach ($categories as $category) {
            $stats[$category] = $this->emptyStats(
                $this->cutoff(
                    category: $category,
                    override: $before,
                )
            );
        }

        foreach ($categories as $category) {
            if (!$this->categoryEnabled($category)) {
                $stats[$category]['note'] = 'disabled by configuration';

                continue;
            }

            $cutoff = $stats[$category]['cutoff'];

            match ($category) {
                self::CATEGORY_RAW_MESSAGES => $this->pruneRawMessages(
                    stats: $stats[$category],
                    cutoff: $cutoff,
                    dryRun: $dryRun,
                    limit: $limit,
                ),
                self::CATEGORY_CLEAN_ATTACHMENTS => $this->pruneCleanAttachments(
                    stats: $stats[$category],
                    cutoff: $cutoff,
                    dryRun: $dryRun,
                    limit: $limit,
                ),
                self::CATEGORY_QUARANTINED_ATTACHMENTS => $this->pruneQuarantinedAttachments(
                    stats: $stats[$category],
                    cutoff: $cutoff,
                    dryRun: $dryRun,
                    limit: $limit,
                ),
                self::CATEGORY_ATTEMPTS => $this->pruneAttempts(
                    stats: $stats[$category],
                    cutoff: $cutoff,
                    dryRun: $dryRun,
                    limit: $limit,
                ),
                self::CATEGORY_QUARANTINES => $this->pruneResolvedQuarantines(
                    stats: $stats[$category],
                    cutoff: $cutoff,
                    dryRun: $dryRun,
                    limit: $limit,
                ),
                self::CATEGORY_MESSAGES => $this->pruneMessages(
                    stats: $stats[$category],
                    cutoff: $cutoff,
                    dryRun: $dryRun,
                    limit: $limit,
                ),
                self::CATEGORY_AUDIT => $this->pruneAudit(
                    stats: $stats[$category],
                    cutoff: $cutoff,
                    dryRun: $dryRun,
                    limit: $limit,
                ),
                default => null,
            };
        }

        foreach ($stats as &$categoryStats) {
            $categoryStats['cutoff'] = $categoryStats['cutoff']->toIso8601String();
        }
        unset($categoryStats);

        return new MailRetentionResultData(
            dryRun: $dryRun,
            startedAt: $startedAt,
            completedAt: CarbonImmutable::now(),
            categories: $stats,
        );
    }

    private function pruneRawMessages(
        array &$stats,
        CarbonImmutable $cutoff,
        bool $dryRun,
        int $limit,
    ): void {
        $query = EmailMessage::query()
            ->whereNotNull('raw_message_disk')
            ->whereNotNull('raw_message_path')
            ->where('created_at', '<=', $cutoff)
            ->whereIn('status', $this->terminalStatuses());

        $this->applySafeMessageScope(
            query: $query,
            quarantineCutoff: $cutoff,
        );

        $messages = $query
            ->orderBy('id')
            ->limit($limit)
            ->get([
                'id',
                'raw_message_disk',
                'raw_message_path',
                'raw_message_size',
            ]);

        foreach ($messages as $message) {
            $stats['candidates']++;
            $size = max(
                0,
                (int) $message->raw_message_size
            );

            if ($dryRun) {
                $stats['bytes'] += $size;

                continue;
            }

            try {
                $file = $this->deleteFile(
                    disk: (string) $message->raw_message_disk,
                    path: (string) $message->raw_message_path,
                );

                $stats['files_pruned'] += $file['deleted'];
                $stats['missing_files'] += $file['missing'];
                $stats['bytes'] += $file['deleted'] === 1
                    ? $size
                    : 0;

                EmailMessage::query()
                    ->whereKey($message->id)
                    ->update([
                        'raw_message_disk' => null,
                        'raw_message_path' => null,
                        'raw_message_size' => null,
                        'raw_message_checksum' => null,
                    ]);

                $stats['records_pruned']++;
            } catch (Throwable $exception) {
                $stats['errors']++;
                report($exception);
            }
        }
    }

    private function pruneCleanAttachments(
        array &$stats,
        CarbonImmutable $cutoff,
        bool $dryRun,
        int $limit,
    ): void {
        $attachments = EmailAttachment::query()
            ->where(
                'scan_status',
                EmailAttachmentScanStatus::Clean->value
            )
            ->where('created_at', '<=', $cutoff)
            ->whereHas(
                'emailMessage',
                function (Builder $query) use ($cutoff): void {
                    $query->whereIn(
                        'status',
                        $this->terminalStatuses()
                    );

                    $this->applySafeMessageScope(
                        query: $query,
                        quarantineCutoff: $cutoff,
                    );
                }
            )
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $this->pruneAttachments(
            attachments: $attachments,
            stats: $stats,
            dryRun: $dryRun,
        );
    }

    private function pruneQuarantinedAttachments(
        array &$stats,
        CarbonImmutable $cutoff,
        bool $dryRun,
        int $limit,
    ): void {
        $attachments = EmailAttachment::query()
            ->whereIn(
                'scan_status',
                [
                    EmailAttachmentScanStatus::Infected->value,
                    EmailAttachmentScanStatus::Failed->value,
                ]
            )
            ->where('created_at', '<=', $cutoff)
            ->whereExists(
                fn (QueryBuilder $query): QueryBuilder => $query
                    ->selectRaw('1')
                    ->from('email_message_quarantines as retention_quarantine')
                    ->whereColumn(
                        'retention_quarantine.email_message_id',
                        'email_attachments.email_message_id'
                    )
                    ->whereNotNull('retention_quarantine.resolved_at')
                    ->where(
                        'retention_quarantine.resolved_at',
                        '<=',
                        $cutoff
                    )
            )
            ->whereHas(
                'emailMessage',
                function (Builder $query) use ($cutoff): void {
                    $query->whereIn(
                        'status',
                        $this->terminalStatuses()
                    );

                    $this->applySafeMessageScope(
                        query: $query,
                        quarantineCutoff: $cutoff,
                    );
                }
            )
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $this->pruneAttachments(
            attachments: $attachments,
            stats: $stats,
            dryRun: $dryRun,
        );
    }

    private function pruneAttachments(
        iterable $attachments,
        array &$stats,
        bool $dryRun,
    ): void {
        foreach ($attachments as $attachment) {
            $stats['candidates']++;
            $size = max(
                0,
                (int) $attachment->size
            );

            if ($dryRun) {
                $stats['bytes'] += $size;

                continue;
            }

            try {
                $file = $this->deleteFile(
                    disk: $attachment->disk,
                    path: $attachment->path,
                );

                $stats['files_pruned'] += $file['deleted'];
                $stats['missing_files'] += $file['missing'];
                $stats['bytes'] += $file['deleted'] === 1
                    ? $size
                    : 0;

                EmailAttachment::query()
                    ->whereKey($attachment->id)
                    ->delete();

                $stats['records_pruned']++;
            } catch (Throwable $exception) {
                $stats['errors']++;
                report($exception);
            }
        }
    }

    private function pruneAttempts(
        array &$stats,
        CarbonImmutable $cutoff,
        bool $dryRun,
        int $limit,
    ): void {
        $ids = EmailMessageAttempt::query()
            ->where('created_at', '<=', $cutoff)
            ->whereHas(
                'emailMessage',
                function (Builder $query) use ($cutoff): void {
                    $query->whereIn(
                        'status',
                        $this->terminalStatuses()
                    );

                    $this->applySafeMessageScope(
                        query: $query,
                        quarantineCutoff: $cutoff,
                    );
                }
            )
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $stats['candidates'] += $ids->count();

        if ($dryRun || $ids->isEmpty()) {
            return;
        }

        $stats['records_pruned'] += EmailMessageAttempt::query()
            ->whereIn('id', $ids)
            ->delete();
    }

    private function pruneResolvedQuarantines(
        array &$stats,
        CarbonImmutable $cutoff,
        bool $dryRun,
        int $limit,
    ): void {
        $ids = EmailMessageQuarantine::query()
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '<=', $cutoff)
            ->whereHas(
                'emailMessage',
                function (Builder $query) use ($cutoff): void {
                    $query->whereIn(
                        'status',
                        $this->terminalStatuses()
                    );

                    $this->applySafeMessageScope(
                        query: $query,
                        quarantineCutoff: $cutoff,
                    );
                }
            )
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $stats['candidates'] += $ids->count();

        if ($dryRun || $ids->isEmpty()) {
            return;
        }

        $stats['records_pruned'] += EmailMessageQuarantine::query()
            ->whereIn('id', $ids)
            ->delete();
    }

    private function pruneMessages(
        array &$stats,
        CarbonImmutable $cutoff,
        bool $dryRun,
        int $limit,
    ): void {
        $messages = EmailMessage::query()
            ->with('attachments')
            ->whereNull('ticket_id')
            ->whereNull('ticket_reply_id')
            ->where('created_at', '<=', $cutoff)
            ->whereIn('status', $this->terminalStatuses())
            ->whereNotExists(
                fn (QueryBuilder $query): QueryBuilder => $query
                    ->selectRaw('1')
                    ->from('email_message_quarantines as retention_quarantine')
                    ->whereColumn(
                        'retention_quarantine.email_message_id',
                        'email_messages.id'
                    )
                    ->where(
                        fn (QueryBuilder $quarantineQuery): QueryBuilder => $quarantineQuery
                            ->whereNull('retention_quarantine.resolved_at')
                            ->orWhere(
                                'retention_quarantine.resolved_at',
                                '>',
                                $cutoff
                            )
                    )
            )
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($messages as $message) {
            $stats['candidates']++;

            $bytes = max(
                0,
                (int) $message->raw_message_size
            );

            foreach ($message->attachments as $attachment) {
                $bytes += max(
                    0,
                    (int) $attachment->size
                );
            }

            if ($dryRun) {
                $stats['bytes'] += $bytes;

                continue;
            }

            try {
                $deletedBytes = 0;
                if (
                    $message->raw_message_disk !== null
                    && $message->raw_message_path !== null
                ) {
                    $file = $this->deleteFile(
                        disk: $message->raw_message_disk,
                        path: $message->raw_message_path,
                    );

                    $stats['files_pruned'] += $file['deleted'];
                    $stats['missing_files'] += $file['missing'];
                    $deletedBytes += $file['deleted'] === 1
                        ? max(0, (int) $message->raw_message_size)
                        : 0;
                }

                foreach ($message->attachments as $attachment) {
                    $file = $this->deleteFile(
                        disk: $attachment->disk,
                        path: $attachment->path,
                    );

                    $stats['files_pruned'] += $file['deleted'];
                    $stats['missing_files'] += $file['missing'];
                    $deletedBytes += $file['deleted'] === 1
                        ? max(0, (int) $attachment->size)
                        : 0;
                }

                EmailMessage::query()
                    ->whereKey($message->id)
                    ->delete();

                $stats['records_pruned']++;
                $stats['bytes'] += $deletedBytes;
            } catch (Throwable $exception) {
                $stats['errors']++;
                report($exception);
            }
        }
    }

    private function pruneAudit(
        array &$stats,
        CarbonImmutable $cutoff,
        bool $dryRun,
        int $limit,
    ): void {
        $modelClass = config(
            'simpledesk-mail-automation.retention.categories.audit.model'
        );

        if (
            !is_string($modelClass)
            || trim($modelClass) === ''
        ) {
            $stats['note'] = 'audit model is not configured';

            return;
        }

        $modelClass = trim($modelClass);

        if (
            !class_exists($modelClass)
            || !is_subclass_of($modelClass, Model::class)
        ) {
            $stats['note'] = "audit model [{$modelClass}] was not found";

            return;
        }

        $timestampColumn = trim(
            (string) config(
                'simpledesk-mail-automation.retention.categories.audit.timestamp_column',
                'created_at'
            )
        );

        if ($timestampColumn === '') {
            $stats['note'] = 'audit timestamp column is empty';

            return;
        }

        $model = new $modelClass();
        $keyName = $model->getKeyName();

        $ids = $modelClass::query()
            ->where($timestampColumn, '<=', $cutoff)
            ->orderBy($keyName)
            ->limit($limit)
            ->pluck($keyName);

        $stats['candidates'] += $ids->count();

        if ($dryRun || $ids->isEmpty()) {
            return;
        }

        $stats['records_pruned'] += DB::table(
            $model->getTable()
        )
            ->whereIn($keyName, $ids)
            ->delete();
    }

    private function applySafeMessageScope(
        Builder $query,
        CarbonImmutable $quarantineCutoff,
    ): Builder {
        return $query
            ->where(
                fn (Builder $ticketQuery): Builder => $ticketQuery
                    ->where(
                        fn (Builder $unlinkedQuery): Builder => $unlinkedQuery
                            ->whereNull('ticket_id')
                            ->whereNull('ticket_reply_id')
                    )
                    ->orWhereHas(
                        'ticket',
                        fn (Builder $closedTicketQuery): Builder => $closedTicketQuery
                            ->where(
                                'status',
                                Ticket::STATUS_CLOSED
                            )
                    )
            )
            ->whereNotExists(
                fn (QueryBuilder $quarantineQuery): QueryBuilder => $quarantineQuery
                    ->selectRaw('1')
                    ->from('email_message_quarantines as retention_quarantine')
                    ->whereColumn(
                        'retention_quarantine.email_message_id',
                        'email_messages.id'
                    )
                    ->where(
                        fn (QueryBuilder $stateQuery): QueryBuilder => $stateQuery
                            ->whereNull('retention_quarantine.resolved_at')
                            ->orWhere(
                                'retention_quarantine.resolved_at',
                                '>',
                                $quarantineCutoff
                            )
                    )
            );
    }

    private function deleteFile(
        string $disk,
        string $path,
    ): array {
        $storage = $this->filesystem->disk($disk);

        if (!$storage->exists($path)) {
            return [
                'deleted' => 0,
                'missing' => 1,
            ];
        }

        if (!$storage->delete($path)) {
            throw new \RuntimeException(
                "Unable to delete retained mail file [{$disk}:{$path}]."
            );
        }

        return [
            'deleted' => 1,
            'missing' => 0,
        ];
    }

    private function cutoff(
        string $category,
        ?CarbonImmutable $override,
    ): CarbonImmutable {
        if ($override !== null) {
            return $override;
        }

        $days = max(
            1,
            (int) config(
                "simpledesk-mail-automation.retention.categories.{$category}.days",
                90
            )
        );

        return CarbonImmutable::now()
            ->subDays($days);
    }

    private function categoryEnabled(
        string $category
    ): bool {
        return (bool) config(
            "simpledesk-mail-automation.retention.categories.{$category}.enabled",
            true
        );
    }

    private function terminalStatuses(): array
    {
        return [
            EmailMessageStatus::Processed->value,
            EmailMessageStatus::Sent->value,
            EmailMessageStatus::Delivered->value,
            EmailMessageStatus::Failed->value,
            EmailMessageStatus::Rejected->value,
            EmailMessageStatus::Bounced->value,
            EmailMessageStatus::Complained->value,
        ];
    }

    private function emptyStats(
        CarbonImmutable $cutoff
    ): array {
        return [
            'cutoff' => $cutoff,
            'candidates' => 0,
            'records_pruned' => 0,
            'files_pruned' => 0,
            'missing_files' => 0,
            'bytes' => 0,
            'errors' => 0,
            'note' => null,
        ];
    }
}
