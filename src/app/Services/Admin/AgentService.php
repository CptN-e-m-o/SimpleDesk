<?php

namespace App\Services\Admin;

use App\Models\User\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AgentService
{
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $roleIds = $data['role_ids'];
            $departmentIds = $data['department_ids'] ?? [];
            $teamIds = $data['team_ids'] ?? [];

            unset($data['role_ids'], $data['department_ids'], $data['team_ids']);

            $data['password'] = Hash::make($data['password']);

            $agent = User::create($data);

            $agent->roles()->sync($roleIds);

            if (method_exists($agent, 'departments')) {
                $agent->departments()->sync($departmentIds);
            }

            if (method_exists($agent, 'teams')) {
                $agent->teams()->sync($teamIds);
            }

            return $agent;
        });
    }

    public function update(User $agent, array $data): User
    {
        return DB::transaction(function () use ($agent, $data) {
            $roleIds = $data['role_ids'];
            $departmentIds = $data['department_ids'] ?? [];
            $teamIds = $data['team_ids'] ?? [];

            unset($data['role_ids'], $data['department_ids'], $data['team_ids']);

            if (empty($data['password'])) {
                unset($data['password'], $data['password_confirmation']);
            } else {
                $data['password'] = Hash::make($data['password']);
                unset($data['password_confirmation']);
            }

            $agent->update($data);

            $agent->roles()->sync($roleIds);

            if (method_exists($agent, 'departments')) {
                $agent->departments()->sync($departmentIds);
            }

            if (method_exists($agent, 'teams')) {
                $agent->teams()->sync($teamIds);
            }

            return $agent;
        });
    }
}
