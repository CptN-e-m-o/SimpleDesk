<?php

namespace App\Services\Admin\Manage;

use App\Enums\Admin\Manage\CatalogVisibility;
use App\Models\TicketPriority;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TicketPriorityCatalogService
{
    public function __construct(private readonly SystemAuditLogger $audit) {}

    public function create(array $data, User $actor): TicketPriority
    {
        return DB::transaction(function () use ($data, $actor) {
            $this->assertNameUnique($data['name']);
            $makeDefault = (bool) ($data['is_default'] ?? false);
            unset($data['is_default']);
            $priority = TicketPriority::query()->create([...$data, 'slug' => $this->uniqueSlug($data['name']), 'is_default' => false, 'created_by' => $actor->id, 'updated_by' => $actor->id]);
            $this->audit($priority, 'manage.priority.created', null, $this->safe($priority), $actor);

            return $makeDefault ? $this->makeDefault($priority, $actor) : $priority->refresh();
        });
    }

    public function update(TicketPriority $priority, array $data, User $actor): TicketPriority
    {
        return DB::transaction(function () use ($priority, $data, $actor) {
            $priority = TicketPriority::withTrashed()->lockForUpdate()->findOrFail($priority->id);
            $before = $this->safe($priority);
            $this->assertNameUnique($data['name'], $priority->id);
            $makeDefault = (bool) ($data['is_default'] ?? false);
            unset($data['slug'], $data['is_default'], $data['is_system']);
            if ($priority->is_default && ((! ($data['is_active'] ?? true)) || ($data['visibility'] ?? $priority->visibility->value) !== CatalogVisibility::Public->value)) {
                throw ValidationException::withMessages(['priority' => 'The default priority must remain public and active.']);
            }
            $priority->update([...$data, 'updated_by' => $actor->id]);
            $this->audit($priority, 'manage.priority.updated', $before, $this->safe($priority), $actor);

            return $makeDefault && ! $priority->is_default ? $this->makeDefault($priority, $actor) : $priority->refresh();
        });
    }

    public function makeDefault(TicketPriority $priority, User $actor): TicketPriority
    {
        return DB::transaction(function () use ($priority, $actor) {
            $priorities = TicketPriority::query()->lockForUpdate()->get();
            $target = $priorities->firstWhere('id', $priority->id);
            if (! $target || ! $target->is_active || $target->visibility !== CatalogVisibility::Public) {
                throw ValidationException::withMessages(['is_default' => 'The default priority must be public and active.']);
            }
            $previous = $priorities->firstWhere('is_default', true);
            TicketPriority::query()->where('is_default', true)->whereKeyNot($target->id)->update(['is_default' => false, 'updated_by' => $actor->id]);
            $target->update(['is_default' => true, 'updated_by' => $actor->id]);
            $this->audit($target, 'manage.priority.default_changed', null, $this->safe($target), $actor, ['previous_priority_id' => $previous?->id, 'new_priority_id' => $target->id]);

            return $target->refresh();
        });
    }

    public function setActive(TicketPriority $priority, bool $active, User $actor): TicketPriority
    {
        return DB::transaction(function () use ($priority, $active, $actor) {
            $priority = TicketPriority::query()->lockForUpdate()->findOrFail($priority->id);
            if (! $active && $priority->is_default) {
                throw ValidationException::withMessages(['priority' => 'The default priority cannot be disabled.']);
            }
            $before = $this->safe($priority);
            $priority->update(['is_active' => $active, 'updated_by' => $actor->id]);
            $this->audit($priority, $active ? 'manage.priority.enabled' : 'manage.priority.disabled', $before, $this->safe($priority), $actor);

            return $priority->refresh();
        });
    }

    public function archive(TicketPriority $priority, User $actor): void
    {
        DB::transaction(function () use ($priority, $actor) {
            $priority = TicketPriority::query()->lockForUpdate()->findOrFail($priority->id);
            if ($priority->is_default) {
                throw ValidationException::withMessages(['priority' => 'The default priority cannot be archived.']);
            }
            $before = $this->safe($priority);
            $priority->update(['updated_by' => $actor->id]);
            $priority->delete();
            $this->audit($priority, 'manage.priority.archived', $before, $this->safe($priority), $actor);
        });
    }

    public function restore(int $id, User $actor): TicketPriority
    {
        return DB::transaction(function () use ($id, $actor) {
            $priority = TicketPriority::onlyTrashed()->lockForUpdate()->findOrFail($id);
            $this->assertNameUnique($priority->name);
            $priority->restore();
            $priority->update(['is_active' => false, 'is_default' => false, 'updated_by' => $actor->id]);
            $this->audit($priority, 'manage.priority.restored', null, $this->safe($priority), $actor);

            return $priority->refresh();
        });
    }

    public function reorder(array $ids, User $actor): void
    {
        DB::transaction(function () use ($ids, $actor) {
            $positions = TicketPriority::query()->whereKey($ids)->lockForUpdate()->orderBy('sort_order')->orderBy('id')->pluck('sort_order')->values();
            if ($positions->count() !== count($ids)) {
                throw ValidationException::withMessages(['ids' => 'One or more priorities are unavailable.']);
            }
            foreach ($ids as $index => $id) {
                TicketPriority::query()->whereKey($id)->update(['sort_order' => $positions[$index], 'updated_by' => $actor->id]);
            }
            $this->audit->log('manage', 'manage.priority.reordered', TicketPriority::class, null, null, null, ['priority_ids' => $ids], $actor);
        });
    }

    private function assertNameUnique(string $name, ?int $except = null): void
    {
        $query = TicketPriority::query()->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))]);
        if ($except) {
            $query->whereKeyNot($except);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages(['name' => 'An active catalog entry with this name already exists.']);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'priority';
        $slug = $base;
        for ($i = 2; TicketPriority::withTrashed()->where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }

    private function safe(TicketPriority $priority): array
    {
        return $priority->only(['id', 'name', 'slug', 'description', 'color', 'visibility', 'sort_order', 'is_default', 'is_active', 'is_system', 'deleted_at']);
    }

    private function audit(TicketPriority $priority, string $action, ?array $before, ?array $after, User $actor, array $metadata = []): void
    {
        $this->audit->log('manage', $action, TicketPriority::class, $priority->id, $before, $after, $metadata, $actor);
    }
}
