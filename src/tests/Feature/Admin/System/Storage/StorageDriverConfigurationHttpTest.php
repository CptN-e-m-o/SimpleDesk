<?php

namespace Tests\Feature\Admin\System\Storage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorageDriverConfigurationHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_storage_routes_require_authentication_and_exact_permissions(): void
    {
        $this->get(route('admin.system.storage.index'))->assertRedirect(route('login'));
        $routes = collect(app('router')->getRoutes()->getRoutes())->keyBy(fn ($route) => $route->getName());
        $this->assertContains('permission:admin.settings.storage.view', $routes['admin.system.storage.index']->gatherMiddleware());
        $this->assertContains('permission:admin.settings.storage.create', $routes['admin.system.storage.store']->gatherMiddleware());
        $this->assertContains('permission:admin.settings.storage.update', $routes['admin.system.storage.update']->gatherMiddleware());
        $this->assertContains('permission:admin.settings.storage.archive', $routes['admin.system.storage.destroy']->gatherMiddleware());
        $this->assertContains('permission:admin.settings.storage.delete', $routes['admin.system.storage.force-delete']->gatherMiddleware());
        $this->assertContains('permission:admin.settings.storage.test', $routes['admin.system.storage.test']->gatherMiddleware());
        $this->assertContains('permission:admin.settings.storage.activate', $routes['admin.system.storage.activate']->gatherMiddleware());
        $this->assertContains('permission:admin.settings.storage.force_activate', $routes['admin.system.storage.force-activate']->gatherMiddleware());
    }
}
