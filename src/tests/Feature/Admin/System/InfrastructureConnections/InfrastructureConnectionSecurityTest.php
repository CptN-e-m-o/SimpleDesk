<?php

namespace Tests\Feature\Admin\System\InfrastructureConnections;

use App\Models\Admin\System\InfrastructureConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InfrastructureConnectionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_credentials_are_encrypted_hidden_and_absent_from_audit(): void
    {
        $c = InfrastructureConnection::factory()->create(['credentials' => ['password' => 'never-plaintext']]);
        $raw = DB::table('infrastructure_connections')->where('id', $c->id)->value('credentials');
        $this->assertStringNotContainsString('never-plaintext', $raw);
        $this->assertArrayNotHasKey('credentials', $c->toArray());
    }
}
