<?php

namespace App\Services\Admin\Mail\Drivers\Smtp;

use App\Data\Admin\Mail\SmtpChannelConfigurationData;
use App\Enums\Admin\Mail\MailAuthenticationType;
use App\Enums\Admin\Mail\MailboxChannelDirection;
use App\Enums\Admin\Mail\MailboxDriver;
use App\Enums\Admin\Mail\SmtpEncryption;
use App\Exceptions\Admin\Mail\MailDriverException;
use App\Models\Admin\Mail\MailboxChannel;

class SmtpChannelConfigurationFactory
{
    public function make(
        MailboxChannel $channel
    ): SmtpChannelConfigurationData {
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
            'SMTP host is required.'
        );

        $encryption = SmtpEncryption::tryFrom(
            (string) ($configuration['encryption'] ?? 'starttls')
        );

        if ($encryption === null) {
            throw $this->configurationException(
                'Unsupported SMTP encryption value.'
            );
        }

        $port = $this->integer(
            $configuration['port']
            ?? $encryption->defaultPort()
        );

        if ($port < 1 || $port > 65535) {
            throw $this->configurationException(
                'SMTP port must be between 1 and 65535.'
            );
        }

        $timeout = $this->integer(
            $configuration['timeout'] ?? 30
        );

        if ($timeout < 1 || $timeout > 300) {
            throw $this->configurationException(
                'SMTP timeout must be between 1 and 300 seconds.'
            );
        }

        [$username, $password] = $this->credentials(
            channel: $channel,
            configuration: $configuration,
            secrets: $secrets,
        );

        return new SmtpChannelConfigurationData(
            host: $host,
            port: $port,
            encryption: $encryption,
            authType: $channel->auth_type,
            username: $username,
            password: $password,
            timeout: $timeout,
            verifyPeer: $this->boolean(
                $configuration['verify_peer'] ?? true
            ),
            localDomain: $this->nullableString(
                $configuration['local_domain'] ?? null
            ),
            sourceIp: $this->nullableString(
                $configuration['source_ip'] ?? null
            ),
            maxPerSecond: $this->nullableFloat(
                $configuration['max_per_second'] ?? null
            ),
            restartThreshold: $this->nullablePositiveInteger(
                $configuration['restart_threshold'] ?? null,
                'SMTP restart threshold must be a positive integer.'
            ),
            restartThresholdSleep: $this->nonNegativeInteger(
                $configuration['restart_threshold_sleep'] ?? 0,
                'SMTP restart threshold sleep cannot be negative.'
            ),
            pingThreshold: $this->nullablePositiveInteger(
                $configuration['ping_threshold'] ?? null,
                'SMTP ping threshold must be a positive integer.'
            ),
        );
    }

    private function assertChannel(
        MailboxChannel $channel
    ): void {
        if (
            $channel->direction
            !== MailboxChannelDirection::Outgoing
        ) {
            throw $this->configurationException(
                "Mailbox channel [{$channel->id}] is not outgoing."
            );
        }

        if ($channel->driver !== MailboxDriver::Smtp) {
            throw $this->configurationException(
                "Mailbox channel [{$channel->id}] is not an SMTP channel."
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
                    'SMTP username is required for password authentication.'
                ),
                $this->requiredCredential(
                    $secrets['password'] ?? null,
                    'SMTP password is required for password authentication.'
                ),
            ],

            MailAuthenticationType::OAuth2 => [
                $this->requiredCredential(
                    $secrets['username']
                    ?? $configuration['username']
                    ?? $channel
                        ->providerConnection
                        ?->account_identifier,
                    'SMTP username is required for OAuth2 authentication.'
                ),
                $this->requiredCredential(
                    $secrets['access_token'] ?? null,
                    'SMTP OAuth2 access token is required.'
                ),
            ],

            default => throw $this->configurationException(
                "Authentication type [{$channel->auth_type->value}] "
                . 'is not supported by the SMTP driver.'
            ),
        };
    }

    private function requiredString(
        array $data,
        string $key,
        string $message,
    ): string {
        $value = $this->nullableString(
            $data[$key] ?? null
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

        return $value !== '' ? $value : null;
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
                'SMTP configuration contains an invalid integer.'
            );
        }

        return (int) $value;
    }

    private function nonNegativeInteger(
        mixed $value,
        string $message,
    ): int {
        $value = $this->integer($value);

        if ($value < 0) {
            throw $this->configurationException($message);
        }

        return $value;
    }

    private function nullablePositiveInteger(
        mixed $value,
        string $message,
    ): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        $value = $this->integer($value);

        if ($value < 1) {
            throw $this->configurationException($message);
        }

        return $value;
    }

    private function nullableFloat(
        mixed $value
    ): ?float {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            throw $this->configurationException(
                'SMTP max_per_second must be numeric.'
            );
        }

        $value = (float) $value;

        if ($value <= 0) {
            throw $this->configurationException(
                'SMTP max_per_second must be greater than zero.'
            );
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
                'SMTP configuration contains an invalid boolean.'
            );
        }

        return $parsed;
    }

    private function configurationException(
        string $message
    ): MailDriverException {
        return new MailDriverException(
            message: $message,
            driverErrorCode: 'smtp_invalid_configuration',
            retryable: false,
            failoverAllowed: true,
            affectsChannelHealth: true,
        );
    }
}
