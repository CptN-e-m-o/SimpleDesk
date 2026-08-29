<?php

namespace Tests\Feature\Admin\Manage;

use App\Enums\Admin\Manage\CatalogVisibility;
use App\Models\Admin\System\SystemAuditLog;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketType;
use App\Models\User\User;
use App\Services\Admin\Manage\TicketPriorityCatalogService;
use App\Services\Admin\Manage\TicketTypeCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TicketCatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_priority_slug_is_generated_and_stable_and_names_are_case_insensitive_unique(): void
    {
        $actor = User::factory()->create();
        $service = app(TicketPriorityCatalogService::class);
        $priority = $service->create($this->priorityData('Customer Impact'), $actor);
        $this->assertSame('customer-impact', $priority->slug);
        $updated = $service->update($priority, $this->priorityData('Business Impact'), $actor);
        $this->assertSame('customer-impact', $updated->slug);

        $this->expectException(ValidationException::class);
        $service->create($this->priorityData('business impact'), $actor);
    }

    public function test_default_invariants_and_transactional_switching_are_enforced(): void
    {
        $actor = User::factory()->create();
        $service = app(TicketPriorityCatalogService::class);
        $normal = TicketPriority::query()->where('is_default', true)->firstOrFail();
        $target = $service->create($this->priorityData('Elevated'), $actor);
        $service->makeDefault($target, $actor);
        $this->assertFalse($normal->refresh()->is_default);
        $this->assertTrue($target->refresh()->is_default);
        $this->assertSame(1, TicketPriority::query()->where('is_default', true)->count());
        $audit = SystemAuditLog::query()->where('action', 'manage.priority.default_changed')->latest('id')->firstOrFail();
        $this->assertSame($normal->id, $audit->metadata['previous_priority_id']);
        $this->assertSame($target->id, $audit->metadata['new_priority_id']);

        foreach (['disable', 'archive'] as $operation) {
            try {
                $operation === 'disable' ? $service->setActive($target, false, $actor) : $service->archive($target, $actor);
                $this->fail('Default invariant was not enforced.');
            } catch (ValidationException) {
                $this->assertTrue($target->refresh()->is_default);
            }
        }

        $internal = $service->create([...$this->priorityData('Internal'), 'visibility' => CatalogVisibility::Internal->value], $actor);
        $this->expectException(ValidationException::class);
        $service->makeDefault($internal, $actor);
    }

    public function test_catalog_lifecycle_restore_reorder_usage_and_audit(): void
    {
        $actor = User::factory()->create();
        $priorityService = app(TicketPriorityCatalogService::class);
        $typeService = app(TicketTypeCatalogService::class);
        $priority = $priorityService->create($this->priorityData('Deferred'), $actor);
        $type = $typeService->create(['name' => 'Change', 'description' => null, 'visibility' => 'public', 'is_active' => true], $actor);
        $ticket = Ticket::factory()->create(['priority_id' => $priority->id, 'ticket_type_id' => $type->id]);
        $priorityService->archive($priority, $actor);
        $typeService->archive($type, $actor);
        $this->assertSame($priority->id, $ticket->fresh()->priority->id);
        $this->assertSame($type->id, $ticket->fresh()->ticketType->id);
        $this->assertFalse($priorityService->restore($priority->id, $actor)->is_active);
        $this->assertFalse($typeService->restore($type->id, $actor)->is_active);
        $priorityService->reorder([$priority->id], $actor);
        $typeService->reorder([$type->id], $actor);
        $this->assertTrue(SystemAuditLog::query()->where('action', 'manage.priority.restored')->exists());
        $this->assertTrue(SystemAuditLog::query()->where('action', 'manage.ticket_type.reordered')->exists());
    }

    public function test_ticket_type_is_nullable_and_public_catalog_excludes_internal_values(): void
    {
        $ticket = Ticket::factory()->create(['ticket_type_id' => null]);
        $this->assertNull($ticket->ticketType);
        TicketPriority::factory()->create(['visibility' => 'internal', 'is_active' => true]);
        TicketType::factory()->create(['visibility' => 'internal', 'is_active' => true]);
        $this->assertFalse(TicketPriority::query()->where('visibility', 'public')->pluck('visibility')->contains(CatalogVisibility::Internal));
        $this->assertFalse(TicketType::query()->where('visibility', 'public')->pluck('visibility')->contains(CatalogVisibility::Internal));
    }

    public function test_portal_ticket_without_priority_uses_current_default(): void
    {
        $user = User::factory()->create();
        $category = TicketCategory::factory()->create(['is_active' => true]);
        $default = TicketPriority::query()->where('is_default', true)->firstOrFail();
        $this->actingAs($user)->post(route('tickets.store'), ['subject' => 'Default priority request', 'category_id' => $category->id, 'priority_id' => null, 'description' => 'Enough detail for a valid support request.'])->assertRedirect();
        $this->assertDatabaseHas('tickets', ['requester_id' => $user->id, 'priority_id' => $default->id]);
    }

    public function test_requester_catalog_excludes_internal_priorities(): void
    {
        $user = User::factory()->create();
        TicketPriority::factory()->create(['name' => 'Staff Only', 'visibility' => 'internal', 'is_active' => true]);
        $this->actingAs($user)->get(route('tickets.create'))->assertInertia(fn ($page) => $page->component('Tickets/User/Create')->where('priorityOptions', fn ($options) => collect($options)->doesntContain('name', 'Staff Only')));
    }

    private function priorityData(string $name): array
    {
        return ['name' => $name, 'description' => null, 'color' => '#2563EB', 'visibility' => 'public', 'is_active' => true, 'is_default' => false];
    }
}
