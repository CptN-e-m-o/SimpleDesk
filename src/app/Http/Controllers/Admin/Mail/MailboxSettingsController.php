<?php

namespace App\Http\Controllers\Admin\Mail;

use App\Enums\Admin\Mail\ImapEncryption;
use App\Enums\Admin\Mail\MailAuthenticationType;
use App\Enums\Admin\Mail\MailboxChannelDirection;
use App\Enums\Admin\Mail\MailboxDriver;
use App\Enums\Admin\Mail\SmtpEncryption;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Mail\Mailboxes\SaveIncomingMailboxSetupRequest;
use App\Http\Requests\Admin\Mail\Mailboxes\SaveOutgoingMailboxSetupRequest;
use App\Http\Requests\Admin\Mail\Mailboxes\StoreMailboxRequest;
use App\Http\Requests\Admin\Mail\Mailboxes\UpdateMailboxRequest;
use App\Models\Admin\Department;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use App\Services\Admin\Mail\Settings\MailboxAdminService;
use App\Services\Admin\Mail\Settings\MailboxChannelAdminService;
use BackedEnum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MailboxSettingsController extends Controller
{
    public function __construct(
        private readonly MailboxAdminService $mailboxes,
        private readonly MailboxChannelAdminService $channels,
    ) {
    }

    public function index(): Response
    {
        $mailboxes = Mailbox::query()
            ->withTrashed()
            ->with([
                'department:id,name',

                'channels' => function ($query): void {
                    $query
                        ->orderByDesc('is_primary')
                        ->orderBy('failover_order')
                        ->orderBy('id');
                },
            ])
            ->orderByRaw(
                'CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END'
            )
            ->orderByDesc('is_default_outgoing')
            ->orderBy('name')
            ->get();

        $items = $mailboxes
            ->map(
                fn (Mailbox $mailbox): array =>
                $this->mailboxData(
                    $mailbox
                )
            )
            ->values();

        $configuredCount = $items
            ->filter(
                fn (array $mailbox): bool =>
                $this->isConfigured(
                    $mailbox
                )
            )
            ->count();

        return Inertia::render(
            'Admin/Email/Mailboxes/Index',
            [
                'mailboxes' => $items,

                'summary' => [
                    'total' =>
                        $items->count(),

                    'active' =>
                        $items
                            ->where(
                                'is_active',
                                true
                            )
                            ->count(),

                    'configured' =>
                        $configuredCount,

                    'healthy' =>
                        $items
                            ->filter(
                                fn (array $mailbox): bool =>
                                $this->isHealthy(
                                    $mailbox
                                )
                            )
                            ->count(),

                    'needs_attention' =>
                        $items
                            ->filter(
                                fn (array $mailbox): bool =>
                                $this->needsAttention(
                                    $mailbox
                                )
                            )
                            ->count(),
                ],

                'system_mail_configured' =>
                    $configuredCount > 0,
            ]
        );
    }

    public function create(): Response
    {
        $departments = Department::query()
            ->select([
                'id',
                'name',
            ])
            ->orderBy('name')
            ->get()
            ->map(
                static fn (
                    Department $department
                ): array => [
                    'id' =>
                        $department->id,

                    'name' =>
                        $department->name,
                ]
            )
            ->values();

        return Inertia::render(
            'Admin/Email/Mailboxes/Create',
            [
                'departments' =>
                    $departments,

                'system_mail_configured' =>
                    $this->systemMailConfigured(),
            ]
        );
    }

    public function store(
        StoreMailboxRequest $request
    ): RedirectResponse {
        $mailbox = $this->mailboxes->create(
            $request->validated()
        );

        return redirect()
            ->route(
                'admin.email.settings.mailboxes.setup.incoming',
                $mailbox
            )
            ->with(
                'success',
                "Mailbox [{$mailbox->name}] was created successfully."
            );
    }

    public function show(
        int $mailbox
    ): Response {
        $mailbox = Mailbox::query()
            ->withTrashed()
            ->with([
                'department:id,name',

                'channels' => function ($query): void {
                    $query
                        ->orderBy('direction')
                        ->orderByDesc('is_primary')
                        ->orderBy('failover_order')
                        ->orderBy('id');
                },
            ])
            ->withCount([
                'channels',
                'emailMessages',
            ])
            ->findOrFail($mailbox);

        $enumValue = static function (
            mixed $value
        ): ?string {
            if ($value instanceof \BackedEnum) {
                return (string) $value->value;
            }

            if ($value === null) {
                return null;
            }

            return (string) $value;
        };

        return Inertia::render(
            'Admin/Email/Mailboxes/Show',
            [
                'mailbox' => [
                    ...$this->mailboxData(
                        $mailbox
                    ),

                    'internal_notes' =>
                        $mailbox->internal_notes,

                    'email_messages_count' =>
                        (int) $mailbox
                            ->email_messages_count,

                    'created_at' =>
                        $mailbox
                            ->created_at
                            ?->toIso8601String(),

                    'updated_at' =>
                        $mailbox
                            ->updated_at
                            ?->toIso8601String(),

                    'deleted_at' =>
                        $mailbox
                            ->deleted_at
                            ?->toIso8601String(),

                    'channels' => $mailbox
                        ->channels
                        ->map(
                            static fn (
                                $channel
                            ): array => [
                                'id' =>
                                    $channel->id,

                                'name' =>
                                    $channel->name,

                                'direction' =>
                                    $enumValue(
                                        $channel->direction
                                    ),

                                'driver' =>
                                    $enumValue(
                                        $channel->driver
                                    ),

                                'auth_type' =>
                                    $enumValue(
                                        $channel->auth_type
                                    ),

                                'is_enabled' =>
                                    (bool) $channel
                                        ->is_enabled,

                                'is_primary' =>
                                    (bool) $channel
                                        ->is_primary,

                                'failover_order' =>
                                    (int) $channel
                                        ->failover_order,

                                'health_status' =>
                                    $enumValue(
                                        $channel->health_status
                                    ),

                                'configuration' =>
                                    is_array(
                                        $channel->configuration
                                    )
                                        ? $channel->configuration
                                        : [],

                                'created_at' =>
                                    $channel
                                        ->created_at
                                        ?->toIso8601String(),

                                'updated_at' =>
                                    $channel
                                        ->updated_at
                                        ?->toIso8601String(),
                            ]
                        )
                        ->values(),
                ],
            ]
        );
    }

    public function edit(
        Mailbox $mailbox
    ): Response {
        $mailbox->loadCount([
            'incomingChannels',
            'outgoingChannels',
        ]);

        $departments = Department::query()
            ->select([
                'id',
                'name',
            ])
            ->orderBy('name')
            ->get()
            ->map(
                static fn (
                    Department $department
                ): array => [
                    'id' =>
                        $department->id,

                    'name' =>
                        $department->name,
                ]
            )
            ->values();

        return Inertia::render(
            'Admin/Email/Mailboxes/Edit',
            [
                'mailbox' => [
                    'id' =>
                        $mailbox->id,

                    'name' =>
                        $mailbox->name,

                    'email_address' =>
                        $mailbox->email_address,

                    'display_name' =>
                        $mailbox->display_name,

                    'department_id' =>
                        $mailbox->department_id,

                    'is_active' =>
                        (bool) $mailbox->is_active,

                    'is_default_outgoing' =>
                        (bool) $mailbox
                            ->is_default_outgoing,

                    'internal_notes' =>
                        $mailbox->internal_notes,

                    'incoming_configured' =>
                        $mailbox
                            ->incoming_channels_count > 0,

                    'outgoing_configured' =>
                        $mailbox
                            ->outgoing_channels_count > 0,

                    'created_at' =>
                        $mailbox
                            ->created_at
                            ?->toIso8601String(),

                    'updated_at' =>
                        $mailbox
                            ->updated_at
                            ?->toIso8601String(),
                ],

                'departments' =>
                    $departments,
            ]
        );
    }

    public function update(
        UpdateMailboxRequest $request,
        Mailbox $mailbox,
    ): RedirectResponse {
        $mailbox = $this->mailboxes->update(
            mailbox: $mailbox,
            data: $request->validated(),
        );

        return redirect()
            ->route(
                'admin.email.settings.mailboxes.edit',
                $mailbox
            )
            ->with(
                'success',
                "Mailbox [{$mailbox->name}] was updated successfully."
            );
    }

    public function destroy(
        Mailbox $mailbox
    ): RedirectResponse {
        $mailboxName =
            $mailbox->name;

        $this->mailboxes->delete(
            $mailbox
        );

        return redirect()
            ->route(
                'admin.email.settings.index'
            )
            ->with(
                'success',
                "Mailbox [{$mailboxName}] was deleted."
            );
    }

    public function restore(
        int $mailbox
    ): RedirectResponse {
        $restoredMailbox = $this->mailboxes->restore(
            $mailbox
        );

        return redirect()
            ->route(
                'admin.email.settings.index'
            )
            ->with(
                'success',
                "Mailbox [{$restoredMailbox->name}] was restored. It remains disabled until you activate it."
            );
    }

    public function forceDestroy(
        int $mailbox
    ): RedirectResponse {
        $deletedMailbox = Mailbox::onlyTrashed()
            ->findOrFail($mailbox);

        $mailboxName =
            $deletedMailbox->name;

        $this->mailboxes->forceDelete(
            $mailbox
        );

        return redirect()
            ->route(
                'admin.email.settings.index'
            )
            ->with(
                'success',
                "Mailbox [{$mailboxName}] was permanently deleted."
            );
    }

    public function incoming(
        Mailbox $mailbox
    ): Response {
        $channel = $this->setupChannel(
            mailbox: $mailbox,
            direction:
            MailboxChannelDirection::Incoming,
            driver:
            MailboxDriver::Imap,
        );

        return Inertia::render(
            'Admin/Email/Mailboxes/Setup/Incoming',
            [
                'mailbox' =>
                    $this->setupMailboxData(
                        $mailbox
                    ),

                'channel' =>
                    $channel !== null
                        ? $this->incomingFormData(
                        $channel
                    )
                        : null,

                'encryption_options' =>
                    collect(
                        ImapEncryption::cases()
                    )
                        ->map(
                            fn (
                                ImapEncryption $encryption
                            ): array => [
                                'value' =>
                                    $encryption->value,

                                'label' =>
                                    $this->enumLabel(
                                        $encryption->value
                                    ),

                                'default_port' =>
                                    $encryption
                                        ->defaultPort(),
                            ]
                        )
                        ->values(),

                'defaults' => [
                    'encryption' =>
                        ImapEncryption::Tls->value,

                    'port' =>
                        ImapEncryption::Tls
                            ->defaultPort(),
                ],
            ]
        );
    }

    public function storeIncoming(
        SaveIncomingMailboxSetupRequest $request,
        Mailbox $mailbox,
    ): RedirectResponse {
        $validated = $request->validated();

        $channel = $this->setupChannel(
            mailbox: $mailbox,
            direction:
            MailboxChannelDirection::Incoming,
            driver:
            MailboxDriver::Imap,
        );

        [
            $secrets,
            $clearSecretKeys,
        ] = $this->passwordCredentials(
            channel: $channel,
            authType:
            $validated['auth_type'],
            username:
            $validated['username'] ?? null,
            password:
            $validated['password'] ?? null,
            transportName:
            'IMAP',
        );

        $data = [
            'provider_connection_id' =>
                null,

            'name' =>
                $validated['name'],

            'direction' =>
                MailboxChannelDirection::Incoming
                    ->value,

            'driver' =>
                MailboxDriver::Imap->value,

            'auth_type' =>
                $validated['auth_type'],

            'is_enabled' =>
                (bool) $validated['is_enabled'],

            'is_primary' =>
                (bool) $validated['is_primary'],

            'failover_order' =>
                (int) $validated['failover_order'],

            'configuration' => [
                'host' =>
                    $validated['host'],

                'port' =>
                    (int) $validated['port'],

                'encryption' =>
                    $validated['encryption'],

                'validate_cert' =>
                    (bool) $validated[
                    'validate_cert'
                    ],

                'folder' =>
                    $validated['folder'],

                'processed_folder' =>
                    $validated[
                    'processed_folder'
                    ] ?? null,

                'create_processed_folder' =>
                    (bool) $validated[
                    'create_processed_folder'
                    ],

                'expunge_on_delete' =>
                    (bool) $validated[
                    'expunge_on_delete'
                    ],

                'store_raw_message' =>
                    (bool) $validated[
                    'store_raw_message'
                    ],

                'max_raw_message_bytes' =>
                    (int) $validated[
                    'max_raw_message_mb'
                    ] * 1024 * 1024,

                'max_attachment_bytes' =>
                    (int) $validated[
                    'max_attachment_mb'
                    ] * 1024 * 1024,
            ],

            'secret_configuration' =>
                $secrets,

            'clear_secret_keys' =>
                $clearSecretKeys,
        ];

        if ($channel === null) {
            $this->channels->create(
                mailbox: $mailbox,
                data: $data,
            );
        } else {
            $this->channels->update(
                channel: $channel,
                data: $data,
            );
        }

        return redirect()
            ->route(
                'admin.email.settings.mailboxes.setup.outgoing',
                $mailbox
            )
            ->with(
                'success',
                'Incoming email configuration was saved.'
            );
    }

    public function outgoing(
        Mailbox $mailbox
    ): Response {
        $channel = $this->setupChannel(
            mailbox: $mailbox,
            direction:
            MailboxChannelDirection::Outgoing,
            driver:
            MailboxDriver::Smtp,
        );

        return Inertia::render(
            'Admin/Email/Mailboxes/Setup/Outgoing',
            [
                'mailbox' =>
                    $this->setupMailboxData(
                        $mailbox
                    ),

                'channel' =>
                    $channel !== null
                        ? $this->outgoingFormData(
                        $channel
                    )
                        : null,

                'encryption_options' =>
                    collect(
                        SmtpEncryption::cases()
                    )
                        ->map(
                            fn (
                                SmtpEncryption $encryption
                            ): array => [
                                'value' =>
                                    $encryption->value,

                                'label' =>
                                    $this->enumLabel(
                                        $encryption->value
                                    ),

                                'default_port' =>
                                    $encryption
                                        ->defaultPort(),
                            ]
                        )
                        ->values(),

                'defaults' => [
                    'encryption' =>
                        SmtpEncryption::StartTls
                            ->value,

                    'port' =>
                        SmtpEncryption::StartTls
                            ->defaultPort(),
                ],
            ]
        );
    }

    public function storeOutgoing(
        SaveOutgoingMailboxSetupRequest $request,
        Mailbox $mailbox,
    ): RedirectResponse {
        $validated = $request->validated();

        $channel = $this->setupChannel(
            mailbox: $mailbox,
            direction:
            MailboxChannelDirection::Outgoing,
            driver:
            MailboxDriver::Smtp,
        );

        [
            $secrets,
            $clearSecretKeys,
        ] = $this->passwordCredentials(
            channel: $channel,
            authType:
            $validated['auth_type'],
            username:
            $validated['username'] ?? null,
            password:
            $validated['password'] ?? null,
            transportName:
            'SMTP',
        );

        $configuration = [
            'host' =>
                $validated['host'],

            'port' =>
                (int) $validated['port'],

            'encryption' =>
                $validated['encryption'],

            'timeout' =>
                (int) $validated['timeout'],

            'verify_peer' =>
                (bool) $validated[
                'verify_peer'
                ],

            'local_domain' =>
                $validated[
                'local_domain'
                ] ?? null,

            'source_ip' =>
                $validated[
                'source_ip'
                ] ?? null,

            'max_per_second' =>
                $validated[
                'max_per_second'
                ] ?? null,

            'restart_threshold' =>
                $validated[
                'restart_threshold'
                ] ?? null,

            'restart_threshold_sleep' =>
                (int) $validated[
                'restart_threshold_sleep'
                ],

            'ping_threshold' =>
                $validated[
                'ping_threshold'
                ] ?? null,
        ];

        $data = [
            'provider_connection_id' =>
                null,

            'name' =>
                $validated['name'],

            'direction' =>
                MailboxChannelDirection::Outgoing
                    ->value,

            'driver' =>
                MailboxDriver::Smtp->value,

            'auth_type' =>
                $validated['auth_type'],

            'is_enabled' =>
                (bool) $validated['is_enabled'],

            'is_primary' =>
                (bool) $validated['is_primary'],

            'failover_order' =>
                (int) $validated['failover_order'],

            'configuration' =>
                Arr::where(
                    $configuration,
                    static fn (
                        mixed $value
                    ): bool =>
                        $value !== null
                        && $value !== ''
                ),

            'secret_configuration' =>
                $secrets,

            'clear_secret_keys' =>
                $clearSecretKeys,
        ];

        if ($channel === null) {
            $this->channels->create(
                mailbox: $mailbox,
                data: $data,
            );
        } else {
            $this->channels->update(
                channel: $channel,
                data: $data,
            );
        }

        return redirect()
            ->route(
                'admin.email.settings.mailboxes.setup.review',
                $mailbox
            )
            ->with(
                'success',
                'Outgoing email configuration was saved.'
            );
    }

    public function review(
        Mailbox $mailbox
    ): Response {
        $mailbox->load([
            'department:id,name',

            'channels' => fn ($query) => $query
                ->orderBy('direction')
                ->orderByDesc('is_primary')
                ->orderBy('failover_order')
                ->orderBy('id'),
        ]);

        $incoming = $this->preferredChannel(
            channels:
            $mailbox->channels,

            direction:
            MailboxChannelDirection::Incoming,
        );

        $outgoing = $this->preferredChannel(
            channels:
            $mailbox->channels,

            direction:
            MailboxChannelDirection::Outgoing,
        );

        return Inertia::render(
            'Admin/Email/Mailboxes/Setup/Review',
            [
                'mailbox' => [
                    ...$this->setupMailboxData(
                        $mailbox
                    ),

                    'display_name' =>
                        $mailbox->display_name,

                    'department' =>
                        $mailbox->department !== null
                            ? [
                            'id' =>
                                $mailbox
                                    ->department
                                    ->id,

                            'name' =>
                                $mailbox
                                    ->department
                                    ->name,
                        ]
                            : null,

                    'is_active' =>
                        (bool) $mailbox
                            ->is_active,

                    'is_default_outgoing' =>
                        (bool) $mailbox
                            ->is_default_outgoing,
                ],

                'incoming_channel' =>
                    $this->reviewChannelData(
                        $incoming
                    ),

                'outgoing_channel' =>
                    $this->reviewChannelData(
                        $outgoing
                    ),
            ]
        );
    }

    public function finish(
        Mailbox $mailbox
    ): RedirectResponse {
        return redirect()
            ->route(
                'admin.email.settings.index'
            )
            ->with(
                'success',
                "Mailbox [{$mailbox->name}] setup was completed."
            );
    }

    private function setupMailboxData(
        Mailbox $mailbox
    ): array {
        return [
            'id' =>
                $mailbox->id,

            'name' =>
                $mailbox->name,

            'email_address' =>
                $mailbox->email_address,
        ];
    }

    private function setupChannel(
        Mailbox $mailbox,
        MailboxChannelDirection $direction,
        MailboxDriver $driver,
    ): ?MailboxChannel {
        return $mailbox
            ->channels()
            ->where(
                'direction',
                $direction->value
            )
            ->where(
                'driver',
                $driver->value
            )
            ->orderByDesc('is_primary')
            ->orderBy('failover_order')
            ->orderBy('id')
            ->first();
    }

    private function incomingFormData(
        MailboxChannel $channel
    ): array {
        $configuration =
            $channel->configuration ?? [];

        $secrets =
            $channel->secret_configuration ?? [];

        return [
            'id' =>
                $channel->id,

            'name' =>
                $channel->name,

            'auth_type' =>
                $channel->auth_type->value,

            'host' =>
                $configuration['host'] ?? '',

            'port' =>
                (int) (
                    $configuration['port']
                    ?? ImapEncryption::Tls
                    ->defaultPort()
                ),

            'encryption' =>
                $configuration['encryption']
                ?? ImapEncryption::Tls->value,

            'username' =>
                $secrets['username']
                ?? $configuration['username']
                    ?? '',

            'password_configured' =>
                !empty(
                $secrets['password']
                ),

            'validate_cert' =>
                (bool) (
                    $configuration[
                    'validate_cert'
                    ] ?? true
                ),

            'folder' =>
                $configuration['folder']
                ?? 'INBOX',

            'processed_folder' =>
                $configuration[
                'processed_folder'
                ] ?? '',

            'create_processed_folder' =>
                (bool) (
                    $configuration[
                    'create_processed_folder'
                    ] ?? true
                ),

            'expunge_on_delete' =>
                (bool) (
                    $configuration[
                    'expunge_on_delete'
                    ] ?? true
                ),

            'store_raw_message' =>
                (bool) (
                    $configuration[
                    'store_raw_message'
                    ] ?? true
                ),

            'max_raw_message_mb' =>
                $this->bytesToMegabytes(
                    $configuration[
                    'max_raw_message_bytes'
                    ] ?? 50 * 1024 * 1024
                ),

            'max_attachment_mb' =>
                $this->bytesToMegabytes(
                    $configuration[
                    'max_attachment_bytes'
                    ] ?? 25 * 1024 * 1024
                ),

            'is_enabled' =>
                (bool) $channel->is_enabled,

            'is_primary' =>
                (bool) $channel->is_primary,

            'failover_order' =>
                (int) $channel
                    ->failover_order,
        ];
    }

    private function outgoingFormData(
        MailboxChannel $channel
    ): array {
        $configuration =
            $channel->configuration ?? [];

        $secrets =
            $channel->secret_configuration ?? [];

        return [
            'id' =>
                $channel->id,

            'name' =>
                $channel->name,

            'auth_type' =>
                $channel->auth_type->value,

            'host' =>
                $configuration['host'] ?? '',

            'port' =>
                (int) (
                    $configuration['port']
                    ?? SmtpEncryption::StartTls
                    ->defaultPort()
                ),

            'encryption' =>
                $configuration['encryption']
                ?? SmtpEncryption::StartTls
                    ->value,

            'username' =>
                $secrets['username']
                ?? $configuration['username']
                    ?? '',

            'password_configured' =>
                !empty(
                $secrets['password']
                ),

            'timeout' =>
                (int) (
                    $configuration['timeout']
                    ?? 30
                ),

            'verify_peer' =>
                (bool) (
                    $configuration[
                    'verify_peer'
                    ] ?? true
                ),

            'local_domain' =>
                $configuration[
                'local_domain'
                ] ?? '',

            'source_ip' =>
                $configuration[
                'source_ip'
                ] ?? '',

            'max_per_second' =>
                $configuration[
                'max_per_second'
                ] ?? '',

            'restart_threshold' =>
                $configuration[
                'restart_threshold'
                ] ?? '',

            'restart_threshold_sleep' =>
                (int) (
                    $configuration[
                    'restart_threshold_sleep'
                    ] ?? 0
                ),

            'ping_threshold' =>
                $configuration[
                'ping_threshold'
                ] ?? '',

            'is_enabled' =>
                (bool) $channel->is_enabled,

            'is_primary' =>
                (bool) $channel->is_primary,

            'failover_order' =>
                (int) $channel
                    ->failover_order,
        ];
    }

    private function passwordCredentials(
        ?MailboxChannel $channel,
        string $authType,
        ?string $username,
        ?string $password,
        string $transportName,
    ): array {
        if (
            $authType
            === MailAuthenticationType::None->value
        ) {
            return [
                [],
                [
                    'username',
                    'password',
                    'access_token',
                ],
            ];
        }

        $existingSecrets =
            $channel?->secret_configuration
            ?? [];

        $password = trim(
            (string) $password
        );

        if (
            $password === ''
            && empty(
            $existingSecrets['password']
            )
        ) {
            throw ValidationException::withMessages([
                'password' => [
                    "{$transportName} password is required.",
                ],
            ]);
        }

        $secrets = [
            'username' =>
                trim(
                    (string) $username
                ),
        ];

        if ($password !== '') {
            $secrets['password'] =
                $password;
        }

        return [
            $secrets,
            [
                'access_token',
            ],
        ];
    }

    private function reviewChannelData(
        ?MailboxChannel $channel
    ): ?array {
        if ($channel === null) {
            return null;
        }

        $configuration =
            $channel->configuration ?? [];

        $secrets =
            $channel->secret_configuration ?? [];

        return [
            'id' =>
                $channel->id,

            'name' =>
                $channel->name,

            'driver' =>
                $channel->driver->value,

            'auth_type' =>
                $channel->auth_type->value,

            'health_status' =>
                $channel->health_status->value,

            'is_enabled' =>
                (bool) $channel->is_enabled,

            'is_primary' =>
                (bool) $channel->is_primary,

            'host' =>
                $configuration['host'] ?? null,

            'port' =>
                $configuration['port'] ?? null,

            'encryption' =>
                $configuration[
                'encryption'
                ] ?? null,

            'username' =>
                $secrets['username']
                ?? $configuration['username']
                    ?? null,

            'credentials_configured' =>
                $channel->auth_type
                === MailAuthenticationType::None
                || !empty(
                $secrets['password']
                )
                || !empty(
                $secrets['access_token']
                ),

            'last_checked_at' =>
                $channel
                    ->last_checked_at
                    ?->toIso8601String(),

            'last_success_at' =>
                $channel
                    ->last_success_at
                    ?->toIso8601String(),

            'last_error_at' =>
                $channel
                    ->last_error_at
                    ?->toIso8601String(),
        ];
    }

    private function mailboxData(
        Mailbox $mailbox
    ): array {
        $incomingChannel =
            $this->preferredChannel(
                channels:
                $mailbox->channels,

                direction:
                MailboxChannelDirection::Incoming,
            );

        $outgoingChannel =
            $this->preferredChannel(
                channels:
                $mailbox->channels,

                direction:
                MailboxChannelDirection::Outgoing,
            );

        return [
            'id' =>
                $mailbox->id,

            'name' =>
                $mailbox->name,

            'email_address' =>
                $mailbox->email_address,

            'display_name' =>
                $mailbox->display_name,

            'is_active' =>
                (bool) $mailbox->is_active,

            'is_deleted' =>
                $mailbox->trashed(),

            'deleted_at' =>
                $mailbox
                    ->deleted_at
                    ?->toIso8601String(),

            'is_default_outgoing' =>
                (bool) $mailbox
                    ->is_default_outgoing,

            'department' =>
                $mailbox->department !== null
                    ? [
                    'id' =>
                        $mailbox
                            ->department
                            ->id,

                    'name' =>
                        $mailbox
                            ->department
                            ->name,
                ]
                    : null,

            'incoming_channel' =>
                $this->channelData(
                    $incomingChannel
                ),

            'outgoing_channel' =>
                $this->channelData(
                    $outgoingChannel
                ),

            'channels_count' =>
                $mailbox
                    ->channels
                    ->count(),

            'created_at' =>
                $mailbox
                    ->created_at
                    ?->toIso8601String(),

            'updated_at' =>
                $mailbox
                    ->updated_at
                    ?->toIso8601String(),
        ];
    }

    private function preferredChannel(
        Collection $channels,
        MailboxChannelDirection $direction,
    ): ?MailboxChannel {
        $directionChannels = $channels
            ->filter(
                fn (
                    MailboxChannel $channel
                ): bool =>
                    $channel->direction
                    === $direction
            )
            ->values();

        $primaryEnabledChannel =
            $directionChannels->first(
                fn (
                    MailboxChannel $channel
                ): bool =>
                    (bool) $channel->is_primary
                    && (bool) $channel->is_enabled
            );

        if ($primaryEnabledChannel !== null) {
            return $primaryEnabledChannel;
        }

        $enabledChannel =
            $directionChannels->first(
                fn (
                    MailboxChannel $channel
                ): bool =>
                (bool) $channel->is_enabled
            );

        if ($enabledChannel !== null) {
            return $enabledChannel;
        }

        return $directionChannels->first();
    }

    private function channelData(
        ?MailboxChannel $channel
    ): ?array {
        if ($channel === null) {
            return null;
        }

        return [
            'id' =>
                $channel->id,

            'name' =>
                $channel->name,

            'direction' =>
                $channel->direction->value,

            'driver' =>
                $channel->driver->value,

            'health_status' =>
                $channel->health_status->value,

            'is_primary' =>
                (bool) $channel->is_primary,

            'is_enabled' =>
                (bool) $channel->is_enabled,

            'failover_order' =>
                (int) $channel
                    ->failover_order,

            'last_checked_at' =>
                $channel
                    ->last_checked_at
                    ?->toIso8601String(),

            'last_success_at' =>
                $channel
                    ->last_success_at
                    ?->toIso8601String(),

            'last_error_at' =>
                $channel
                    ->last_error_at
                    ?->toIso8601String(),
        ];
    }

    private function systemMailConfigured(): bool
    {
        return Mailbox::query()
            ->where(
                'is_active',
                true
            )
            ->whereHas(
                'incomingChannels',
                fn ($query) => $query->where(
                    'is_enabled',
                    true
                )
            )
            ->whereHas(
                'outgoingChannels',
                fn ($query) => $query->where(
                    'is_enabled',
                    true
                )
            )
            ->exists();
    }

    private function isConfigured(
        array $mailbox
    ): bool {
        if (
            $mailbox['is_deleted']
            || ! $mailbox['is_active']
        ) {
            return false;
        }

        $incoming =
            $mailbox['incoming_channel'];

        $outgoing =
            $mailbox['outgoing_channel'];

        return $incoming !== null
            && $outgoing !== null
            && $incoming['is_enabled']
            && $outgoing['is_enabled'];
    }

    private function isHealthy(
        array $mailbox
    ): bool {
        if (!$this->isConfigured($mailbox)) {
            return false;
        }

        return $mailbox[
            'incoming_channel'
            ]['health_status'] === 'healthy'
            && $mailbox[
            'outgoing_channel'
            ]['health_status'] === 'healthy';
    }

    private function needsAttention(
        array $mailbox
    ): bool {
        return ! $mailbox['is_deleted']
            && $mailbox['is_active']
            && ! $this->isHealthy(
                $mailbox
            );
    }

    private function enumLabel(
        string $value
    ): string {
        return ucwords(
            str_replace(
                [
                    '_',
                    '-',
                ],
                ' ',
                $value
            )
        );
    }

    private function bytesToMegabytes(
        mixed $bytes
    ): int {
        return max(
            1,
            (int) ceil(
                ((int) $bytes)
                / 1024
                / 1024
            )
        );
    }
}
