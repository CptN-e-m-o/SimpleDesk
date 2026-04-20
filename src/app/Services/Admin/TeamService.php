<?php

namespace App\Services\Admin;

use App\Models\Admin\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeamService
{
    public function create(array $data): Team
    {
        return DB::transaction(function () use ($data) {
            $team = Team::create([
                'name' => $data['name'],
                'slug' => $this->makeUniqueSlug($data['name']),
                'is_active' => (bool) $data['is_active'],
                'admin_notes' => $data['admin_notes'] ?? null,
            ]);

            $team->departments()->sync(
                $this->normalizeDepartmentIds($data['departments'] ?? [])
            );

            $team->members()->sync(
                $this->buildMembersSyncData(
                    collect($data['member_ids'] ?? []),
                    $data['lead_user_id'] ?? null
                )
            );

            return $team;
        });
    }

    public function update(Team $team, array $data): Team
    {
        return DB::transaction(function () use ($team, $data) {
            $team->update([
                'name' => $data['name'],
                'slug' => $this->makeUniqueSlug($data['name'], $team->id),
                'is_active' => (bool) $data['is_active'],
                'admin_notes' => $data['admin_notes'] ?? null,
            ]);

            $team->departments()->sync(
                $this->normalizeDepartmentIds($data['departments'] ?? [])
            );

            $team->members()->sync(
                $this->buildMembersSyncData(
                    collect($data['member_ids'] ?? []),
                    $data['lead_user_id'] ?? null
                )
            );

            return $team;
        });
    }

    protected function normalizeDepartmentIds(array $departmentIds): array
    {
        return collect($departmentIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    protected function buildMembersSyncData(Collection $memberIds, int|string|null $leadUserId): array
    {
        $leadUserId = $leadUserId !== null && $leadUserId !== ''
            ? (int) $leadUserId
            : null;

        return $memberIds
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->mapWithKeys(function (int $userId) use ($leadUserId) {
                return [
                    $userId => [
                        'is_lead' => $leadUserId === $userId,
                    ],
                ];
            })
            ->toArray();
    }

    protected function makeUniqueSlug(string $name, ?int $ignoreTeamId = null): string
    {
        $baseSlug = Str::slug($name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'team';

        $slug = $baseSlug;
        $counter = 1;

        while (
            Team::query()
                ->when(
                    $ignoreTeamId,
                    fn ($query) => $query->where('id', '!=', $ignoreTeamId)
                )
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
