<?php

namespace Tests\Feature\Admin\System\Search;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchDriverConfigurationHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_routes_require_authentication_and_exact_permissions(): void
    {
        $this->get(route('admin.system.search.index'))->assertRedirect(route('login'));
        $routes = collect(app('router')->getRoutes()->getRoutes())->keyBy(fn ($route) => $route->getName());
        $this->assertContains('permission:admin.settings.search.view', $routes['admin.system.search.index']->gatherMiddleware());
        $this->assertContains('permission:admin.settings.search.test', $routes['admin.system.search.test']->gatherMiddleware());
        $this->assertContains('permission:admin.settings.search.force_activate', $routes['admin.system.search.force-activate']->gatherMiddleware());
    }
}
