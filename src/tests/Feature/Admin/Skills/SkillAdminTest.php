<?php

namespace Tests\Feature\Admin\Skills;

use App\Models\Admin\Skill;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class SkillAdminTest extends TestCase
{
    use DatabaseMigrations;

    public function test_index_requires_view_permission(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.skills.index'))->assertForbidden();
    }

    public function test_user_with_view_permission_can_view_index(): void
    {
        $this->actingAs($this->user(['admin.staff.skills.view']))
            ->get(route('admin.skills.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Skills/Index')->has('skills'));
    }

    public function test_skill_can_be_created_with_one_or_multiple_ordered_rules(): void
    {
        $admin = $this->user(['admin.staff.skills.create']);

        $this->actingAs($admin)->post(route('admin.skills.store'), $this->payload())->assertRedirect();
        $this->assertDatabaseHas('skills', ['name' => 'VIP Billing', 'match_type' => 'any', 'version' => 1]);
        $this->assertDatabaseHas('skill_rules', ['subject_type' => 'ticket', 'sort_order' => 0]);

        $payload = $this->payload('All Conditions');
        $payload['match_type'] = 'all';
        $payload['rules'][] = ['field_key' => 'source', 'operator' => 'equals', 'value' => 'email'];
        $this->actingAs($admin)->post(route('admin.skills.store'), $payload)->assertRedirect();

        $skill = Skill::where('name', 'All Conditions')->firstOrFail();
        $this->assertSame('all', $skill->match_type->value);
        $this->assertSame([0, 1], $skill->rules->pluck('sort_order')->all());
    }

    public function test_unknown_fields_operators_and_reference_values_are_rejected(): void
    {
        $admin = $this->user(['admin.staff.skills.create']);
        $payload = $this->payload();
        $payload['rules'][0]['field_key'] = 'password';
        $this->actingAs($admin)->post(route('admin.skills.store'), $payload)->assertSessionHasErrors('rules.0.field_key');

        $payload = $this->payload();
        $payload['rules'][0]['operator'] = 'contains';
        $this->actingAs($admin)->post(route('admin.skills.store'), $payload)->assertSessionHasErrors('rules.0.operator');

        $payload = $this->payload();
        $payload['rules'][0] = ['field_key' => 'department_id', 'operator' => 'equals', 'value' => 999999];
        $this->actingAs($admin)->post(route('admin.skills.store'), $payload)->assertSessionHasErrors('rules.0.value');
        $this->assertDatabaseEmpty('skills');
    }

    public function test_update_synchronizes_rules_and_versions_only_rule_changes(): void
    {
        $admin = $this->user(['admin.staff.skills.create', 'admin.staff.skills.update']);
        $this->actingAs($admin)->post(route('admin.skills.store'), $this->payload())->assertRedirect();
        $skill = Skill::firstOrFail();

        $descriptionOnly = $this->payload();
        $descriptionOnly['description'] = 'Changed description';
        $this->actingAs($admin)->put(route('admin.skills.update', $skill), $descriptionOnly)->assertRedirect();
        $this->assertSame(1, $skill->fresh()->version);

        $changed = $descriptionOnly;
        $changed['rules'] = [
            ['field_key' => 'source', 'operator' => 'equals', 'value' => 'email'],
            ['field_key' => 'priority', 'operator' => 'in', 'value' => ['high', 'urgent']],
        ];
        $this->actingAs($admin)->put(route('admin.skills.update', $skill), $changed)->assertRedirect();

        $skill = $skill->fresh();
        $this->assertSame(2, $skill->version);
        $this->assertSame(['source', 'priority'], $skill->rules->pluck('field_key')->all());
        $this->assertDatabaseCount('skill_rules', 2);
    }

    public function test_duplicate_toggle_archive_restore_and_force_delete(): void
    {
        $admin = $this->user([
            'admin.staff.skills.create', 'admin.staff.skills.update',
            'admin.staff.skills.archive', 'admin.staff.skills.delete',
        ]);
        $this->actingAs($admin)->post(route('admin.skills.store'), $this->payload())->assertRedirect();
        $skill = Skill::firstOrFail();

        $this->actingAs($admin)->post(route('admin.skills.duplicate', $skill))->assertRedirect();
        $copy = Skill::whereKeyNot($skill->id)->firstOrFail();
        $this->assertSame('VIP Billing Copy', $copy->name);
        $this->assertSame(1, $copy->version);
        $this->assertSame($skill->rules->count(), $copy->rules->count());

        $this->actingAs($admin)->patch(route('admin.skills.toggle', $skill))->assertRedirect();
        $this->assertFalse($skill->fresh()->is_active);
        $this->actingAs($admin)->delete(route('admin.skills.destroy', $skill))->assertRedirect();
        $this->assertSoftDeleted($skill);
        $this->actingAs($admin)->post(route('admin.skills.restore', $skill->id))->assertRedirect();
        $this->assertNotSoftDeleted($skill->fresh());
        $this->actingAs($admin)->delete(route('admin.skills.destroy', $skill))->assertRedirect();
        $this->actingAs($admin)->delete(route('admin.skills.force-delete', $skill->id))->assertRedirect();
        $this->assertDatabaseMissing('skills', ['id' => $skill->id]);
        $this->assertDatabaseMissing('skill_rules', ['skill_id' => $skill->id]);
    }

    public function test_active_skill_cannot_be_force_deleted(): void
    {
        $admin = $this->user(['admin.staff.skills.delete']);
        $skill = Skill::factory()->create();

        $this->actingAs($admin)->delete(route('admin.skills.force-delete', $skill->id))->assertNotFound();
        $this->assertDatabaseHas('skills', ['id' => $skill->id]);
    }

    public function test_each_mutation_requires_its_permission(): void
    {
        $user = $this->user([]);
        $skill = Skill::factory()->create();

        $this->actingAs($user)->post(route('admin.skills.store'), $this->payload())->assertForbidden();
        $this->actingAs($user)->put(route('admin.skills.update', $skill), $this->payload())->assertForbidden();
        $this->actingAs($user)->delete(route('admin.skills.destroy', $skill))->assertForbidden();
        $skill->delete();
        $this->actingAs($user)->delete(route('admin.skills.force-delete', $skill->id))->assertForbidden();
    }

    private function payload(string $name = 'VIP Billing'): array
    {
        return [
            'name' => $name,
            'description' => 'Classifies important billing requests.',
            'match_type' => 'any',
            'is_active' => true,
            'sort_order' => 10,
            'rules' => [
                ['field_key' => 'priority', 'operator' => 'equals', 'value' => 'high'],
            ],
        ];
    }

    private function user(array $keys): User
    {
        $user = User::factory()->create();
        $group = PermissionGroup::create(['key' => 'skills-'.$user->id, 'label' => 'Skills', 'panel' => 'admin', 'type' => 'agent', 'sort_order' => 1]);
        $role = Role::create(['name' => 'skills-'.$user->id, 'label' => 'Skills', 'type' => 'user']);
        $ids = collect($keys)->map(fn (string $key) => Permission::create(['permission_group_id' => $group->id, 'key' => $key, 'label' => $key, 'type' => 'agent', 'ui_type' => 'checkbox', 'sort_order' => 1])->id);
        $role->permissions()->sync($ids);
        $user->roles()->attach($role);

        return $user;
    }
}
