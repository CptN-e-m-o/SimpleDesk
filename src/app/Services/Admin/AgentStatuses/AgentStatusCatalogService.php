<?php

namespace App\Services\Admin\AgentStatuses;

use App\Models\Admin\AgentStatus;
use App\Models\User\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AgentStatusCatalogService
{
    public function create(
        array $data,
        User $actor
    ): AgentStatus {
        return DB::transaction(function () use ($data, $actor) {
            $data['slug'] = $this->uniqueSlug(
                $data['slug'] ?? $data['name']
            );

            $data['created_by'] = $actor->id;
            $data['updated_by'] = $actor->id;

            $status = AgentStatus::create($data);

            if ($status->is_default) {
                $this->makeDefault(
                    $status,
                    $actor
                );
            }

            $this->ensureDefault();

            return $status->refresh();
        });
    }

    public function update(
        AgentStatus $status,
        array $data,
        User $actor
    ): AgentStatus {
        return DB::transaction(
            function () use ($status, $data, $actor) {
                $status = AgentStatus::withTrashed()
                    ->lockForUpdate()
                    ->findOrFail($status->id);

                if ($status->is_system) {
                    foreach (
                        [
                            'slug',
                            'availability',
                            'routing_eligibility',
                            'is_system',
                        ] as $field
                    ) {
                        unset($data[$field]);
                    }
                }

                if (
                    $status->is_default
                    && (
                        ($data['is_default'] ?? true) === false
                        || ($data['is_active'] ?? true) === false
                    )
                ) {
                    throw ValidationException::withMessages([
                        'is_default' => 'Assign another default status first.',
                    ]);
                }

                $data['updated_by'] = $actor->id;

                $status->update($data);

                if ($status->is_default) {
                    $this->makeDefault(
                        $status,
                        $actor
                    );
                }

                return $status->refresh();
            }
        );
    }

    public function duplicate(
        AgentStatus $status,
        User $actor
    ): AgentStatus {
        $data = $status->only([
            'name',
            'description',
            'availability',
            'routing_eligibility',
            'icon',
            'color',
            'default_duration_minutes',
            'is_active',
            'is_selectable',
            'sort_order',
        ]);

        $data['name'] .= ' Copy';
        $data['is_system'] = false;
        $data['is_default'] = false;

        return $this->create(
            $data,
            $actor
        );
    }

    public function setActive(
        AgentStatus $status,
        bool $active,
        User $actor
    ): AgentStatus {
        return $this->update(
            $status,
            [
                'is_active' => $active,
            ],
            $actor
        );
    }

    public function archive(
        AgentStatus $status,
        User $actor
    ): void {
        DB::transaction(function () use ($status, $actor) {
            $status = AgentStatus::query()
                ->lockForUpdate()
                ->findOrFail($status->id);

            if ($status->is_system || $status->is_default) {
                throw ValidationException::withMessages([
                    'status' => 'System/default status cannot be archived.',
                ]);
            }

            $status->update([
                'updated_by' => $actor->id,
            ]);

            $status->delete();
        });
    }

    public function restore(
        int $id,
        User $actor
    ): AgentStatus {
        return DB::transaction(function () use ($id, $actor) {
            $status = AgentStatus::onlyTrashed()
                ->lockForUpdate()
                ->findOrFail($id);

            $status->restore();

            $status->update([
                'updated_by' => $actor->id,
            ]);

            return $status->refresh();
        });
    }

    public function forceDelete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $status = AgentStatus::onlyTrashed()
                ->lockForUpdate()
                ->findOrFail($id);

            if ($status->is_system) {
                throw ValidationException::withMessages([
                    'status' => 'System statuses cannot be permanently deleted.',
                ]);
            }

            if ($status->is_default) {
                throw ValidationException::withMessages([
                    'status' => 'The default status cannot be permanently deleted.',
                ]);
            }

            $isReferencedByPeriods = $status
                ->periods()
                ->exists();

            $isReferencedAsReturnStatus = $status
                ->revertPeriods()
                ->exists();

            if (
                $isReferencedByPeriods
                || $isReferencedAsReturnStatus
            ) {
                throw ValidationException::withMessages([
                    'status' => 'This status cannot be permanently deleted because it is referenced by agent status history.',
                ]);
            }

            $status->forceDelete();
        });
    }

    public function makeDefault(
        AgentStatus $status,
        User $actor
    ): AgentStatus {
        return DB::transaction(
            function () use ($status, $actor) {
                $target = AgentStatus::query()
                    ->lockForUpdate()
                    ->findOrFail($status->id);

                if (! $target->is_active) {
                    throw ValidationException::withMessages([
                        'status' => 'Default status must be active.',
                    ]);
                }

                AgentStatus::query()
                    ->where('is_default', true)
                    ->whereKeyNot($target->id)
                    ->update([
                        'is_default' => false,
                        'updated_by' => $actor->id,
                    ]);

                $target->update([
                    'is_default' => true,
                    'is_active' => true,
                    'updated_by' => $actor->id,
                ]);

                return $target->refresh();
            }
        );
    }

    private function ensureDefault(): void
    {
        $defaultExists = AgentStatus::active()
            ->where('is_default', true)
            ->exists();

        if (! $defaultExists) {
            throw ValidationException::withMessages([
                'is_default' => 'An active default status is required.',
            ]);
        }
    }

    private function uniqueSlug(string $value): string
    {
        $base = Str::slug($value) ?: 'status';
        $slug = $base;
        $index = 2;

        while (
            AgentStatus::withTrashed()
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$index;
            $index++;
        }

        return $slug;
    }
}
