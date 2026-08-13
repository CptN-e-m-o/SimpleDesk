<?php

namespace Tests\Feature\Admin\System;

use App\Models\Admin\System\SystemAuditLog;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_logger_appends_sanitized_audit_entry(): void
    {
        $this->app->make(SystemAuditLogger::class)->log('drivers', 'view', null, null, null, null, ['safe' => true]);
        $this->assertSame(1, SystemAuditLog::count());
        $this->assertTrue(SystemAuditLog::first()->metadata['safe']);
    }
}
