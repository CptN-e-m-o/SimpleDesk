<?php

namespace App\Providers\Admin\Mail;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class MailAutomationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            config_path(
                'simpledesk-mail-automation.php'
            ),
            'simpledesk-mail-automation',
        );
    }

    public function boot(): void
    {
        if (
            ! $this->app->runningInConsole()
            || ! (bool) config(
                'simpledesk-mail-automation.enabled',
                true
            )
        ) {
            return;
        }

        $schedule = $this->app->make(
            Schedule::class
        );

        $this->scheduleIncomingSync(
            $schedule
        );

        $this->schedulePipelineRecovery(
            $schedule
        );

        $this->scheduleAttachmentScanRecovery(
            $schedule
        );

        $this->scheduleChannelHealthChecks(
            $schedule
        );

        $this->scheduleRetention(
            $schedule
        );
    }

    private function scheduleIncomingSync(
        Schedule $schedule
    ): void {
        if (
            ! (bool) config(
                'simpledesk-mail-automation.sync.enabled',
                true
            )
        ) {
            return;
        }

        $batchSize = $this->batchSize(
            'simpledesk-mail-automation.sync.batch_size',
            100
        );

        $interval = (int) config(
            'simpledesk-mail-automation.sync.interval_minutes',
            1
        );

        $event = $schedule
            ->command(
                "simpledesk:mail:dispatch-syncs --limit={$batchSize}"
            )
            ->cron(
                $this->minuteCron(
                    $interval
                )
            )
            ->name(
                'simpledesk-mail-dispatch-incoming-syncs'
            );

        $this->configureEvent(
            $event
        );
    }

    private function schedulePipelineRecovery(
        Schedule $schedule
    ): void {
        if (
            ! (bool) config(
                'simpledesk-mail-automation.recovery.enabled',
                true
            )
        ) {
            return;
        }

        $batchSize = $this->batchSize(
            'simpledesk-mail-automation.recovery.batch_size',
            100
        );

        $interval = (int) config(
            'simpledesk-mail-automation.recovery.interval_minutes',
            5
        );

        $event = $schedule
            ->command(
                "simpledesk:mail:recover --limit={$batchSize}"
            )
            ->cron(
                $this->minuteCron(
                    $interval
                )
            )
            ->name(
                'simpledesk-mail-pipeline-recovery'
            );

        $this->configureEvent(
            $event
        );
    }

    private function scheduleAttachmentScanRecovery(
        Schedule $schedule
    ): void {
        if (
            ! (bool) config(
                'simpledesk-mail-automation.attachment_recovery.enabled',
                true
            )
        ) {
            return;
        }

        $interval = (int) config(
            'simpledesk-mail-automation.attachment_recovery.interval_minutes',
            5
        );

        $event = $schedule
            ->command(
                'simpledesk:mail:recover-attachment-scans'
            )
            ->cron(
                $this->minuteCron(
                    $interval
                )
            )
            ->name(
                'simpledesk-mail-attachment-scan-recovery'
            );

        $this->configureEvent(
            $event
        );
    }

    private function scheduleChannelHealthChecks(
        Schedule $schedule
    ): void {
        if (
            ! (bool) config(
                'simpledesk-mail-automation.health.enabled',
                true
            )
        ) {
            return;
        }

        $batchSize = $this->batchSize(
            'simpledesk-mail-automation.health.batch_size',
            100
        );

        $interval = (int) config(
            'simpledesk-mail-automation.health.interval_minutes',
            15
        );

        $event = $schedule
            ->command(
                "simpledesk:mail:check-health --limit={$batchSize}"
            )
            ->cron(
                $this->minuteCron(
                    $interval
                )
            )
            ->name(
                'simpledesk-mail-channel-health-checks'
            );

        $this->configureEvent(
            $event
        );
    }

    private function scheduleRetention(
        Schedule $schedule
    ): void {
        if (
            ! (bool) config(
                'simpledesk-mail-automation.retention.enabled',
                true
            )
        ) {
            return;
        }

        $batchSize = $this->batchSize(
            'simpledesk-mail-automation.retention.batch_size',
            500
        );

        $event = $schedule
            ->command(
                "simpledesk:mail:prune --limit={$batchSize}"
            )
            ->dailyAt(
                $this->dailyTime(
                    (string) config(
                        'simpledesk-mail-automation.retention.run_at',
                        '02:30'
                    )
                )
            )
            ->name(
                'simpledesk-mail-retention'
            );

        $this->configureEvent(
            $event
        );
    }

    private function configureEvent(
        Event $event
    ): void {
        $event->withoutOverlapping(
            $this->overlapExpirationMinutes()
        );

        if (
            (bool) config(
                'simpledesk-mail-automation.scheduler.on_one_server',
                true
            )
        ) {
            $event->onOneServer();
        }
    }

    private function overlapExpirationMinutes(): int
    {
        return max(
            1,
            (int) config(
                'simpledesk-mail-automation.scheduler.overlap_expiration_minutes',
                10
            )
        );
    }

    private function batchSize(
        string $key,
        int $default
    ): int {
        return max(
            1,
            min(
                5000,
                (int) config(
                    $key,
                    $default
                )
            )
        );
    }

    private function minuteCron(
        int $minutes
    ): string {
        $minutes = max(
            1,
            min(59, $minutes)
        );

        if ($minutes === 1) {
            return '* * * * *';
        }

        return "*/{$minutes} * * * *";
    }

    private function dailyTime(
        string $value
    ): string {
        $value = trim($value);

        return preg_match(
            '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
            $value
        ) === 1
            ? $value
            : '02:30';
    }
}
