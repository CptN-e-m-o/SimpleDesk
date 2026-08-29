<?php

namespace Tests\Feature\Admin\Manage;

use App\Models\Ticket;
use App\Models\TicketPriority;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TicketPriorityMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_legacy_priority_values_are_backfilled_to_catalog_relations(): void
    {
        $tickets = collect(['low', 'medium', 'high', 'urgent'])->map(fn () => Ticket::factory()->create());
        $migration = require database_path('migrations/2026_08_29_000002_add_ticket_catalog_relations.php');
        $migration->down();
        foreach (['low', 'medium', 'high', 'urgent'] as $index => $legacy) {
            DB::table('tickets')->where('id', $tickets[$index]->id)->update(['priority' => $legacy]);
        }
        $migration->up();
        $expected = ['low', 'normal', 'high', 'urgent'];
        foreach ($tickets as $index => $ticket) {
            $priorityId = DB::table('tickets')->where('id', $ticket->id)->value('priority_id');
            $this->assertSame($expected[$index], TicketPriority::query()->findOrFail($priorityId)->slug);
        }
        $this->assertFalse(DB::getSchemaBuilder()->hasColumn('tickets', 'priority'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('tickets', 'priority_id'));
    }
}
