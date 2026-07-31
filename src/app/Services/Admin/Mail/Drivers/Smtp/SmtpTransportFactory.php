<?php

namespace App\Services\Admin\Mail\Drivers\Smtp;

use App\Data\Admin\Mail\SmtpChannelConfigurationData;
use App\Enums\Admin\Mail\MailAuthenticationType;
use App\Enums\Admin\Mail\SmtpEncryption;
use App\Exceptions\Admin\Mail\MailDriverException;
use Illuminate\Mail\MailManager;
use Symfony\Component\Mailer\Transport\Smtp\Auth\XOAuth2Authenticator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

class SmtpTransportFactory
{
    public function __construct(
        private readonly MailManager $mailManager,
    ) {}

    public function make(
        SmtpChannelConfigurationData $configuration
    ): EsmtpTransport {
        $transport = $this->mailManager->createSymfonyTransport(
            $this->laravelConfiguration($configuration)
        );

        if (! $transport instanceof EsmtpTransport) {
            throw new MailDriverException(
                message: 'Laravel did not create an ESMTP transport.',
                driverErrorCode: 'smtp_transport_creation_failed',
                retryable: false,
                failoverAllowed: true,
                affectsChannelHealth: true,
            );
        }

        $transport->setAutoTls(
            $configuration->encryption
            === SmtpEncryption::StartTls
        );

        if (
            $configuration->authType
            === MailAuthenticationType::OAuth2
        ) {
            $transport->setAuthenticators([
                new XOAuth2Authenticator,
            ]);
        }

        /*
         * setRequireTls появился не во всех поддерживаемых
         * версиях Symfony Mailer, поэтому проверяем метод.
         */
        if (method_exists($transport, 'setRequireTls')) {
            $transport->setRequireTls(
                $configuration->encryption->usesTls()
            );
        }

        return $transport;
    }

    public function assertSecureConnection(
        EsmtpTransport $transport,
        SmtpChannelConfigurationData $configuration,
    ): void {
        if (! $configuration->encryption->usesTls()) {
            return;
        }

        $stream = $transport->getStream();

        if (
            ! $stream instanceof SocketStream
            || ! $stream->isTls()
        ) {
            throw new MailDriverException(
                message: 'SMTP connection was established without TLS.',
                driverErrorCode: 'smtp_tls_not_established',
                retryable: false,
                failoverAllowed: true,
                affectsChannelHealth: true,
            );
        }
    }

    private function laravelConfiguration(
        SmtpChannelConfigurationData $configuration
    ): array {
        $config = [
            'transport' => 'smtp',

            /*
             * smtps означает implicit TLS.
             * Для STARTTLS остаётся обычная схема smtp.
             */
            'scheme' => $configuration
                ->encryption
                ->usesImplicitTls()
                ? 'smtps'
                : 'smtp',

            'host' => $configuration->host,
            'port' => $configuration->port,
            'username' => $configuration->username,
            'password' => $configuration->password,
            'timeout' => $configuration->timeout,

            'auto_tls' => $configuration->encryption
                === SmtpEncryption::StartTls,

            'verify_peer' => $configuration->verifyPeer,
        ];

        if ($configuration->localDomain !== null) {
            $config['local_domain'] =
                $configuration->localDomain;
        }

        if ($configuration->sourceIp !== null) {
            $config['source_ip'] =
                $configuration->sourceIp;
        }

        if ($configuration->maxPerSecond !== null) {
            $config['max_per_second'] =
                $configuration->maxPerSecond;
        }

        if ($configuration->restartThreshold !== null) {
            $config['restart_threshold'] =
                $configuration->restartThreshold;

            $config['restart_threshold_sleep'] =
                $configuration->restartThresholdSleep;
        }

        if ($configuration->pingThreshold !== null) {
            $config['ping_threshold'] =
                $configuration->pingThreshold;
        }

        return $config;
    }
}
