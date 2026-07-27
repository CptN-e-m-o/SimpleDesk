<?php

namespace App\Data\Admin\Mail;

use App\Enums\Admin\Mail\MailAuthenticationType;
use App\Enums\Admin\Mail\SmtpEncryption;

final readonly class SmtpChannelConfigurationData
{
    public function __construct(
        public string $host,
        public int $port,
        public SmtpEncryption $encryption,
        public MailAuthenticationType $authType,
        public ?string $username,
        public ?string $password,
        public int $timeout,
        public bool $verifyPeer,
        public ?string $localDomain = null,
        public ?string $sourceIp = null,
        public ?float $maxPerSecond = null,
        public ?int $restartThreshold = null,
        public int $restartThresholdSleep = 0,
        public ?int $pingThreshold = null,
    ) {
    }
}
