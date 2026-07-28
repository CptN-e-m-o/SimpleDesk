<?php

namespace App\Providers\Admin\Mail;

use App\Contracts\Admin\Mail\Antivirus\AttachmentScanDriver;
use App\Services\Admin\Mail\Antivirus\Drivers\ClamAvAttachmentScanDriver;
use App\Services\Admin\Mail\Antivirus\EmailAttachmentScanService;
use App\Services\Admin\Mail\Antivirus\OutgoingAttachmentScanCompletionService;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class MailAntivirusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            config_path(
                'simpledesk-mail-antivirus.php'
            ),
            'simpledesk-mail-antivirus',
        );

        $this->app->singleton(
            AttachmentScanDriver::class,
            function (): AttachmentScanDriver {
                $driver = (string) config(
                    'simpledesk-mail-antivirus.driver',
                    'clamav'
                );

                if ($driver !== 'clamav') {
                    throw new InvalidArgumentException(
                        "Unsupported attachment antivirus driver [{$driver}]."
                    );
                }

                return new ClamAvAttachmentScanDriver(
                    host: (string) config(
                        'simpledesk-mail-antivirus.clamav.host',
                        'clamav'
                    ),
                    port: (int) config(
                        'simpledesk-mail-antivirus.clamav.port',
                        3310
                    ),
                    connectionTimeoutSeconds: (float) config(
                        'simpledesk-mail-antivirus.clamav.connection_timeout_seconds',
                        5
                    ),
                    readTimeoutSeconds: (int) config(
                        'simpledesk-mail-antivirus.clamav.read_timeout_seconds',
                        60
                    ),
                    chunkBytes: max(
                        1024,
                        (int) config(
                            'simpledesk-mail-antivirus.clamav.chunk_bytes',
                            8192
                        )
                    ),
                    maxStreamBytes: max(
                        1,
                        (int) config(
                            'simpledesk-mail-antivirus.clamav.max_stream_bytes',
                            25 * 1024 * 1024
                        )
                    ),
                );
            }
        );

        $this->app->singleton(
            EmailAttachmentScanService::class,
            function (
                Application $app
            ): EmailAttachmentScanService {
                return new EmailAttachmentScanService(
                    driver: $app->make(
                        AttachmentScanDriver::class
                    ),
                    filesystem: $app->make(
                        FilesystemFactory::class
                    ),
                    completion: $app->make(
                        OutgoingAttachmentScanCompletionService::class
                    ),
                    processingLockSeconds: max(
                        60,
                        (int) config(
                            'simpledesk-mail-antivirus.processing_lock_seconds',
                            300
                        )
                    ),
                    verifyChecksums: (bool) config(
                        'simpledesk-mail-antivirus.verify_checksums',
                        true
                    ),
                );
            }
        );
    }

    public function boot(): void
    {
        if (
            !$this->app->runningInConsole()
            || !(bool) config(
                'simpledesk-mail-antivirus.enabled',
                false
            )
            || !(bool) config(
                'simpledesk-mail-antivirus.recovery.enabled',
                true
            )
        ) {
            return;
        }

        $batchSize = max(
            1,
            min(
                1000,
                (int) config(
                    'simpledesk-mail-antivirus.recovery.batch_size',
                    100
                )
            )
        );

        $intervalMinutes = max(
            1,
            min(
                59,
                (int) config(
                    'simpledesk-mail-antivirus.recovery.interval_minutes',
                    5
                )
            )
        );

        $event = $this->app
            ->make(Schedule::class)
            ->command(
                "simpledesk:mail:recover-attachment-scans --limit={$batchSize}"
            )
            ->cron(
                $intervalMinutes === 1
                    ? '* * * * *'
                    : "*/{$intervalMinutes} * * * *"
            )
            ->name(
                'simpledesk-mail-attachment-scan-recovery'
            )
            ->withoutOverlapping(
                max(
                    1,
                    (int) config(
                        'simpledesk-mail-antivirus.recovery.overlap_expiration_minutes',
                        10
                    )
                )
            );

        $this->configureSingleServer(
            $event
        );
    }

    private function configureSingleServer(
        Event $event
    ): void {
        if (
            (bool) config(
                'simpledesk-mail-antivirus.recovery.on_one_server',
                true
            )
        ) {
            $event->onOneServer();
        }
    }
}
