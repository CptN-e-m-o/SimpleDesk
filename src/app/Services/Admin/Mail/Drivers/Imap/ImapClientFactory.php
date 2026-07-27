<?php

namespace App\Services\Admin\Mail\Drivers\Imap;

use App\Data\Admin\Mail\ImapChannelConfigurationData;
use App\Enums\Admin\Mail\MailAuthenticationType;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\IMAP;

class ImapClientFactory
{
    public function make(
        ImapChannelConfigurationData $configuration
    ): Client {
        $manager = new ClientManager([
            'options' => [
                'fetch' => IMAP::FT_PEEK,

                'sequence' => IMAP::ST_UID,

                'fetch_body' => true,
                'fetch_flags' => true,
                'message_key' => 'list',
                'fetch_order' => 'asc',
                'soft_fail' => false,
            ],
        ]);

        $account = [
            'host' => $configuration->host,
            'port' => $configuration->port,
            'encryption' =>
                $configuration->encryption->webklexValue(),
            'validate_cert' =>
                $configuration->validateCertificate,
            'protocol' => 'imap',
        ];

        if ($configuration->username !== null) {
            $account['username'] =
                $configuration->username;
        }

        if ($configuration->password !== null) {
            $account['password'] =
                $configuration->password;
        }

        if (
            $configuration->authType
            === MailAuthenticationType::OAuth2
        ) {
            $account['authentication'] = 'oauth';
        }

        return $manager->make($account);
    }
}
