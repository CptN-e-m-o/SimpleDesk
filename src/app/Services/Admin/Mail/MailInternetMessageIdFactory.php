<?php

namespace App\Services\Admin\Mail;

use App\Models\Admin\Mail\Mailbox;

class MailInternetMessageIdFactory
{
    public function make(
        Mailbox $mailbox,
        string $idempotencyKey,
    ): string {
        $domain = $this->domainFromAddress(
            $mailbox->email_address
        );

        $localPart = 'simpledesk-'
            . substr(
                hash('sha256', $idempotencyKey),
                0,
                40
            );

        return "{$localPart}@{$domain}";
    }

    private function domainFromAddress(
        string $address
    ): string {
        $position = strrpos($address, '@');

        if ($position === false) {
            return 'simpledesk.local';
        }

        $domain = strtolower(
            trim(
                substr(
                    $address,
                    $position + 1
                )
            )
        );

        if (
            $domain === ''
            || preg_match(
                '/^[a-z0-9.-]+$/',
                $domain
            ) !== 1
        ) {
            return 'simpledesk.local';
        }

        return $domain;
    }
}
