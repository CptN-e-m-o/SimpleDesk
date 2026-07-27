<?php

namespace App\Data\Admin\Mail;

use App\Enums\Admin\Mail\ImapEncryption;
use App\Enums\Admin\Mail\MailAuthenticationType;

final readonly class ImapChannelConfigurationData
{
    public function __construct(
        public string $host,
        public int $port,
        public ImapEncryption $encryption,
        public MailAuthenticationType $authType,
        public ?string $username,
        public ?string $password,
        public bool $validateCertificate,
        public string $folder,
        public ?string $processedFolder,
        public bool $createProcessedFolder,
        public bool $expungeOnDelete,
        public bool $storeRawMessage,
        public int $maxRawMessageBytes,
        public int $maxAttachmentBytes,
    ) {
    }
}
