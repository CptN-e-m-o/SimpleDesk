<?php

namespace Tests\Unit\Admin\Mail\Drivers\Imap;

use App\Data\Admin\Mail\ImapChannelConfigurationData;
use App\Data\Admin\Mail\MailConnectionTestResultData;
use App\Enums\Admin\Mail\ImapEncryption;
use App\Enums\Admin\Mail\MailAuthenticationType;
use App\Exceptions\Admin\Mail\MailDriverException;
use App\Models\Admin\Mail\MailboxChannel;
use App\Services\Admin\Mail\Drivers\Imap\ImapChannelConfigurationFactory;
use App\Services\Admin\Mail\Drivers\Imap\ImapClientFactory;
use App\Services\Admin\Mail\Drivers\Imap\ImapExceptionMapper;
use App\Services\Admin\Mail\Drivers\Imap\ImapMailDriver;
use App\Services\Admin\Mail\Drivers\Imap\ImapMessageNormalizer;
use Mockery;
use RuntimeException;
use Tests\TestCase;
use Webklex\PHPIMAP\Client;

class ImapMailDriverOAuthRetryTest extends TestCase
{
    public function test_oauth_authentication_failure_disconnects_client_forces_one_refresh_and_retries_once(): void
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

        $firstClient = Mockery::mock(
            Client::class
        );

        $secondClient = Mockery::mock(
            Client::class
        );

        $configurationFactory = Mockery::mock(
            ImapChannelConfigurationFactory::class
        );

        $clientFactory = Mockery::mock(
            ImapClientFactory::class
        );

        $normalizer = Mockery::mock(
            ImapMessageNormalizer::class
        );

        $exceptions = Mockery::mock(
            ImapExceptionMapper::class
        );

        $authenticationFailure = new RuntimeException(
            'IMAP authentication failed.'
        );

        $mappedFailure = $this
            ->authenticationFailure();

        $firstClientDisconnected = false;
        $refreshPerformed = false;
        $configurationCalls = 0;

        $configurationFactory
            ->shouldReceive('make')
            ->twice()
            ->with($channel)
            ->andReturnUsing(
                function () use (
                    $oldConfiguration,
                    $newConfiguration,
                    &$configurationCalls,
                    &$refreshPerformed
                ): ImapChannelConfigurationData {
                    $configurationCalls++;

                    if ($configurationCalls === 1) {
                        return $oldConfiguration;
                    }

                    $this->assertTrue(
                        $refreshPerformed,
                        'The second IMAP configuration was created before the OAuth token was refreshed.'
                    );

                    return $newConfiguration;
                }
            );

        $clientFactory
            ->shouldReceive('make')
            ->once()
            ->with($oldConfiguration)
            ->andReturn(
                $firstClient
            );

        $clientFactory
            ->shouldReceive('make')
            ->once()
            ->with($newConfiguration)
            ->andReturn(
                $secondClient
            );

        $firstClient
            ->shouldReceive('connect')
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

        $firstClient
            ->shouldReceive('isConnected')
            ->twice()
            ->andReturn(
                true,
                false
            );

        $firstClient
            ->shouldReceive('disconnect')
            ->once()
            ->andReturnUsing(
                function () use (
                    &$firstClientDisconnected
                ): void {
                    $firstClientDisconnected = true;
                }
            );

        $configurationFactory
            ->shouldReceive(
                'refreshOAuthToken'
            )
            ->once()
            ->with($channel)
            ->andReturnUsing(
                function () use (
                    &$firstClientDisconnected,
                    &$refreshPerformed
                ): void {
                    $this->assertTrue(
                        $firstClientDisconnected,
                        'The failed IMAP client must be disconnected before refreshing the OAuth token.'
                    );

                    $refreshPerformed = true;
                }
            );

        $secondClient
            ->shouldReceive('connect')
            ->once();

        $secondClient
            ->shouldReceive('checkFolder')
            ->once()
            ->with('INBOX')
            ->andReturn([
                'exists' => 5,
                'recent' => 1,
                'uidvalidity' => 100,
                'uidnext' => 6,
            ]);

        $secondClient
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $secondClient
            ->shouldReceive('disconnect')
            ->once();

        $driver = new ImapMailDriver(
            configurationFactory: $configurationFactory,

            clientFactory: $clientFactory,

            normalizer: $normalizer,

            exceptions: $exceptions,
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

        $this->assertTrue(
            $firstClientDisconnected
        );

        $this->assertTrue(
            $refreshPerformed
        );

        $this->assertSame(
            5,
            $result->details['exists']
        );

        $this->assertSame(
            100,
            $result->details['uidvalidity']
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

        $firstClient = Mockery::mock(
            Client::class
        );

        $secondClient = Mockery::mock(
            Client::class
        );

        $configurationFactory = Mockery::mock(
            ImapChannelConfigurationFactory::class
        );

        $clientFactory = Mockery::mock(
            ImapClientFactory::class
        );

        $normalizer = Mockery::mock(
            ImapMessageNormalizer::class
        );

        $exceptions = Mockery::mock(
            ImapExceptionMapper::class
        );

        $firstFailure = new RuntimeException(
            'First IMAP authentication failure.'
        );

        $secondFailure = new RuntimeException(
            'Second IMAP authentication failure.'
        );

        $firstMappedFailure = $this
            ->authenticationFailure();

        $secondMappedFailure = $this
            ->authenticationFailure();

        $firstClientDisconnected = false;
        $refreshPerformed = false;
        $configurationCalls = 0;

        $configurationFactory
            ->shouldReceive('make')
            ->twice()
            ->with($channel)
            ->andReturnUsing(
                function () use (
                    $oldConfiguration,
                    $newConfiguration,
                    &$configurationCalls,
                    &$refreshPerformed
                ): ImapChannelConfigurationData {
                    $configurationCalls++;

                    if ($configurationCalls === 1) {
                        return $oldConfiguration;
                    }

                    $this->assertTrue(
                        $refreshPerformed
                    );

                    return $newConfiguration;
                }
            );

        $clientFactory
            ->shouldReceive('make')
            ->once()
            ->with($oldConfiguration)
            ->andReturn(
                $firstClient
            );

        $clientFactory
            ->shouldReceive('make')
            ->once()
            ->with($newConfiguration)
            ->andReturn(
                $secondClient
            );

        $firstClient
            ->shouldReceive('connect')
            ->once()
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
            ->andReturn(
                $firstMappedFailure
            );

        $firstClient
            ->shouldReceive('isConnected')
            ->twice()
            ->andReturn(
                true,
                false
            );

        $firstClient
            ->shouldReceive('disconnect')
            ->once()
            ->andReturnUsing(
                function () use (
                    &$firstClientDisconnected
                ): void {
                    $firstClientDisconnected = true;
                }
            );

        $configurationFactory
            ->shouldReceive(
                'refreshOAuthToken'
            )
            ->once()
            ->with($channel)
            ->andReturnUsing(
                function () use (
                    &$firstClientDisconnected,
                    &$refreshPerformed
                ): void {
                    $this->assertTrue(
                        $firstClientDisconnected
                    );

                    $refreshPerformed = true;
                }
            );

        $secondClient
            ->shouldReceive('connect')
            ->once()
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
            ->andReturn(
                $secondMappedFailure
            );

        $secondClient
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $secondClient
            ->shouldReceive('disconnect')
            ->once();

        $driver = new ImapMailDriver(
            configurationFactory: $configurationFactory,

            clientFactory: $clientFactory,

            normalizer: $normalizer,

            exceptions: $exceptions,
        );

        try {
            $driver->test(
                $channel
            );

            $this->fail(
                'Expected the second IMAP authentication failure to be thrown.'
            );
        } catch (
            MailDriverException $exception
        ) {
            $this->assertSame(
                'imap_authentication_failed',
                $exception
                    ->driverErrorCode()
            );
        }

        $this->assertTrue(
            $firstClientDisconnected
        );

        $this->assertTrue(
            $refreshPerformed
        );
    }

    public function test_password_authentication_failure_does_not_refresh_oauth_token(): void
    {
        $channel = $this->channel(
            MailAuthenticationType::Password
        );

        $configuration = $this->configuration(
            password: 'imap-password',
            authType: MailAuthenticationType::Password
        );

        $client = Mockery::mock(
            Client::class
        );

        $configurationFactory = Mockery::mock(
            ImapChannelConfigurationFactory::class
        );

        $clientFactory = Mockery::mock(
            ImapClientFactory::class
        );

        $normalizer = Mockery::mock(
            ImapMessageNormalizer::class
        );

        $exceptions = Mockery::mock(
            ImapExceptionMapper::class
        );

        $authenticationFailure = new RuntimeException(
            'IMAP password authentication failed.'
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

        $clientFactory
            ->shouldReceive('make')
            ->once()
            ->with($configuration)
            ->andReturn(
                $client
            );

        $client
            ->shouldReceive('connect')
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

        $client
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(true);

        $client
            ->shouldReceive('disconnect')
            ->once();

        $driver = new ImapMailDriver(
            configurationFactory: $configurationFactory,

            clientFactory: $clientFactory,

            normalizer: $normalizer,

            exceptions: $exceptions,
        );

        try {
            $driver->test(
                $channel
            );

            $this->fail(
                'Expected the IMAP authentication failure to be thrown.'
            );
        } catch (
            MailDriverException $exception
        ) {
            $this->assertSame(
                'imap_authentication_failed',
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
            'auth_type' => $authType->value,
        ]);

        return $channel;
    }

    private function configuration(
        string $password,
        MailAuthenticationType $authType
    ): ImapChannelConfigurationData {
        return new ImapChannelConfigurationData(
            host: 'imap.example.test',

            port: 993,

            encryption: ImapEncryption::Tls,

            authType: $authType,

            username: 'mailbox@example.test',

            password: $password,

            validateCertificate: true,

            folder: 'INBOX',

            processedFolder: 'Processed',

            createProcessedFolder: true,

            expungeOnDelete: true,

            storeRawMessage: true,

            maxRawMessageBytes: 50 * 1024 * 1024,

            maxAttachmentBytes: 25 * 1024 * 1024,
        );
    }

    private function authenticationFailure(): MailDriverException
    {
        return new MailDriverException(
            message: 'IMAP authentication failed.',

            driverErrorCode: 'imap_authentication_failed',

            retryable: false,

            failoverAllowed: true,

            affectsChannelHealth: true,
        );
    }
}
