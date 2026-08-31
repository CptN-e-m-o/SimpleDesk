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

    public function test_relation_values_survive_down_and_repeated_up_migration(): void
    {
        $priorities = TicketPriority::query()->whereIn('slug', ['low', 'normal', 'high', 'urgent'])->orderByRaw("CASE slug WHEN 'low' THEN 1 WHEN 'normal' THEN 2 WHEN 'high' THEN 3 ELSE 4 END")->get();
        $custom = TicketPriority::factory()->create(['slug' => 'customer-impact']);
        $tickets = $priorities->push($custom)->map(fn (TicketPriority $priority) => Ticket::factory()->create(['priority_id' => $priority->id]));
        $migration = require database_path('migrations/2026_08_29_000002_add_ticket_catalog_relations.php');
        $migration->down();

        foreach (['low', 'medium', 'high', 'urgent', 'customer-impact'] as $index => $legacy) {
            $this->assertSame($legacy, DB::table('tickets')->where('id', $tickets[$index]->id)->value('priority'));
        }

        $migration->up();
        $expected = ['low', 'normal', 'high', 'urgent', 'customer-impact'];
        foreach ($tickets as $index => $ticket) {
            $priorityId = DB::table('tickets')->where('id', $ticket->id)->value('priority_id');
            $this->assertSame($expected[$index], TicketPriority::query()->findOrFail($priorityId)->slug);
        }
        $this->assertFalse(DB::getSchemaBuilder()->hasColumn('tickets', 'priority'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('tickets', 'priority_id'));
    }
}
