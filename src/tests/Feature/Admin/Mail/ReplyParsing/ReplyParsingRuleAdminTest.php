<?php

namespace Tests\Feature\Admin\Mail\ReplyParsing;

use App\Enums\Admin\Mail\MailAdminAuditEvent;
use App\Models\Admin\Mail\MailAdminAuditLog;
use App\Models\Admin\Mail\ReplyParsingRule;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ReplyParsingRuleAdminTest extends TestCase
{
    use DatabaseMigrations;

    public function test_index_requires_permission_and_includes_soft_deleted_rules(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.email.reply-parsing.index'))->assertForbidden();

        $viewer = $this->createAgentWithPermissions(['admin.mail.view_reply_parsing']);
        $active = ReplyParsingRule::query()->create($this->payload(['name' => 'Active']));
        $deleted = ReplyParsingRule::query()->create($this->payload(['name' => 'Deleted']));
        $deleted->delete();

        $this->actingAs($viewer)
            ->get(route('admin.email.reply-parsing.index'))
            ->assertOk()
            ->assertSee('Admin\\/Email\\/ReplyParsing\\/Index', false)
            ->assertSee('Active')
            ->assertSee('Deleted');

        $this->assertNotSame($active->id, $deleted->id);
    }

    public function test_manage_permission_controls_mutations(): void
    {
        $viewer = $this->createAgentWithPermissions(['admin.mail.view_reply_parsing']);

        $this->actingAs($viewer)
            ->post(route('admin.email.reply-parsing.store'), $this->payload())
            ->assertForbidden();
    }

    public function test_create_update_and_audit_events(): void
    {
        $admin = $this->createAgentWithPermissions(['admin.mail.manage_reply_parsing']);

        $this->actingAs($admin)
            ->post(route('admin.email.reply-parsing.store'), $this->payload())
            ->assertRedirect(route('admin.email.reply-parsing.index'));

        $rule = ReplyParsingRule::query()->sole();
        $this->assertSame('On wrote', $rule->name);

        $this->actingAs($admin)
            ->put(route('admin.email.reply-parsing.update', $rule->id), $this->payload([
                'name' => 'Updated rule',
                'display_order' => 5,
            ]))
            ->assertRedirect(route('admin.email.reply-parsing.index'));

        $this->assertDatabaseHas('mail_reply_parsing_rules', [
            'id' => $rule->id,
            'name' => 'Updated rule',
            'display_order' => 5,
        ]);
        $this->assertAuditEvents([
            MailAdminAuditEvent::ReplyParsingRuleCreated,
            MailAdminAuditEvent::ReplyParsingRuleUpdated,
        ]);
    }

    public function test_delete_restore_and_force_delete_are_audited(): void
    {
        $admin = $this->createAgentWithPermissions(['admin.mail.manage_reply_parsing']);
        $rule = ReplyParsingRule::query()->create($this->payload());

        $this->actingAs($admin)
            ->delete(route('admin.email.reply-parsing.destroy', $rule->id))
            ->assertRedirect();
        $this->assertSoftDeleted('mail_reply_parsing_rules', ['id' => $rule->id]);

        $this->actingAs($admin)
            ->post(route('admin.email.reply-parsing.restore', $rule->id))
            ->assertRedirect();
        $rule->refresh();
        $this->assertFalse($rule->is_active);
        $this->assertSame('On .+ wrote:', $rule->pattern);
        $this->assertSame(10, $rule->display_order);

        $this->actingAs($admin)->delete(route('admin.email.reply-parsing.destroy', $rule->id));
        $this->actingAs($admin)
            ->delete(route('admin.email.reply-parsing.force-destroy', $rule->id))
            ->assertRedirect();
        $this->assertDatabaseMissing('mail_reply_parsing_rules', ['id' => $rule->id]);

        $this->assertAuditEvents([
            MailAdminAuditEvent::ReplyParsingRuleDeleted,
            MailAdminAuditEvent::ReplyParsingRuleRestored,
            MailAdminAuditEvent::ReplyParsingRuleDeleted,
            MailAdminAuditEvent::ReplyParsingRuleForceDeleted,
        ]);
    }

    public function test_force_delete_rejects_active_rule(): void
    {
        $admin = $this->createAgentWithPermissions(['admin.mail.manage_reply_parsing']);
        $rule = ReplyParsingRule::query()->create($this->payload());

        $this->actingAs($admin)
            ->delete(route('admin.email.reply-parsing.force-destroy', $rule->id))
            ->assertNotFound();

        $this->assertDatabaseHas('mail_reply_parsing_rules', ['id' => $rule->id]);
    }

    public function test_preview_supports_literal_and_regex_without_saving(): void
    {
        $admin = $this->createAgentWithPermissions(['admin.mail.manage_reply_parsing']);

        $this->actingAs($admin)
            ->postJson(route('admin.email.reply-parsing.preview'), [
                ...$this->payload(['pattern' => '-----Original Message-----', 'pattern_type' => 'literal']),
                'test_content' => 'Useful'."\n".'-----Original Message-----'."\n".'Old',
                'test_content_type' => 'plain_text',
            ])
            ->assertOk()
            ->assertJsonPath('data.matched', true)
            ->assertJsonPath('data.parsed_content', 'Useful'."\n");

        $this->actingAs($admin)
            ->postJson(route('admin.email.reply-parsing.preview'), [
                ...$this->payload(),
                'test_content' => 'Useful'."\n".'On Monday, John wrote:',
                'test_content_type' => 'plain_text',
            ])
            ->assertOk()
            ->assertJsonPath('data.matched', true);

        $this->assertDatabaseCount('mail_reply_parsing_rules', 0);
    }

    public function test_invalid_regex_returns_validation_error(): void
    {
        $admin = $this->createAgentWithPermissions(['admin.mail.manage_reply_parsing']);

        $this->actingAs($admin)
            ->postJson(route('admin.email.reply-parsing.store'), $this->payload([
                'pattern' => '([invalid',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['pattern']);
    }

    private function assertAuditEvents(array $events): void
    {
        $this->assertSame(
            array_map(static fn (MailAdminAuditEvent $event): string => $event->value, $events),
            MailAdminAuditLog::query()->orderBy('id')->get()->map(
                static fn (MailAdminAuditLog $log): string => (string) $log->getRawOriginal('event')
            )->all(),
        );
    }

    private function createAgentWithPermissions(array $keys): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'name' => 'reply-parser-'.$user->id,
            'label' => 'Reply parser',
            'description' => null,
            'type' => 'agent',
            'is_system' => false,
            'is_default' => false,
        ]);
        $group = PermissionGroup::query()->create([
            'key' => 'reply-parser-'.$user->id,
            'label' => 'Reply parser',
            'panel' => 'admin',
            'type' => 'agent',
            'sort_order' => 1,
        ]);
        $ids = collect($keys)->map(fn (string $key): int => Permission::query()->create([
            'permission_group_id' => $group->id,
            'parent_id' => null,
            'key' => $key,
            'label' => $key,
            'type' => 'agent',
            'ui_type' => 'checkbox',
            'description' => null,
            'sort_order' => 1,
        ])->id)->all();
        $role->permissions()->sync($ids);
        $user->roles()->attach($role);

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'On wrote',
            'pattern' => 'On .+ wrote:',
            'pattern_type' => 'regex',
            'content_type' => 'both',
            'display_order' => 10,
            'is_active' => true,
            'description' => 'Standard reply header',
        ], $overrides);
    }
}
