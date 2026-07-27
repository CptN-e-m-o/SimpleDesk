<?php

namespace App\Console\Commands\Admin\Mail;

use App\Enums\Admin\Mail\EmailQuarantineStage;
use App\Models\Admin\Mail\EmailMessageQuarantine;
use App\Services\Admin\Mail\Quarantine\EmailMessageQuarantineService;
use App\Services\Admin\Mail\Ticketing\InboundEmailTicketProcessor;
use Illuminate\Console\Command;
use Throwable;

class ManageEmailQuarantineCommand extends Command
{
    protected $signature =
        'simpledesk:mail:quarantine
        {action=list : list, show, retry or ignore}
        {id? : Quarantine record ID}
        {--all : Include resolved records}
        {--limit= : Maximum records for list}
        {--now : Process retry synchronously}
        {--reason= : Reason for ignoring the message}';

    protected $description =
        'Inspect and manage quarantined email messages';

    public function handle(
        EmailMessageQuarantineService $service,
        InboundEmailTicketProcessor $processor,
    ): int {
        $action = strtolower(
            trim(
                (string) $this->argument(
                    'action'
                )
            )
        );

        return match ($action) {
            'list' =>
            $this->listRecords(),

            'show' =>
            $this->showRecord(),

            'retry' =>
            $this->retryRecord(
                service: $service,
                processor: $processor,
            ),

            'ignore' =>
            $this->ignoreRecord(
                $service
            ),

            default =>
            $this->invalidAction(
                $action
            ),
        };
    }

    private function listRecords(): int
    {
        $limitOption = $this->option(
            'limit'
        );

        $limit = is_numeric($limitOption)
            ? (int) $limitOption
            : (int) config(
                'simpledesk-mail-quarantine.command_list_limit',
                50
            );

        $limit = max(
            1,
            min(1000, $limit)
        );

        $query =
            EmailMessageQuarantine::query()
                ->with([
                    'emailMessage',
                    'mailbox',
                    'mailboxChannel',
                ]);

        if (
            !(bool) $this->option('all')
        ) {
            $query->whereNull(
                'resolved_at'
            );
        }

        $records = $query
            ->latest(
                'last_quarantined_at'
            )
            ->limit($limit)
            ->get();

        if ($records->isEmpty()) {
            $this->info(
                'No quarantine records found.'
            );

            return self::SUCCESS;
        }

        $this->table(
            [
                'ID',
                'Email',
                'Mailbox',
                'Stage',
                'Reason',
                'Attempts',
                'Released',
                'Resolved',
                'Last failure',
            ],
            $records
                ->map(
                    static fn (
                        EmailMessageQuarantine $record
                    ): array => [
                        $record->id,

                        $record
                            ->email_message_id,

                        $record
                            ->mailbox
                            ?->email_address
                        ?? $record
                            ->mailbox_id
                            ?? 'null',

                        $record
                            ->stage
                            ->value,

                        $record
                            ->reason_code
                        ?? 'unknown',

                        $record->attempts,

                        $record->released_at
                            ? 'yes'
                            : 'no',

                        $record->resolved_at
                            ? 'yes'
                            : 'no',

                        $record
                            ->last_quarantined_at
                            ?->toDateTimeString()
                        ?? 'null',
                    ]
                )
                ->all()
        );

        return self::SUCCESS;
    }

    private function showRecord(): int
    {
        $id = $this->requiredId();

        if ($id === null) {
            return self::FAILURE;
        }

        $record =
            EmailMessageQuarantine::query()
                ->with([
                    'emailMessage',
                    'mailbox',
                    'mailboxChannel',
                    'releasedBy',
                ])
                ->find($id);

        if ($record === null) {
            $this->error(
                'Quarantine record was not found.'
            );

            return self::FAILURE;
        }

        $this->table(
            [
                'Parameter',
                'Value',
            ],
            [
                [
                    'Quarantine ID',
                    $record->id,
                ],
                [
                    'Email message',
                    $record
                        ->email_message_id,
                ],
                [
                    'Mailbox',
                    $record
                        ->mailbox
                        ?->email_address
                    ?? 'null',
                ],
                [
                    'Channel',
                    $record
                        ->mailboxChannel
                        ?->name
                    ?? 'null',
                ],
                [
                    'Stage',
                    $record
                        ->stage
                        ->value,
                ],
                [
                    'Reason code',
                    $record
                        ->reason_code
                    ?? 'null',
                ],
                [
                    'Reason message',
                    $record
                        ->reason_message
                    ?? 'null',
                ],
                [
                    'Exception',
                    $record
                        ->exception_class
                    ?? 'null',
                ],
                [
                    'Attempts',
                    $record->attempts,
                ],
                [
                    'Released at',
                    $record
                        ->released_at
                        ?->toDateTimeString()
                    ?? 'null',
                ],
                [
                    'Resolved at',
                    $record
                        ->resolved_at
                        ?->toDateTimeString()
                    ?? 'null',
                ],
                [
                    'Resolution',
                    $record
                        ->resolution
                        ?->value
                    ?? 'null',
                ],
            ]
        );

        $this->newLine();

        $this->info(
            'Metadata:'
        );

        $this->line(
            json_encode(
                $record->metadata,
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            ) ?: '{}'
        );

        return self::SUCCESS;
    }

    private function retryRecord(
        EmailMessageQuarantineService $service,
        InboundEmailTicketProcessor $processor,
    ): int {
        $id = $this->requiredId();

        if ($id === null) {
            return self::FAILURE;
        }

        $processNow = (bool) $this
            ->option('now');

        try {
            $record = $service->retry(
                quarantineId: $id,
                releasedById: null,
                dispatch: !$processNow,
            );

            if (!$processNow) {
                $this->info(
                    "Email message "
                    . "[{$record->email_message_id}] "
                    . 'was queued for retry.'
                );

                return self::SUCCESS;
            }

            try {
                $emailMessage =
                    $processor->process(
                        $record
                            ->email_message_id
                    );

                $service->resolveForEmail(
                    $emailMessage->id
                );
            } catch (Throwable $exception) {
                $service->quarantine(
                    emailMessageId:
                    $record
                        ->email_message_id,

                    stage:
                    EmailQuarantineStage::InboundTicketing,

                    exception:
                    $exception,
                );

                throw $exception;
            }

            $this->info(
                "Email message "
                . "[{$record->email_message_id}] "
                . 'was processed successfully.'
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error(
                $exception->getMessage()
            );

            return self::FAILURE;
        }
    }

    private function ignoreRecord(
        EmailMessageQuarantineService $service
    ): int {
        $id = $this->requiredId();

        if ($id === null) {
            return self::FAILURE;
        }

        $reason = $this->option(
            'reason'
        );

        $reason = is_string($reason)
        && trim($reason) !== ''
            ? trim($reason)
            : null;

        try {
            $record = $service->ignore(
                quarantineId: $id,
                releasedById: null,
                reason: $reason,
            );
        } catch (Throwable $exception) {
            $this->error(
                $exception->getMessage()
            );

            return self::FAILURE;
        }

        $this->info(
            "Quarantine record "
            . "[{$record->id}] "
            . 'was marked as ignored.'
        );

        return self::SUCCESS;
    }

    private function requiredId(): ?int
    {
        $value = $this->argument(
            'id'
        );

        if (
            $value === null
            || filter_var(
                $value,
                FILTER_VALIDATE_INT
            ) === false
            || (int) $value <= 0
        ) {
            $this->error(
                'A valid quarantine record ID is required.'
            );

            return null;
        }

        return (int) $value;
    }

    private function invalidAction(
        string $action
    ): int {
        $this->error(
            "Unknown quarantine action [{$action}]."
        );

        $this->line(
            'Available actions: list, show, retry, ignore.'
        );

        return self::FAILURE;
    }
}
