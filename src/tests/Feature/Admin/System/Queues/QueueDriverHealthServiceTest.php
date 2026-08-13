<?php

namespace Tests\Feature\Admin\System\Queues;

use App\Enums\Admin\System\QueueDriverType;
use App\Models\Admin\System\QueueDriverConfiguration;
use App\Models\Admin\System\SystemAuditLog;
use App\Services\Admin\System\Queues\QueueDriverHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QueueDriverHealthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_and_sync_health_are_persisted_and_audited(): void
    {
        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', fn ($t) => $t->id());
        }$cases = [[QueueDriverType::Database, ['database_connection' => config('database.default'), 'retry_after' => 360, 'after_commit' => false]], [QueueDriverType::Sync, []]];
        foreach ($cases as [$driver,$values]) {
            $c = QueueDriverConfiguration::query()->create(['name' => $driver->value, 'driver' => $driver, 'configuration' => $values]);
            $result = app(QueueDriverHealthService::class)->test($c);
            $this->assertSame('healthy', $result->status->value);
            $this->assertNotNull($c->latestHealthCheck()->first());
        } $this->assertSame(2, SystemAuditLog::where('action', 'test')->count());
    }
}
