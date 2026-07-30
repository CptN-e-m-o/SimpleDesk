<?php

namespace App\Services\Admin\Mail\Settings;

use App\Data\Admin\Mail\MailConnectionTestResultData;
use App\Services\Admin\Mail\MailSensitiveDataRedactor;

class MailConnectionTestResultSanitizer
{
    public function __construct(
        private readonly MailSensitiveDataRedactor $redactor,
    ) {
    }

    public function sanitize(
        MailConnectionTestResultData $result
    ): MailConnectionTestResultData {
        return new MailConnectionTestResultData(
            successful: $result->successful,
            message: mb_substr(
                $this->redactor->redactString(
                    $result->message
                ),
                0,
                2000,
            ),
            latencyMilliseconds: $result->latencyMilliseconds,
            details: $this->redactor->sanitizeArray(
                $result->details
            ),
        );
    }
}
