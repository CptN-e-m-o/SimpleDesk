<?php

namespace App\Console\Commands\Admin\Mail;

use App\Services\Admin\Mail\Automation\MailPipelineRecoveryService;
use Illuminate\Console\Command;
use Throwable;

class RecoverMailPipelineCommand extends Command
{
    protected $signature =
        'simpledesk:mail:recover
        {--limit= : Maximum records per recovery category}';

    protected $description =
        'Recover interrupted or undispatched mail pipeline operations';

    public function handle(
        MailPipelineRecoveryService $recovery
    ): int {
        $limitOption = $this->option(
            'limit'
        );

        $limit = is_numeric($limitOption)
            ? (int) $limitOption
            : (int) config(
                'simpledesk-mail-automation.recovery.batch_size',
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
                    'Stuck incoming reset',
                    $result
                        ->incomingStuckReset,
                ],
                [
                    'Received incoming dispatched',
                    $result
                        ->incomingReceivedDispatched,
                ],
                [
                    'Stuck outgoing reset',
                    $result
                        ->outgoingStuckReset,
                ],
                [
                    'Queued outgoing dispatched',
                    $result
                        ->outgoingQueuedDispatched,
                ],
                [
                    'Ticket replies dispatched',
                    $result
                        ->ticketRepliesDispatched,
                ],
                [
                    'Total actions',
                    $result
                        ->totalActions(),
                ],
            ]
        );

        return self::SUCCESS;
    }
}
