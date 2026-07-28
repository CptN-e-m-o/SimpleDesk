<?php

namespace App\Console\Commands\Admin\Mail;

use App\Contracts\Admin\Mail\Antivirus\AttachmentScanDriver;
use Illuminate\Console\Command;

class TestAttachmentAntivirusCommand extends Command
{
    protected $signature =
        'simpledesk:mail:antivirus:test';

    protected $description =
        'Test the configured attachment antivirus connection';

    public function handle(
        AttachmentScanDriver $driver
    ): int {
        if (
            !(bool) config(
                'simpledesk-mail-antivirus.enabled',
                false
            )
        ) {
            $this->warn(
                'Attachment antivirus scanning is disabled.'
            );

            return self::FAILURE;
        }

        $result = $driver->testConnection();

        $this->table(
            [
                'Property',
                'Value',
            ],
            [
                [
                    'Driver',
                    $driver->name(),
                ],
                [
                    'Successful',
                    $result->successful
                        ? 'yes'
                        : 'no',
                ],
                [
                    'Message',
                    $result->message,
                ],
                [
                    'Latency',
                    $result->latencyMilliseconds !== null
                        ? $result->latencyMilliseconds . ' ms'
                        : 'n/a',
                ],
            ]
        );

        return $result->successful
            ? self::SUCCESS
            : self::FAILURE;
    }
}
