<?php

namespace App\Data\Admin\Mail;

use Carbon\CarbonImmutable;

class MailRetentionResultData
{
    public function __construct(
        public readonly bool $dryRun,
        public readonly CarbonImmutable $startedAt,
        public readonly CarbonImmutable $completedAt,
        public readonly array $categories,
    ) {
    }

    public function totalCandidates(): int
    {
        return array_sum(
            array_column(
                $this->categories,
                'candidates'
            )
        );
    }

    public function totalRecordsPruned(): int
    {
        return array_sum(
            array_column(
                $this->categories,
                'records_pruned'
            )
        );
    }

    public function totalFilesPruned(): int
    {
        return array_sum(
            array_column(
                $this->categories,
                'files_pruned'
            )
        );
    }

    public function totalMissingFiles(): int
    {
        return array_sum(
            array_column(
                $this->categories,
                'missing_files'
            )
        );
    }

    public function totalBytes(): int
    {
        return array_sum(
            array_column(
                $this->categories,
                'bytes'
            )
        );
    }

    public function totalErrors(): int
    {
        return array_sum(
            array_column(
                $this->categories,
                'errors'
            )
        );
    }
}
