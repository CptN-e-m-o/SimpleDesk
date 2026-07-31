<?php

namespace App\Console\Commands\Admin\Mail;

use App\Data\Admin\Mail\MailAddressData;
use App\Data\Admin\Mail\MailAttachmentData;
use App\Data\Admin\Mail\OutgoingEmailMessageData;
use App\Enums\Admin\Mail\MailboxChannelDirection;
use App\Enums\Admin\Mail\MailboxDriver;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use App\Services\Admin\Mail\MailDriverRegistry;
use App\Services\Admin\Mail\MailInternetMessageIdFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class InjectGreenMailAttachmentCommand extends Command
{
    protected $signature = 'simpledesk:mail:inject-greenmail-attachment
        {senderMailbox : Mailbox ID used as the sender}
        {senderChannel : GreenMail SMTP channel ID}
        {targetMailbox : Mailbox ID that will receive the message through IMAP}
        {--type=clean : Attachment type: clean or eicar}
        {--subject= : Optional custom subject}
        {--text= : Optional custom message body}';

    protected $description =
        'Inject a clean or EICAR attachment into GreenMail for inbound antivirus verification';

    private const EICAR_CONTENT =
        'X5O!P%@AP[4\\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';

    public function handle(
        MailDriverRegistry $drivers,
        MailInternetMessageIdFactory $messageIds,
    ): int {
        try {
            $senderMailbox = $this->mailbox(
                argument: 'senderMailbox',
                role: 'Sender'
            );

            $targetMailbox = $this->mailbox(
                argument: 'targetMailbox',
                role: 'Target'
            );

            $channel = $this->channel(
                senderMailbox: $senderMailbox
            );

            $type = $this->attachmentType();
            $token = (string) Str::uuid();

            $idempotencyKey =
                "greenmail-attachment-test:{$type}:{$token}";

            $internetMessageId = $messageIds->make(
                mailbox: $senderMailbox,
                idempotencyKey: $idempotencyKey,
            );

            $subject = $this->subject(
                type: $type,
                token: $token,
            );

            $body = $this->body(
                type: $type,
                token: $token,
            );

            $attachment = $this->attachment(
                type: $type,
                token: $token,
            );

            $message = new OutgoingEmailMessageData(
                idempotencyKey: $idempotencyKey,

                from: new MailAddressData(
                    address: $senderMailbox->email_address,
                    name:
                    $senderMailbox->display_name
                    ?? $senderMailbox->name,
                ),

                to: [
                    new MailAddressData(
                        address: $targetMailbox->email_address,
                        name:
                        $targetMailbox->display_name
                        ?? $targetMailbox->name,
                    ),
                ],

                cc: [],
                bcc: [],
                replyTo: [],

                subject: $subject,
                textBody: $body,
                htmlBody:
                '<p><strong>SimpleDesk inbound attachment verification</strong></p>'
                . "<p>Type: {$type}</p>"
                . "<p>Token: {$token}</p>",

                headers: [
                    'X-SimpleDesk-Integration-Test' => $token,
                    'X-SimpleDesk-Attachment-Type' => $type,
                ],

                attachments: [
                    $attachment,
                ],

                internetMessageId: $internetMessageId,
                inReplyToMessageId: null,
                references: [],

                metadata: [
                    'source' =>
                        'greenmail_attachment_injection',

                    'attachment_type' => $type,
                    'verification_token' => $token,
                ],
            );

            $driver = $drivers->outgoing(
                $channel->driver
            );

            $result = $driver->send(
                channel: $channel,
                message: $message,
            );

            $this->newLine();

            $this->info(
                'GreenMail attachment test message was injected successfully.'
            );

            $this->table(
                [
                    'Parameter',
                    'Value',
                ],
                [
                    [
                        'Attachment type',
                        $type,
                    ],
                    [
                        'Sender mailbox',
                        (string) $senderMailbox->id,
                    ],
                    [
                        'SMTP channel',
                        (string) $channel->id,
                    ],
                    [
                        'Target mailbox',
                        (string) $targetMailbox->id,
                    ],
                    [
                        'Recipient',
                        $targetMailbox->email_address,
                    ],
                    [
                        'Subject',
                        $subject,
                    ],
                    [
                        'File name',
                        $attachment->fileName,
                    ],
                    [
                        'File size',
                        (string) $attachment->size,
                    ],
                    [
                        'Internet Message-ID',
                        $internetMessageId,
                    ],
                    [
                        'Transport Message-ID',
                        $result->externalMessageId
                        ?? 'not available',
                    ],
                    [
                        'Verification token',
                        $token,
                    ],
                ]
            );

            $this->newLine();

            $this->line(
                'Now synchronize the target mailbox:'
            );

            $this->line(
                "php artisan simpledesk:mail:sync {$targetMailbox->id}"
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error(
                $exception->getMessage()
            );

            return self::FAILURE;
        }
    }

    private function mailbox(
        string $argument,
        string $role,
    ): Mailbox {
        $mailboxId = $this->argument($argument);

        if (
            filter_var(
                $mailboxId,
                FILTER_VALIDATE_INT
            ) === false
        ) {
            throw new RuntimeException(
                "{$role} mailbox ID must be an integer."
            );
        }

        $mailbox = Mailbox::query()->find(
            (int) $mailboxId
        );

        if ($mailbox === null) {
            throw new RuntimeException(
                "{$role} mailbox [{$mailboxId}] was not found."
            );
        }

        if (!$mailbox->is_active) {
            throw new RuntimeException(
                "{$role} mailbox [{$mailbox->id}] is disabled."
            );
        }

        if (
            filter_var(
                $mailbox->email_address,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            throw new RuntimeException(
                "{$role} mailbox [{$mailbox->id}] has an invalid email address."
            );
        }

        return $mailbox;
    }

    private function channel(
        Mailbox $senderMailbox
    ): MailboxChannel {
        $channelId = $this->argument(
            'senderChannel'
        );

        if (
            filter_var(
                $channelId,
                FILTER_VALIDATE_INT
            ) === false
        ) {
            throw new RuntimeException(
                'SMTP channel ID must be an integer.'
            );
        }

        $channel = MailboxChannel::query()->find(
            (int) $channelId
        );

        if ($channel === null) {
            throw new RuntimeException(
                "Mailbox channel [{$channelId}] was not found."
            );
        }

        if (
            $channel->mailbox_id
            !== $senderMailbox->id
        ) {
            throw new RuntimeException(
                "Channel [{$channel->id}] does not belong to sender mailbox [{$senderMailbox->id}]."
            );
        }

        if (!$channel->is_enabled) {
            throw new RuntimeException(
                "Channel [{$channel->id}] is disabled."
            );
        }

        if (
            $channel->direction
            !== MailboxChannelDirection::Outgoing
        ) {
            throw new RuntimeException(
                "Channel [{$channel->id}] is not outgoing."
            );
        }

        if (
            $channel->driver
            !== MailboxDriver::Smtp
        ) {
            throw new RuntimeException(
                "Channel [{$channel->id}] is not an SMTP channel."
            );
        }

        return $channel;
    }

    private function attachmentType(): string
    {
        $type = strtolower(
            trim((string) $this->option('type'))
        );

        if (
            !in_array(
                $type,
                [
                    'clean',
                    'eicar',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'The --type option must be clean or eicar.'
            );
        }

        return $type;
    }

    private function subject(
        string $type,
        string $token,
    ): string {
        $subject = $this->option(
            'subject'
        );

        if (
            is_string($subject)
            && trim($subject) !== ''
        ) {
            return mb_substr(
                trim($subject),
                0,
                255
            );
        }

        return "[SimpleDesk {$type} attachment verification] {$token}";
    }

    private function body(
        string $type,
        string $token,
    ): string {
        $text = $this->option('text');

        if (
            is_string($text)
            && trim($text) !== ''
        ) {
            return trim($text);
        }

        return implode("\n", [
            'SimpleDesk inbound attachment verification.',
            "Attachment type: {$type}",
            "Verification token: {$token}",
        ]);
    }

    private function attachment(
        string $type,
        string $token,
    ): MailAttachmentData {
        if ($type === 'eicar') {
            return new MailAttachmentData(
                fileName: 'eicar.com.txt',
                mimeType: 'text/plain',
                size: strlen(
                    self::EICAR_CONTENT
                ),
                content:
                self::EICAR_CONTENT,
                metadata: [
                    'integration_test' => true,
                    'attachment_type' => 'eicar',
                ],
            );
        }

        $content = implode("\n", [
            'SimpleDesk clean attachment verification.',
            "Token: {$token}",
            '',
        ]);

        return new MailAttachmentData(
            fileName:
            "simpledesk-clean-{$token}.txt",

            mimeType: 'text/plain',

            size: strlen($content),

            content: $content,

            metadata: [
                'integration_test' => true,
                'attachment_type' => 'clean',
            ],
        );
    }
}
