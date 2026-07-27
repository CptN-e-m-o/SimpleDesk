<?php

namespace App\Console\Commands\Admin\Mail;

use App\Data\Admin\Mail\MailAddressData;
use App\Data\Admin\Mail\OutgoingEmailMessageData;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use App\Services\Admin\Mail\OutgoingEmailQueueService;
use App\Services\Admin\Mail\OutgoingMailFailoverService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

class SendThreadReplyTestCommand extends Command
{
    protected $signature =
        'simpledesk:mail:test-thread-reply
        {senderMailbox : Mailbox ID used as the simulated customer}
        {replyToMessage : Existing EmailMessage ID being replied to}
        {--subject= : Optional reply subject}
        {--text=Спасибо, вопрос всё ещё актуален. : Plain-text reply body}
        {--html= : Optional HTML reply body}
        {--now : Send immediately instead of using the queue}';

    protected $description =
        'Send a test email reply with In-Reply-To and References headers';

    public function handle(
        OutgoingEmailQueueService $queue,
        OutgoingMailFailoverService $sender,
    ): int {
        $senderMailbox = Mailbox::query()
            ->find(
                $this->argument('senderMailbox')
            );

        if ($senderMailbox === null) {
            $this->error(
                'Sender mailbox was not found.'
            );

            return self::FAILURE;
        }

        if (!$senderMailbox->is_active) {
            $this->error(
                'Sender mailbox is disabled.'
            );

            return self::FAILURE;
        }

        $replyToMessage = EmailMessage::query()
            ->with([
                'mailbox',
                'ticket',
            ])
            ->find(
                $this->argument('replyToMessage')
            );

        if ($replyToMessage === null) {
            $this->error(
                'Referenced email message was not found.'
            );

            return self::FAILURE;
        }

        if ($replyToMessage->ticket_id === null) {
            $this->error(
                'Referenced email message is not linked to a ticket.'
            );

            return self::FAILURE;
        }

        if ($replyToMessage->mailbox === null) {
            $this->error(
                'Referenced email message has no target mailbox.'
            );

            return self::FAILURE;
        }

        if (
            $replyToMessage->internet_message_id === null
            || trim(
                $replyToMessage->internet_message_id
            ) === ''
        ) {
            $this->error(
                'Referenced email message has no Internet Message-ID.'
            );

            return self::FAILURE;
        }

        $recipientAddress = trim(
            (string) $replyToMessage
                ->mailbox
                ->email_address
        );

        if (
            filter_var(
                $recipientAddress,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            $this->error(
                'Target mailbox has an invalid email address.'
            );

            return self::FAILURE;
        }

        try {
            $payload = new OutgoingEmailMessageData(
                idempotencyKey:
                'thread-reply-test:'
                . Str::uuid(),

                from: null,

                to: [
                    new MailAddressData(
                        address: $recipientAddress,
                        name:
                        $replyToMessage
                            ->mailbox
                            ->display_name
                        ?? $replyToMessage
                        ->mailbox
                        ->name,
                    ),
                ],

                cc: [],
                bcc: [],
                replyTo: [],

                subject: $this->subject(
                    $replyToMessage
                ),

                textBody: $this->textBody(),

                htmlBody:
                $this->nullableOption(
                    'html'
                ),

                headers: [],

                attachments: [],

                internetMessageId: null,

                inReplyToMessageId:
                $replyToMessage
                    ->internet_message_id,

                references:
                $this->references(
                    $replyToMessage
                ),

                metadata: [
                    'source' =>
                        'artisan_thread_reply_test',

                    'reply_to_email_message_id' =>
                        $replyToMessage->id,

                    'expected_ticket_id' =>
                        $replyToMessage->ticket_id,

                    'simulated_customer_mailbox_id' =>
                        $senderMailbox->id,
                ],
            );

            $sendNow = (bool) $this
                ->option('now');

            $emailMessage = $queue->queue(
                mailbox: $senderMailbox,
                message: $payload,
                ticketId: null,
                ticketReplyId: null,
                dispatch: !$sendNow,
            );

            $this->info(
                "Test reply email [{$emailMessage->id}] "
                . 'was prepared.'
            );

            $this->table(
                [
                    'Parameter',
                    'Value',
                ],
                [
                    [
                        'Sender mailbox',
                        $senderMailbox->id,
                    ],
                    [
                        'Recipient',
                        $recipientAddress,
                    ],
                    [
                        'Referenced message',
                        $replyToMessage->id,
                    ],
                    [
                        'Expected ticket',
                        $replyToMessage->ticket_id,
                    ],
                    [
                        'In-Reply-To',
                        $replyToMessage
                            ->internet_message_id,
                    ],
                    [
                        'References',
                        implode(
                            ' ',
                            $payload->references
                        ),
                    ],
                ]
            );

            if (!$sendNow) {
                $this->info(
                    'Test reply was queued.'
                );

                return self::SUCCESS;
            }

            $result = $sender->send(
                $emailMessage
            );

            $this->info(
                'Test reply was sent successfully.'
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

    private function subject(
        EmailMessage $replyToMessage
    ): string {
        $subjectOption = $this
            ->nullableOption('subject');

        if ($subjectOption !== null) {
            return mb_substr(
                $subjectOption,
                0,
                255
            );
        }

        $subject = trim(
            (string) $replyToMessage->subject
        );

        $subject = preg_replace(
            '/^(?:(?:re|fw|fwd|aw|sv)\s*:\s*)+/iu',
            '',
            $subject
        );

        $subject = preg_replace(
            '/\s+/u',
            ' ',
            trim((string) $subject)
        );

        if ($subject === '') {
            $subject = 'Обращение по электронной почте';
        }

        return mb_substr(
            'Re: ' . $subject,
            0,
            255
        );
    }

    private function textBody(): string
    {
        $value = $this->option('text');

        if (!is_string($value)) {
            return 'Спасибо, вопрос всё ещё актуален.';
        }

        $value = trim($value);

        return $value !== ''
            ? $value
            : 'Спасибо, вопрос всё ещё актуален.';
    }

    /**
     * @return array<int, string>
     */
    private function references(
        EmailMessage $replyToMessage
    ): array {
        $references = [];

        foreach (
            $replyToMessage
                ->reference_message_ids
            ?? []
            as $reference
        ) {
            if (!is_scalar($reference)) {
                continue;
            }

            $reference = $this
                ->normalizeMessageId(
                    (string) $reference
                );

            if ($reference !== null) {
                $references[] = $reference;
            }
        }

        $internetMessageId =
            $this->normalizeMessageId(
                $replyToMessage
                    ->internet_message_id
            );

        if ($internetMessageId !== null) {
            $references[] =
                $internetMessageId;
        }

        return array_values(
            array_unique(
                $references
            )
        );
    }

    private function normalizeMessageId(
        ?string $messageId
    ): ?string {
        if ($messageId === null) {
            return null;
        }

        $messageId = trim(
            $messageId,
            " \t\n\r\0\x0B<>"
        );

        return $messageId !== ''
            ? $messageId
            : null;
    }

    private function nullableOption(
        string $name
    ): ?string {
        $value = $this->option($name);

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== ''
            ? $value
            : null;
    }
}
