<?php

namespace App\Console\Commands\Admin\Mail;

use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Jobs\Admin\Mail\ProcessInboundEmailJob;
use App\Models\Admin\Mail\EmailMessage;
use App\Services\Admin\Mail\Ticketing\InboundEmailTicketProcessor;
use Illuminate\Console\Command;
use Throwable;

class ProcessInboundEmailCommand extends Command
{
    protected $signature =
        'simpledesk:mail:process-inbound
        {message? : EmailMessage ID}
        {--all-received : Process all received messages}
        {--queue : Dispatch processing to the queue}
        {--limit=100 : Maximum number of messages}';

    protected $description =
        'Create tickets and ticket replies from inbound email messages';

    public function handle(
        InboundEmailTicketProcessor $processor
    ): int {
        $ids = $this->messageIds();

        if ($ids === []) {
            $this->error(
                'No inbound email messages were selected.'
            );

            return self::FAILURE;
        }

        $failed = false;

        foreach ($ids as $id) {
            if ((bool) $this->option('queue')) {
                $this->dispatchJob($id);

                $this->info(
                    "Email message [{$id}] "
                    . 'was queued for ticket processing.'
                );

                continue;
            }

            try {
                $emailMessage = $processor->process(
                    $id
                );

                $action =
                    $emailMessage->ticket_reply_id
                    !== null
                        ? 'reply created'
                        : 'ticket created';

                $this->table(
                    [
                        'Parameter',
                        'Value',
                    ],
                    [
                        [
                            'Email message',
                            $emailMessage->id,
                        ],
                        [
                            'Result',
                            $action,
                        ],
                        [
                            'Ticket',
                            $emailMessage->ticket_id,
                        ],
                        [
                            'Ticket reply',
                            $emailMessage
                                ->ticket_reply_id
                            ?? 'null',
                        ],
                        [
                            'Status',
                            $emailMessage
                                ->status
                                ->value,
                        ],
                    ]
                );
            } catch (Throwable $exception) {
                $failed = true;

                $this->error(
                    "Email message [{$id}]: "
                    . $exception->getMessage()
                );
            }
        }

        return $failed
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * @return array<int, int>
     */
    private function messageIds(): array
    {
        $messageId =
            $this->argument('message');

        if ($messageId !== null) {
            if (
                filter_var(
                    $messageId,
                    FILTER_VALIDATE_INT
                ) === false
            ) {
                return [];
            }

            return [
                (int) $messageId,
            ];
        }

        if (
            !(bool) $this
                ->option('all-received')
        ) {
            return [];
        }

        $limit = max(
            1,
            min(
                1000,
                (int) $this->option(
                    'limit'
                )
            )
        );

        return EmailMessage::query()
            ->where(
                'direction',
                EmailMessageDirection::Incoming
                    ->value
            )
            ->where(
                function (
                    $query
                ): void {
                    $query
                        ->where(
                            'status',
                            EmailMessageStatus::Received
                                ->value
                        )
                        ->orWhere(
                            function (
                                $query
                            ): void {
                                $query
                                    ->where(
                                        'status',
                                        EmailMessageStatus::Failed
                                            ->value
                                    )
                                    ->where(
                                        'failure_code',
                                        'inbound_ticket_processing_failed'
                                    );
                            }
                        );
                }
            )
            ->oldest('id')
            ->limit($limit)
            ->pluck('id')
            ->map(
                static fn (
                    mixed $id
                ): int => (int) $id
            )
            ->all();
    }

    private function dispatchJob(
        int $emailMessageId
    ): void {
        $pendingDispatch =
            ProcessInboundEmailJob::dispatch(
                $emailMessageId
            );

        $connection = config(
            'simpledesk-mail-ticketing.queue_connection'
        );

        if (
            is_string($connection)
            && $connection !== ''
        ) {
            $pendingDispatch
                ->onConnection(
                    $connection
                );
        }

        $pendingDispatch
            ->onQueue(
                (string) config(
                    'simpledesk-mail-ticketing.queue',
                    'mail-incoming'
                )
            )
            ->afterCommit();
    }
}
