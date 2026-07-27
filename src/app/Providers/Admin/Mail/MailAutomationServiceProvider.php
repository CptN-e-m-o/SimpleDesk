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
            !$this->app->runningInConsole()
            || !(bool) config(
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

        $this->scheduleRecovery(
            $schedule
        );
    }

    private function scheduleIncomingSync(
        Schedule $schedule
    ): void {
        if (
            !(bool) config(
                'simpledesk-mail-automation.sync.enabled',
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
                    'simpledesk-mail-automation.sync.batch_size',
                    100
                )
            )
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
            )
            ->withoutOverlapping(
                $this->overlapExpirationMinutes()
            );

        $this->configureSingleServer(
            $event
        );
    }

    private function scheduleRecovery(
        Schedule $schedule
    ): void {
        if (
            !(bool) config(
                'simpledesk-mail-automation.recovery.enabled',
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
                    'simpledesk-mail-automation.recovery.batch_size',
                    100
                )
            )
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
            )
            ->withoutOverlapping(
                $this->overlapExpirationMinutes()
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
}
