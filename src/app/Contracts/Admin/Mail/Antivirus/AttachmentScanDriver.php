<?php

namespace App\Contracts\Admin\Mail\Antivirus;

use App\Data\Admin\Mail\AttachmentScanResultData;
use App\Data\Admin\Mail\MailConnectionTestResultData;

interface AttachmentScanDriver
{
    public function name(): string;

    public function testConnection(): MailConnectionTestResultData;

    /**
     * @param  resource  $stream
     */
    public function scanStream(
        $stream,
        string $fileName,
        int $expectedSize,
    ): AttachmentScanResultData;
}
