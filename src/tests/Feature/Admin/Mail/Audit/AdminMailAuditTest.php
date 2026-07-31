<?php

namespace Tests\Feature\Admin\Mail\Audit;

use App\Enums\Admin\Mail\MailAdminAuditEvent;
use App\Enums\Admin\Mail\MailAdminAuditStatus;
use App\Enums\Admin\Mail\MailAdminAuditSubjectType;
use App\Models\Admin\Mail\MailAdminAuditLog;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class AdminMailAuditTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerTestRoutes();

        $routes = app('router')->getRoutes();

        $routes->refreshNameLookups();
        $routes->refreshActionLookups();
    }

    public function test_successful_action_is_audited_without_sensitive_values(): void
    {
        $admin = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->postJson(
                route('test.mail-audit.mailboxes.store'),
                [
                    'name' => 'Support',
                    'email_address' => 'support@example.test',
                    'display_name' => 'SimpleDesk Support',
                    'department_id' => null,
                    'is_active' => true,
                    'is_default_outgoing' => true,
                    'internal_notes' => 'Private internal note',
                    'secret_configuration' => [
                        'password' => 'private-password',
                    ],
                ]
            )
            ->assertCreated();

        $mailboxId = (int) $response->json('data.id');

        $audit = MailAdminAuditLog::query()->sole();

        $this->assertSame(
            MailAdminAuditEvent::MailboxCreated,
            $audit->event
        );

        $this->assertSame(
            MailAdminAuditStatus::Succeeded,
            $audit->status
        );

        $this->assertSame(
            MailAdminAuditSubjectType::Mailbox,
            $audit->subject_type
        );

        $this->assertSame($mailboxId, $audit->subject_id);
        $this->assertSame($mailboxId, $audit->mailbox_id);
        $this->assertSame($admin->id, $audit->actor_id);

        $serializedContext = json_encode(
            $audit->context,
            JSON_THROW_ON_ERROR
        );

        $this->assertStringContainsString(
            'support@example.test',
            $serializedContext
        );

        $this->assertStringNotContainsString(
            'Private internal note',
            $serializedContext
        );

        $this->assertStringNotContainsString(
            'private-password',
            $serializedContext
        );

        $this->assertStringNotContainsString(
            'secret_configuration',
            $serializedContext
        );
    }

    public function test_rejected_action_is_audited(): void
    {
        $admin = User::factory()->create();
        $mailbox = $this->createMailbox();

        $this
            ->actingAs($admin)
            ->patchJson(
                route(
                    'test.mail-audit.mailboxes.rejected',
                    $mailbox
                ),
                [
                    'name' => '',
                ]
            )
            ->assertUnprocessable();

        $audit = MailAdminAuditLog::query()->sole();

        $this->assertSame(
            MailAdminAuditEvent::MailboxUpdated,
            $audit->event
        );

        $this->assertSame(
            MailAdminAuditStatus::Rejected,
            $audit->status
        );

        $this->assertSame(
            ['name'],
            $audit->context['validation_fields']
        );

        $this->assertSame(
            'mailbox_validation_failed',
            $audit->context['error_code']
        );
    }

    public function test_failed_connection_test_is_audited_as_failed(): void
    {
        $admin = User::factory()->create();
        $mailbox = $this->createMailbox();
        $channel = $this->createChannel($mailbox);

        $response = $this
            ->actingAs($admin)
            ->postJson(
                route(
                    'test.mail-audit.channels.test',
                    $channel
                )
            )
            ->assertOk()
            ->assertJsonPath(
                'data.successful',
                false
            );

        $audit = MailAdminAuditLog::query()->sole();

        $this->assertSame(
            MailAdminAuditEvent::ChannelConnectionTested,
            $audit->event
        );

        $this->assertSame(
            MailAdminAuditStatus::Failed,
            $audit->status
        );

        $this->assertSame($channel->id, $audit->subject_id);
        $this->assertSame($mailbox->id, $audit->mailbox_id);

        $serializedContext = json_encode(
            $audit->context,
            JSON_THROW_ON_ERROR
        );

        $this->assertStringContainsString(
            'smtp_authentication_failed',
            $serializedContext
        );

        $this->assertStringNotContainsString(
            'private-password',
            $serializedContext
        );
    }

    public function test_unexpected_exception_is_audited_and_rethrown(): void
    {
        $admin = User::factory()->create();
        $mailbox = $this->createMailbox();

        $this->withoutExceptionHandling();

        try {
            $this
                ->actingAs($admin)
                ->postJson(
                    route(
                        'test.mail-audit.mailboxes.failed',
                        $mailbox
                    )
                );

            $this->fail(
                'The expected exception was not thrown.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Unexpected failure password=private-password',
                $exception->getMessage()
            );
        }

        $audit = MailAdminAuditLog::query()->sole();

        $this->assertSame(
            MailAdminAuditStatus::Failed,
            $audit->status
        );

        $this->assertSame(
            RuntimeException::class,
            $audit->context['exception']['class']
        );

        $this->assertStringNotContainsString(
            'private-password',
            $audit->context['exception']['message']
        );
    }

    public function test_audit_list_requires_permission_and_supports_filters(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->getJson(
                route('admin.email.audit-logs.index')
            )
            ->assertForbidden();

        $admin = $this->createAgentWithPermissions([
            'admin.mail.view_audit',
        ]);

        $mailbox = $this->createMailbox();

        MailAdminAuditLog::query()->create([
            'actor_id' => $admin->id,
            'mailbox_id' => $mailbox->id,
            'event' => MailAdminAuditEvent::MailboxUpdated,
            'status' => MailAdminAuditStatus::Succeeded,
            'subject_type' => MailAdminAuditSubjectType::Mailbox,
            'subject_id' => $mailbox->id,
            'request_id' => '11111111-1111-4111-8111-111111111111',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'context' => [
                'request' => [
                    'name' => 'Support',
                ],
            ],
            'created_at' => now(),
        ]);

        MailAdminAuditLog::query()->create([
            'actor_id' => $admin->id,
            'mailbox_id' => null,
            'event' => MailAdminAuditEvent::AntivirusConnectionTested,
            'status' => MailAdminAuditStatus::Failed,
            'subject_type' => MailAdminAuditSubjectType::Antivirus,
            'subject_id' => null,
            'request_id' => '22222222-2222-4222-8222-222222222222',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'context' => [
                'result' => [
                    'successful' => false,
                ],
            ],
            'created_at' => now(),
        ]);

        $this
            ->actingAs($admin)
            ->getJson(
                route(
                    'admin.email.audit-logs.index',
                    [
                        'event' => 'mailbox_updated',
                        'status' => 'succeeded',
                        'mailbox_id' => $mailbox->id,
                    ]
                )
            )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.event',
                'mailbox_updated'
            )
            ->assertJsonPath(
                'data.0.status',
                'succeeded'
            )
            ->assertJsonPath(
                'data.0.subject_type',
                'mailbox'
            )
            ->assertJsonPath(
                'data.0.subject_id',
                $mailbox->id
            )
            ->assertJsonPath(
                'data.0.actor.id',
                $admin->id
            );
    }

    private function registerTestRoutes(): void
    {
        Route::middleware([
            'web',
            'auth',
        ])
            ->prefix('_test/mail-audit')
            ->name('test.mail-audit.')
            ->group(function (): void {
                Route::post('/mailboxes', function (Request $request) {
                    $mailbox = Mailbox::query()->create([
                        'name' => $request->string('name')->toString(),
                        'email_address' => $request->string('email_address')->toString(),
                        'display_name' => $request->string('display_name')->toString(),
                        'department_id' => null,
                        'is_active' => $request->boolean('is_active'),
                        'is_default_outgoing' => $request->boolean('is_default_outgoing'),
                        'internal_notes' => $request->input('internal_notes'),
                    ]);

                    return response()->json([
                        'data' => [
                            'id' => $mailbox->id,
                            'mailbox_id' => $mailbox->id,
                        ],
                    ], 201);
                })
                    ->middleware('mail.audit:mailbox_created')
                    ->name('mailboxes.store');

                Route::patch(
                    '/mailboxes/{mailbox}/rejected',
                    fn () => response()->json([
                        'message' => 'The given data was invalid.',
                        'error_code' => 'mailbox_validation_failed',
                        'errors' => [
                            'name' => [
                                'The name field is required.',
                            ],
                        ],
                    ], 422)
                )
                    ->middleware('mail.audit:mailbox_updated')
                    ->name('mailboxes.rejected');

                Route::post(
                    '/mailboxes/{mailbox}/failed',
                    function (): never {
                        throw new RuntimeException(
                            'Unexpected failure password=private-password'
                        );
                    }
                )
                    ->middleware('mail.audit:mailbox_updated')
                    ->name('mailboxes.failed');

                Route::post(
                    '/channels/{channel}/test',
                    fn (MailboxChannel $channel) => response()->json([
                        'data' => [
                            'successful' => false,
                            'message' => 'Authentication failed password=private-password',
                            'latency_ms' => 12,
                            'details' => [
                                'channel_id' => $channel->id,
                                'driver' => 'smtp',
                                'error_code' => 'smtp_authentication_failed',
                            ],
                        ],
                    ])
                )
                    ->middleware('mail.audit:channel_connection_tested')
                    ->name('channels.test');
            });
    }

    private function createMailbox(): Mailbox
    {
        return Mailbox::query()->create([
            'name' => 'Support',
            'email_address' => 'support-'.uniqid().'@example.test',
            'display_name' => 'SimpleDesk Support',
            'department_id' => null,
            'is_active' => true,
            'is_default_outgoing' => false,
            'internal_notes' => null,
        ]);
    }

    private function createChannel(
        Mailbox $mailbox
    ): MailboxChannel {
        return MailboxChannel::query()->create([
            'mailbox_id' => $mailbox->id,
            'provider_connection_id' => null,
            'name' => 'SMTP',
            'direction' => 'outgoing',
            'driver' => 'smtp',
            'auth_type' => 'none',
            'is_enabled' => true,
            'is_primary' => true,
            'failover_order' => 100,
            'configuration' => [],
            'secret_configuration' => [],
            'health_status' => 'unknown',
        ]);
    }

    private function createAgentWithPermissions(
        array $permissionKeys
    ): User {
        $user = User::factory()->create();

        $role = Role::query()->create([
            'name' => 'mail-audit-admin-'.$user->id,
            'label' => 'Mail audit administrator',
            'description' => null,
            'type' => 'agent',
            'is_system' => false,
            'is_default' => false,
        ]);

        $group = PermissionGroup::query()->create([
            'key' => 'mail-audit-test-'.$user->id,
            'label' => 'Mail audit test',
            'panel' => 'admin',
            'type' => 'agent',
            'sort_order' => 1,
        ]);

        $permissionIds = collect($permissionKeys)
            ->map(
                fn (string $key): int => Permission::query()->create([
                    'permission_group_id' => $group->id,
                    'parent_id' => null,
                    'key' => $key,
                    'label' => $key,
                    'type' => 'agent',
                    'ui_type' => 'checkbox',
                    'description' => null,
                    'sort_order' => 1,
                ])->id
            )
            ->all();

        $role->permissions()->sync($permissionIds);
        $user->roles()->attach($role);

        return $user;
    }
}
