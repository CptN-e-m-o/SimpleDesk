<?php

namespace App\Services\Admin\Mail\Settings;

use App\Contracts\Admin\Mail\Antivirus\AttachmentScanDriver;
use App\Data\Admin\Mail\MailConnectionTestResultData;
use Throwable;

class AttachmentAntivirusConnectionTestService
{
    public function __construct(
        private readonly AttachmentScanDriver $driver,
        private readonly MailConnectionTestResultSanitizer $sanitizer,
    ) {}

    public function test(): MailConnectionTestResultData
    {
        try {
            $result = $this->driver->testConnection();
        } catch (Throwable $exception) {
            report($exception);

            $result = MailConnectionTestResultData::failure(
                message: 'The attachment antivirus connection test failed unexpectedly.',
                details: [
                    'driver' => $this->driver->name(),
                    'error_code' => 'antivirus_connection_test_unexpected_error',
                ],
            );
        }

        return $this->sanitizer->sanitize($result);
    }
}
