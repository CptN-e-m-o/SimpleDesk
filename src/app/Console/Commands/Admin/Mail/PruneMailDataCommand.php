<?php

namespace App\Console\Commands\Admin\Mail;

use App\Services\Admin\Mail\Retention\MailRetentionService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

class PruneMailDataCommand extends Command
{
    protected $signature = 'simpledesk:mail:prune
        {--category=* : Categories: all, raw_messages, clean_attachments, quarantined_attachments, attempts, quarantines, messages, audit}
        {--before= : Override every retention cutoff with a parseable date/time}
        {--limit= : Maximum records processed per category}
        {--dry-run : Report candidates without deleting files or database records}';

    protected $description = 'Safely prune old mail files and operational records according to retention rules';

    public function handle(
        MailRetentionService $retention
    ): int {
        try {
            $categories = $this->categories();
            $limit = $this->limit();
            $before = $this->before();
            $dryRun = (bool) $this->option('dry-run');

            $result = $retention->prune(
                categories: $categories,
                dryRun: $dryRun,
                limit: $limit,
                before: $before,
            );

            $this->newLine();

            $this->components->info(
                $result->dryRun
                    ? 'Mail retention dry-run completed.'
                    : 'Mail retention completed.'
            );

            $rows = [];

            foreach ($result->categories as $category => $stats) {
                $rows[] = [
                    $category,
                    $stats['cutoff'],
                    (string) $stats['candidates'],
                    (string) $stats['records_pruned'],
                    (string) $stats['files_pruned'],
                    (string) $stats['missing_files'],
                    $this->formatBytes(
                        (int) $stats['bytes']
                    ),
                    (string) $stats['errors'],
                    $stats['note'] ?? '-',
                ];
            }

            $this->table(
                [
                    'Category',
                    'Cutoff',
                    'Candidates',
                    'Pruned',
                    'Files',
                    'Missing',
                    $result->dryRun
                        ? 'Potential bytes'
                        : 'Freed bytes',
                    'Errors',
                    'Note',
                ],
                $rows
            );

            $this->newLine();

            $this->table(
                [
                    'Summary',
                    'Value',
                ],
                [
                    [
                        'Mode',
                        $result->dryRun
                            ? 'dry-run'
                            : 'delete',
                    ],
                    [
                        'Candidates',
                        (string) $result->totalCandidates(),
                    ],
                    [
                        'Records pruned',
                        (string) $result->totalRecordsPruned(),
                    ],
                    [
                        'Files pruned',
                        (string) $result->totalFilesPruned(),
                    ],
                    [
                        'Missing files',
                        (string) $result->totalMissingFiles(),
                    ],
                    [
                        $result->dryRun
                            ? 'Potential bytes'
                            : 'Freed bytes',
                        $this->formatBytes(
                            $result->totalBytes()
                        ),
                    ],
                    [
                        'Errors',
                        (string) $result->totalErrors(),
                    ],
                    [
                        'Duration',
                        $result->completedAt
                            ->diffForHumans(
                                $result->startedAt,
                                true
                            ),
                    ],
                ]
            );

            return $result->totalErrors() === 0
                ? self::SUCCESS
                : self::FAILURE;
        } catch (Throwable $exception) {
            $this->components->error(
                $exception->getMessage()
            );

            report($exception);

            return self::FAILURE;
        }
    }

    private function categories(): array
    {
        $requested = array_values(
            array_filter(
                array_map(
                    static fn (mixed $value): string => strtolower(
                        trim((string) $value)
                    ),
                    (array) $this->option('category')
                )
            )
        );

        if (
            $requested === []
            || in_array('all', $requested, true)
        ) {
            return MailRetentionService::categories();
        }

        $unknown = array_values(
            array_diff(
                $requested,
                MailRetentionService::categories()
            )
        );

        if ($unknown !== []) {
            throw new RuntimeException(
                'Unknown retention categories: '
                .implode(', ', $unknown)
            );
        }

        return array_values(
            array_unique($requested)
        );
    }

    private function limit(): int
    {
        $value = $this->option('limit');

        $limit = is_numeric($value)
            ? (int) $value
            : (int) config(
                'simpledesk-mail-automation.retention.batch_size',
                500
            );

        return max(
            1,
            min(5000, $limit)
        );
    }

    private function before(): ?CarbonImmutable
    {
        $value = trim(
            (string) $this->option('before')
        );

        if ($value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "Unable to parse --before value [{$value}].",
                previous: $exception,
            );
        }
    }

    private function formatBytes(
        int $bytes
    ): string {
        $bytes = max(0, $bytes);
        $units = [
            'B',
            'KB',
            'MB',
            'GB',
            'TB',
        ];
        $value = (float) $bytes;
        $unit = 0;

        while (
            $value >= 1024
            && $unit < count($units) - 1
        ) {
            $value /= 1024;
            $unit++;
        }

        return sprintf(
            $unit === 0 ? '%.0f %s' : '%.2f %s',
            $value,
            $units[$unit]
        );
    }
}
