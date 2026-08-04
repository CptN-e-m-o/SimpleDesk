<?php

namespace Tests\Unit\Admin\Mail\Drivers\Smtp;

use App\Data\Admin\Mail\MailConnectionTestResultData;
use App\Data\Admin\Mail\SmtpChannelConfigurationData;
use App\Enums\Admin\Mail\MailAuthenticationType;
use App\Enums\Admin\Mail\SmtpEncryption;
use App\Exceptions\Admin\Mail\MailDriverException;
use App\Models\Admin\Mail\MailboxChannel;
use App\Services\Admin\Mail\Drivers\Smtp\SmtpChannelConfigurationFactory;
use App\Services\Admin\Mail\Drivers\Smtp\SmtpExceptionMapper;
use App\Services\Admin\Mail\Drivers\Smtp\SmtpMailDriver;
use App\Services\Admin\Mail\Drivers\Smtp\SmtpTransportFactory;
use App\Services\Admin\Mail\Drivers\Smtp\SymfonyEmailFactory;
use Mockery;
use RuntimeException;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Tests\TestCase;

class SmtpMailDriverOAuthRetryTest extends TestCase
{
    public function test_oauth_authentication_failure_forces_one_refresh_and_retries_once(): void
    {
        $channel = $this->channel(
            MailAuthenticationType::OAuth2
        );

        $oldConfiguration = $this->configuration(
            password: 'old-access-token',
            authType: MailAuthenticationType::OAuth2
        );

        $newConfiguration = $this->configuration(
            password: 'new-access-token',
            authType: MailAuthenticationType::OAuth2
        );

        $firstTransport = Mockery::mock(
            EsmtpTransport::class
        );

        $secondTransport = Mockery::mock(
            EsmtpTransport::class
        );

        $configurationFactory = Mockery::mock(
            SmtpChannelConfigurationFactory::class
        );

        $transportFactory = Mockery::mock(
            SmtpTransportFactory::class
        );

        $emailFactory = Mockery::mock(
            SymfonyEmailFactory::class
        );

        $exceptions = Mockery::mock(
            SmtpExceptionMapper::class
        );

        $authenticationFailure = new RuntimeException(
            'SMTP authentication failed.'
        );

        $mappedFailure = $this
            ->authenticationFailure();

        $configurationFactory
            ->shouldReceive('make')
            ->once()
            ->with($channel)
            ->ordered()
            ->andReturn(
                $oldConfiguration
            );

        $transportFactory
            ->shouldReceive('make')
            ->once()
            ->with($oldConfiguration)
            ->ordered()
            ->andReturn(
                $firstTransport
            );

        $firstTransport
            ->shouldReceive('start')
            ->once()
            ->ordered()
            ->andThrow(
                $authenticationFailure
            );

        $exceptions
            ->shouldReceive('map')
            ->once()
            ->with(
                $authenticationFailure,
                'connection test'
            )
            ->ordered()
            ->andReturn(
                $mappedFailure
            );

        $configurationFactory
            ->shouldReceive(
                'refreshOAuthToken'
            )
            ->once()
            ->with($channel)
            ->ordered();

        $firstTransport
            ->shouldReceive('stop')
            ->once()
            ->ordered();

        $configurationFactory
            ->shouldReceive('make')
            ->once()
            ->with($channel)
            ->ordered()
            ->andReturn(
                $newConfiguration
            );

        $transportFactory
            ->shouldReceive('make')
            ->once()
            ->with($newConfiguration)
            ->ordered()
            ->andReturn(
                $secondTransport
            );

        $secondTransport
            ->shouldReceive('start')
            ->once()
            ->ordered();

        $transportFactory
            ->shouldReceive(
                'assertSecureConnection'
            )
            ->once()
            ->with(
                $secondTransport,
                $newConfiguration
            )
            ->ordered();

        $secondTransport
            ->shouldReceive('stop')
            ->once()
            ->ordered();

        $driver = new SmtpMailDriver(
            configurationFactory:
            $configurationFactory,

            transportFactory:
            $transportFactory,

            emailFactory:
            $emailFactory,

            exceptions:
            $exceptions,
        );

        $result = $driver->test(
            $channel
        );

        $this->assertInstanceOf(
            MailConnectionTestResultData::class,
            $result
        );

        $this->assertTrue(
            $result->successful
        );
    }

    public function test_second_oauth_authentication_failure_is_not_retried_again(): void
    {
        $channel = $this->channel(
            MailAuthenticationType::OAuth2
        );

        $oldConfiguration = $this->configuration(
            password: 'old-access-token',
            authType: MailAuthenticationType::OAuth2
        );

        $newConfiguration = $this->configuration(
            password: 'new-access-token',
            authType: MailAuthenticationType::OAuth2
        );

        $firstTransport = Mockery::mock(
            EsmtpTransport::class
        );

        $secondTransport = Mockery::mock(
            EsmtpTransport::class
        );

        $configurationFactory = Mockery::mock(
            SmtpChannelConfigurationFactory::class
        );

        $transportFactory = Mockery::mock(
            SmtpTransportFactory::class
        );

        $emailFactory = Mockery::mock(
            SymfonyEmailFactory::class
        );

        $exceptions = Mockery::mock(
            SmtpExceptionMapper::class
        );

        $firstFailure = new RuntimeException(
            'First SMTP authentication failure.'
        );

        $secondFailure = new RuntimeException(
            'Second SMTP authentication failure.'
        );

        $firstMappedFailure = $this
            ->authenticationFailure();

        $secondMappedFailure = $this
            ->authenticationFailure();

        $configurationFactory
            ->shouldReceive('make')
            ->once()
            ->with($channel)
            ->ordered()
            ->andReturn(
                $oldConfiguration
            );

        $transportFactory
            ->shouldReceive('make')
            ->once()
            ->with($oldConfiguration)
            ->ordered()
            ->andReturn(
                $firstTransport
            );

        $firstTransport
            ->shouldReceive('start')
            ->once()
            ->ordered()
            ->andThrow(
                $firstFailure
            );

        $exceptions
            ->shouldReceive('map')
            ->once()
            ->with(
                $firstFailure,
                'connection test'
            )
            ->ordered()
            ->andReturn(
                $firstMappedFailure
            );

        $configurationFactory
            ->shouldReceive(
                'refreshOAuthToken'
            )
            ->once()
            ->with($channel)
            ->ordered();

        $firstTransport
            ->shouldReceive('stop')
            ->once()
            ->ordered();

        $configurationFactory
            ->shouldReceive('make')
            ->once()
            ->with($channel)
            ->ordered()
            ->andReturn(
                $newConfiguration
            );

        $transportFactory
            ->shouldReceive('make')
            ->once()
            ->with($newConfiguration)
            ->ordered()
            ->andReturn(
                $secondTransport
            );

        $secondTransport
            ->shouldReceive('start')
            ->once()
            ->ordered()
            ->andThrow(
                $secondFailure
            );

        $exceptions
            ->shouldReceive('map')
            ->once()
            ->with(
                $secondFailure,
                'connection test'
            )
            ->ordered()
            ->andReturn(
                $secondMappedFailure
            );

        $secondTransport
            ->shouldReceive('stop')
            ->once()
            ->ordered();

        $driver = new SmtpMailDriver(
            configurationFactory:
            $configurationFactory,

            transportFactory:
            $transportFactory,

            emailFactory:
            $emailFactory,

            exceptions:
            $exceptions,
        );

        try {
            $driver->test(
                $channel
            );

            $this->fail(
                'Expected the second SMTP authentication failure to be thrown.'
            );
        } catch (
        MailDriverException $exception
        ) {
            $this->assertSame(
                'smtp_authentication_failed',
                $exception
                    ->driverErrorCode()
            );
        }
    }

    public function test_password_authentication_failure_does_not_refresh_oauth_token(): void
    {
        $channel = $this->channel(
            MailAuthenticationType::Password
        );

        $configuration = $this->configuration(
            password: 'smtp-password',
            authType: MailAuthenticationType::Password
        );

        $transport = Mockery::mock(
            EsmtpTransport::class
        );

        $configurationFactory = Mockery::mock(
            SmtpChannelConfigurationFactory::class
        );

        $transportFactory = Mockery::mock(
            SmtpTransportFactory::class
        );

        $emailFactory = Mockery::mock(
            SymfonyEmailFactory::class
        );

        $exceptions = Mockery::mock(
            SmtpExceptionMapper::class
        );

        $authenticationFailure = new RuntimeException(
            'SMTP password authentication failed.'
        );

        $mappedFailure = $this
            ->authenticationFailure();

        $configurationFactory
            ->shouldReceive('make')
            ->once()
            ->with($channel)
            ->andReturn(
                $configuration
            );

        $transportFactory
            ->shouldReceive('make')
            ->once()
            ->with($configuration)
            ->andReturn(
                $transport
            );

        $transport
            ->shouldReceive('start')
            ->once()
            ->andThrow(
                $authenticationFailure
            );

        $exceptions
            ->shouldReceive('map')
            ->once()
            ->with(
                $authenticationFailure,
                'connection test'
            )
            ->andReturn(
                $mappedFailure
            );

        $configurationFactory
            ->shouldNotReceive(
                'refreshOAuthToken'
            );

        $transport
            ->shouldReceive('stop')
            ->once();

        $driver = new SmtpMailDriver(
            configurationFactory:
            $configurationFactory,

            transportFactory:
            $transportFactory,

            emailFactory:
            $emailFactory,

            exceptions:
            $exceptions,
        );

        try {
            $driver->test(
                $channel
            );

            $this->fail(
                'Expected the SMTP authentication failure to be thrown.'
            );
        } catch (
        MailDriverException $exception
        ) {
            $this->assertSame(
                'smtp_authentication_failed',
                $exception
                    ->driverErrorCode()
            );
        }
    }

    private function channel(
        MailAuthenticationType $authType
    ): MailboxChannel {
        $channel = new MailboxChannel;

        $channel->forceFill([
            'auth_type' =>
                $authType->value,
        ]);

        return $channel;
    }

    private function configuration(
        string $password,
        MailAuthenticationType $authType
    ): SmtpChannelConfigurationData {
        return new SmtpChannelConfigurationData(
            host: 'smtp.example.test',
            port: 587,
            encryption: SmtpEncryption::StartTls,
            authType: $authType,
            username: 'mailbox@example.test',
            password: $password,
            timeout: 30,
            verifyPeer: true,
            localDomain: null,
            sourceIp: null,
            maxPerSecond: null,
            restartThreshold: null,
            restartThresholdSleep: 0,
            pingThreshold: null,
        );
    }

    private function authenticationFailure(): MailDriverException
    {
        return new MailDriverException(
            message: 'SMTP authentication failed.',
            driverErrorCode:
            'smtp_authentication_failed',
            retryable: false,
            failoverAllowed: true,
            affectsChannelHealth: true,
        );
    }
}
