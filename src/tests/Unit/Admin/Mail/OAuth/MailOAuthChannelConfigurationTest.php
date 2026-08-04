<?php

namespace Tests\Unit\Admin\Mail\OAuth;

use App\Models\Admin\Mail\MailboxChannel;
use App\Models\Admin\Mail\MailProviderConnection;
use App\Services\Admin\Mail\Drivers\Imap\ImapChannelConfigurationFactory;
use App\Services\Admin\Mail\Drivers\Smtp\SmtpChannelConfigurationFactory;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\Support\CreatesMailTestData;
use Tests\TestCase;

class MailOAuthChannelConfigurationTest extends TestCase
{
    use CreatesMailTestData;
    use DatabaseMigrations;

    public function test_linked_imap_and_smtp_channels_receive_provider_access_token_for_xoauth2(): void
    {
        $mailbox = $this->createMailbox();
        $connection = $this->connection();
        $imap = $this->channel($mailbox->id, $connection->id, 'incoming', 'imap', ['host' => 'imap.gmail.com', 'port' => 993, 'encryption' => 'tls', 'folder' => 'INBOX']);
        $smtp = $this->channel($mailbox->id, $connection->id, 'outgoing', 'smtp', ['host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'starttls']);

        $imapConfiguration = app(ImapChannelConfigurationFactory::class)->make($imap);
        $smtpConfiguration = app(SmtpChannelConfigurationFactory::class)->make($smtp);

        $this->assertSame('verified@example.test', $imapConfiguration->username);
        $this->assertSame('access-token-value', $imapConfiguration->password);
        $this->assertSame('verified@example.test', $smtpConfiguration->username);
        $this->assertSame('access-token-value', $smtpConfiguration->password);
        $this->assertArrayNotHasKey('access_token', $imap->secret_configuration);
        $this->assertArrayNotHasKey('access_token', $smtp->secret_configuration);
    }

    public function test_password_smtp_channel_still_uses_its_password(): void
    {
        $mailbox = $this->createMailbox();
        $channel = $this->channel($mailbox->id, null, 'outgoing', 'smtp', ['host' => 'smtp.example.test', 'port' => 587, 'encryption' => 'starttls'], 'password', ['username' => 'support@example.test', 'password' => 'mail-password']);

        $configuration = app(SmtpChannelConfigurationFactory::class)->make($channel);

        $this->assertSame('support@example.test', $configuration->username);
        $this->assertSame('mail-password', $configuration->password);
    }

    private function connection(): MailProviderConnection
    {
        return MailProviderConnection::query()->create([
            'name' => 'Google', 'provider' => 'google', 'auth_type' => 'oauth2', 'account_identifier' => 'verified@example.test',
            'configuration' => ['client_id' => 'client-id'],
            'secret_configuration' => ['client_secret' => 'client-secret-value', 'access_token' => 'access-token-value', 'refresh_token' => 'refresh-token-value'],
            'scopes' => ['https://mail.google.com/'], 'token_expires_at' => now()->addHour(), 'is_active' => true, 'health_status' => 'healthy',
        ]);
    }

    private function channel(int $mailboxId, ?int $connectionId, string $direction, string $driver, array $configuration, string $authType = 'oauth2', array $secrets = []): MailboxChannel
    {
        return MailboxChannel::query()->create([
            'mailbox_id' => $mailboxId, 'provider_connection_id' => $connectionId, 'name' => ucfirst($direction),
            'direction' => $direction, 'driver' => $driver, 'auth_type' => $authType, 'is_enabled' => true,
            'is_primary' => true, 'failover_order' => 1, 'configuration' => $configuration, 'secret_configuration' => $secrets, 'health_status' => 'unknown',
        ]);
    }
}
