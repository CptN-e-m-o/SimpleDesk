<?php

namespace App\Services\Admin;

use App\Models\Admin\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeamService
{
    public function create(array $data): Team
    {
        return DB::transaction(function () use ($data) {
            $team = Team::create([
                'name' => $data['name'],
                'departments' => $data['departments'] ?? [],
                'is_active' => (bool) $data['is_active'],
                'admin_notes' => $data['admin_notes'] ?? null,
            ]);

            $team->members()->sync(
                $this->buildMembersSyncData(
                    collect($data['member_ids']),
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
                'departments' => $data['departments'] ?? [],
                'is_active' => (bool) $data['is_active'],
                'admin_notes' => $data['admin_notes'] ?? null,
            ]);

            $team->members()->sync(
                $this->buildMembersSyncData(
                    collect($data['member_ids']),
                    $data['lead_user_id'] ?? null
                )
            );

            return $team;
        });
    }

    protected function buildMembersSyncData(Collection $memberIds, int|string|null $leadUserId): array
    {
        $leadUserId = $leadUserId !== null ? (int) $leadUserId : null;

        return $memberIds
            ->map(fn ($id) => (int) $id)
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
}
