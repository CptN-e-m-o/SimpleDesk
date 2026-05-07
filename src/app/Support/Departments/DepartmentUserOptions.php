<?php

namespace App\Support\Departments;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DepartmentUserOptions
{
    private const REQUIRED_PERMISSION =
        'admin.staff.assign_as_department_manager';

    public function options(): Collection
    {
        return $this->query()
            ->select([
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.email',
            ])
            ->orderBy('users.first_name')
            ->orderBy('users.last_name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->values();
    }

    public function ids(): array
    {
        return $this->query()
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function query(): Builder
    {
        return User::query()
            ->whereHas('roles.permissions', function (Builder $query) {
                $query->where(
                    'permissions.key',
                    self::REQUIRED_PERMISSION
                );
            });
    }
}
