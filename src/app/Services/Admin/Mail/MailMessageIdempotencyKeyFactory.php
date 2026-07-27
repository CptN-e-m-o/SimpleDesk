<?php

namespace App\Services\Admin\Mail;

use App\Data\Mail\NormalizedInboundMessageData;
use App\Models\Admin\Mail\MailboxChannel;

class MailMessageIdempotencyKeyFactory
{
    public function forIncoming(
        MailboxChannel $channel,
        NormalizedInboundMessageData $message,
    ): string {
        $internetMessageId = $this->normalizeInternetMessageId(
            $message->internetMessageId
        );

        if ($internetMessageId !== null) {
            return hash(
                'sha256',
                implode('|', [
                    'incoming',
                    "mailbox:{$channel->mailbox_id}",
                    "message-id:{$internetMessageId}",
                ])
            );
        }

        return hash(
            'sha256',
            implode('|', [
                'incoming',
                "channel:{$channel->id}",
                "external:{$message->externalMessageId}",
            ])
        );
    }

    private function normalizeInternetMessageId(
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
}
