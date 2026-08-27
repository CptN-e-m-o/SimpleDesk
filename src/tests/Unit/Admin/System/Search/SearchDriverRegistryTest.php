<?php

namespace Tests\Unit\Admin\System\Search;

use App\Exceptions\Admin\System\Search\SearchDriverAdapterNotRegisteredException;
use App\Services\Admin\System\Search\SearchDriverRegistry;
use Tests\Fakes\Admin\System\FakeSearchDriverAdapter;
use Tests\TestCase;

class SearchDriverRegistryTest extends TestCase
{
    public function test_registry_resolves_strictly_typed_adapter(): void
    {
        $registry = new SearchDriverRegistry(app(), ['database' => FakeSearchDriverAdapter::class]);
        $this->assertInstanceOf(FakeSearchDriverAdapter::class, $registry->adapter('database'));
        $this->expectException(SearchDriverAdapterNotRegisteredException::class);
        $registry->adapter('algolia');
    }
}
