<?php

namespace App\Services\Admin\Manage;

use App\Models\TicketType;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TicketTypeCatalogService
{
    public function __construct(private readonly SystemAuditLogger $audit) {}

    public function create(array $data, User $actor): TicketType
    {
        $this->assertNameUnique($data['name']);
        $type = TicketType::query()->create([...$data, 'slug' => $this->uniqueSlug($data['name']), 'created_by' => $actor->id, 'updated_by' => $actor->id]);
        $this->log($type, 'manage.ticket_type.created', null, $this->safe($type), $actor);

        return $type;
    }

    public function update(TicketType $type, array $data, User $actor): TicketType
    {
        $this->assertNameUnique($data['name'], $type->id);
        $before = $this->safe($type);
        unset($data['slug'], $data['is_system']);
        $type->update([...$data, 'updated_by' => $actor->id]);
        $this->log($type, 'manage.ticket_type.updated', $before, $this->safe($type), $actor);

        return $type->refresh();
    }

    public function setActive(TicketType $type, bool $active, User $actor): TicketType
    {
        $before = $this->safe($type);
        $type->update(['is_active' => $active, 'updated_by' => $actor->id]);
        $this->log($type, $active ? 'manage.ticket_type.enabled' : 'manage.ticket_type.disabled', $before, $this->safe($type), $actor);

        return $type->refresh();
    }

    public function archive(TicketType $type, User $actor): void
    {
        $before = $this->safe($type);
        $type->update(['updated_by' => $actor->id]);
        $type->delete();
        $this->log($type, 'manage.ticket_type.archived', $before, $this->safe($type), $actor);
    }

    public function restore(int $id, User $actor): TicketType
    {
        $type = TicketType::onlyTrashed()->findOrFail($id);
        $type->restore();
        $type->update(['is_active' => false, 'updated_by' => $actor->id]);
        $this->log($type, 'manage.ticket_type.restored', null, $this->safe($type), $actor);

        return $type->refresh();
    }

    public function reorder(array $ids, User $actor): void
    {
        DB::transaction(function () use ($ids, $actor) {
            $positions = TicketType::query()->whereKey($ids)->lockForUpdate()->orderBy('sort_order')->orderBy('id')->pluck('sort_order')->values();
            if ($positions->count() !== count($ids)) {
                throw ValidationException::withMessages(['ids' => 'One or more ticket types are unavailable.']);
            }
            foreach ($ids as $index => $id) {
                TicketType::query()->whereKey($id)->update(['sort_order' => $positions[$index], 'updated_by' => $actor->id]);
            }
            $this->audit->log('manage', 'manage.ticket_type.reordered', TicketType::class, null, null, null, ['ticket_type_ids' => $ids], $actor);
        });
    }

    private function assertNameUnique(string $name, ?int $except = null): void
    {
        $query = TicketType::query()->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))]);
        if ($except) {
            $query->whereKeyNot($except);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages(['name' => 'An active catalog entry with this name already exists.']);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'ticket-type';
        $slug = $base;
        for ($i = 2; TicketType::withTrashed()->where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }

    private function safe(TicketType $type): array
    {
        return $type->only(['id', 'name', 'slug', 'description', 'visibility', 'sort_order', 'is_active', 'is_system', 'deleted_at']);
    }

    private function log(TicketType $type, string $action, ?array $before, ?array $after, User $actor): void
    {
        $this->audit->log('manage', $action, TicketType::class, $type->id, $before, $after, [], $actor);
    }
}
