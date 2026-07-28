<?php

namespace App\Providers;

use App\Enums\Admin\Mail\IncomingAcknowledgeAction;
use App\Services\Admin\Mail\Drivers\Smtp\SmtpTransportFactory;
use App\Services\Admin\Mail\InboundNormalizationFailurePersister;
use App\Services\Admin\Mail\IncomingEmailMessagePersister;
use App\Services\Admin\Mail\IncomingMailboxSyncService;
use App\Services\Admin\Mail\MailAttachmentDownloadService;
use App\Services\Admin\Mail\MailAttachmentStorageService;
use App\Services\Admin\Mail\MailChannelHealthRecorder;
use App\Services\Admin\Mail\MailChannelSelector;
use App\Services\Admin\Mail\MailDriverRegistry;
use App\Services\Admin\Mail\OutgoingEmailMessageFactory;
use App\Services\Admin\Mail\OutgoingMailAttachmentValidator;
use App\Services\Admin\Mail\OutgoingMailFailoverService;
use App\Services\Admin\Mail\Quarantine\EmailMessageQuarantineService;
use App\Services\Admin\Mail\RawEmailStorageService;
use App\Services\Admin\Mail\RejectedEmailAttachmentPersister;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Mail\MailManager;
use Illuminate\Support\ServiceProvider;

class MailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            config_path('simpledesk-mail.php'),
            'simpledesk-mail',
        );

        $this->app->singleton(
            SmtpTransportFactory::class,
            function (
                Application $app
            ): SmtpTransportFactory {
                return new SmtpTransportFactory(
                    mailManager: $app->make(
                        MailManager::class
                    ),
                );
            }
        );

        $this->app->singleton(
            MailDriverRegistry::class,
            function (
                Application $app
            ): MailDriverRegistry {
                return new MailDriverRegistry(
                    container: $app,
                    incomingDrivers: config(
                        'simpledesk-mail.drivers.incoming',
                        []
                    ),
                    outgoingDrivers: config(
                        'simpledesk-mail.drivers.outgoing',
                        []
                    ),
                );
            }
        );

        $this->app->singleton(
            MailChannelSelector::class,
            function (): MailChannelSelector {
                return new MailChannelSelector(
                    failedChannelCooldownSeconds:
                    (int) config(
                        'simpledesk-mail.failover.failed_channel_cooldown_seconds',
                        300,
                    ),
                );
            }
        );

        $this->app->singleton(
            RawEmailStorageService::class,
            function (
                Application $app
            ): RawEmailStorageService {
                return new RawEmailStorageService(
                    filesystem: $app->make(
                        FilesystemFactory::class
                    ),
                    disk: (string) config(
                        'simpledesk-mail.storage.disk',
                        'local'
                    ),
                    rootPath: (string) config(
                        'simpledesk-mail.storage.raw_messages_path',
                        'mail/raw'
                    ),
                );
            }
        );

        $this->app->singleton(
            MailAttachmentStorageService::class,
            function (
                Application $app
            ): MailAttachmentStorageService {
                return new MailAttachmentStorageService(
                    filesystem: $app->make(
                        FilesystemFactory::class
                    ),
                    disk: (string) config(
                        'simpledesk-mail.storage.disk',
                        'local'
                    ),
                    rootPath: (string) config(
                        'simpledesk-mail.storage.attachments_path',
                        'mail/attachments'
                    ),
                );
            }
        );

        $this->app->singleton(
            OutgoingMailAttachmentValidator::class,
            function (): OutgoingMailAttachmentValidator {
                return new OutgoingMailAttachmentValidator(
                    allowedMimeTypes: (array) config(
                        'simpledesk-mail.outgoing.allowed_attachment_mime_types',
                        []
                    ),
                    maxAttachmentCount: (int) config(
                        'simpledesk-mail.outgoing.max_attachment_count',
                        10
                    ),
                    maxAttachmentBytes: (int) config(
                        'simpledesk-mail.outgoing.max_attachment_bytes',
                        25 * 1024 * 1024,
                    ),
                    maxTotalAttachmentBytes: (int) config(
                        'simpledesk-mail.outgoing.max_total_attachment_bytes',
                        40 * 1024 * 1024,
                    ),
                );
            }
        );

        $this->app->singleton(
            MailAttachmentDownloadService::class,
            function (
                Application $app
            ): MailAttachmentDownloadService {
                return new MailAttachmentDownloadService(
                    filesystem: $app->make(
                        FilesystemFactory::class
                    ),
                    allowedScanStatuses: (array) config(
                        'simpledesk-mail.downloads.allowed_scan_statuses',
                        [
                            'not_scanned',
                            'clean',
                        ]
                    ),
                    verifyChecksums: (bool) config(
                        'simpledesk-mail.downloads.verify_checksums',
                        true
                    ),
                );
            }
        );

        $this->app->singleton(
            OutgoingEmailMessageFactory::class,
            function (
                Application $app
            ): OutgoingEmailMessageFactory {
                return new OutgoingEmailMessageFactory(
                    filesystem: $app->make(
                        FilesystemFactory::class
                    ),
                    maxAttachmentBytes:
                    (int) config(
                        'simpledesk-mail.outgoing.max_attachment_bytes',
                        25 * 1024 * 1024,
                    ),
                    maxTotalAttachmentBytes:
                    (int) config(
                        'simpledesk-mail.outgoing.max_total_attachment_bytes',
                        40 * 1024 * 1024,
                    ),
                    verifyChecksums:
                    (bool) config(
                        'simpledesk-mail.outgoing.verify_attachment_checksums',
                        true,
                    ),
                );
            }
        );

        $this->app->singleton(
            IncomingEmailMessagePersister::class,
            function (
                Application $app
            ): IncomingEmailMessagePersister {
                return new IncomingEmailMessagePersister(
                    keys: $app->make(
                        \App\Services\Admin\Mail\MailMessageIdempotencyKeyFactory::class
                    ),
                    rawStorage: $app->make(
                        RawEmailStorageService::class
                    ),
                    attachmentStorage: $app->make(
                        MailAttachmentStorageService::class
                    ),
                    rejectedAttachments:
                    $app->make(
                        RejectedEmailAttachmentPersister::class
                    ),
                    processingLockSeconds:
                    (int) config(
                        'simpledesk-mail.sync.message_processing_lock_seconds',
                        600,
                    ),
                );
            }
        );

        $this->app->singleton(
            IncomingMailboxSyncService::class,
            function (
                Application $app
            ): IncomingMailboxSyncService {
                $defaultAction =
                    IncomingAcknowledgeAction::tryFrom(
                        (string) config(
                            'simpledesk-mail.sync.default_post_fetch_action',
                            'mark_read'
                        )
                    )
                    ?? IncomingAcknowledgeAction::MarkRead;

                return new IncomingMailboxSyncService(
                    selector: $app->make(
                        MailChannelSelector::class
                    ),
                    fetcher: $app->make(
                        \App\Services\Admin\Mail\IncomingMailFetchService::class
                    ),
                    persister: $app->make(
                        IncomingEmailMessagePersister::class
                    ),
                    normalizationFailures:
                    $app->make(
                        InboundNormalizationFailurePersister::class
                    ),
                    acknowledger: $app->make(
                        \App\Services\Admin\Mail\IncomingMailAcknowledger::class
                    ),
                    quarantine:
                    $app->make(
                        EmailMessageQuarantineService::class
                    ),
                    health: $app->make(
                        MailChannelHealthRecorder::class
                    ),
                    batchSize: (int) config(
                        'simpledesk-mail.sync.batch_size',
                        100,
                    ),
                    maxPagesPerRun: (int) config(
                        'simpledesk-mail.sync.max_pages_per_run',
                        10,
                    ),
                    defaultAction: $defaultAction,
                );
            }
        );

        $this->app->singleton(
            OutgoingMailFailoverService::class,
            function (
                Application $app
            ): OutgoingMailFailoverService {
                return new OutgoingMailFailoverService(
                    drivers: $app->make(
                        MailDriverRegistry::class
                    ),
                    selector: $app->make(
                        MailChannelSelector::class
                    ),
                    health: $app->make(
                        MailChannelHealthRecorder::class
                    ),
                    messageFactory: $app->make(
                        OutgoingEmailMessageFactory::class
                    ),
                    sendingLockSeconds:
                    (int) config(
                        'simpledesk-mail.failover.sending_lock_seconds',
                        600,
                    ),
                );
            }
        );
    }
}
