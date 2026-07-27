<?php

namespace App\Providers\Admin\Mail;

use App\Events\Admin\Mail\InboundEmailStored;
use App\Jobs\Admin\Mail\ProcessInboundEmailJob;
use App\Models\TicketReply;
use App\Observers\Admin\Mail\TicketReplyObserver;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Throwable;

class InboundEmailTicketingServiceProvider extends
    ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            config_path(
                'simpledesk-mail-ticketing.php'
            ),
            'simpledesk-mail-ticketing',
        );
    }

    public function boot(
        Dispatcher $events
    ): void {
        TicketReply::observe(
            TicketReplyObserver::class
        );

        $events->listen(
            InboundEmailStored::class,
            function (
                InboundEmailStored $event
            ): void {
                if (
                    !(bool) config(
                        'simpledesk-mail-ticketing.enabled',
                        true
                    )
                ) {
                    return;
                }

                try {
                    $pendingDispatch =
                        ProcessInboundEmailJob::dispatch(
                            $event->emailMessageId
                        );

                    $connection = config(
                        'simpledesk-mail-ticketing.queue_connection'
                    );

                    if (
                        is_string($connection)
                        && $connection !== ''
                    ) {
                        $pendingDispatch
                            ->onConnection(
                                $connection
                            );
                    }

                    $pendingDispatch
                        ->onQueue(
                            (string) config(
                                'simpledesk-mail-ticketing.queue',
                                'mail-incoming'
                            )
                        )
                        ->afterCommit();
                } catch (Throwable $exception) {
                    report($exception);
                }
            }
        );
    }
}
