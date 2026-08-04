<?php

namespace App\Services\Admin\Mail\Drivers\Smtp;

use App\Contracts\Admin\Mail\OutgoingMailDriver;
use App\Data\Admin\Mail\MailConnectionTestResultData;
use App\Data\Admin\Mail\OutgoingEmailMessageData;
use App\Data\Admin\Mail\OutgoingSendResultData;
use App\Enums\Admin\Mail\MailAuthenticationType;
use App\Enums\Admin\Mail\MailboxDriver;
use App\Exceptions\Admin\Mail\MailDriverException;
use App\Models\Admin\Mail\MailboxChannel;
use DateTimeImmutable;
use Throwable;

class SmtpMailDriver implements OutgoingMailDriver
{
    public function __construct(
        private readonly SmtpChannelConfigurationFactory $configurationFactory,
        private readonly SmtpTransportFactory $transportFactory,
        private readonly SymfonyEmailFactory $emailFactory,
        private readonly SmtpExceptionMapper $exceptions,
    ) {}

    public function driver(): MailboxDriver
    {
        return MailboxDriver::Smtp;
    }

    public function test(
        MailboxChannel $channel
    ): MailConnectionTestResultData {
        $oauthRetried = false;

        while (true) {
            $configuration = $this
                ->configurationFactory
                ->make($channel);

            $transport = $this
                ->transportFactory
                ->make($configuration);

            $startedAt = hrtime(true);

            try {
                $transport->start();

                $this->transportFactory->assertSecureConnection(
                    transport: $transport,
                    configuration: $configuration,
                );

                $latencyMilliseconds = (int) round(
                    (
                        hrtime(true) - $startedAt
                    ) / 1_000_000
                );

                return MailConnectionTestResultData::success(
                    message: 'SMTP connection and authentication succeeded.',

                    latencyMilliseconds: $latencyMilliseconds,

                    details: [
                        'host' =>
                            $configuration->host,

                        'port' =>
                            $configuration->port,

                        'encryption' =>
                            $configuration
                                ->encryption
                                ->value,

                        'authenticated' =>
                            $configuration->username
                            !== null,

                        'verify_peer' =>
                            $configuration->verifyPeer,
                    ],
                );
            } catch (Throwable $exception) {
                $mapped = $this->exceptions->map(
                    exception: $exception,
                    operation: 'connection test',
                );

                if (
                    ! $oauthRetried
                    && $channel->auth_type
                    === MailAuthenticationType::OAuth2
                    && $mapped->driverErrorCode()
                    === 'smtp_authentication_failed'
                ) {
                    $oauthRetried = true;

                    $this
                        ->configurationFactory
                        ->refreshOAuthToken(
                            $channel
                        );
                    continue;
                }

                throw $mapped;
            } finally {
                try {
                    $transport->stop();
                } catch (Throwable) {
                    //
                }
            }
        }
    }

    public function send(
        MailboxChannel $channel,
        OutgoingEmailMessageData $message
    ): OutgoingSendResultData {
        $oauthRetried = false;

        while (true) {
            $configuration = $this
                ->configurationFactory
                ->make($channel);

            $transport = $this
                ->transportFactory
                ->make($configuration);

            $email = $this->emailFactory->make(
                $message
            );

            try {
                $transport->start();

                $this->transportFactory->assertSecureConnection(
                    transport: $transport,
                    configuration: $configuration,
                );

                $sentMessage = $transport->send(
                    $email
                );

                if ($sentMessage === null) {
                    throw new MailDriverException(
                        message: 'SMTP transport did not return a sent message.',
                        driverErrorCode: 'smtp_empty_send_result',
                        retryable: true,
                        failoverAllowed: true,
                        affectsChannelHealth: true,
                    );
                }

                return new OutgoingSendResultData(
                    externalMessageId:
                    $sentMessage->getMessageId(),

                    internetMessageId:
                    $message->internetMessageId,

                    acceptedRecipients:
                    array_values(
                        array_merge(
                            $message->to,
                            $message->cc,
                            $message->bcc,
                        )
                    ),

                    rejectedRecipients:
                    [],

                    sentAt:
                    new DateTimeImmutable,

                    providerResponse: [
                        'transport_message_id' =>
                            $sentMessage->getMessageId(),
                    ],

                    metadata: [
                        'host' =>
                            $configuration->host,

                        'port' =>
                            $configuration->port,

                        'encryption' =>
                            $configuration
                                ->encryption
                                ->value,
                    ],
                );
            } catch (Throwable $exception) {
                $mapped = $this->exceptions->map(
                    exception: $exception,
                    operation: 'send',
                );

                if (
                    ! $oauthRetried
                    && $channel->auth_type
                    === MailAuthenticationType::OAuth2
                    && $mapped->driverErrorCode()
                    === 'smtp_authentication_failed'
                ) {
                    $oauthRetried = true;

                    $this
                        ->configurationFactory
                        ->refreshOAuthToken(
                            $channel
                        );


                    continue;
                }

                throw $mapped;
            } finally {
                try {
                    $transport->stop();
                } catch (Throwable) {
                    //
                }
            }
        }
    }
}
