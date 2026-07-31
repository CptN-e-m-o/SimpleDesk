<?php

namespace App\Services\Admin\Mail\Ticketing;

use App\Data\Admin\Mail\RenderedTicketReplyEmailData;
use App\Models\TicketReply;

class TicketReplyEmailRenderer
{
    public function render(
        TicketReply $reply
    ): RenderedTicketReplyEmailData {
        $reply->loadMissing([
            'ticket.department',
            'user',
        ]);

        $message = $this->normalizeText(
            (string) $reply->message
        );

        if ($message === '') {
            $message =
                'Ответ не содержит текстового содержимого.';
        }

        $signatures = $this->signatures(
            $reply
        );

        $textBody = $message;

        if ($signatures !== []) {
            $textBody .= "\n\n--\n";
            $textBody .= implode(
                "\n\n",
                $signatures
            );
        }

        $htmlBody =
            '<div class="simpledesk-message">'
            .$this->textToHtml($message)
            .'</div>';

        if ($signatures !== []) {
            $htmlSignatures = array_map(
                fn (string $signature): string => '<div class="simpledesk-signature">'
                    .$this->textToHtml($signature)
                    .'</div>',
                $signatures,
            );

            $htmlBody .=
                '<br><div class="simpledesk-signatures">'
                .implode(
                    '<br>',
                    $htmlSignatures
                )
                .'</div>';
        }

        return new RenderedTicketReplyEmailData(
            subject: $this->subject(
                $reply
            ),
            textBody: $textBody,
            htmlBody: $htmlBody,
        );
    }

    private function subject(
        TicketReply $reply
    ): string {
        $subject = trim(
            (string) $reply
                ->ticket
                ?->subject
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
            $subject =
                'Обращение в службу поддержки';
        }

        $prefix = (string) config(
            'simpledesk-mail-ticketing.outgoing_replies.subject_prefix',
            'Re: '
        );

        return mb_substr(
            $prefix.$subject,
            0,
            255
        );
    }

    /**
     * @return array<int, string>
     */
    private function signatures(
        TicketReply $reply
    ): array {
        $signatures = [];

        if (
            (bool) config(
                'simpledesk-mail-ticketing.outgoing_replies.include_agent_signature',
                true
            )
        ) {
            $agentSignature = $this->signatureToText(
                $reply->user?->signature
            );

            if ($agentSignature !== null) {
                $signatures[] =
                    $agentSignature;
            }
        }

        if (
            (bool) config(
                'simpledesk-mail-ticketing.outgoing_replies.include_department_signature',
                true
            )
        ) {
            $departmentSignature =
                $this->signatureToText(
                    $reply
                        ->ticket
                        ?->department
                        ?->signature
                );

            if (
                $departmentSignature !== null
                && ! in_array(
                    $departmentSignature,
                    $signatures,
                    true
                )
            ) {
                $signatures[] =
                    $departmentSignature;
            }
        }

        return $signatures;
    }

    private function signatureToText(
        ?string $signature
    ): ?string {
        if ($signature === null) {
            return null;
        }

        $signature = preg_replace(
            '/<(br|hr)\s*\/?>/iu',
            "\n",
            $signature
        );

        $signature = preg_replace(
            '/<\/(p|div|section|article|li|h[1-6])>/iu',
            "\n",
            (string) $signature
        );

        $signature = strip_tags(
            (string) $signature
        );

        $signature = html_entity_decode(
            $signature,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $signature = $this->normalizeText(
            $signature
        );

        return $signature !== ''
            ? $signature
            : null;
    }

    private function normalizeText(
        string $text
    ): string {
        $text = str_replace(
            [
                "\r\n",
                "\r",
            ],
            "\n",
            $text
        );

        $text = preg_replace(
            '/[ \t]+\n/u',
            "\n",
            $text
        );

        $text = preg_replace(
            '/\n{3,}/u',
            "\n\n",
            (string) $text
        );

        return trim(
            (string) $text
        );
    }

    private function textToHtml(
        string $text
    ): string {
        $escaped = htmlspecialchars(
            $text,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        return nl2br(
            $escaped,
            false
        );
    }
}
