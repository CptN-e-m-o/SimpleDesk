<?php

namespace App\Services\Admin\Mail\Drivers\Imap;

use App\Data\Admin\Mail\ImapChannelConfigurationData;
use App\Enums\Admin\Mail\ImapEncryption;
use App\Enums\Admin\Mail\MailAuthenticationType;
use App\Enums\Admin\Mail\MailboxChannelDirection;
use App\Enums\Admin\Mail\MailboxDriver;
use App\Exceptions\Admin\Mail\MailDriverException;
use App\Models\Admin\Mail\MailboxChannel;

class ImapChannelConfigurationFactory
{
    public function make(
        MailboxChannel $channel
    ): ImapChannelConfigurationData {
        $channel->loadMissing('providerConnection');

        $this->assertChannel($channel);

        $configuration = array_replace(
            $channel->providerConnection?->configuration ?? [],
            $channel->configuration ?? [],
        );

        $secrets = array_replace(
            $channel->providerConnection?->secret_configuration ?? [],
            $channel->secret_configuration ?? [],
        );

        $host = $this->requiredString(
            $configuration,
            'host',
            'IMAP host is required.'
        );

        $encryption = ImapEncryption::tryFrom(
            (string) (
                $configuration['encryption']
                ?? ImapEncryption::Tls->value
            )
        );

        if ($encryption === null) {
            throw $this->configurationException(
                'Unsupported IMAP encryption value.'
            );
        }

        $port = $this->integer(
            $configuration['port']
            ?? $encryption->defaultPort()
        );

        if ($port < 1 || $port > 65535) {
            throw $this->configurationException(
                'IMAP port must be between 1 and 65535.'
            );
        }

        [$username, $password] = $this->credentials(
            channel: $channel,
            configuration: $configuration,
            secrets: $secrets,
        );

        $folder = $this->requiredString(
            $configuration,
            'folder',
            'IMAP folder is required.',
            'INBOX',
        );

        return new ImapChannelConfigurationData(
            host: $host,
            port: $port,
            encryption: $encryption,
            authType: $channel->auth_type,
            username: $username,
            password: $password,
            validateCertificate: $this->boolean(
                $configuration['validate_cert'] ?? true
            ),
            folder: $folder,
            processedFolder: $this->nullableString(
                $configuration['processed_folder'] ?? null
            ),
            createProcessedFolder: $this->boolean(
                $configuration['create_processed_folder']
                ?? true
            ),
            expungeOnDelete: $this->boolean(
                $configuration['expunge_on_delete']
                ?? true
            ),
            storeRawMessage: $this->boolean(
                $configuration['store_raw_message']
                ?? config(
                'simpledesk-mail.imap.store_raw_message',
                true
            )
            ),
            maxRawMessageBytes: $this->positiveInteger(
                $configuration['max_raw_message_bytes']
                ?? config(
                'simpledesk-mail.imap.max_raw_message_bytes',
                50 * 1024 * 1024
            ),
                'IMAP raw message size limit must be positive.'
            ),
            maxAttachmentBytes: $this->positiveInteger(
                $configuration['max_attachment_bytes']
                ?? config(
                'simpledesk-mail.imap.max_attachment_bytes',
                25 * 1024 * 1024
            ),
                'IMAP attachment size limit must be positive.'
            ),
        );
    }

    private function assertChannel(
        MailboxChannel $channel
    ): void {
        if (
            $channel->direction
            !== MailboxChannelDirection::Incoming
        ) {
            throw $this->configurationException(
                "Mailbox channel [{$channel->id}] is not incoming."
            );
        }

        if ($channel->driver !== MailboxDriver::Imap) {
            throw $this->configurationException(
                "Mailbox channel [{$channel->id}] is not an IMAP channel."
            );
        }
    }

    private function credentials(
        MailboxChannel $channel,
        array $configuration,
        array $secrets,
    ): array {
        return match ($channel->auth_type) {
            MailAuthenticationType::None => [
                null,
                null,
            ],

            MailAuthenticationType::Password => [
                $this->requiredCredential(
                    $secrets['username']
                    ?? $configuration['username']
                    ?? $channel
                        ->providerConnection
                        ?->account_identifier,
                    'IMAP username is required.'
                ),
                $this->requiredCredential(
                    $secrets['password'] ?? null,
                    'IMAP password is required.'
                ),
            ],

            MailAuthenticationType::OAuth2 => [
                $this->requiredCredential(
                    $secrets['username']
                    ?? $configuration['username']
                    ?? $channel
                        ->providerConnection
                        ?->account_identifier,
                    'IMAP username is required for OAuth2.'
                ),
                $this->requiredCredential(
                    $secrets['access_token'] ?? null,
                    'IMAP OAuth2 access token is required.'
                ),
            ],

            default => throw $this->configurationException(
                "Authentication type [{$channel->auth_type->value}] "
                . 'is not supported by the IMAP driver.'
            ),
        };
    }

    private function requiredString(
        array $data,
        string $key,
        string $message,
        ?string $default = null,
    ): string {
        $value = $this->nullableString(
            $data[$key] ?? $default
        );

        if ($value === null) {
            throw $this->configurationException($message);
        }

        return $value;
    }

    private function requiredCredential(
        mixed $value,
        string $message,
    ): string {
        $value = $this->nullableString($value);

        if ($value === null) {
            throw $this->configurationException($message);
        }

        return $value;
    }

    private function nullableString(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== ''
            ? $value
            : null;
    }

    private function integer(
        mixed $value
    ): int {
        if (
            filter_var(
                $value,
                FILTER_VALIDATE_INT
            ) === false
        ) {
            throw $this->configurationException(
                'IMAP configuration contains an invalid integer.'
            );
        }

        return (int) $value;
    }

    private function positiveInteger(
        mixed $value,
        string $message,
    ): int {
        $value = $this->integer($value);

        if ($value < 1) {
            throw $this->configurationException($message);
        }

        return $value;
    }

    private function boolean(
        mixed $value
    ): bool {
        if (is_bool($value)) {
            return $value;
        }

        $parsed = filter_var(
            $value,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE,
        );

        if ($parsed === null) {
            throw $this->configurationException(
                'IMAP configuration contains an invalid boolean.'
            );
        }

        return $parsed;
    }

    private function configurationException(
        string $message
    ): MailDriverException {
        return new MailDriverException(
            message: $message,
            driverErrorCode: 'imap_invalid_configuration',
            retryable: false,
            failoverAllowed: true,
            affectsChannelHealth: true,
        );
    }
}
