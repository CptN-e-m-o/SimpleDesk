<?php

namespace Tests\Feature\Admin\Manage;

use App\Models\Permission;
use App\Models\Role;
use App\Models\TicketPriority;
use App\Models\TicketType;
use App\Models\User\User;
use Database\Seeders\Permissions\Agent\AdminPanel\PermissionAdminManageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TicketCatalogHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_routes_require_authentication_and_exact_permissions(): void
    {
        $this->get(route('admin.manage.priorities.index'))->assertRedirect(route('login'));
        $this->seed(PermissionAdminManageSeeder::class);
        $role = Role::query()->create(['name' => 'catalog-viewer', 'label' => 'Catalog Viewer', 'type' => 'user']);
        $user = User::factory()->create();
        $user->roles()->attach($role);
        $this->actingAs($user)->get(route('admin.manage.priorities.index'))->assertForbidden();
        $role->permissions()->attach(Permission::query()->where('key', 'admin.manage.priorities.view')->valueOrFail('id'));
        $this->actingAs($user)->get(route('admin.manage.priorities.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.manage.priorities.create'))->assertForbidden();
    }

    public function test_index_supports_search_visibility_status_and_usage_counts(): void
    {
        $this->seed(PermissionAdminManageSeeder::class);
        $role = Role::query()->create(['name' => 'catalog-filter', 'label' => 'Catalog Filter', 'type' => 'user']);
        $role->permissions()->attach(Permission::query()->where('key', 'admin.manage.priorities.view')->valueOrFail('id'));
        $user = User::factory()->create();
        $user->roles()->attach($role);
        TicketPriority::factory()->create(['name' => 'Private Impact', 'visibility' => 'internal', 'is_active' => false]);
        $this->actingAs($user)->get(route('admin.manage.priorities.index', ['search' => 'Private', 'visibility' => 'internal', 'status' => 'inactive']))->assertInertia(fn (Assert $page) => $page->component('Admin/Manage/Priorities/Index')->has('priorities.data', 1)->where('priorities.data.0.tickets_count', 0));
    }

    public function test_normal_indexes_exclude_archived_records_and_archive_filter_only_returns_archived(): void
    {
        $this->seed(PermissionAdminManageSeeder::class);
        $role = Role::query()->create(['name' => 'catalog-archive', 'label' => 'Catalog Archive', 'type' => 'user']);
        $role->permissions()->attach(Permission::query()->whereIn('key', ['admin.manage.priorities.view', 'admin.manage.ticket_types.view'])->pluck('id'));
        $user = User::factory()->create();
        $user->roles()->attach($role);
        $priority = TicketPriority::factory()->create(['name' => 'Archived Only Priority']);
        $priority->delete();
        $type = TicketType::factory()->create(['name' => 'Archived Only Type']);
        $type->delete();

        $this->actingAs($user)->get(route('admin.manage.priorities.index', ['search' => 'Archived Only Priority']))->assertInertia(fn (Assert $page) => $page->has('priorities.data', 0));
        $this->actingAs($user)->get(route('admin.manage.priorities.index', ['search' => 'Archived Only Priority', 'status' => 'archived']))->assertInertia(fn (Assert $page) => $page->has('priorities.data', 1)->where('priorities.data.0.id', $priority->id));
        $this->actingAs($user)->get(route('admin.manage.ticket-types.index', ['search' => 'Archived Only Type']))->assertInertia(fn (Assert $page) => $page->has('ticketTypes.data', 0));
        $this->actingAs($user)->get(route('admin.manage.ticket-types.index', ['search' => 'Archived Only Type', 'status' => 'archived']))->assertInertia(fn (Assert $page) => $page->has('ticketTypes.data', 1)->where('ticketTypes.data.0.id', $type->id));
    }
}
