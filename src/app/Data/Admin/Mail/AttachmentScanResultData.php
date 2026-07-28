<?php

namespace App\Data\Admin\Mail;

final readonly class AttachmentScanResultData
{
    public function __construct(
        public bool $clean,
        public ?string $signature,
        public string $driver,
        public string $rawResponse,
        public int $scannedBytes,
        public array $metadata = [],
    ) {
    }

    public static function clean(
        string $driver,
        string $rawResponse,
        int $scannedBytes,
        array $metadata = [],
    ): self {
        return new self(
            clean: true,
            signature: null,
            driver: $driver,
            rawResponse: $rawResponse,
            scannedBytes: $scannedBytes,
            metadata: $metadata,
        );
    }

    public static function infected(
        string $signature,
        string $driver,
        string $rawResponse,
        int $scannedBytes,
        array $metadata = [],
    ): self {
        return new self(
            clean: false,
            signature: $signature,
            driver: $driver,
            rawResponse: $rawResponse,
            scannedBytes: $scannedBytes,
            metadata: $metadata,
        );
    }
}
