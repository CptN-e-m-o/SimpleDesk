<?php

namespace App\Support\Teams;

use App\Models\User;
use Illuminate\Support\Collection;

class TeamEligibleUsers
{
    public function ids(): array
    {
        return User::query()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['admin', 'agent']);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function forSelect(): Collection
    {
        return User::query()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['admin', 'agent']);
            })
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();
    }
}
