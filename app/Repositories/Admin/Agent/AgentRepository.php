<?php

namespace App\Repositories\Admin\Agent;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class AgentRepository
{
    public function query(): Builder
    {
        return User::agents();
    }

    public function filter($query, array $filters): Builder
    {
        if ($filters['status'] !== null) {
            $query->where('is_active', $filters['status']);
        }

        if ($filters['search']) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'LIKE', "%{$s}%")
                    ->orWhere('last_name', 'LIKE', "%{$s}%")
                    ->orWhere('patronymic', 'LIKE', "%{$s}%")
                    ->orWhere('login', 'LIKE', "%{$s}%")
                    ->orWhere('email', 'LIKE', "%{$s}%")
                    ->orWhere('phone_number', 'LIKE', "%{$s}%");
            });
        }

        return $query;
    }

    public function sort($query, string $sortBy, string $direction): Builder
    {
        return $query->sort($sortBy, $direction);
    }

    public function paginate($query, int $perPage): LengthAwarePaginator
    {
        return $query->paginate($perPage);
    }
}
