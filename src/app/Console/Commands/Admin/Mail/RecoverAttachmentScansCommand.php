<?php

namespace App\Console\Commands\Admin\Mail;

use App\Services\Admin\Mail\Antivirus\AttachmentScanRecoveryService;
use Illuminate\Console\Command;
use Throwable;

class RecoverAttachmentScansCommand extends Command
{
    protected $signature =
        'simpledesk:mail:recover-attachment-scans
        {--limit= : Maximum attachments per recovery category}';

    protected $description =
        'Recover stuck or undispatched attachment antivirus scans';

    public function handle(
        AttachmentScanRecoveryService $recovery
    ): int {
        $limitOption = $this->option(
            'limit'
        );

        $limit = is_numeric($limitOption)
            ? (int) $limitOption
            : (int) config(
                'simpledesk-mail-antivirus.recovery.batch_size',
                100
            );

        try {
            $result = $recovery->recover(
                $limit
            );
        } catch (Throwable $exception) {
            $this->error(
                $exception->getMessage()
            );

            report($exception);

            return self::FAILURE;
        }

        $this->table(
            [
                'Recovery action',
                'Count',
            ],
            [
                [
                    'Stuck scans reset',
                    $result->stuckScansReset,
                ],
                [
                    'Pending scans dispatched',
                    $result->pendingScansDispatched,
                ],
                [
                    'Total actions',
                    $result->totalActions(),
                ],
            ]
        );

        return self::SUCCESS;
    }
}
