<?php

namespace App\Console\Commands\Admin\Mail;

use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Services\Admin\Mail\OutgoingMailFailoverService;
use App\Services\Admin\Mail\Ticketing\TicketReplyEmailService;
use Illuminate\Console\Command;
use Throwable;

class SendTicketReplyEmailCommand extends Command
{
    protected $signature =
        'simpledesk:mail:send-ticket-reply
        {reply : TicketReply ID}
        {--now : Send immediately instead of using the queue}';

    protected $description =
        'Create and send an outgoing email for a ticket reply';

    public function handle(
        TicketReplyEmailService $service,
        OutgoingMailFailoverService $sender,
    ): int {
        try {
            $sendNow = (bool) $this
                ->option('now');

            $emailMessage = $service->queue(
                ticketReplyId:
                (int) $this
                    ->argument('reply'),

                dispatch: !$sendNow,
            );

            $this->table(
                [
                    'Parameter',
                    'Value',
                ],
                [
                    [
                        'Ticket reply',
                        $emailMessage
                            ->ticket_reply_id,
                    ],
                    [
                        'Email message',
                        $emailMessage->id,
                    ],
                    [
                        'Ticket',
                        $emailMessage->ticket_id,
                    ],
                    [
                        'Status',
                        $emailMessage
                            ->status
                            ->value,
                    ],
                    [
                        'Recipient',
                        data_get(
                            $emailMessage
                                ->to_recipients,
                            '0.address',
                            'unknown'
                        ),
                    ],
                    [
                        'In-Reply-To',
                        $emailMessage
                            ->in_reply_to_message_id
                        ?? 'null',
                    ],
                ]
            );

            if (!$sendNow) {
                $this->info(
                    'Ticket reply email was queued.'
                );

                return self::SUCCESS;
            }

            if (in_array(
                $emailMessage->status,
                [
                    EmailMessageStatus::Sent,
                    EmailMessageStatus::Delivered,
                ],
                true,
            )) {
                $this->info(
                    'Ticket reply email has already been sent.'
                );

                return self::SUCCESS;
            }

            $result = $sender->send(
                $emailMessage
            );

            $this->info(
                'Ticket reply email was sent successfully.'
            );

            $this->line(
                'Internet Message-ID: '
                . (
                    $result->internetMessageId
                    ?? 'not available'
                )
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error(
                $exception->getMessage()
            );

            return self::FAILURE;
        }
    }
}
